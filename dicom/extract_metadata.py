#!/usr/bin/env python3
"""
Extract DICOM metadata directly from files on disk and populate Aurora's
clinical.imaging_studies and imaging_series tables.

Reads one .dcm file per series directory to extract study/series metadata,
counts files for num_instances, and stores the directory path as file_path.
No Orthanc involved — much faster for bulk TCIA imports.

Usage:
    python3 extract_metadata.py <collection> [--parallel N] [--dry-run]
    python3 extract_metadata.py all [--parallel N] [--dry-run]

The script:
1. Scans the collection directory for series subdirectories
2. Reads one DICOM file per series (header only — fast)
3. Groups series by study
4. Auto-creates patients if needed (uses sync_orthanc_to_aurora.py logic)
5. Upserts imaging_studies and imaging_series with file_path references
"""

import argparse
import os
import sys
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path

import pydicom
from pydicom.errors import InvalidDicomError

# Reuse patient mapping logic from sync script
sys.path.insert(0, os.path.dirname(__file__))
from sync_orthanc_to_aurora import (
    connect_db, infer_collection, generate_mrn, get_patient_mapping,
    determine_body_part, format_dicom_date,
)

DOWNLOAD_ROOT = os.environ.get("DOWNLOAD_ROOT", "/media/smudoshi/DATA/TCIA-downloads")

COLLECTIONS = [
    "CPTAC-PDA", "PSMA-PET-CT-Lesions", "NSCLC-Radiomics", "HCC-TACE-Seg",
    "TCGA-KIRC", "TCGA-LUAD", "TCGA-BRCA", "CPTAC-CCRCC",
]


def read_series_metadata(series_dir: str) -> dict | None:
    """Read one DICOM file from a series directory, extract metadata."""
    dcm_files = [f for f in os.listdir(series_dir) if f.endswith(".dcm")]
    if not dcm_files:
        return None

    # Read just the first file's header (stop_before_pixels for speed)
    sample_file = os.path.join(series_dir, dcm_files[0])
    try:
        ds = pydicom.dcmread(sample_file, stop_before_pixels=True, force=True)
    except (InvalidDicomError, Exception):
        return None

    return {
        "series_dir": series_dir,
        "patient_id_dicom": str(getattr(ds, "PatientID", "")),
        "patient_name": str(getattr(ds, "PatientName", "")),
        "study_uid": str(getattr(ds, "StudyInstanceUID", "")),
        "series_uid": str(getattr(ds, "SeriesInstanceUID", "")),
        "study_date": str(getattr(ds, "StudyDate", "")),
        "study_description": str(getattr(ds, "StudyDescription", "")),
        "series_description": str(getattr(ds, "SeriesDescription", "")),
        "series_number": int(getattr(ds, "SeriesNumber", 0) or 0),
        "modality": str(getattr(ds, "Modality", "")),
        "body_part": str(getattr(ds, "BodyPartExamined", "")),
        "accession_number": str(getattr(ds, "AccessionNumber", "")),
        "num_instances": len(dcm_files),
    }


def scan_collection(collection_dir: str, parallel: int) -> list[dict]:
    """Scan all series directories in a collection, extract metadata in parallel."""
    # Find series directories (any subdir containing .dcm files)
    series_dirs = []
    for entry in os.scandir(collection_dir):
        if entry.is_dir() and not entry.name.startswith("."):
            series_dirs.append(entry.path)

    if not series_dirs:
        return []

    print(f"  Found {len(series_dirs)} series directories")

    results = []
    with ThreadPoolExecutor(max_workers=parallel) as pool:
        futures = {pool.submit(read_series_metadata, d): d for d in series_dirs}
        done = 0
        for future in as_completed(futures):
            done += 1
            if done % 200 == 0:
                print(f"    Scanned {done}/{len(series_dirs)} series...", flush=True)
            meta = future.result()
            if meta:
                results.append(meta)

    print(f"  Successfully read metadata from {len(results)} series")
    return results


def group_by_study(series_list: list[dict]) -> dict[str, dict]:
    """Group series metadata by StudyInstanceUID."""
    studies = {}
    for s in series_list:
        uid = s["study_uid"]
        if not uid:
            continue
        if uid not in studies:
            studies[uid] = {
                "study_uid": uid,
                "patient_id_dicom": s["patient_id_dicom"],
                "patient_name": s["patient_name"],
                "study_date": s["study_date"],
                "study_description": s["study_description"],
                "accession_number": s["accession_number"],
                "modalities": set(),
                "body_parts": set(),
                "series": [],
                "total_instances": 0,
            }
        study = studies[uid]
        if s["modality"]:
            study["modalities"].add(s["modality"])
        if s["body_part"]:
            study["body_parts"].add(s["body_part"])
        study["series"].append(s)
        study["total_instances"] += s["num_instances"]
    return studies


