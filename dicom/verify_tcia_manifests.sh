#!/usr/bin/env bash
set -euo pipefail

SCRIPT_NAME="$(basename "$0")"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MANIFEST_DIR="${MANIFEST_DIR:-${SCRIPT_DIR}/tcia_manifests}"
PHASE="${1:-all}"

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
  ${SCRIPT_NAME} phase1
  ${SCRIPT_NAME} phase2
  ${SCRIPT_NAME} phase3
  ${SCRIPT_NAME} all

Environment variables:
  MANIFEST_DIR   Directory containing .tcia manifest files
                 Default: ${MANIFEST_DIR}
EOF
}

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
  --help|-h|list)
    usage
    exit 0
    ;;
  *)
    echo "Unknown phase: ${PHASE}" >&2
    usage >&2
    exit 1
    ;;
esac

echo "Verifying TCIA manifests in ${MANIFEST_DIR}"
echo "Phase: ${PHASE}"
echo

failures=0

verify_manifest() {
  local collection="$1"
  local manifest="${MANIFEST_DIR}/${collection}.tcia"

  if [[ ! -f "${manifest}" ]]; then
    echo "FAIL  ${collection}: missing file ${manifest}"
    failures=1
    return
  fi

  if [[ ! -s "${manifest}" ]]; then
    echo "FAIL  ${collection}: file is empty"
    failures=1
    return
  fi

  local mime
  mime="$(file -b --mime-type "${manifest}" 2>/dev/null || true)"
  case "${mime}" in
    text/*|application/xml|application/json|application/octet-stream|application/zip|"")
      ;;
    *)
      echo "WARN  ${collection}: unexpected mime type ${mime}"
      ;;
  esac

  local hit
  hit="$(grep -E -c 'https?://|series|Series|patient|Patient|Study|manifest|nbia|dicom' "${manifest}" || true)"
  if [[ "${hit}" -eq 0 ]]; then
    echo "FAIL  ${collection}: file does not look like a TCIA/NBIA manifest"
    failures=1
    return
  fi

  echo "PASS  ${collection}: ${manifest}"
}

for collection in "${collections[@]}"; do
  verify_manifest "${collection}"
done

echo
if [[ "${failures}" -ne 0 ]]; then
  echo "Manifest verification failed." >&2
  exit 1
fi

echo "All requested manifests passed basic verification."
