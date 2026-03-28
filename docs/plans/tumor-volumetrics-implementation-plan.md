# Aurora Tumor Volumetrics: Capability Report & Implementation Plan

**Date:** 2026-03-28
**Author:** Claude Code / Dr. Sanjay Udoshi
**Status:** Ready for Implementation

---

## 1. Executive Summary

Aurora is uniquely positioned to demonstrate AI-powered tumor volumetrics over time — a capability that transforms radiology from subjective visual assessment into quantitative, longitudinal disease tracking. This document details what we have, what we can build, and a phased implementation plan to make it real.

**What we can demonstrate:**
- Automated tumor segmentation from CT scans using open-source AI models
- Volumetric measurement extraction from existing DICOM SEG and RTSTRUCT annotations
- Longitudinal tumor volume tracking across multiple timepoints per patient
- RECIST-like response classification (CR/PR/SD/PD) derived from volume changes
- Interactive visualization in OHIF viewer with segmentation overlays
- Clinical decision support: tumor growth kinetics, treatment response curves

**Current data assets:**

| Asset | Scale | Status |
|-------|-------|--------|
| Orthanc PACS | 2,036 studies, 484K instances, 215 GB on NVMe RAID0 | Live |
| NSCLC-Radiomics | 1,265 patients with RTSTRUCT tumor contours | Importing |
| HCC-TACE-Seg | 677 patients with DICOM SEG (liver + tumor masks) | Importing |
| PSMA-PET-CT-Lesions | 1,791 patients with lesion annotations | On disk |
| Golden Cohort | 20 oncology patients, 64 studies, 5 cancer types | Linked |
| Clinical Schema | `imaging_segmentations` and `imaging_measurements` tables | Exists, empty |

---

## 2. Current Infrastructure

### 2.1 DICOM Pipeline (Operational)

```
TCIA/NBIA Downloads (NVMe RAID0, 4TB)
    |
    v
Orthanc PACS (localhost:8042)
    |
    v
Nginx Reverse Proxy (DICOMweb at /orthanc/dicom-web/)
    |
    v
Aurora Backend (Laravel, imaging_studies table)
    |
    v
OHIF Viewer (embedded at /ohif/viewer)
```

- **Storage:** 2x2TB NVMe internal RAID0 array
- **PACS:** Orthanc with DICOMweb (WADO-RS, QIDO-RS, STOW-RS)
- **Viewer:** OHIF v3 with Cornerstone3D rendering engine
- **Database:** PostgreSQL 17 with dedicated imaging schema

### 2.2 Available TCIA Collections

| Collection | Patients | Studies | Modalities | Annotations | Cancer Type |
|------------|----------|---------|------------|-------------|-------------|
| NSCLC-Radiomics | 1,265 | ~1,265 | CT + RTSTRUCT | GTV (gross tumor volume), lungs, heart, cord | Non-small cell lung cancer |
| HCC-TACE-Seg | 677 | ~677 | CT + SEG | Liver parenchyma, tumor mass, portal vein, aorta | Hepatocellular carcinoma |
| PSMA-PET-CT-Lesions | 1,791 | ~1,791 | PET/CT | Lesion-level annotations | Prostate cancer |
| CPTAC-PDA | 1,137 | ~1,137 | CT | None (raw imaging only) | Pancreatic ductal adenocarcinoma |
| CPTAC-CCRCC | 727 | ~727 | CT + MR | None (raw imaging only) | Clear cell renal cell carcinoma |
| TCGA-BRCA | 1,877 | ~1,877 | MR + MG | None (raw imaging only) | Breast cancer |
| TCGA-KIRC | 2,654 | ~2,654 | CT + MR | None (raw imaging only) | Kidney renal clear cell carcinoma |
| TCGA-LUAD | 624 | ~624 | CT + PET | None (raw imaging only) | Lung adenocarcinoma |

### 2.3 Existing Database Schema

The `clinical` schema already has the tables needed for volumetrics:

