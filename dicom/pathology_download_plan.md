# Imaging & Pathology Download Plan v2 — Pancreatic Focus

Prepared: 2026-03-28
Budget: 2 TB of new acquisitions
Strategy: build the deepest possible pancreatic cancer corpus, then fill remaining space
with matched pathology for existing radiology collections

## Existing radiology (816 GB — already downloaded from TCIA)

| Collection | Cancer | Size | Studies | Series |
|-----------|--------|------|---------|--------|
| CPTAC-PDA | Pancreatic | 155 GB | 2,012* | 4,864* |
| PSMA-PET-CT-Lesions | Prostate | 117 GB | — | — |
| NSCLC-Radiomics | Lung | 36 GB | — | — |
| HCC-TACE-Seg | Liver | 29 GB | — | — |
| TCGA-KIRC | Kidney | 92 GB | 439 | 2,654 |
| TCGA-LUAD | Lung | 20 GB | 152 | 624 |
| TCGA-BRCA | Breast | 88 GB | 164 | 1,877 |
| CPTAC-CCRCC | Kidney | 280 GB | 85 | 726 |

*orthanc_sync_v1 counts include CPTAC-PDA + other Phase 1 collections

---

## Phase 1: Pancreatic Cancer Corpus (~1,028 GB)

The anchor of this plan. Builds the most comprehensive open-access pancreatic
cancer dataset achievable — pathology, radiology, segmentation, genomics, and
MRI — suitable for presenting to a Pancreatic Cancer Consortium.

### 1a. Patient-matched pathology (trimodal linkage)

| Dataset | Items | Size | Access | Value |
|---------|-------|------|--------|-------|
| CPTAC-PDA pathology (SVS) | 168 slides | **88 GB** | TCIA Aspera | Same patients as existing 155 GB radiology + PDC proteomics |
| TCGA-PAAD diagnostic slides | 209 slides | **228 GB** | GDC `gdc-client` | FFPE H&E, linked to TCGA genomics (WXS, RNA-Seq, methylation) |
| TCGA-PAAD tissue slides | 257 slides | **57.5 GB** | GDC `gdc-client` | Frozen sections, complements diagnostics for same 185 cases |

Subtotal: 634 slides, **373.5 GB**

### 1b. Pancreatic CT corpora (segmentation + detection)

| Dataset | Items | Size | Access | Value |
|---------|-------|------|--------|-------|
| PanTS (NeurIPS 2025) | 9,901 training CTs | **~300 GB** | Hugging Face / GitHub | Largest pancreatic tumor CT dataset: 36K volumes, 993K annotations, head/body/tail + tumor segmentation from 145 centers |
| PANORAMA | 2,238 CTs | **194 GB** | Grand Challenge + Zenodo (CC BY-NC 4.0) | PDAC detection with labels, multi-center (Radboud, Karolinska, Haukeland) |
| MSD Task07_Pancreas | 420 CTs | **12 GB** | AWS / HF / medicaldecathlon.com | Benchmark pancreas + tumor segmentation, widely cited |
| PANCREAS-CT (NIH) | 82 patients | **9.3 GB** | TCIA / Kaggle | Normal pancreas segmentation baseline (healthy controls) |
| PANCREATIC-CT-CBCT-SEG | 40 patients | **~15 GB** | TCIA | Locally advanced PDAC, planning CT + CBCT, MSK annotated |

Subtotal: ~12,681 CTs, **~530.3 GB**

### 1c. Pancreatic MRI + genomics

| Dataset | Items | Size | Access | Value |
|---------|-------|------|--------|-------|
| PANTHER | 509 MR images | **3.4 GB** | Zenodo | First public pancreatic tumor MRI segmentation dataset (diagnostic + MR-Linac) |
| TCGA-PAAD open genomics | 185 cases | **~120 GB** | GDC (open access) | Transcriptome (1.3 GB), SNV/MAF (23.7 GB), CNV (88.8 GB), methylation (5.7 GB) |

Subtotal: **~123.4 GB**

### Phase 1 total: ~1,027 GB (~1.0 TB)

```
Pancreatic corpus breakdown:
  Pathology WSI:      634 slides     373.5 GB
  CT (seg + detect):  12,681 scans   530.3 GB
  MRI:                509 scans        3.4 GB
  Genomics:           185 cases      120.0 GB
  ─────────────────────────────────────────────
  Total:                           1,027.2 GB
```

Combined with existing CPTAC-PDA radiology (155 GB), the full pancreatic
corpus will be **~1.18 TB** spanning radiology, pathology, genomics,
proteomics (via PDC), CT segmentation, and MRI.

### Download commands

