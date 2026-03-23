#!/usr/bin/env python3
"""
Sync Orthanc PACS studies to Aurora's clinical.imaging_studies table.

Queries Orthanc for all studies, extracts DICOM metadata (PatientID,
StudyInstanceUID, modality, date, description), and upserts into Aurora's
PostgreSQL database. Links studies to TCIA patients via PatientIdentifier
records (tcia_subject identifiers).

Usage:
    python3 sync_orthanc_to_aurora.py [--dry-run] [--collection COLLECTION]

Environment:
    ORTHANC_URL     (default: http://localhost:8042)
    ORTHANC_USER    (default: parthenon)
    ORTHANC_PASS    (default: orthanc_secret)
    DB_HOST         (default: localhost)
    DB_PORT         (default: 5485)
    DB_NAME         (default: aurora)
    DB_USER         (default: aurora)
    DB_PASS         (default: aurora)
"""

import argparse
import json
import os
import sys
from urllib.request import Request, urlopen
from urllib.error import URLError
import base64

# ── Config ───────────────────────────────────────────────────

ORTHANC_URL = os.environ.get("ORTHANC_URL", "http://localhost:8042")
ORTHANC_USER = os.environ.get("ORTHANC_USER", "parthenon")
ORTHANC_PASS = os.environ.get("ORTHANC_PASS", "orthanc_secret")

DB_HOST = os.environ.get("DB_HOST", "")  # empty = unix socket (peer auth)
DB_PORT = os.environ.get("DB_PORT", "5432")
DB_NAME = os.environ.get("DB_NAME", "aurora")
DB_USER = os.environ.get("DB_USER", "smudoshi")
DB_PASS = os.environ.get("DB_PASS", "")


def orthanc_get(path: str) -> dict | list:
    """GET from Orthanc REST API with basic auth."""
    url = f"{ORTHANC_URL}{path}"
    credentials = base64.b64encode(f"{ORTHANC_USER}:{ORTHANC_PASS}".encode()).decode()
    req = Request(url, headers={"Authorization": f"Basic {credentials}"})
    with urlopen(req, timeout=30) as resp:
        return json.loads(resp.read())


def get_orthanc_studies() -> list[dict]:
    """Fetch all studies from Orthanc with expanded metadata."""
    study_ids = orthanc_get("/studies")
    studies = []

    total = len(study_ids)
    for i, study_id in enumerate(study_ids):
        if (i + 1) % 50 == 0 or i == 0:
            print(f"  Fetching study metadata: {i + 1}/{total}...", flush=True)

        try:
            study = orthanc_get(f"/studies/{study_id}")
        except Exception as e:
            print(f"  WARN: Failed to fetch study {study_id}: {e}")
            continue

        main_tags = study.get("MainDicomTags", {})
        patient_tags = study.get("PatientMainDicomTags", {})

        # Extract modalities from series
        modalities = set()
        series_count = 0
        instance_count = 0
        for series_id in study.get("Series", []):
            try:
                series = orthanc_get(f"/series/{series_id}")
                series_tags = series.get("MainDicomTags", {})
                modality = series_tags.get("Modality", "")
                if modality:
                    modalities.add(modality)
                series_count += 1
                instance_count += len(series.get("Instances", []))
            except Exception:
                pass

        studies.append({
            "orthanc_id": study_id,
            "patient_id_dicom": patient_tags.get("PatientID", ""),
            "patient_name": patient_tags.get("PatientName", ""),
            "study_uid": main_tags.get("StudyInstanceUID", ""),
            "study_date": main_tags.get("StudyDate", ""),
            "study_description": main_tags.get("StudyDescription", ""),
            "accession_number": main_tags.get("AccessionNumber", ""),
            "modalities": sorted(modalities),
            "num_series": series_count,
            "num_instances": instance_count,
        })

    print(f"  Fetched {len(studies)} studies from Orthanc.")
    return studies


def connect_db():
    """Connect to Aurora PostgreSQL."""
    try:
        import psycopg2
    except ImportError:
        print("ERROR: psycopg2 not installed. Install with: pip install psycopg2-binary")
        sys.exit(1)

    kwargs = {"dbname": DB_NAME, "user": DB_USER}
    if DB_HOST:
        kwargs["host"] = DB_HOST
        kwargs["port"] = DB_PORT
    if DB_PASS:
        kwargs["password"] = DB_PASS
    return psycopg2.connect(**kwargs)


def format_dicom_date(date_str: str) -> str | None:
    """Convert DICOM date (YYYYMMDD) to ISO (YYYY-MM-DD)."""
    if not date_str or len(date_str) < 8:
        return None
    try:
        return f"{date_str[:4]}-{date_str[4:6]}-{date_str[6:8]}"
    except (IndexError, ValueError):
        return None


def get_patient_mapping(conn) -> dict[str, int]:
    """
    Build a mapping from DICOM PatientID → Aurora patient_id.

    Uses two strategies:
    1. PatientIdentifier records (tcia_subject, tcga_barcode)
    2. Direct MRN matching
    """
    mapping = {}
    cur = conn.cursor()

    # Strategy 1: PatientIdentifier (tcia_subject, tcga_barcode)
    cur.execute("""
        SELECT pi.identifier_value, pi.patient_id
        FROM clinical.patient_identifiers pi
        WHERE pi.identifier_type IN ('tcia_subject', 'tcga_barcode')
    """)
    for row in cur.fetchall():
        mapping[row[0]] = row[1]

    # Strategy 2: MRN matching for existing patients
    cur.execute("""
        SELECT mrn, id FROM clinical.patients
        WHERE mrn LIKE 'TCIA-%' OR mrn LIKE 'DEMO-%'
    """)
    for row in cur.fetchall():
        mapping[row[0]] = row[1]

    cur.close()
    return mapping