def ensure_patients(studies: dict, conn) -> dict[str, int]:
    """Ensure all patients exist in Aurora, return DICOM PatientID -> Aurora ID mapping."""
    mapping = get_patient_mapping(conn)
    cur = conn.cursor()
    created = 0

    unmapped_pids = set()
    for study in studies.values():
        dpid = study["patient_id_dicom"]
        if dpid and dpid not in mapping:
            unmapped_pids.add((dpid, study.get("patient_name", "")))

    for dpid, pname in unmapped_pids:
        collection = infer_collection(dpid)
        mrn = generate_mrn(collection, dpid)
        parts = pname.replace("^", " ").split() if pname else []
        first_name = parts[0] if parts else dpid[:20]
        last_name = parts[1] if len(parts) > 1 else collection

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

        cur.execute("""
            INSERT INTO clinical.patient_identifiers
                (patient_id, identifier_type, identifier_value, source_system, created_at, updated_at)
            VALUES (%s, 'tcia_subject', %s, %s, NOW(), NOW())
            ON CONFLICT DO NOTHING
        """, (patient_id, dpid, collection))

        cur.execute("""
            INSERT INTO clinical.patient_identifiers
                (patient_id, identifier_type, identifier_value, source_system, created_at, updated_at)
            VALUES (%s, 'tcia_collection', %s, %s, NOW(), NOW())
            ON CONFLICT DO NOTHING
        """, (patient_id, collection, 'dicom_extract'))

        mapping[dpid] = patient_id

    conn.commit()
    if created:
        print(f"  Created {created} new patients")
    return mapping


def upsert_studies_and_series(studies: dict, patient_mapping: dict,
                               collection_name: str, conn, dry_run: bool) -> dict:
    """Upsert study and series records into Aurora."""
    cur = conn.cursor()
    stats = {"studies_inserted": 0, "studies_updated": 0, "series_inserted": 0,
             "series_updated": 0, "skipped_no_patient": 0}

    for study in studies.values():
        dpid = study["patient_id_dicom"]
        patient_id = patient_mapping.get(dpid)
        if not patient_id:
            stats["skipped_no_patient"] += 1
            continue

        study_uid = study["study_uid"]
        study_date = format_dicom_date(study["study_date"])
        primary_modality = sorted(study["modalities"])[0] if study["modalities"] else None
        body_part = (sorted(study["body_parts"])[0] if study["body_parts"]
                     else determine_body_part(study["study_description"], list(study["modalities"])))

        # Base path for this collection's DICOM files
        dicom_endpoint = f"file://{DOWNLOAD_ROOT}/{collection_name}"

        if dry_run:
            stats["studies_inserted"] += 1
            stats["series_inserted"] += len(study["series"])
            continue

        # Upsert study
        cur.execute("SELECT id FROM clinical.imaging_studies WHERE study_uid = %s", (study_uid,))
        existing = cur.fetchone()

        if existing:
            study_id = existing[0]
            cur.execute("""
                UPDATE clinical.imaging_studies SET
                    patient_id = %s, modality = COALESCE(%s, modality),
                    study_date = COALESCE(%s, study_date),
                    description = COALESCE(%s, description),
                    body_part = COALESCE(%s, body_part),
                    accession_number = COALESCE(%s, accession_number),
                    num_series = %s, num_instances = %s,
                    dicom_endpoint = %s, source_type = 'tcia',
                    source_id = %s, updated_at = NOW()
                WHERE id = %s
            """, (patient_id, primary_modality, study_date,
                  study["study_description"], body_part,
                  study["accession_number"],
                  len(study["series"]), study["total_instances"],
                  dicom_endpoint, f"dicom_extract_{collection_name}",
                  study_id))
            stats["studies_updated"] += 1
        else:
            cur.execute("""
                INSERT INTO clinical.imaging_studies
                    (patient_id, study_uid, modality, study_date, description,
                     body_part, accession_number, num_series, num_instances,
                     dicom_endpoint, source_type, source_id, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s,
                        %s, 'tcia', %s, NOW(), NOW())
                RETURNING id
            """, (patient_id, study_uid, primary_modality, study_date,
                  study["study_description"], body_part,
                  study["accession_number"],
                  len(study["series"]), study["total_instances"],
                  dicom_endpoint, f"dicom_extract_{collection_name}"))
            study_id = cur.fetchone()[0]
            stats["studies_inserted"] += 1

        # Upsert series
        for s in study["series"]:
            series_uid = s["series_uid"]
            if not series_uid:
                continue

            # Store relative path from collection root
            series_dir_rel = os.path.basename(s["series_dir"])

            cur.execute("SELECT id FROM clinical.imaging_series WHERE series_uid = %s", (series_uid,))
            existing_series = cur.fetchone()

            if existing_series:
                cur.execute("""
                    UPDATE clinical.imaging_series SET
                        imaging_study_id = %s, series_number = %s,
                        modality = COALESCE(%s, modality),
                        description = COALESCE(%s, description),
                        num_instances = %s,
                        source_type = %s, source_id = %s,
                        updated_at = NOW()
                    WHERE id = %s
                """, (study_id, s["series_number"], s["modality"],
                      s["series_description"], s["num_instances"],
                      'tcia', series_dir_rel, existing_series[0]))
                stats["series_updated"] += 1
            else:
                cur.execute("""
                    INSERT INTO clinical.imaging_series
                        (imaging_study_id, series_uid, series_number, modality,
                         description, num_instances, source_type, source_id,
                         created_at, updated_at)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
                """, (study_id, series_uid, s["series_number"], s["modality"],
                      s["series_description"], s["num_instances"],
                      'tcia', series_dir_rel))
                stats["series_inserted"] += 1

    if not dry_run:
        conn.commit()

    cur.close()
    return stats