```bash
TARGET=/media/smudoshi/DATA/pancreatic-corpus

# --- 1a. Pathology ---

# CPTAC-PDA pathology — TCIA manifest download
# Get manifest from https://www.cancerimagingarchive.net/collection/cptac-pda/
# Filter: Image Type = "Pathology"
mkdir -p $TARGET/pathology/CPTAC-PDA

# TCGA-PAAD diagnostic + tissue slides — GDC
pip install gdc-client
# GDC portal → Projects → TCGA-PAAD → Files
# Filter: Data Type = "Slide Image"
# Separate manifests for Diagnostic Slide vs Tissue Slide
mkdir -p $TARGET/pathology/TCGA-PAAD-diagnostic
mkdir -p $TARGET/pathology/TCGA-PAAD-tissue
gdc-client download -m gdc_manifest_tcga_paad_diagnostic.txt -d $TARGET/pathology/TCGA-PAAD-diagnostic/
gdc-client download -m gdc_manifest_tcga_paad_tissue.txt -d $TARGET/pathology/TCGA-PAAD-tissue/

# --- 1b. CT corpora ---

# PanTS — Hugging Face
mkdir -p $TARGET/ct/PanTS
cd $TARGET/ct/PanTS && bash download_PanTS_data.sh
# Or: huggingface-cli download MrGiovanni/PanTS --local-dir $TARGET/ct/PanTS

# PANORAMA — Zenodo (4 parts)
mkdir -p $TARGET/ct/PANORAMA
# Download from:
#   https://zenodo.org/records/13715870  (49.3 GB)
#   https://zenodo.org/records/13742336  (49.3 GB)
#   https://zenodo.org/records/11034011  (49.3 GB)
#   https://zenodo.org/records/10999754  (46.3 GB)

# MSD Task07_Pancreas — AWS
mkdir -p $TARGET/ct/MSD-Pancreas
aws s3 cp s3://msd-data/Task07_Pancreas.tar $TARGET/ct/MSD-Pancreas/
tar -xf $TARGET/ct/MSD-Pancreas/Task07_Pancreas.tar -C $TARGET/ct/MSD-Pancreas/

# PANCREAS-CT (NIH) — TCIA
mkdir -p $TARGET/ct/PANCREAS-CT
# Download via NBIA Data Retriever or REST script

# PANCREATIC-CT-CBCT-SEG — TCIA
mkdir -p $TARGET/ct/PANCREATIC-CT-CBCT-SEG
# Download via NBIA Data Retriever

# --- 1c. MRI + Genomics ---

# PANTHER — Zenodo
mkdir -p $TARGET/mri/PANTHER
# Download from https://zenodo.org/records/15192302

# TCGA-PAAD open genomics — GDC
mkdir -p $TARGET/genomics/TCGA-PAAD
# GDC portal → TCGA-PAAD → Files
# Filter: Access = "open", Data Category in (Transcriptome Profiling, Simple Nucleotide Variation, Copy Number Variation, DNA Methylation)
gdc-client download -m gdc_manifest_tcga_paad_genomics_open.txt -d $TARGET/genomics/TCGA-PAAD/
```

---

## Phase 2: Matched pathology for existing radiology (~633 GB)

Diagnostic slides for non-pancreatic TCGA projects where you already have radiology.
Gives trimodal linkage for kidney cancers.

| Dataset | Diag. Slides | Size | Linkage |
|---------|-------------|------|---------|
| CPTAC-CCRCC pathology (SVS) | 222 | **190 GB** | Same patients as 280 GB CPTAC-CCRCC radiology + PDC proteomics |
| TCGA-KIRC diagnostic | 519 | **443 GB** | Matches 92 GB TCGA-KIRC radiology + TCGA genomics |

**Phase 2 total: 633 GB** — 741 slides covering kidney (clear cell RCC)
**Running total: ~1,660 GB**

### Download commands

```bash
# CPTAC-CCRCC pathology — TCIA
# Get manifest from https://www.cancerimagingarchive.net/collection/cptac-ccrcc/
# Filter: Image Type = "Pathology"

# TCGA-KIRC diagnostic — GDC
gdc-client download -m gdc_manifest_tcga_kirc_diagnostic.txt -d /media/smudoshi/DATA/pathology/TCGA-KIRC/
```

### Why kidney instead of lung?

- TCGA-KIRC diagnostic (443 GB) + CPTAC-CCRCC pathology (190 GB) = 633 GB
  for two kidney collections you already have radiology for
- TCGA-LUAD diagnostic alone = 387 GB for one lung collection
- Kidney gives better trimodal density per GB (radiology + pathology + genomics + proteomics)
- TCGA-LUAD is a strong Phase 4 candidate

---

## Phase 3: Brain/neuro gap (~280 GB)

| Dataset | Items | Size | Access | Value |
|---------|-------|------|--------|-------|
| OASIS-3 | 2,168 MRI + 1,608 PET sessions | **~280 GB** | XNAT, DUA required (~1 week) | Longitudinal Alzheimer's (ages 42-95), CDR ratings, neuropsych, 1,378 participants |

**Phase 3 total: ~280 GB**
**Running total: ~1,940 GB** (~60 GB buffer)

### Download commands

```bash
# OASIS-3
# 1. Register at https://sites.wustl.edu/oasisbrains/
# 2. Sign Data Use Agreement
# 3. Download via XNAT Central or NITRC-IR
# Recommend: MRI sessions first (~200 GB), then PET (~80 GB) if space allows
```

---

## Budget summary

