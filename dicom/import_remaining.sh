#!/usr/bin/env bash
set -euo pipefail

# Import remaining collections to Orthanc, then re-sync to Aurora.
# Run after PSMA import completes.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG="${SCRIPT_DIR}/orthanc_import.log"

echo "=== Starting remaining imports: $(date) ===" >> "${LOG}"

for collection in TCGA-KIRC TCGA-LUAD TCGA-BRCA CPTAC-CCRCC; do
    echo ""
    echo ">>> Importing ${collection} at $(date)"
    PARALLEL=4 bash "${SCRIPT_DIR}/import_to_orthanc.sh" "${collection}" 2>&1 | tee -a "${LOG}"
done

echo ""
echo "=== All imports complete: $(date) ==="
echo ">>> Running Orthanc → Aurora sync..."
python3 "${SCRIPT_DIR}/sync_orthanc_to_aurora.py" 2>&1 | tee -a "${LOG}"

echo ""
echo "=== Done: $(date) ==="
