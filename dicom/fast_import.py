#!/usr/bin/env python3
"""
Fast parallel DICOM import to Orthanc using Python threads.

Much faster than bash find+xargs: uses os.scandir (faster than find on spinning
disks), urllib (avoids curl process spawn overhead), and ThreadPoolExecutor for
true parallel uploads.

Usage:
    python3 fast_import.py <collection> [--parallel N] [--dry-run]
"""

import argparse
import base64
import json
import os
import sys
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from pathlib import Path
from urllib.request import Request, urlopen
from urllib.error import URLError, HTTPError

DOWNLOAD_ROOT = os.environ.get("DOWNLOAD_ROOT", "/media/smudoshi/DATA/TCIA-downloads")
ORTHANC_URL = os.environ.get("ORTHANC_URL", "http://localhost:8042")
ORTHANC_USER = os.environ.get("ORTHANC_USER", "parthenon")
ORTHANC_PASS = os.environ.get("ORTHANC_PASS", "orthanc_secret")

AUTH_HEADER = "Basic " + base64.b64encode(
    f"{ORTHANC_USER}:{ORTHANC_PASS}".encode()
).decode()


def scan_dcm_files(collection_dir: str) -> list[str]:
    """Fast recursive scan for .dcm files using os.scandir."""
    files = []
    stack = [collection_dir]
    while stack:
        d = stack.pop()
        try:
            with os.scandir(d) as entries:
                for entry in entries:
                    if entry.is_dir(follow_symlinks=False):
                        stack.append(entry.path)
                    elif entry.name.endswith(".dcm") and entry.is_file(follow_symlinks=False):
                        files.append(entry.path)
        except PermissionError:
            pass
    return files


def upload_file(filepath: str) -> tuple[str, str]:
    """Upload a single DICOM file to Orthanc. Returns (status, filepath)."""
    try:
        with open(filepath, "rb") as f:
            data = f.read()

        req = Request(
            f"{ORTHANC_URL}/instances",
            data=data,
            headers={
                "Authorization": AUTH_HEADER,
                "Content-Type": "application/dicom",
            },
            method="POST",
        )
        with urlopen(req, timeout=120) as resp:
            return ("OK", filepath)
    except HTTPError as e:
        if e.code == 409:
            return ("SKIP", filepath)
        return (f"FAIL_{e.code}", filepath)
    except Exception as e:
        return (f"FAIL_ERR", filepath)


def main():
    parser = argparse.ArgumentParser(description="Fast DICOM import to Orthanc")
    parser.add_argument("collection", help="Collection directory name")
    parser.add_argument("--parallel", type=int, default=24,
                        help="Number of parallel upload threads (default: 24)")
    parser.add_argument("--dry-run", action="store_true",
                        help="Scan files only, don't upload")
    args = parser.parse_args()

    collection_dir = os.path.join(DOWNLOAD_ROOT, args.collection)
    if not os.path.isdir(collection_dir):
        print(f"ERROR: Directory not found: {collection_dir}")
        sys.exit(1)

    # Check Orthanc
    try:
        req = Request(f"{ORTHANC_URL}/statistics",
                      headers={"Authorization": AUTH_HEADER})
        with urlopen(req, timeout=10) as resp:
            stats = json.loads(resp.read())
        print(f"Orthanc: {stats['CountInstances']} instances, "
              f"{stats['TotalDiskSizeMB']} MB")
    except Exception as e:
        print(f"ERROR: Cannot reach Orthanc: {e}")
        sys.exit(1)

    # Scan files
    print(f"\nScanning {args.collection} for .dcm files...")
    t0 = time.time()
    files = scan_dcm_files(collection_dir)
    scan_time = time.time() - t0
    print(f"  Found {len(files)} files in {scan_time:.1f}s")

    if not files:
        print("  No .dcm files found.")
        return

    if args.dry_run:
        print(f"  DRY RUN: Would upload {len(files)} files with {args.parallel} threads")
        return

    # Upload
    print(f"\nUploading with {args.parallel} threads...")
    stats = {"OK": 0, "SKIP": 0, "FAIL": 0}
    t0 = time.time()
    last_report = t0

    with ThreadPoolExecutor(max_workers=args.parallel) as pool:
        futures = {pool.submit(upload_file, f): f for f in files}
        done_count = 0

        for future in as_completed(futures):
            status, filepath = future.result()
            done_count += 1

            if status == "OK":
                stats["OK"] += 1
            elif status == "SKIP":
                stats["SKIP"] += 1
            else:
                stats["FAIL"] += 1

            now = time.time()
            if now - last_report >= 30 or done_count == len(files):
                elapsed = now - t0
                rate = done_count / elapsed * 60 if elapsed > 0 else 0
                remaining = (len(files) - done_count) / rate if rate > 0 else 0
                print(f"  [{done_count}/{len(files)}] "
                      f"OK={stats['OK']} SKIP={stats['SKIP']} FAIL={stats['FAIL']} "
                      f"({rate:.0f}/min, ETA {remaining:.0f} min)", flush=True)
                last_report = now

    elapsed = time.time() - t0
    print(f"\n=== Import Complete: {args.collection} ===")
    print(f"  Uploaded: {stats['OK']}")
    print(f"  Skipped:  {stats['SKIP']}")
    print(f"  Failed:   {stats['FAIL']}")
    print(f"  Time:     {elapsed/60:.1f} min ({len(files)/elapsed*60:.0f} files/min)")

    if stats["FAIL"] == 0:
        Path(collection_dir, ".orthanc_imported").touch()
        print("  Marked as imported.")


if __name__ == "__main__":
    main()
