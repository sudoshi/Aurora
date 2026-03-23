#!/usr/bin/env bash
set -euo pipefail

SCRIPT_NAME="$(basename "$0")"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

MANIFEST_DIR="${MANIFEST_DIR:-${SCRIPT_DIR}/tcia_manifests}"
DOWNLOAD_ROOT="${DOWNLOAD_ROOT:-$HOME/TCIA-downloads}"
PHASE="${1:-phase1}"
VERIFY_SCRIPT="${SCRIPT_DIR}/verify_tcia_manifests.sh"

find_nbia_cli() {
  local candidates=(
    "/opt/nbia-data-retriever/nbia-data-retriever"
    "/opt/nbia-data-retriever/bin/nbia-data-retriever"
    "/opt/NBIADataRetriever/bin/NBIADataRetriever"
    "$(command -v nbia-data-retriever 2>/dev/null || true)"
    "$(command -v NBIADataRetriever 2>/dev/null || true)"
  )
  local c
  for c in "${candidates[@]}"; do
    if [[ -n "${c}" && -x "${c}" ]]; then
      printf '%s\n' "${c}"
      return 0
    fi
  done
  return 1
}

usage() {
  cat <<EOF
Usage:
  ${SCRIPT_NAME} phase1
  ${SCRIPT_NAME} phase2
  ${SCRIPT_NAME} phase3
  ${SCRIPT_NAME} all
  ${SCRIPT_NAME} list

Environment variables:
  MANIFEST_DIR   Directory containing .tcia manifest files
                 Default: ${MANIFEST_DIR}
  DOWNLOAD_ROOT  Parent directory for downloaded collections
                 Default: ${DOWNLOAD_ROOT}

Expected manifest filenames:
  CPTAC-PDA.tcia
  PSMA-PET-CT-Lesions.tcia
  NSCLC-Radiomics.tcia
  HCC-TACE-Seg.tcia
  TCGA-KIRC.tcia
  TCGA-LUAD.tcia
  TCGA-BRCA.tcia
  CPTAC-CCRCC.tcia

How to use:
  1. Install NBIA Data Retriever.
  2. Download each collection's .tcia manifest from the TCIA collection page.
  3. Save the manifest files into \$MANIFEST_DIR using the filenames above.
  4. Run this script with a phase name.
EOF
}

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

print_plan() {
  cat <<EOF
Phase 1
  CPTAC-PDA              155.24 GB
  PSMA-PET-CT-Lesions    117.08 GB
  NSCLC-Radiomics         35.78 GB
  HCC-TACE-Seg            28.57 GB
  Total:                 336.67 GB

Phase 2
  TCGA-KIRC               91.56 GB
  TCGA-LUAD               19.62 GB
  Running total:         447.85 GB

Phase 3
  TCGA-BRCA               88.13 GB
  CPTAC-CCRCC            280.22 GB
  Full total:            816.20 GB
EOF
}

if [[ "${PHASE}" == "list" || "${PHASE}" == "--help" || "${PHASE}" == "-h" ]]; then
  usage
  echo
  print_plan
  exit 0
fi

if ! NBIA_CLI="$(find_nbia_cli)"; then
  cat <<EOF >&2
NBIA Data Retriever CLI not found.

Install it first, then rerun this script.
The script looks for:
  /opt/nbia-data-retriever/bin/nbia-data-retriever
  /opt/NBIADataRetriever/bin/NBIADataRetriever
  nbia-data-retriever
  NBIADataRetriever
EOF
  exit 1
fi

mkdir -p "${DOWNLOAD_ROOT}"
mkdir -p "${MANIFEST_DIR}"

collections=()
case "${PHASE}" in
  phase1)
    collections=("${phase1[@]}")
    ;;
  phase2)
    collections=("${phase2[@]}")
    ;;
  phase3)
    collections=("${phase3[@]}")
    ;;
  all)
    collections=("${phase1[@]}" "${phase2[@]}" "${phase3[@]}")
    ;;
  *)
    echo "Unknown phase: ${PHASE}" >&2
    usage >&2
    exit 1
    ;;
esac

echo "Using NBIA CLI: ${NBIA_CLI}"
echo "Manifest directory: ${MANIFEST_DIR}"
echo "Download root: ${DOWNLOAD_ROOT}"
echo "Selected phase: ${PHASE}"
echo

if [[ -x "${VERIFY_SCRIPT}" ]]; then
  "${VERIFY_SCRIPT}" "${PHASE}"
else
  missing=0
  for collection in "${collections[@]}"; do
    manifest="${MANIFEST_DIR}/${collection}.tcia"
    if [[ ! -f "${manifest}" ]]; then
      echo "Missing manifest: ${manifest}" >&2
      missing=1
    fi
  done

  if [[ "${missing}" -ne 0 ]]; then
    cat <<EOF >&2

One or more required manifest files are missing.
Download the .tcia manifest for each missing collection from its TCIA page
and save it under ${MANIFEST_DIR} using the exact collection name.
EOF
    exit 1
  fi
fi

for collection in "${collections[@]}"; do
  manifest="${MANIFEST_DIR}/${collection}.tcia"
  target_dir="${DOWNLOAD_ROOT}/${collection}"
  mkdir -p "${target_dir}"

  echo "Starting ${collection}"
  echo "  manifest: ${manifest}"
  echo "  target:   ${target_dir}"

  echo "Y" | "${NBIA_CLI}" --cli "${manifest}" -d "${target_dir}" -v -f

  echo "Completed ${collection}"
  echo
done

echo "Done."