**`imaging_segmentations`** — stores per-study segmentation results:
- `imaging_study_id` (FK to imaging_studies)
- `segmentation_uid` (DICOM SEG/RTSTRUCT UID)
- `algorithm` (e.g., "nnUNet-lung-tumor", "TotalSegmentator", "manual-RTSTRUCT")
- `label` (e.g., "GTV-1", "Liver", "Mass")
- `volume_mm3` (computed volume in cubic millimeters)
- `source_type` (e.g., "rtstruct", "dicom-seg", "ai-generated")

**`imaging_measurements`** — stores RECIST-style measurements:
- `imaging_study_id` (FK)
- `measurement_type` (e.g., "RECIST_longest_diameter", "volume", "SUVmax")
- `target_lesion` (boolean)
- `value_numeric` + `unit`
- `measured_by` (e.g., "TotalSegmentator v2", "manual")

### 2.4 Golden Cohort Patients

20 patients across 5 cancer types, each with 3-4 longitudinal imaging studies mapped to real TCIA DICOM data:

| Cancer Type | Patients | Studies | Avg Studies/Pt | Modalities |
|-------------|----------|---------|----------------|------------|
| BRCA (breast) | 5 | 18 | 3.6 | CT, MR |
| NSCLC (lung) | 5 | 15 | 3.0 | CT, MR, PET |
| PDAC (pancreatic) | 5 | 15 | 3.0 | CT, MR |
| RCC (renal) | 5 | 16 | 3.2 | CT, MR |
| **Total** | **20** | **64** | **3.2** | CT, MR, PET |

---

## 3. Annotation Data Deep Dive

### 3.1 NSCLC-Radiomics RTSTRUCT

Each patient has an RTSTRUCT file containing radiation therapy structure sets with the following ROIs:

| ROI | Description | Volumetrics Use |
|-----|-------------|-----------------|
| **GTV-1** | Gross Tumor Volume (primary lung tumor) | Primary metric for tumor volumetrics |
| Lung-Right | Right lung contour | Anatomical reference, lung involvement % |
| Lung-Left | Left lung contour | Anatomical reference |
| Heart | Heart contour | Proximity assessment |
| Esophagus | Esophageal contour | Proximity assessment |
| Spinal-Cord | Spinal cord contour | Proximity assessment |

**Volumetrics extraction:** GTV-1 contours on each CT slice can be converted to 3D volumes using the slice thickness and contour area. This gives us absolute tumor volume in cm3. Combined with the study date, we get a volumetric growth curve.

**Scale:** 1,265 patients with GTV measurements. While most have a single timepoint, these provide a population-level distribution of tumor volumes at diagnosis that can be used for:
- Benchmarking AI-generated volumes against manual contours
- Training/validating AI segmentation models
- Statistical analysis of tumor size vs. outcomes

### 3.2 HCC-TACE-Seg DICOM SEG

Each patient has a multi-segment DICOM SEG file with voxel-level masks:

| Segment | Label | Clinical Significance |
|---------|-------|----------------------|
| 1 | **Liver** | Total liver volume — needed for tumor burden ratio |
| 2 | **Mass** | Tumor mass — primary volumetric target |
| 3 | **Portal vein** | Vascular involvement — surgical planning |
| 4 | **Abdominal aorta** | Anatomical reference |

**Volumetrics extraction:** DICOM SEG masks are binary voxel arrays. Volume = voxel count x voxel dimensions. This is the most precise format for volumetrics — no contour interpolation needed.

**Clinical value:** Tumor-to-liver volume ratio is a key metric in HCC staging and treatment response assessment (mRECIST criteria). Having both liver and tumor segmentation in one file is ideal.

### 3.3 PSMA-PET-CT-Lesions

Whole-body PET/CT with lesion-level annotations. The PET component provides SUVmax (standardized uptake value) measurements, which are the standard for metabolic tumor response assessment (PERCIST criteria).

**Scale:** 1,791 patients, many with longitudinal follow-up (up to 7 timepoints over 5 years). This is the strongest collection for demonstrating volumetric + metabolic response tracking over time.

---

## 4. Open-Source AI Models for Tumor Volumetrics

### 4.1 Recommended Model Stack

