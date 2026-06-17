#!/usr/bin/env python3
"""
Sync Orthanc PACS studies to Aurora's clinical schema.

Queries Orthanc for all patients and studies, auto-creates Aurora patient
records for DICOM patients that don't exist yet, then upserts imaging_studies.

Usage:
    python3 sync_orthanc_to_aurora.py [--dry-run] [--collection COLLECTION] [--auto-create-patients]

Environment:
    ORTHANC_URL     (default: http://localhost:8042)
    ORTHANC_USER    (default: parthenon)
    ORTHANC_PASS    (default: current local Orthanc password)
    DB_HOST         (default: empty for unix socket peer auth)
    DB_PORT         (default: 5432)
    DB_NAME         (default: aurora)
    DB_USER         (default: smudoshi)
    DB_PASS         (default: empty)
"""

import argparse
import csv
import json
import os
import sys
from urllib.request import Request, urlopen
from urllib.error import URLError
import base64

# ── Config ───────────────────────────────────────────────────

ORTHANC_URL = os.environ.get("ORTHANC_URL", "http://localhost:8042")
ORTHANC_USER = os.environ.get("ORTHANC_USER", "parthenon")
ORTHANC_PASS = os.environ.get("ORTHANC_PASS", "GixsEIl0hpOAeOwKdmmlAMe04SQ0CKih")

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


CATALOGUE_PATH = os.path.join(os.path.dirname(__file__), "tcia_dicom_study_catalogue.csv")

_cptac_lookup: dict[str, str] | None = None


def _load_cptac_lookup() -> dict[str, str]:
    """Load subject_id -> collection mapping from TCIA catalogue CSV."""
    global _cptac_lookup
    if _cptac_lookup is not None:
        return _cptac_lookup

    _cptac_lookup = {}
    if not os.path.exists(CATALOGUE_PATH):
        print(f"  WARN: Catalogue CSV not found at {CATALOGUE_PATH}")
        return _cptac_lookup

    with open(CATALOGUE_PATH, newline="") as f:
        reader = csv.DictReader(f)
        for row in reader:
            collection = row.get("collection", "")
            subject_id = row.get("subject_id", "")
            if subject_id and collection:
                _cptac_lookup[subject_id] = collection

    print(f"  Loaded {len(_cptac_lookup)} subject→collection mappings from catalogue.")
    return _cptac_lookup


def infer_collection(dicom_patient_id: str) -> str:
    """Infer TCIA collection name from DICOM PatientID pattern."""
    pid = dicom_patient_id.strip()

    # Try catalogue lookup first (covers CPTAC, HCC, NSCLC, etc.)
    lookup = _load_cptac_lookup()
    if pid in lookup:
        return lookup[pid]

    if pid.startswith("PSMA_"):
        return "PSMA-PET-CT-Lesions"
    if pid.startswith("LUNG1-"):
        return "NSCLC-Radiomics"
    if pid.startswith("C3L-") or pid.startswith("C3N-"):
        return "CPTAC"
    if pid.startswith("TCGA-"):
        # Parse TCGA-XX-YYYY → project code is XX
        parts = pid.split("-")
        if len(parts) >= 2:
            project_code = parts[1]
            tcga_map = {
                "CJ": "TCGA-KIRC", "CZ": "TCGA-KIRC", "CC": "TCGA-KIRC",
                "B0": "TCGA-KIRC", "BP": "TCGA-KIRC", "A3": "TCGA-KIRC",
                "BH": "TCGA-BRCA", "A7": "TCGA-BRCA", "AC": "TCGA-BRCA",
                "AN": "TCGA-BRCA", "AO": "TCGA-BRCA", "AR": "TCGA-BRCA",
                "A8": "TCGA-BRCA", "B6": "TCGA-BRCA", "D8": "TCGA-BRCA",
                "E2": "TCGA-BRCA", "E9": "TCGA-BRCA", "EW": "TCGA-BRCA",
                "GM": "TCGA-BRCA", "LL": "TCGA-BRCA", "OL": "TCGA-BRCA",
                "PE": "TCGA-BRCA", "PL": "TCGA-BRCA", "S3": "TCGA-BRCA",
                "49": "TCGA-LUAD", "50": "TCGA-LUAD", "55": "TCGA-LUAD",
                "64": "TCGA-LUAD", "67": "TCGA-LUAD", "69": "TCGA-LUAD",
                "73": "TCGA-LUAD", "75": "TCGA-LUAD", "78": "TCGA-LUAD",
                "80": "TCGA-LUAD", "86": "TCGA-LUAD", "91": "TCGA-LUAD",
                "97": "TCGA-LUAD", "05": "TCGA-LUAD", "38": "TCGA-LUAD",
                "44": "TCGA-LUAD", "4B": "TCGA-LUAD", "J2": "TCGA-LUAD",
                "G9": "TCGA-KIRC",
            }
            return tcga_map.get(project_code, "TCGA-UNKNOWN")
        return "TCGA-UNKNOWN"
    # HCC-TACE-Seg: numeric IDs like 0090105101391401-32315-2
    if pid[:1].isdigit() and len(pid) > 10:
        return "HCC-TACE-Seg"
    # HCC_xxx pattern
    if pid.startswith("HCC_") or pid.startswith("HCC-"):
        return "HCC-TACE-Seg"
    return "UNKNOWN"


