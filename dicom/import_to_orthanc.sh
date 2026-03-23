#!/usr/bin/env bash
set -euo pipefail

# Import TCIA DICOM files into Orthanc PACS via the REST API.
# Uploads .dcm files in parallel, tracks progress, and is resumable.

SCRIPT_NAME="$(basename "$0")"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

DOWNLOAD_ROOT="${DOWNLOAD_ROOT:-/media/smudoshi/DATA/TCIA-downloads}"
ORTHANC_URL="${ORTHANC_URL:-http://localhost:8042}"
ORTHANC_USER="${ORTHANC_USER:-parthenon}"
ORTHANC_PASS="${ORTHANC_PASS:-orthanc_secret}"
PARALLEL="${PARALLEL:-8}"
COLLECTION="${1:-}"

usage() {
  cat <<EOF
Usage:
  ${SCRIPT_NAME} <collection>          Import a single collection
  ${SCRIPT_NAME} all                   Import all downloaded collections
  ${SCRIPT_NAME} status                Show Orthanc stats and available collections
  ${SCRIPT_NAME} list                  List available collections

Environment variables:
  DOWNLOAD_ROOT  Parent directory for downloaded collections (default: ${DOWNLOAD_ROOT})
  ORTHANC_URL    Orthanc REST API URL (default: ${ORTHANC_URL})
  ORTHANC_USER   Orthanc username (default: ${ORTHANC_USER})
  ORTHANC_PASS   Orthanc password
  PARALLEL       Number of parallel uploads (default: ${PARALLEL})
EOF
}

orthanc_curl() {
  curl -s -u "${ORTHANC_USER}:${ORTHANC_PASS}" "$@"
}

check_orthanc() {
  if ! orthanc_curl "${ORTHANC_URL}/system" > /dev/null 2>&1; then
    echo "ERROR: Cannot reach Orthanc at ${ORTHANC_URL}" >&2
    echo "Make sure Orthanc is running." >&2
    exit 1
  fi
}