| Model | Purpose | Input | Output | License |
|-------|---------|-------|--------|---------|
| **TotalSegmentator v2** | Anatomical segmentation (117 structures) | CT (NIfTI) | Organ masks including lungs, liver, kidneys | Apache 2.0 |
| **nnU-Net** | Tumor-specific segmentation | CT (NIfTI) | Tumor masks | Apache 2.0 |
| **MONAI Bundle: Lung Tumor** | Pre-trained lung tumor segmentation | CT (NIfTI/DICOM) | Tumor probability map | Apache 2.0 |
| **MONAI Bundle: Liver Tumor** | Pre-trained liver tumor segmentation | CT (NIfTI/DICOM) | Tumor + liver mask | Apache 2.0 |
| **MedGemma** (Google) | Radiology report generation, visual QA | DICOM/PNG | Text descriptions, findings | Gemma license |
| **BiomedCLIP** | Image-text matching, zero-shot classification | DICOM/PNG | Similarity scores | MIT |
| **3D Slicer (SlicerRT)** | RTSTRUCT/SEG volume computation | DICOM SEG/RTSTRUCT | Volume in mm3/cm3 | BSD |

### 4.2 Model Selection by Cancer Type

| Cancer Type | Primary Model | Segmentation Target | Measurement |
|-------------|--------------|---------------------|-------------|
| NSCLC | MONAI Lung Tumor + TotalSegmentator | Lung nodule/mass | Volume (cm3), longest diameter |
| HCC | MONAI Liver Tumor + TotalSegmentator | Hepatic mass | Volume (cm3), tumor-to-liver ratio |
| PDAC | nnU-Net (fine-tuned on CPTAC-PDA) | Pancreatic mass | Volume (cm3), CA involvement |
| RCC | TotalSegmentator + nnU-Net | Renal mass | Volume (cm3), growth rate |
| BRCA | MONAI Breast MRI | Enhancing lesion | Volume (cm3), kinetic curves |
| Prostate | TotalSegmentator + SUV extraction | PSMA-avid lesions | SUVmax, total lesion volume |

### 4.3 MedGemma Integration