def determine_body_part(description: str, modalities: list[str]) -> str | None:
    """Infer body part from study description."""
    desc = (description or "").lower()
    if any(w in desc for w in ["chest", "lung", "thorax"]):
        return "Chest"
    if any(w in desc for w in ["abdomen", "liver", "pancrea", "hepat", "renal", "kidney"]):
        return "Abdomen"
    if any(w in desc for w in ["pelvis", "prostate", "bladder"]):
        return "Pelvis"
    if any(w in desc for w in ["brain", "head", "neuro"]):
        return "Brain"
    if any(w in desc for w in ["breast", "mammo"]):
        return "Breast"
    if any(w in desc for w in ["spine", "lumbar", "cervical", "thoracic"]):
        return "Spine"
    if any(w in desc for w in ["whole body", "wb", "total body"]):
        return "Whole body"
    if "bone" in desc:
        return "Skeleton"
    return None


def sync_studies(studies: list[dict], conn, dry_run: bool = False):
    """Upsert Orthanc studies into Aurora's imaging_studies table."""
    patient_mapping = get_patient_mapping(conn)
    print(f"  Patient mapping: {len(patient_mapping)} identifiers loaded.")

    cur = conn.cursor()

    stats = {"inserted": 0, "updated": 0, "skipped_no_patient": 0, "skipped_exists": 0}

    for study in studies:
        study_uid = study["study_uid"]
        if not study_uid:
            continue

        dicom_patient_id = study["patient_id_dicom"]
        aurora_patient_id = patient_mapping.get(dicom_patient_id)

        if not aurora_patient_id:
            stats["skipped_no_patient"] += 1
            continue

        study_date = format_dicom_date(study["study_date"])
        primary_modality = study["modalities"][0] if study["modalities"] else None
        body_part = determine_body_part(study["study_description"], study["modalities"])

        if dry_run:
            print(f"  DRY RUN: Would upsert study {study_uid} "
                  f"(patient DICOM={dicom_patient_id}, aurora_id={aurora_patient_id}, "
                  f"modality={primary_modality}, date={study_date})")
            stats["inserted"] += 1
            continue

        # Check if study already exists
        cur.execute(
            "SELECT id FROM clinical.imaging_studies WHERE study_uid = %s",
            (study_uid,),
        )
        existing = cur.fetchone()

        if existing:
            # Update existing record
            cur.execute("""
                UPDATE clinical.imaging_studies SET
                    patient_id = %s,
                    modality = COALESCE(%s, modality),
                    study_date = COALESCE(%s, study_date),
                    description = COALESCE(%s, description),
                    body_part = COALESCE(%s, body_part),
                    accession_number = COALESCE(%s, accession_number),
                    num_series = %s,
                    num_instances = %s,
                    dicom_endpoint = 'orthanc',
                    source_type = 'tcia',
                    source_id = 'orthanc_sync_v1',
                    updated_at = NOW()
                WHERE study_uid = %s
            """, (
                aurora_patient_id,
                primary_modality,
                study_date,
                study["study_description"],
                body_part,
                study["accession_number"],
                study["num_series"],
                study["num_instances"],
                study_uid,
            ))
            stats["updated"] += 1
        else:
            # Insert new record
            cur.execute("""
                INSERT INTO clinical.imaging_studies
                    (patient_id, study_uid, modality, study_date, description,
                     body_part, accession_number, num_series, num_instances,
                     dicom_endpoint, source_type, source_id, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s,
                        'orthanc', 'tcia', 'orthanc_sync_v1', NOW(), NOW())
            """, (
                aurora_patient_id,
                study_uid,
                primary_modality,
                study_date,
                study["study_description"],
                body_part,
                study["accession_number"],
                study["num_series"],
                study["num_instances"],
            ))
            stats["inserted"] += 1

    if not dry_run:
        conn.commit()

    cur.close()
    return stats


def main():
    parser = argparse.ArgumentParser(description="Sync Orthanc studies to Aurora DB")
    parser.add_argument("--dry-run", action="store_true", help="Print what would be done without writing to DB")
    parser.add_argument("--collection", type=str, help="Filter by TCIA collection name (in DICOM PatientID)")
    args = parser.parse_args()

    print("=== Orthanc → Aurora Sync ===")
    print(f"  Orthanc: {ORTHANC_URL}")
    print(f"  Database: {DB_HOST}:{DB_PORT}/{DB_NAME}")
    if args.dry_run:
        print("  Mode: DRY RUN")
    print("")

    # Check Orthanc connection
    try:
        stats = orthanc_get("/statistics")
        print(f"  Orthanc: {stats['CountStudies']} studies, {stats['CountInstances']} instances")
    except Exception as e:
        print(f"ERROR: Cannot connect to Orthanc: {e}")
        sys.exit(1)

    # Fetch all studies
    print("\n[1/3] Fetching studies from Orthanc...")
    studies = get_orthanc_studies()

    # Filter by collection if specified
    if args.collection:
        before = len(studies)
        studies = [s for s in studies if args.collection.lower() in s["patient_id_dicom"].lower()]
        print(f"  Filtered to {len(studies)} studies matching '{args.collection}' (from {before})")

    # Connect to Aurora DB
    print("\n[2/3] Connecting to Aurora database...")
    conn = connect_db()
    print("  Connected.")

    # Sync
    print("\n[3/3] Syncing studies to Aurora...")
    result = sync_studies(studies, conn, dry_run=args.dry_run)

    conn.close()

    print(f"\n=== Sync Complete ===")
    print(f"  Inserted:          {result['inserted']}")
    print(f"  Updated:           {result['updated']}")
    print(f"  No patient match:  {result['skipped_no_patient']}")


if __name__ == "__main__":
    main()