show_status() {
  check_orthanc
  echo "=== Orthanc Server ==="
  orthanc_curl "${ORTHANC_URL}/statistics" | python3 -c "
import sys, json
s = json.load(sys.stdin)
print(f\"  Patients:  {s['CountPatients']}\")
print(f\"  Studies:   {s['CountStudies']}\")
print(f\"  Series:    {s['CountSeries']}\")
print(f\"  Instances: {s['CountInstances']}\")
print(f\"  Disk:      {s['TotalDiskSizeMB']} MB\")
"
  echo ""
  list_collections
}

list_collections() {
  echo "=== Available Collections ==="
  for d in "${DOWNLOAD_ROOT}"/*/; do
    name=$(basename "$d")
    # Skip non-DICOM directories
    [[ "${name}" == "genomics" ]] && continue
    [[ "${name}" == "manifests" ]] && continue

    dcm_count=$(find "$d" -name '*.dcm' -type f 2>/dev/null | wc -l)
    if [[ "${dcm_count}" -gt 0 ]]; then
      size=$(du -sh "$d" 2>/dev/null | cut -f1)
      imported_marker="${d}/.orthanc_imported"
      if [[ -f "${imported_marker}" ]]; then
        status="IMPORTED"
      else
        status="READY"
      fi
      printf "  %-30s %8d files  %8s  [%s]\n" "${name}" "${dcm_count}" "${size}" "${status}"
    fi
  done
}

upload_file() {
  local file="$1"
  local progress_file="$2"
  local orthanc_url="$3"
  local orthanc_user="$4"
  local orthanc_pass="$5"

  local http_code
  http_code=$(curl -s -u "${orthanc_user}:${orthanc_pass}" \
    -X POST "${orthanc_url}/instances" \
    --data-binary @"${file}" \
    -H "Content-Type: application/dicom" \
    -o /dev/null \
    -w "%{http_code}" \
    --retry 2 --retry-delay 3 \
    --max-time 60)

  if [[ "${http_code}" == "200" ]]; then
    echo "OK" >> "${progress_file}"
  elif [[ "${http_code}" == "409" ]]; then
    # Already exists — that's fine
    echo "SKIP" >> "${progress_file}"
  else
    echo "FAIL ${http_code} ${file}" >> "${progress_file}"
  fi
}

export -f upload_file

import_collection() {
  local collection_dir="$1"
  local name=$(basename "${collection_dir}")

  echo "=== Importing ${name} ==="

  # Build file list
  local file_list="${collection_dir}/.dcm_file_list.txt"
  echo "  Scanning for .dcm files..."
  find "${collection_dir}" -name '*.dcm' -type f > "${file_list}"
  local total=$(wc -l < "${file_list}")

  if [[ "${total}" -eq 0 ]]; then
    echo "  No .dcm files found. Skipping."
    return
  fi

  local progress_file="${collection_dir}/.orthanc_progress.log"
  : > "${progress_file}"

  echo "  Files:    ${total}"
  echo "  Parallel: ${PARALLEL}"
  echo "  Target:   ${ORTHANC_URL}"
  echo ""

  # Upload in parallel
  cat "${file_list}" | xargs -P "${PARALLEL}" -I {} bash -c \
    'upload_file "$1" "$2" "$3" "$4" "$5"' _ {} \
    "${progress_file}" "${ORTHANC_URL}" "${ORTHANC_USER}" "${ORTHANC_PASS}"

  # Summarize
  local ok_count=$(grep -c '^OK' "${progress_file}" 2>/dev/null || echo 0)
  local skip_count=$(grep -c '^SKIP' "${progress_file}" 2>/dev/null || echo 0)
  local fail_count=$(grep -c '^FAIL' "${progress_file}" 2>/dev/null || echo 0)

  echo "  Results: ${ok_count} uploaded, ${skip_count} already existed, ${fail_count} failed"

  if [[ "${fail_count}" -gt 0 ]]; then
    echo "  Failed files (first 10):"
    grep '^FAIL' "${progress_file}" | head -10 | sed 's/^/    /'
  fi

  if [[ "${fail_count}" -eq 0 ]]; then
    touch "${collection_dir}/.orthanc_imported"
    echo "  Marked as imported."
  fi

  echo ""
}

# ── Main ─────────────────────────────────────────────────────

if [[ -z "${COLLECTION}" || "${COLLECTION}" == "--help" || "${COLLECTION}" == "-h" ]]; then
  usage
  exit 0
fi

if [[ "${COLLECTION}" == "status" ]]; then
  show_status
  exit 0
fi

if [[ "${COLLECTION}" == "list" ]]; then
  list_collections
  exit 0
fi

check_orthanc

if [[ "${COLLECTION}" == "all" ]]; then
  for d in "${DOWNLOAD_ROOT}"/*/; do
    name=$(basename "$d")
    [[ "${name}" == "genomics" ]] && continue
    [[ "${name}" == "manifests" ]] && continue
    dcm_count=$(find "$d" -name '*.dcm' -type f 2>/dev/null | wc -l)
    if [[ "${dcm_count}" -gt 0 ]]; then
      import_collection "$d"
    fi
  done
else
  collection_dir="${DOWNLOAD_ROOT}/${COLLECTION}"
  if [[ ! -d "${collection_dir}" ]]; then
    echo "ERROR: Directory not found: ${collection_dir}" >&2
    exit 1
  fi
  import_collection "${collection_dir}"
fi

# Show final Orthanc stats
echo "=== Final Orthanc Statistics ==="
orthanc_curl "${ORTHANC_URL}/statistics" | python3 -c "
import sys, json
s = json.load(sys.stdin)
print(f\"  Patients:  {s['CountPatients']}\")
print(f\"  Studies:   {s['CountStudies']}\")
print(f\"  Series:    {s['CountSeries']}\")
print(f\"  Instances: {s['CountInstances']}\")
print(f\"  Disk:      {s['TotalDiskSizeMB']} MB\")
"

echo ""
echo "Done."