MedGemma (Google's medical multimodal model) is best suited for:
- **Automated radiology narrative generation** from segmentation results ("The primary lung mass measures 3.2 cm3, representing a 15% reduction from prior study consistent with partial response")
- **Visual question answering** on DICOM images ("Is there evidence of tumor progression?")
- **Report summarization** across longitudinal studies

MedGemma is NOT a segmentation model — it complements the segmentation pipeline by providing clinical interpretation of volumetric data.

---

## 5. Implementation Plan

### Phase 1: Volume Extraction from Existing Annotations (1-2 days)

**Goal:** Extract tumor volumes from RTSTRUCT and DICOM SEG files already in Orthanc, populate `imaging_segmentations` table.

**Tasks:**
1. **Build Python extraction pipeline** (`ai/volumetrics/extract_volumes.py`)
   - Read RTSTRUCT from Orthanc via DICOMweb → parse ROI contours → compute 3D volume
   - Read DICOM SEG from Orthanc → extract voxel masks → compute volume from voxel dimensions
   - Store results in `clinical.imaging_segmentations` table
   - Libraries: `pydicom`, `rt-utils`, `highdicom`, `numpy`

2. **Run extraction on imported data**
   - NSCLC-Radiomics: Extract GTV-1 volumes for ~1,265 patients
   - HCC-TACE-Seg: Extract liver + tumor volumes for ~677 patients
   - Compute tumor-to-organ volume ratios where applicable

3. **Populate `imaging_measurements` with RECIST-equivalent metrics**
   - Convert volumes to equivalent sphere diameters for RECIST comparison
   - Flag target vs. non-target lesions

**Deliverable:** Database populated with tumor volumes for ~1,942 patients.

### Phase 2: Longitudinal Tracking & Response Classification (2-3 days)

**Goal:** Build the tumor tracking engine that computes response over time.

**Tasks:**
1. **Create `clinical.tumor_tracking` table (new migration)**
   ```sql
   CREATE TABLE clinical.tumor_tracking (
     id BIGSERIAL PRIMARY KEY,
     patient_id BIGINT REFERENCES clinical.patients(id),
     lesion_label VARCHAR,           -- e.g., "GTV-1", "Liver Mass"
     baseline_study_id BIGINT,       -- first measurement
     current_study_id BIGINT,        -- latest measurement
     baseline_volume_mm3 NUMERIC,
     current_volume_mm3 NUMERIC,
     volume_change_pct NUMERIC,      -- % change from baseline
     response_category VARCHAR,      -- CR/PR/SD/PD per RECIST-like criteria
     doubling_time_days NUMERIC,     -- tumor doubling time
     created_at TIMESTAMP DEFAULT NOW(),
     updated_at TIMESTAMP DEFAULT NOW()
   );
   ```

2. **Implement response classification logic**
   - Volume-based RECIST analog:
     - CR (Complete Response): Volume = 0 or below detection threshold
     - PR (Partial Response): >= 65% volume decrease (equivalent to 30% diameter decrease)
     - PD (Progressive Disease): >= 73% volume increase (equivalent to 20% diameter increase)
     - SD (Stable Disease): Between PR and PD thresholds
   - Compute tumor doubling time using exponential growth model

3. **Build API endpoints**
   - `GET /api/imaging/studies/{id}/volumetrics` — segmentation volumes for a study
   - `GET /api/patients/{id}/tumor-tracking` — longitudinal volume data
   - `GET /api/patients/{id}/response-assessment` — RECIST classification

**Deliverable:** REST API serving tumor volumetrics and response assessments.

### Phase 3: AI Segmentation Pipeline (3-5 days)

**Goal:** Enable on-demand AI tumor segmentation for studies without pre-existing annotations.

**Tasks:**
1. **Deploy TotalSegmentator + MONAI as FastAPI microservices**
   - Add Docker services to `docker-compose.yml`
   - GPU support via NVIDIA Container Toolkit (if GPU available) or CPU fallback
   - Input: Study UID → fetch DICOM from Orthanc → convert to NIfTI → run model → convert back to DICOM SEG → store in Orthanc

2. **Build segmentation request queue**
   - User clicks "Analyze" on a study in Aurora → job queued
   - Worker processes job → stores DICOM SEG in Orthanc + volume in DB
   - WebSocket notification when complete

3. **Model-specific pipelines:**
   - **Lung CT:** TotalSegmentator (lungs) + MONAI Lung Tumor (nodules)
   - **Liver CT:** TotalSegmentator (liver) + MONAI Liver Tumor (masses)
   - **Abdomen CT:** TotalSegmentator (kidneys, pancreas) + nnU-Net (tumors)

**Deliverable:** On-demand AI segmentation available from the Aurora UI.

### Phase 4: Frontend Visualization (3-5 days)

**Goal:** Interactive tumor volumetrics dashboard in Aurora.

**Tasks:**
1. **OHIF Segmentation Overlay**
   - OHIF v3 natively supports DICOM SEG display via Cornerstone3D
   - Configure OHIF to load SEG alongside CT from the same study
   - Color-coded overlays: tumor (red), liver (green), vessels (blue)

2. **Tumor Volume Timeline Component** (React)
   - Line chart showing volume over time per lesion (Recharts or Chart.js)
   - RECIST response bands (CR/PR/SD/PD) as colored zones
   - Click a timepoint → jumps to OHIF viewer for that study
   - Doubling time annotation

3. **Patient Volumetrics Dashboard**
   - Integrate into existing Patient Profile → Imaging Tab
   - Summary cards: baseline volume, current volume, % change, response category
   - Waterfall plot for multi-lesion patients (standard oncology visualization)
   - Export to PDF for tumor board presentation

4. **Study-Level Volumetrics Panel**
   - On ImagingStudyPage: show segmentation results alongside viewer
   - "Run AI Analysis" button → triggers Phase 3 pipeline
   - Side-by-side comparison with prior study

**Deliverable:** Full volumetrics UI in Aurora.

### Phase 5: MedGemma Integration (2-3 days)

**Goal:** AI-generated clinical narratives from volumetric data.

**Tasks:**
1. **Deploy MedGemma via Ollama or HuggingFace**
   - Local inference for data privacy
   - Input: volumetric data + representative DICOM slices
   - Output: structured radiology narrative

2. **Automated Volume Report Generation**
   - Template: "Compared to [prior date], the [lesion] measures [X cm3], representing a [Y%] [increase/decrease], consistent with [response category]."
   - Include imaging context from MedGemma visual analysis

3. **Copilot Integration**
   - Connect to existing Aurora Copilot feature
   - "Summarize this patient's tumor trajectory" → MedGemma generates narrative from volumetric data

**Deliverable:** AI-generated volumetric reports accessible from patient profile.

---

## 6. Demo Scenarios

### Demo 1: "Liver Tumor Response to TACE" (HCC-TACE-Seg)

**Narrative:** Hepatocellular carcinoma patient undergoing transarterial chemoembolization.

1. Open patient profile → Imaging tab shows 2 CT studies (pre and post-TACE)
2. Pre-TACE study: OHIF displays CT with liver + tumor segmentation overlay
3. Volumetrics panel: Liver volume 1,450 cm3, Tumor volume 85 cm3, Tumor burden 5.8%
4. Post-TACE study: Tumor volume 42 cm3 (51% decrease)
5. Response assessment: **Partial Response** per mRECIST
6. Timeline chart shows volume decrease with annotated treatment date

### Demo 2: "Lung Cancer Treatment Monitoring" (NSCLC-Radiomics)

**Narrative:** NSCLC patient with GTV tracked across treatment.

1. Baseline CT: GTV-1 = 45 cm3, annotated by radiation oncologist (RTSTRUCT)
2. AI verification: TotalSegmentator confirms lung anatomy, MONAI validates tumor boundary
3. Volumetrics dashboard: tumor volume, equivalent RECIST diameter, growth kinetics
4. MedGemma generates: "The primary right upper lobe mass measures 45 cm3 (equivalent longest diameter 4.4 cm). No mediastinal lymphadenopathy."

### Demo 3: "Golden Cohort Longitudinal Tracking" (Multi-cancer)

**Narrative:** 20-patient oncology cohort with 3-4 timepoints each.

1. Dashboard view: all 20 patients with response waterfall plot
2. Drill into Sandra Kowalski (BRCA-05): 4 studies showing brain metastasis progression
3. Drill into Samuel Rivera (PDAC-04): 3 studies showing durable partial response on pembrolizumab
4. Tumor board view: comparative volumetrics across cancer types
5. Export summary PDF with volume curves for each patient

### Demo 4: "On-Demand AI Analysis" (CPTAC-PDA)

**Narrative:** Pancreatic cancer patient without existing annotations.

1. Open unannotated CPTAC-PDA study
2. Click "Run AI Analysis" → TotalSegmentator + nnU-Net process the CT
3. ~2 minutes later: segmentation overlay appears in OHIF
4. Pancreatic mass volume extracted, stored in database
5. Compare with subsequent study → automated response assessment

---

## 7. Technical Architecture

```
                    +-------------------+
                    |   Aurora Frontend  |
                    |  (React + OHIF)   |
                    +--------+----------+
                             |
                    +--------v----------+
                    |   Aurora Backend   |
                    |    (Laravel API)   |
                    +--------+----------+
                             |
              +--------------+--------------+
              |              |              |
     +--------v---+  +------v------+  +----v--------+
     |  Orthanc   |  |  PostgreSQL |  |  AI Service |
     |  (PACS)    |  |  (PG 17)   |  |  (FastAPI)  |
     |  DICOMweb  |  |  clinical.* |  |  MONAI/nnU  |
     +------------+  +-------------+  +-------------+
          |                                  |
     +----v----------------------------------v----+
     |        NVMe RAID0 (4TB, 215GB used)        |
     |   DICOM files + Orthanc DB + Model weights |
     +--------------------------------------------+
```

### AI Service Components

```
ai/
  app/                          # Existing FastAPI app
  volumetrics/
    extract_volumes.py          # Phase 1: RTSTRUCT/SEG → volumes
    tumor_tracking.py           # Phase 2: Longitudinal analysis
    response_classifier.py      # Phase 2: RECIST classification
    segmentation_service.py     # Phase 3: AI model inference
    report_generator.py         # Phase 5: MedGemma narratives
  models/
    totalsegmentator/           # Anatomical segmentation
    monai_lung_tumor/           # Lung tumor model
    monai_liver_tumor/          # Liver tumor model
    medgemma/                   # Report generation
```

---

## 8. Data Scale & Performance

| Metric | Current | After Phase 1 | After Phase 3 |
|--------|---------|---------------|---------------|
| Studies with segmentation | 1 | ~1,942 | ~2,500+ |
| Patients with volumetrics | 0 | ~1,600 | ~2,000+ |
| Longitudinal tracking pairs | 0 | ~48 | ~200+ |
| Golden cohort with full demo | 0 | 20 | 20 |
| AI models deployed | 0 | 0 | 3-4 |
| On-demand analysis capacity | N/A | N/A | ~10 studies/hr (CPU) |

---

## 9. Dependencies & Prerequisites

| Dependency | Status | Action |
|------------|--------|--------|
| Orthanc with DICOMweb | Operational | None |
| OHIF v3 with SEG support | Deployed | Verify SEG rendering config |
| Python 3.10+ with pydicom | Available | Install rt-utils, highdicom |
| PostgreSQL 17 | Operational | Run Phase 2 migration |
| NVIDIA GPU (optional) | Unknown | Check `nvidia-smi`; CPU fallback available |
| TotalSegmentator | Not installed | `pip install totalsegmentator` |
| MONAI | Not installed | `pip install monai[all]` |
| MedGemma | Not available locally | Download via Ollama or HuggingFace |

---

## 10. Risk Mitigation

| Risk | Mitigation |
|------|------------|
| RTSTRUCT import fails (Orthanc rejects) | Fall back to file-based extraction; store volumes without PACS integration |
| AI segmentation quality varies | Always show confidence scores; human review toggle in UI |
| GPU not available | All models have CPU fallback; batch processing overnight if needed |
| Volume computation discrepancy vs. manual | Validate Phase 1 against published NSCLC-Radiomics dataset statistics |
| OHIF SEG rendering issues | Cornerstone3D supports DICOM SEG natively; test with HCC-TACE-Seg first |
| MedGemma hallucination | Template-based generation with volumetric data as ground truth; MedGemma adds context, not measurements |

---

## 11. Success Criteria

1. **Phase 1:** Tumor volumes extracted and stored for >= 1,500 patients
2. **Phase 2:** Longitudinal tracking operational for Golden Cohort (20 patients, 64 studies)
3. **Phase 3:** On-demand AI segmentation completes within 5 minutes per study
4. **Phase 4:** Tumor volume timeline visible in patient profile for all tracked patients
5. **Phase 5:** MedGemma generates clinically plausible reports for 90%+ of studies

---

## 12. Competitive Differentiation

Aurora's tumor volumetrics capability would differentiate it from existing platforms:

| Capability | Aurora | Typical PACS | Research Tools (3D Slicer) |
|------------|--------|-------------|---------------------------|
| Integrated clinical + imaging data | Yes | No | No |
| Automated longitudinal tracking | Yes | No | Manual only |
| AI-powered segmentation | Yes (on-demand) | Vendor-specific add-on | Plugin-based |
| Multi-cancer type support | 5+ cancer types | Limited | Unlimited but manual |
| RECIST-equivalent response | Automated | Manual | Semi-automated |
| Natural language reports | MedGemma | Radiologist-written | N/A |
| Real-time collaboration | Yes (existing) | Limited | Single-user |
| Patient fingerprint integration | Yes (existing) | No | No |

This positions Aurora as a **clinical intelligence platform** — not just a viewer, but an active participant in treatment monitoring and tumor board decision-making.