def generate_mrn(collection: str, dicom_patient_id: str) -> str:
    """Generate a stable Aurora MRN from collection + DICOM PatientID."""
    import hashlib
    # Use a short hash suffix to ensure uniqueness
    h = hashlib.sha256(dicom_patient_id.encode()).hexdigest()[:6].upper()
    prefix_map = {
        "CPTAC-PDA": "TCIA-PDA",
        "CPTAC-CCRCC": "TCIA-CCRCC",
        "CPTAC": "TCIA-CPTAC",
        "PSMA-PET-CT-Lesions": "TCIA-PRAD",
        "NSCLC-Radiomics": "TCIA-NSCLC",
        "HCC-TACE-Seg": "TCIA-LIHC",
        "TCGA-KIRC": "TCIA-KIRC",
        "TCGA-LUAD": "TCIA-LUAD",
        "TCGA-BRCA": "TCIA-BRCA",
    }
    prefix = prefix_map.get(collection, "TCIA-UNK")
    return f"{prefix}-{h}"


def auto_create_patients(studies: list[dict], conn, dry_run: bool = False) -> dict[str, int]:
    """
    Auto-create Aurora patient records for DICOM patients not yet in Aurora.
    Returns the updated mapping: DICOM PatientID -> Aurora patient_id.
    """
    existing_mapping = get_patient_mapping(conn)

    # Collect unique DICOM patient IDs not yet mapped
    unmapped = {}
    for study in studies:
        dpid = study["patient_id_dicom"]
        if dpid and dpid not in existing_mapping and dpid not in unmapped:
            unmapped[dpid] = study.get("patient_name", "")

    if not unmapped:
        print(f"  All {len(existing_mapping)} DICOM patients already mapped.")
        return existing_mapping

    print(f"  Found {len(unmapped)} unmapped DICOM patients. Creating...")

    cur = conn.cursor()
    created = 0

    for dicom_pid, patient_name in unmapped.items():
        collection = infer_collection(dicom_pid)
        mrn = generate_mrn(collection, dicom_pid)

        # Parse patient name (DICOM format: last^first or just ID)
        parts = patient_name.replace("^", " ").split() if patient_name else []
        first_name = parts[0] if parts else dicom_pid[:20]
        last_name = parts[1] if len(parts) > 1 else collection

        if dry_run:
            print(f"  DRY RUN: Would create patient MRN={mrn} "
                  f"({first_name} {last_name}, collection={collection})")
            created += 1
            continue

        # Check MRN doesn't already exist (could be from a prior partial run)
        cur.execute("SELECT id FROM clinical.patients WHERE mrn = %s", (mrn,))
        existing = cur.fetchone()
        if existing:
            patient_id = existing[0]
        else:
            cur.execute("""
                INSERT INTO clinical.patients
                    (mrn, first_name, last_name, source_type, source_id, created_at, updated_at)
                VALUES (%s, %s, %s, 'tcia', %s, NOW(), NOW())
                RETURNING id
            """, (mrn, first_name, last_name, collection))
            patient_id = cur.fetchone()[0]
            created += 1

        # Add identifier mapping: tcia_subject -> dicom_pid
        cur.execute("""
            INSERT INTO clinical.patient_identifiers
                (patient_id, identifier_type, identifier_value, source_system, created_at, updated_at)
            VALUES (%s, 'tcia_subject', %s, %s, NOW(), NOW())
            ON CONFLICT DO NOTHING
        """, (patient_id, dicom_pid, collection))

        # Add collection identifier
        cur.execute("""
            INSERT INTO clinical.patient_identifiers
                (patient_id, identifier_type, identifier_value, source_system, created_at, updated_at)
            VALUES (%s, 'tcia_collection', %s, %s, NOW(), NOW())
            ON CONFLICT DO NOTHING
        """, (patient_id, collection, 'orthanc_sync'))

        existing_mapping[dicom_pid] = patient_id

    if not dry_run:
        conn.commit()

    print(f"  Created {created} new patient records.")
    return existing_mapping


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


