#!/usr/bin/env bash
set -euo pipefail

# Download TCIA collections via the public REST API, bypassing NBIA Data Retriever.
# Each series is fetched as a zip and extracted into the target directory.

SCRIPT_NAME="$(basename "$0")"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

MANIFEST_DIR="${MANIFEST_DIR:-${SCRIPT_DIR}/tcia_manifests}"
DOWNLOAD_ROOT="${DOWNLOAD_ROOT:-$HOME/TCIA-downloads}"
PHASE="${1:-phase1}"
PARALLEL="${PARALLEL:-4}"
API_BASE="https://public.cancerimagingarchive.net/nbia-api/services/v1"

declare -a phase1=(
  "CPTAC-PDA"
  "PSMA-PET-CT-Lesions"
  "NSCLC-Radiomics"
  "HCC-TACE-Seg"
)

declare -a phase2=(
  "TCGA-KIRC"
  "TCGA-LUAD"
)

declare -a phase3=(
  "TCGA-BRCA"
  "CPTAC-CCRCC"
)

usage() {
  cat <<EOF
Usage:
  ${SCRIPT_NAME} phase1|phase2|phase3|all|list [collection_name]

  If a collection_name is given, only that collection is downloaded.

Environment variables:
  MANIFEST_DIR   Directory containing .tcia manifest files (default: ${MANIFEST_DIR})
  DOWNLOAD_ROOT  Parent directory for downloaded collections (default: ${DOWNLOAD_ROOT})
  PARALLEL       Number of parallel downloads (default: ${PARALLEL})
EOF
}

print_plan() {
  cat <<EOF
Phase 1
  CPTAC-PDA              155.24 GB   1133 series
  PSMA-PET-CT-Lesions    117.08 GB   1791 series
  NSCLC-Radiomics         35.78 GB   1265 series
  HCC-TACE-Seg            28.57 GB    677 series

Phase 2
  TCGA-KIRC               91.56 GB
  TCGA-LUAD               19.62 GB

Phase 3
  TCGA-BRCA               88.13 GB
  CPTAC-CCRCC            280.22 GB
EOF
}

if [[ "${PHASE}" == "list" || "${PHASE}" == "--help" || "${PHASE}" == "-h" ]]; then
  usage
  echo
  print_plan
  exit 0
fi

collections=()
case "${PHASE}" in
  phase1) collections=("${phase1[@]}") ;;
  phase2) collections=("${phase2[@]}") ;;
  phase3) collections=("${phase3[@]}") ;;
  all)    collections=("${phase1[@]}" "${phase2[@]}" "${phase3[@]}") ;;
  *)
    echo "Unknown phase: ${PHASE}" >&2
    usage >&2
    exit 1
    ;;
esac

# Optional: filter to a single collection
if [[ -n "${2:-}" ]]; then
  found=0
  for c in "${collections[@]}"; do
    if [[ "${c}" == "$2" ]]; then
      collections=("$2")
      found=1
      break
    fi
  done
  if [[ "${found}" -eq 0 ]]; then
    echo "Collection $2 not found in ${PHASE}" >&2
    exit 1
  fi
fi

download_series() {
  local series_uid="$1"
  local target_dir="$2"
  local progress_file="$3"
  local done_marker="${target_dir}/.done_${series_uid}"

  # Skip if already downloaded
  if [[ -f "${done_marker}" ]]; then
    echo "SKIP  ${series_uid}" >> "${progress_file}"
    return 0
  fi

  local tmp_zip="${target_dir}/.tmp_${series_uid}.zip"
  local series_dir="${target_dir}/${series_uid}"

  # Download zip
  local http_code
  http_code=$(curl -s -o "${tmp_zip}" -w "%{http_code}" --retry 3 --retry-delay 5 \
    "${API_BASE}/getDCMImage?SeriesInstanceUID=${series_uid}")

  if [[ "${http_code}" != "200" ]]; then
    echo "FAIL  ${series_uid} (HTTP ${http_code})" >> "${progress_file}"
    rm -f "${tmp_zip}"
    return 1
  fi

  # Extract
  mkdir -p "${series_dir}"
  if unzip -qo "${tmp_zip}" -d "${series_dir}" 2>/dev/null; then
    rm -f "${tmp_zip}"
    touch "${done_marker}"
    echo "OK    ${series_uid}" >> "${progress_file}"
  else
    echo "FAIL  ${series_uid} (unzip error)" >> "${progress_file}"
    rm -f "${tmp_zip}"
    return 1
  fi
}

export -f download_series
export API_BASE

for collection in "${collections[@]}"; do
  manifest="${MANIFEST_DIR}/${collection}.tcia"
  if [[ ! -f "${manifest}" ]]; then
    echo "Missing manifest: ${manifest}" >&2
    continue
  fi

  target_dir="${DOWNLOAD_ROOT}/${collection}"
  mkdir -p "${target_dir}"

  # Extract series UIDs from manifest
  series_file="${target_dir}/.series_list.txt"
  grep -E '^[0-9]+\.' "${manifest}" > "${series_file}"

  total=$(wc -l < "${series_file}")
  already_done=$(find "${target_dir}" -maxdepth 1 -name '.done_*' 2>/dev/null | wc -l)

  progress_file="${target_dir}/.progress.log"
  : > "${progress_file}"

  echo "=== ${collection} ==="
  echo "  Series: ${total} total, ${already_done} already done, $((total - already_done)) remaining"
  echo "  Target: ${target_dir}"
  echo "  Parallel: ${PARALLEL}"
  echo ""

  if [[ "${already_done}" -eq "${total}" ]]; then
    echo "  All series already downloaded. Skipping."
    echo ""
    continue
  fi

  # Download in parallel using xargs
  cat "${series_file}" | xargs -P "${PARALLEL}" -I {} bash -c \
    'download_series "$1" "$2" "$3"' _ {} "${target_dir}" "${progress_file}"

  ok_count=$(grep -c '^OK' "${progress_file}" || true)
  skip_count=$(grep -c '^SKIP' "${progress_file}" || true)
  fail_count=$(grep -c '^FAIL' "${progress_file}" || true)

  echo "  Results: ${ok_count} downloaded, ${skip_count} skipped, ${fail_count} failed"

  if [[ "${fail_count}" -gt 0 ]]; then
    echo "  Failed series:"
    grep '^FAIL' "${progress_file}" | head -10
    echo "  (rerun to retry failed series)"
  fi
  echo ""
done

echo "Done."
