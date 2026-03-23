#!/usr/bin/env bash
set -euo pipefail

SCRIPT_NAME="$(basename "$0")"
NBIA_DEB_URL="${NBIA_DEB_URL:-https://wiki.cancerimagingarchive.net/download/attachments/392070977/nbia-data-retriever-4.4.1.deb}"
TMP_DEB="${TMP_DEB:-/tmp/nbia-data-retriever-4.4.1.deb}"

usage() {
  cat <<EOF
Usage:
  ${SCRIPT_NAME}

Environment variables:
  NBIA_DEB_URL   Override the NBIA .deb download URL
                 Default: ${NBIA_DEB_URL}
  TMP_DEB        Temporary path for the downloaded .deb
                 Default: ${TMP_DEB}

What it does:
  1. Downloads the NBIA Data Retriever Ubuntu .deb package
  2. Installs it with dpkg
  3. Prints the detected CLI path

Notes:
  - This script requires sudo privileges.
  - TCIA currently documents the Ubuntu package as nbia-data-retriever-4.4.1.deb.
EOF
}

if [[ "${1:-}" == "--help" || "${1:-}" == "-h" ]]; then
  usage
  exit 0
fi

if ! command -v curl >/dev/null 2>&1; then
  echo "curl is required but not installed." >&2
  exit 1
fi

if ! command -v sudo >/dev/null 2>&1; then
  echo "sudo is required but not installed." >&2
  exit 1
fi

echo "Downloading NBIA Data Retriever package"
echo "  URL:  ${NBIA_DEB_URL}"
echo "  File: ${TMP_DEB}"

curl -fL "${NBIA_DEB_URL}" -o "${TMP_DEB}"

echo "Installing package with dpkg"
sudo dpkg -i "${TMP_DEB}" || sudo apt-get install -f -y

echo
echo "Checking installed CLI"
for candidate in \
  /opt/nbia-data-retriever/bin/nbia-data-retriever \
  /opt/NBIADataRetriever/bin/NBIADataRetriever \
  "$(command -v nbia-data-retriever 2>/dev/null || true)" \
  "$(command -v NBIADataRetriever 2>/dev/null || true)"
do
  if [[ -n "${candidate}" && -x "${candidate}" ]]; then
    echo "Installed CLI: ${candidate}"
    exit 0
  fi
done

echo "Installation completed, but the CLI path was not found automatically." >&2
echo "Check installed files with: dpkg -L nbia-data-retriever" >&2
exit 1