def sync_studies(studies: list[dict], conn, dry_run: bool = False,
                 patient_mapping: dict[str, int] | None = None):
    """Upsert Orthanc studies into Aurora's imaging_studies table."""
    if patient_mapping is None:
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
    parser.add_argument("--dry-run", action="store_true",
                        help="Print what would be done without writing to DB")
    parser.add_argument("--collection", type=str,
                        help="Filter by TCIA collection name (in DICOM PatientID)")
    parser.add_argument("--auto-create-patients", action="store_true", default=True,
                        help="Auto-create Aurora patients for unmapped DICOM patients (default: on)")
    parser.add_argument("--no-auto-create-patients", action="store_false",
                        dest="auto_create_patients",
                        help="Skip auto-creation of patient records")
    args = parser.parse_args()

    print("=== Orthanc → Aurora Sync ===")
    print(f"  Orthanc: {ORTHANC_URL}")
    print(f"  Database: {DB_HOST or '(unix socket)'}:{DB_PORT}/{DB_NAME}")
    if args.dry_run:
        print("  Mode: DRY RUN")
    if args.auto_create_patients:
        print("  Auto-create patients: ON")
    print("")

    # Check Orthanc connection
    try:
        stats = orthanc_get("/statistics")
        print(f"  Orthanc: {stats['CountStudies']} studies, "
              f"{stats['CountPatients']} patients, "
              f"{stats['CountInstances']} instances")
    except Exception as e:
        print(f"ERROR: Cannot connect to Orthanc: {e}")
        sys.exit(1)

    # Fetch all studies
    print("\n[1/4] Fetching studies from Orthanc...")
    studies = get_orthanc_studies()

    # Filter by collection if specified
    if args.collection:
        before = len(studies)
        studies = [s for s in studies if args.collection.lower() in s["patient_id_dicom"].lower()]
        print(f"  Filtered to {len(studies)} studies matching '{args.collection}' (from {before})")

    # Connect to Aurora DB
    print("\n[2/4] Connecting to Aurora database...")
    conn = connect_db()
    print("  Connected.")

    # Auto-create patients if enabled
    patient_mapping = None
    if args.auto_create_patients:
        print("\n[3/4] Auto-creating patient records...")
        patient_mapping = auto_create_patients(studies, conn, dry_run=args.dry_run)
    else:
        print("\n[3/4] Skipping patient auto-creation.")

    # Sync studies
    print("\n[4/4] Syncing studies to Aurora...")
    result = sync_studies(studies, conn, dry_run=args.dry_run,
                         patient_mapping=patient_mapping)

    conn.close()

    print(f"\n=== Sync Complete ===")
    print(f"  Inserted:          {result['inserted']}")
    print(f"  Updated:           {result['updated']}")
    print(f"  No patient match:  {result['skipped_no_patient']}")


if __name__ == "__main__":
    main()
