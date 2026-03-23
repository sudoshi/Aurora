# How To Use These TCIA Assets

This directory contains a small TCIA workflow bundle for cataloguing existing DICOM archives and downloading additional TCIA collections in phases.

Prepared on: 2026-03-22

## Directory contents

- `tcia_dicom_study_catalogue.csv`
  - Per-study catalogue derived from the local directory:
    - `/media/smudoshi/DATA/Old Backup Data/DICOM/TCIA`
  - One row per unique study
  - Includes:
    - collection
    - subject ID
    - study UID
    - study date
    - study description
    - modality summary
    - disease association
    - TCIA source URL

- `tcia_disease_summary.md`
  - Short human-readable summary of the local TCIA collections already present
  - Maps each collection to its disease association and study counts

- `tcia_download_plan.md`
  - Ranked acquisition plan for additional TCIA oncology collections
  - Organized into `phase1`, `phase2`, and `phase3`
  - Includes approximate storage footprint and rationale

- `download_tcia_phases.sh`
  - Main download runner
  - Uses NBIA Data Retriever CLI plus `.tcia` manifest files
  - Downloads recommended collections by phase

- `verify_tcia_manifests.sh`
  - Preflight checker for `.tcia` manifest files
  - Validates existence, non-empty files, and basic manifest-like content

- `install_nbia_data_retriever_ubuntu.sh`
  - Ubuntu installer helper for NBIA Data Retriever
  - Downloads the official documented `.deb` and installs it with `dpkg`

## What problem these files solve

There are two separate tasks here:

1. Understand what is already present locally in the existing TCIA DICOM archive.
2. Download additional high-value TCIA oncology collections in a controlled, phased way.

The catalogue and summary files solve task 1.
The installer, verifier, download plan, and phased downloader solve task 2.

## Existing local archive

The existing local TCIA archive that was catalogued is:

`/media/smudoshi/DATA/Old Backup Data/DICOM/TCIA`

Important details:

- The archive is TCIA manifest-style and contains collection-specific `metadata.csv` files.
- The per-study catalogue was built from those `metadata.csv` files, not by parsing every DICOM header.
- `OsiriX Data.nosync` and macOS `._*` artifacts were intentionally ignored.

Summary of the local archive:

- 8 collections
- 784 unique studies

Collections already identified in the local archive:

- `CPTAC-PDA`
- `CTpred-Sunitinib-panNET`
- `PDMR-292921-168-R`
- `PDMR-521955-158-R4`
- `PDMR-833975-119-R`
- `Pancreas-CT`
- `Pancreatic-CT-CBCT-SEG`
- `Prostate-Anatomical-Edge-Cases`

## Recommended download phases

The recommended acquisition order is encoded in both `tcia_download_plan.md` and `download_tcia_phases.sh`.

### Phase 1

- `CPTAC-PDA`
- `PSMA-PET-CT-Lesions`
- `NSCLC-Radiomics`
- `HCC-TACE-Seg`

### Phase 2

- `TCGA-KIRC`
- `TCGA-LUAD`

### Phase 3

- `TCGA-BRCA`
- `CPTAC-CCRCC`

## Required inputs before downloading

The phased downloader does not fetch `.tcia` manifests automatically.
Another agent or user must first:

1. Install NBIA Data Retriever.
2. Visit the TCIA collection pages.
3. Download the corresponding `.tcia` manifest file for each collection.
4. Save each file in:
   - `~/tcia_manifests/`
5. Use these exact filenames:
   - `CPTAC-PDA.tcia`
   - `PSMA-PET-CT-Lesions.tcia`
   - `NSCLC-Radiomics.tcia`
   - `HCC-TACE-Seg.tcia`
   - `TCGA-KIRC.tcia`
   - `TCGA-LUAD.tcia`
   - `TCGA-BRCA.tcia`
   - `CPTAC-CCRCC.tcia`

## Intended execution order

### 1. Install NBIA Data Retriever on Ubuntu

Run:

```bash
/home/smudoshi/Github/Aurora/dicom/install_nbia_data_retriever_ubuntu.sh
```

This script requires:

- `curl`
- `sudo`
- network access

It was not executed during creation of these assets, so installation still needs to be performed.

### 2. Place `.tcia` manifests in the manifest directory

Default directory:

```bash
~/tcia_manifests
```

### 3. Verify manifests before downloading

Run:

```bash
/home/smudoshi/Github/Aurora/dicom/verify_tcia_manifests.sh phase1
```

Or:

```bash
/home/smudoshi/Github/Aurora/dicom/verify_tcia_manifests.sh all
```

### 4. Start downloads

Run:

```bash
/home/smudoshi/Github/Aurora/dicom/download_tcia_phases.sh phase1
```

The downloader will:

- locate the NBIA CLI
- verify manifests automatically if `verify_tcia_manifests.sh` is present
- download into:
  - `~/TCIA-downloads/<CollectionName>/`

Other valid invocations:

```bash
/home/smudoshi/Github/Aurora/dicom/download_tcia_phases.sh phase2
/home/smudoshi/Github/Aurora/dicom/download_tcia_phases.sh phase3
/home/smudoshi/Github/Aurora/dicom/download_tcia_phases.sh all
/home/smudoshi/Github/Aurora/dicom/download_tcia_phases.sh list
```

## Environment variables

The download and verification scripts support overrides:

- `MANIFEST_DIR`
  - default: `~/tcia_manifests`
- `DOWNLOAD_ROOT`
  - default: `~/TCIA-downloads`
- `NBIA_DEB_URL`
  - installer override for the `.deb` source URL
- `TMP_DEB`
  - installer override for the temporary package path

Example:

```bash
MANIFEST_DIR=/data/manifests DOWNLOAD_ROOT=/data/tcia /home/smudoshi/Github/Aurora/dicom/download_tcia_phases.sh phase1
```

## What was verified vs not verified

Verified locally:

- generated catalogue and markdown files were written successfully
- scripts exist in this directory
- scripts are executable
- help and non-network control paths for the scripts work

Not verified locally:

- actual NBIA Data Retriever installation
- actual `.tcia` manifest retrieval
- actual TCIA download execution

Reason:

- this machine did not have NBIA Data Retriever installed
- no `.tcia` manifest files were present
- network/install/download operations were not executed in this workflow

## Source assumptions

Disease labels and collection recommendations were based on TCIA collection metadata and collection pages current on 2026-03-22.

Examples of referenced TCIA sources:

- https://www.cancerimagingarchive.net/collection/cptac-pda/
- https://www.cancerimagingarchive.net/collection/psma-pet-ct-lesions/
- https://www.cancerimagingarchive.net/collection/nsclc-radiomics/
- https://www.cancerimagingarchive.net/collection/hcc-tace-seg/
- https://www.cancerimagingarchive.net/collection/tcga-kirc/
- https://www.cancerimagingarchive.net/collection/tcga-luad/
- https://www.cancerimagingarchive.net/collection/tcga-brca/
- https://www.cancerimagingarchive.net/collection/cptac-ccrcc/
- https://wiki.nci.nih.gov/spaces/NBIA/pages/392070977/Downloading%2BNBIA%2BImages

## Practical advice for the next agent

- Treat `tcia_dicom_study_catalogue.csv` as the canonical local inventory.
- Do not assume all TCIA collections are currently openly downloadable; some brain datasets have controlled-access restrictions.
- Before extending the download plan, re-check TCIA collection pages because access rules and collection sizes can change.
- If adding more collections, keep the phase model so downloads remain manageable.
- If automating manifest retrieval, preserve the exact filename convention already expected by `download_tcia_phases.sh`.