| Phase | Focus | Size | Running |
|-------|-------|------|---------|
| 1 | **Pancreatic cancer corpus** (path + CT + MRI + genomics) | 1,027 GB | 1,027 GB |
| 2 | Kidney matched pathology (CPTAC-CCRCC + TCGA-KIRC) | 633 GB | 1,660 GB |
| 3 | Brain/neuro (OASIS-3) | 280 GB | **1,940 GB** |
| | *Buffer* | *~60 GB* | **~2.0 TB** |

---

## What this gives you

### By modality (new acquisitions only)

| Modality | Items | Size |
|----------|-------|------|
| Pathology WSI (SVS) | 1,375 slides | 862 GB |
| CT (segmentation + detection) | ~12,681 scans | 530 GB |
| MRI | 509 pancreatic + 2,168 brain | 283 GB |
| PET | 1,608 brain sessions | (included in OASIS-3) |
| Genomics (open-access) | 185 pancreatic cases | 120 GB |

### Pancreatic cancer coverage (the crown jewel)

| Data Type | Source | Size | Cases/Items |
|-----------|--------|------|-------------|
| Radiology (CT/MRI) | CPTAC-PDA (existing) | 155 GB | 1,133 series |
| Pathology — CPTAC (same patients) | CPTAC-PDA path | 88 GB | 168 slides |
| Pathology — TCGA diagnostic | TCGA-PAAD | 228 GB | 209 slides |
| Pathology — TCGA tissue | TCGA-PAAD | 57.5 GB | 257 slides |
| CT segmentation — PanTS | PanTS (145 centers) | 300 GB | 9,901 volumes |
| CT detection — PANORAMA | PANORAMA (multi-center) | 194 GB | 2,238 CTs |
| CT segmentation — MSD | MSD Task07 | 12 GB | 420 CTs |
| CT normal baseline | PANCREAS-CT (NIH) | 9.3 GB | 82 patients |
| CT treatment planning | PANCREATIC-CT-CBCT-SEG | 15 GB | 40 patients |
| MRI segmentation | PANTHER | 3.4 GB | 509 MRIs |
| Genomics (open) | TCGA-PAAD via GDC | 120 GB | 185 cases |
| Proteomics (via PDC) | CPTAC-PDA (free download) | matrices only | 140 tumors |
| **Pancreatic total** | | **~1,182 GB** | |

That is **~1.18 TB of pancreatic cancer data** spanning 6 modalities, from
146+ medical centers, covering radiology, pathology, genomics, proteomics,
CT segmentation, CT detection, and MRI segmentation.

### Full cancer type coverage after download

| Cancer | Radiology | Pathology | Genomics | CT Seg/Detect |
|--------|-----------|-----------|----------|---------------|
| **Pancreatic** | 155 GB | **374 GB, 634 slides** | **120 GB** | **530 GB, 12.7K CTs** |
| Kidney | 372 GB | **633 GB, 741 slides** | GDC | — |
| Lung | 56 GB | — (Phase 4) | GDC | — |
| Prostate | 117 GB | — (Phase 4) | — | — |
| Breast | 88 GB | — (Phase 4) | GDC | — |
| Liver | 29 GB | — (Phase 4) | GDC | — |
| Brain/Alzheimer's | — | — | — | OASIS-3 MRI+PET (280 GB) |

---

## Phase 4: Future expansion (if storage grows)

| Dataset | Size | Why | Priority |
|---------|------|-----|----------|
| TCGA-LUAD diagnostic slides | 387 GB | Matches existing LUAD radiology | High |
| PANDA prostate WSI | 383 GB | 10,616 Gleason-graded prostate biopsies | High |
| TCGA-BRCA diagnostic slides | 1,079 GB | Matches existing BRCA radiology | Medium |
| TCGA-LIHC diagnostic slides | 469 GB | Matches HCC-TACE-Seg radiology | Medium |
| CAMELYON16 | ~850 GB | Gold-standard breast metastasis benchmark | Medium |
| AbdomenCT-1K | ~200 GB | Multi-organ CT seg including pancreas | Low (overlaps PanTS) |
| ADNI | Multi-TB | Deep longitudinal neuro + genomics | Low (have OASIS-3) |
| GTEx DICOM-WSI | 8,500 GB | Multi-tissue normal histology | Low |
| NLST low-dose CT | 11.3 TB | Lung screening, 75K+ CT exams | Low |
| TCGA-PAAD BAMs (dbGaP) | 132.2 TB | Full sequencing reads | Controlled access |

## Notes

- All TCGA slides and open genomics are open access — no application needed
- CPTAC pathology available via TCIA (same infra as existing downloads)
- OASIS-3 requires a Data Use Agreement (~1 week turnaround)
- PanTS requires Hugging Face download; PANORAMA requires Grand Challenge registration
- SVS files viewable with OpenSlide, QuPath, or ASAP
- Consider importing SVS to OMERO or converting to DICOM-WSI for unified viewer
- PDC proteomics processed matrices are small (~100-500 MB); raw mass spec is 2-5 TB
- Storage: recommend a dedicated 2 TB partition or second drive
- Download order within Phase 1: start with CPTAC-PDA pathology (88 GB, fastest
  trimodal linkage), then TCGA-PAAD slides, then PanTS + PANORAMA in parallel