def process_collection(collection_name: str, conn, parallel: int, dry_run: bool):
    """Process a single TCIA collection end-to-end."""
    collection_dir = os.path.join(DOWNLOAD_ROOT, collection_name)
    if not os.path.isdir(collection_dir):
        print(f"  SKIP: Directory not found: {collection_dir}")
        return

    print(f"\n{'='*60}")
    print(f"  Collection: {collection_name}")
    print(f"  Path: {collection_dir}")
    print(f"  Parallel: {parallel}")
    if dry_run:
        print(f"  Mode: DRY RUN")

    # Step 1: Scan DICOM metadata
    print(f"\n  [1/3] Scanning DICOM headers...")
    t0 = time.time()
    series_list = scan_collection(collection_dir, parallel)
    scan_time = time.time() - t0
    print(f"  Scan complete in {scan_time:.1f}s")

    if not series_list:
        print(f"  No DICOM data found.")
        return

    # Step 2: Group by study and ensure patients
    print(f"\n  [2/3] Grouping studies and ensuring patients...")
    studies = group_by_study(series_list)
    print(f"  {len(studies)} studies from {len(set(s['patient_id_dicom'] for s in series_list))} patients")

    if not dry_run:
        patient_mapping = ensure_patients(studies, conn)
    else:
        patient_mapping = get_patient_mapping(conn)

    # Step 3: Upsert into Aurora
    print(f"\n  [3/3] Upserting to Aurora DB...")
    stats = upsert_studies_and_series(studies, patient_mapping, collection_name, conn, dry_run)

    print(f"\n  Results for {collection_name}:")
    print(f"    Studies inserted:  {stats['studies_inserted']}")
    print(f"    Studies updated:   {stats['studies_updated']}")
    print(f"    Series inserted:   {stats['series_inserted']}")
    print(f"    Series updated:    {stats['series_updated']}")
    print(f"    No patient match:  {stats['skipped_no_patient']}")


def main():
    parser = argparse.ArgumentParser(description="Extract DICOM metadata to Aurora DB")
    parser.add_argument("collection", help="Collection name or 'all'")
    parser.add_argument("--parallel", type=int, default=16,
                        help="Parallel threads for DICOM reads (default: 16)")
    parser.add_argument("--dry-run", action="store_true")
    args = parser.parse_args()

    collections = COLLECTIONS if args.collection == "all" else [args.collection]

    print("=== DICOM Metadata Extraction → Aurora ===")
    print(f"  Root: {DOWNLOAD_ROOT}")

    conn = connect_db()
    t0 = time.time()

    for collection in collections:
        process_collection(collection, conn, args.parallel, args.dry_run)

    elapsed = time.time() - t0
    conn.close()

    print(f"\n{'='*60}")
    print(f"  Total time: {elapsed:.1f}s ({elapsed/60:.1f} min)")
    print(f"  Done.")


if __name__ == "__main__":
    main()
