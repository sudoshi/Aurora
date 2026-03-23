# TCIA Download Plan

Prepared: 2026-03-22

## Recommended acquisition order

### Phase 1: Highest value, manageable footprint

1. CPTAC-PDA
   - Why first: best pancreatic radiogenomics set; links to clinical, genomic, and proteomic data
   - Size: 155.24 GB
   - Cancer type: pancreatic ductal adenocarcinoma
   - Access: open DICOM download via NBIA Data Retriever
   - URL: https://www.cancerimagingarchive.net/collection/cptac-pda/

2. PSMA-PET-CT-Lesions
   - Why second: best current segmentation-heavy oncology set; 597 studies with DICOM SEG
   - Size: 117.08 GB
   - Cancer type: prostate cancer
   - Access: open DICOM download via NBIA Data Retriever
   - URL: https://www.cancerimagingarchive.net/collection/psma-pet-ct-lesions/

3. NSCLC-Radiomics
   - Why third: strong benchmark dataset for outcomes and segmentation work
   - Size: 35.78 GB
   - Cancer type: lung cancer
   - Access: open DICOM download via NBIA Data Retriever
   - URL: https://www.cancerimagingarchive.net/collection/nsclc-radiomics/

4. HCC-TACE-Seg
   - Why fourth: best treatment-response and liver segmentation dataset in the shortlist
   - Size: 28.57 GB
   - Cancer type: hepatocellular carcinoma
   - Access: open DICOM download via NBIA Data Retriever
   - URL: https://www.cancerimagingarchive.net/collection/hcc-tace-seg/

Phase 1 total: 336.67 GB

### Phase 2: Add radiogenomics breadth

5. TCGA-KIRC
   - Why fifth: strong TCGA radiogenomics dataset with kidney CT/MR/CR
   - Size: 91.56 GB
   - Cancer type: kidney renal clear cell carcinoma
   - Access: open DICOM download via NBIA Data Retriever
   - URL: https://www.cancerimagingarchive.net/collection/tcga-kirc/

6. TCGA-LUAD
   - Why sixth: compact lung adenocarcinoma TCGA cohort with clinical/genomics linkage
   - Size: 19.62 GB
   - Cancer type: lung adenocarcinoma
   - Access: open DICOM download via NBIA Data Retriever
   - URL: https://www.cancerimagingarchive.net/collection/tcga-luad/

Phase 1 + 2 total: 447.85 GB

### Phase 3: Only if you want larger multimodal cohorts

7. TCGA-BRCA
   - Why seventh: useful breast imaging plus TCGA linkage, but less aligned to your current pancreatic-heavy archive
   - Size: 88.13 GB
   - Cancer type: breast cancer
   - Access: open DICOM download via NBIA Data Retriever
   - URL: https://www.cancerimagingarchive.net/collection/tcga-brca/

8. CPTAC-CCRCC
   - Why eighth: very strong multimodal radiogenomics set, but large
   - Size: 280.22 GB
   - Cancer type: clear cell renal cell carcinoma
   - Access: open DICOM download via NBIA Data Retriever
   - URL: https://www.cancerimagingarchive.net/collection/cptac-ccrcc/

All 8 total: 816.20 GB

## If disk is limited

- Under 350 GB: stop after Phase 1
- Under 500 GB: add TCGA-KIRC and TCGA-LUAD
- Over 800 GB: take the full 8-collection plan

## Notes

- Sizes are from TCIA collection pages current as of 2026-03-22.
- "Open download" here means the TCIA page shows DICOM download via NBIA Data Retriever, not "Unavailable".
- Some brain collections, especially GBM-related sets, currently have radiology download restrictions and are not in this plan.
