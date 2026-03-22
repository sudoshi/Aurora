# Synthetic Clinical Demo Patients Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Seed 12 clinically defensible synthetic patients into Aurora's PostgreSQL database to demonstrate the platform to physicians.

**Architecture:** One orchestrator seeder (`ClinicalDemoSeeder`) calls 12 per-patient seeder classes, each creating a `ClinicalPatient` and all related records (conditions, medications, procedures, measurements, observations, visits, notes, imaging, genomics, eras). A shared `DemoSeederHelper` trait provides common methods for creating records with `source_type='synthetic'` provenance. Idempotent — clears all `DEMO-*` patients before re-seeding.

**Tech Stack:** Laravel 11 / PHP 8.4, Eloquent ORM, PostgreSQL 16 with `clinical` schema (resolved via `search_path`).

**Spec:** `docs/superpowers/specs/2026-03-21-synthetic-clinical-demo-patients-design.md`

---

## File Structure

```
backend/database/seeders/
  ClinicalDemoSeeder.php                          # Orchestrator — cleans + calls all 12
  DemoPatients/
    DemoSeederHelper.php                          # Trait: shared helper methods
    RareDiseasePatient1_hATTR.php                 # A1: hATTR Amyloidosis, 52-60yo AA Male
    RareDiseasePatient2_TSC.php                   # A2: TSC, 0-14yo Hispanic Female (pediatric)
    RareDiseasePatient3_CAPS.php                  # A3: CAPS, 26-36yo South Asian Female
    PreSurgicalPatient1_CABG.php                  # B1: Redo CABG+AVR, 68yo White Male
    PreSurgicalPatient2_HIPEC.php                 # B2: CRS-HIPEC, 54yo Hispanic Female
    PreSurgicalPatient3_VHL_HHT.php               # B3: VHL+HHT Posterior Fossa, 41yo Male
    OncologyPatient1_LungEGFR.php                 # C1: EGFR Lung, 62yo White Male
    OncologyPatient2_CRC_BRAF.php                 # C2: BRAF CRC, 54yo Black Female
    OncologyPatient3_TNBC_BRCA1.php               # C3: BRCA1 TNBC, 41yo South Asian Female
    UndiagnosedPatient1_ECD.php                   # D1: ECD, 54yo AA Male
    UndiagnosedPatient2_VEXAS.php                 # D2: VEXAS, 67yo White Male
    UndiagnosedPatient3_APS1.php                  # D3: APS-1/APECED, 8-11yo Hispanic Female (pediatric)
```

Each per-patient file is a class with a single `public function seed(): void` method and uses the `DemoSeederHelper` trait. Each file is self-contained (~250-400 lines).

**Models used** (all in `App\Models\Clinical\`, all `$guarded = []`, all tables resolve via `search_path` to `clinical.*`):
- `ClinicalPatient` (`patients`)
- `PatientIdentifier` (`patient_identifiers`)
- `Condition` (`conditions`)
- `Medication` (`medications`)
- `Procedure` (`procedures`)
- `Measurement` (`measurements`)
- `Observation` (`observations`)
- `Visit` (`visits`)
- `ClinicalNote` (`clinical_notes`)
- `ImagingStudy` (`imaging_studies`)
- `ImagingSeries` (`imaging_series`)
- `ImagingMeasurement` (`imaging_measurements`)
- `GenomicVariant` (`genomic_variants`)
- `ConditionEra` (`condition_eras`)
- `DrugEra` (`drug_eras`)

---

## Task 1: Create DemoSeederHelper Trait

**Files:**
- Create: `backend/database/seeders/DemoPatients/DemoSeederHelper.php`

This trait provides reusable methods for all 12 patient seeders to avoid repeating provenance columns and boilerplate.

- [ ] **Step 1: Create the helper trait**

```php
<?php

namespace Database\Seeders\DemoPatients;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\Condition;
use App\Models\Clinical\ConditionEra;
use App\Models\Clinical\ClinicalNote;
use App\Models\Clinical\DrugEra;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingSeries;
use App\Models\Clinical\ImagingStudy;
use App\Models\Clinical\Measurement;
use App\Models\Clinical\Medication;
use App\Models\Clinical\Observation;
use App\Models\Clinical\PatientIdentifier;
use App\Models\Clinical\Procedure;
use App\Models\Clinical\Visit;
use Illuminate\Support\Str;

trait DemoSeederHelper
{
    private function provenance(): array
    {
        return [
            'source_type' => 'synthetic',
            'source_id' => 'demo_seeder_v1',
        ];
    }

    private function createPatient(array $attrs): ClinicalPatient
    {
        return ClinicalPatient::create(array_merge($attrs, $this->provenance()));
    }

    private function addIdentifier(ClinicalPatient $patient, string $type, string $value, ?string $sourceSystem = null): PatientIdentifier
    {
        return PatientIdentifier::create(array_merge([
            'patient_id' => $patient->id,
            'identifier_type' => $type,
            'identifier_value' => $value,
            'source_system' => $sourceSystem,
        ], $this->provenance()));
    }

    private function addCondition(ClinicalPatient $patient, array $attrs): Condition
    {
        return Condition::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    private function addMedication(ClinicalPatient $patient, array $attrs): Medication
    {
        return Medication::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    private function addProcedure(ClinicalPatient $patient, array $attrs): Procedure
    {
        return Procedure::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    private function addMeasurement(ClinicalPatient $patient, array $attrs): Measurement
    {
        return Measurement::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    private function addObservation(ClinicalPatient $patient, array $attrs): Observation
    {
        return Observation::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    private function addVisit(ClinicalPatient $patient, array $attrs): Visit
    {
        return Visit::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    private function addNote(ClinicalPatient $patient, array $attrs): ClinicalNote
    {
        return ClinicalNote::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    private function addImagingStudy(ClinicalPatient $patient, array $attrs): ImagingStudy
    {
        $study = ImagingStudy::create(array_merge([
            'patient_id' => $patient->id,
            'study_uid' => '2.25.' . Str::random(32),
        ], $attrs, $this->provenance()));

        // Create a default series for each study
        ImagingSeries::create(array_merge([
            'imaging_study_id' => $study->id,
            'series_uid' => '2.25.' . Str::random(32),
            'series_number' => 1,
            'modality' => $study->modality,
            'description' => $study->description ?? $study->modality . ' Series 1',
            'num_instances' => $study->num_instances ?? 1,
        ], $this->provenance()));

        return $study;
    }

    private function addImagingMeasurement(ImagingStudy $study, array $attrs): ImagingMeasurement
    {
        return ImagingMeasurement::create(array_merge([
            'imaging_study_id' => $study->id,
        ], $attrs, $this->provenance()));
    }

    private function addGenomicVariant(ClinicalPatient $patient, array $attrs): GenomicVariant
    {
        return GenomicVariant::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    private function addConditionEra(ClinicalPatient $patient, array $attrs): ConditionEra
    {
        return ConditionEra::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    private function addDrugEra(ClinicalPatient $patient, array $attrs): DrugEra
    {
        return DrugEra::create(array_merge(['patient_id' => $patient->id], $attrs, $this->provenance()));
    }

    /**
     * Add a batch of lab measurements at a single timepoint.
     * $labs is an array of [name, code, value, unit, refLow, refHigh, abnormalFlag].
     */
    private function addLabPanel(ClinicalPatient $patient, string $measuredAt, array $labs): void
    {
        foreach ($labs as $lab) {
            $this->addMeasurement($patient, [
                'measurement_name' => $lab[0],
                'concept_code' => $lab[1] ?? null,
                'vocabulary' => 'LOINC',
                'value_numeric' => $lab[2],
                'unit' => $lab[3],
                'reference_range_low' => $lab[4] ?? null,
                'reference_range_high' => $lab[5] ?? null,
                'abnormal_flag' => $lab[6] ?? null,
                'measured_at' => $measuredAt,
            ]);
        }
    }
}
```

- [ ] **Step 2: Verify file is syntactically valid**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/DemoSeederHelper.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/DemoPatients/DemoSeederHelper.php
git commit -m "feat: add DemoSeederHelper trait for synthetic patient seeding"
```

---

## Task 2: Create ClinicalDemoSeeder Orchestrator

**Files:**
- Create: `backend/database/seeders/ClinicalDemoSeeder.php`

- [ ] **Step 1: Create the orchestrator seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Clinical\ClinicalPatient;
use Database\Seeders\DemoPatients\RareDiseasePatient1_hATTR;
use Database\Seeders\DemoPatients\RareDiseasePatient2_TSC;
use Database\Seeders\DemoPatients\RareDiseasePatient3_CAPS;
use Database\Seeders\DemoPatients\PreSurgicalPatient1_CABG;
use Database\Seeders\DemoPatients\PreSurgicalPatient2_HIPEC;
use Database\Seeders\DemoPatients\PreSurgicalPatient3_VHL_HHT;
use Database\Seeders\DemoPatients\OncologyPatient1_LungEGFR;
use Database\Seeders\DemoPatients\OncologyPatient2_CRC_BRAF;
use Database\Seeders\DemoPatients\OncologyPatient3_TNBC_BRCA1;
use Database\Seeders\DemoPatients\UndiagnosedPatient1_ECD;
use Database\Seeders\DemoPatients\UndiagnosedPatient2_VEXAS;
use Database\Seeders\DemoPatients\UndiagnosedPatient3_APS1;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClinicalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Cleaning existing demo patients...');
        $this->cleanDemoPatients();

        $seeders = [
            RareDiseasePatient1_hATTR::class,
            RareDiseasePatient2_TSC::class,
            RareDiseasePatient3_CAPS::class,
            PreSurgicalPatient1_CABG::class,
            PreSurgicalPatient2_HIPEC::class,
            PreSurgicalPatient3_VHL_HHT::class,
            OncologyPatient1_LungEGFR::class,
            OncologyPatient2_CRC_BRAF::class,
            OncologyPatient3_TNBC_BRCA1::class,
            UndiagnosedPatient1_ECD::class,
            UndiagnosedPatient2_VEXAS::class,
            UndiagnosedPatient3_APS1::class,
        ];

        foreach ($seeders as $seederClass) {
            $seeder = new $seederClass();
            $name = class_basename($seederClass);
            $this->command->info("  Seeding {$name}...");
            $seeder->seed();
        }

        $count = ClinicalPatient::where('mrn', 'like', 'DEMO-%')->count();
        $this->command->info("Done! Seeded {$count} demo patients.");
    }

    private function cleanDemoPatients(): void
    {
        // Cascade deletes handle all child records
        $deleted = ClinicalPatient::where('mrn', 'like', 'DEMO-%')->delete();
        if ($deleted > 0) {
            $this->command->info("  Deleted {$deleted} existing demo patients.");
        }
    }
}
```

- [ ] **Step 2: Verify syntax**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/ClinicalDemoSeeder.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/ClinicalDemoSeeder.php
git commit -m "feat: add ClinicalDemoSeeder orchestrator with idempotent cleanup"
```

---

## Task 3: Seed Patient A1 — hATTR Amyloidosis

**Files:**
- Create: `backend/database/seeders/DemoPatients/RareDiseasePatient1_hATTR.php`

**Data source:** Spec section A1 + research agent output for Case 1 (hATTR).

- [ ] **Step 1: Create the patient seeder**

The file must implement a `seed()` method that creates:
- 1 patient (MRN `DEMO-RD-001`, Marcus Washington, 52yo AA Male, DOB 1966-03-14)
- 2 patient identifiers (insurance ID, facility MRN)
- ~8-10 conditions (hATTR E85.1, HFpEF I43, bilateral CTS G56.0, autonomic neuropathy G90.09, CKD 3a N18.31, VT I47.20, gastroparesis K31.84, malnutrition E44.0)
- ~6-8 medications (tafamidis 61mg, midodrine 5-10mg TID, gabapentin 300mg TID, diflunisal 250mg BID, furosemide 40-80mg, spironolactone 25mg)
- ~5 procedures (bilateral carpal tunnel release, cardiac cath, endomyocardial biopsy, fat pad aspirate, ICD implantation)
- ~120 measurements across 8 years (NT-proBNP, Troponin T, eGFR, albumin, free light chains, TTR/prealbumin, BNP — trending values per spec)
- ~15-20 observations (NYHA class, weight trending, orthostatic BP, Karnofsky)
- ~25-30 visits (PCP, ortho, cardiology x multiple, neurology, GI, genetics, hematology, nuclear med, multidisciplinary clinic)
- ~12-15 clinical notes (H&P notes, consult notes, procedure notes for each specialty visit)
- ~8-10 imaging studies with series (echo x4, cardiac MRI, Tc-99m PYP, EMG/NCS x2, nerve US)
- ~2-3 genomic variants (TTR Val142Ile pathogenic, pharmacogenomic variants)
- ~3-4 condition eras (HFpEF era, neuropathy era, CKD era)
- ~4-5 drug eras (tafamidis era, midodrine era, gabapentin era, diflunisal era)

All lab values must use the longitudinal trending data from the spec (NT-proBNP 1850→3200→4500→3100→2400 etc). All dates anchored relative to diagnosis year.

**Important**: This file will be ~300-400 lines. The implementing agent must populate every Eloquent model with clinically accurate data per the design spec. Use `$this->addLabPanel()` for lab batches. Use `$this->addImagingStudy()` which auto-creates a series record.

- [ ] **Step 2: Verify syntax**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/RareDiseasePatient1_hATTR.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Run the seeder to verify data loads**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | head -20`
Expected: Output showing `Seeding RareDiseasePatient1_hATTR...` and `Done! Seeded 1 demo patients.`

- [ ] **Step 4: Verify data in database**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan tinker --execute="echo App\Models\Clinical\ClinicalPatient::where('mrn','DEMO-RD-001')->first()?->toJson(JSON_PRETTY_PRINT);"`
Expected: JSON output showing Marcus Washington with correct demographics

- [ ] **Step 5: Commit**

```bash
git add backend/database/seeders/DemoPatients/RareDiseasePatient1_hATTR.php
git commit -m "feat: seed demo patient A1 — hATTR amyloidosis (8yr diagnostic odyssey)"
```

---

## Task 4: Seed Patient A2 — Tuberous Sclerosis Complex (Pediatric)

**Files:**
- Create: `backend/database/seeders/DemoPatients/RareDiseasePatient2_TSC.php`

**Data source:** Spec section A2 + research agent output for Case 2 (TSC).

- [ ] **Step 1: Create the patient seeder**

Same structure as Task 3. MRN `DEMO-RD-002`. 14-year pediatric timeline from prenatal to age 14. Create all records per spec (~200 measurements over 14 years, 15-18 imaging studies including serial brain MRI/echo/renal MRI/EEG, TSC2 genomic variant, everolimus trough levels, etc).

- [ ] **Step 2: Verify syntax**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/RareDiseasePatient2_TSC.php`

- [ ] **Step 3: Run seeder and verify**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 2 demo patients.`

- [ ] **Step 4: Commit**

```bash
git add backend/database/seeders/DemoPatients/RareDiseasePatient2_TSC.php
git commit -m "feat: seed demo patient A2 — TSC pediatric (14yr multi-organ surveillance)"
```

---

## Task 5: Seed Patient A3 — Catastrophic APS

**Files:**
- Create: `backend/database/seeders/DemoPatients/RareDiseasePatient3_CAPS.php`

**Data source:** Spec section A3 + research agent output for Case 3 (CAPS).

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-RD-003`. 10-year arc including ICU CAPS event with dense daily labs. Create all records per spec (~250 measurements including ICU-density data, 12-15 imaging studies, pharmacogenomic variants for CYP2C9/VKORC1, 4 pathology specimens as clinical notes, etc).

- [ ] **Step 2: Verify syntax**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/RareDiseasePatient3_CAPS.php`

- [ ] **Step 3: Run seeder and verify**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 3 demo patients.`

- [ ] **Step 4: Commit**

```bash
git add backend/database/seeders/DemoPatients/RareDiseasePatient3_CAPS.php
git commit -m "feat: seed demo patient A3 — catastrophic APS (ICU data density)"
```

---

## Task 6: Seed Patient B1 — Redo CABG+AVR

**Files:**
- Create: `backend/database/seeders/DemoPatients/PreSurgicalPatient1_CABG.php`

**Data source:** Spec section B1 + research agent output for Case 1 (Cardiac Surgery).

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-PS-001`. 6-month pre-op workup. ~80 measurements, 6-8 imaging studies, risk scores as observations (STS 8.2%, EuroSCORE II 9.6%, MELD 17, Lee RCRI 4, CHA₂DS₂-VASc 5, ASA IV), 12-14 medications, 10-12 conditions with ICD-10 codes.

- [ ] **Step 2: Verify syntax and run seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/PreSurgicalPatient1_CABG.php && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 4 demo patients.`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/DemoPatients/PreSurgicalPatient1_CABG.php
git commit -m "feat: seed demo patient B1 — redo CABG+AVR (5 converging risk scores)"
```

---

## Task 7: Seed Patient B2 — CRS-HIPEC

**Files:**
- Create: `backend/database/seeders/DemoPatients/PreSurgicalPatient2_HIPEC.php`

**Data source:** Spec section B2 + research agent output for Case 2 (CRS-HIPEC).

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-PS-002`. 3-week pre-op snapshot. ~40 measurements, PCI score as observation, competing DAPT urgency documented in notes, tumor markers (CEA, CA-125, CA 19-9), VerifyNow P2Y12 platelet function.

- [ ] **Step 2: Verify syntax and run seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/PreSurgicalPatient2_HIPEC.php && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 5 demo patients.`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/DemoPatients/PreSurgicalPatient2_HIPEC.php
git commit -m "feat: seed demo patient B2 — CRS-HIPEC (competing urgencies)"
```

---

## Task 8: Seed Patient B3 — VHL+HHT Posterior Fossa

**Files:**
- Create: `backend/database/seeders/DemoPatients/PreSurgicalPatient3_VHL_HHT.php`

**Data source:** Spec section B3 + research agent output for Case 3 (Neurosurgery VHL+HHT).

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-PS-003`. 2-month workup. Dual genetic syndromes: VHL c.499C>T + ENG c.1088G>A genomic variants, ~60 measurements (ABG, erythrocytosis labs, pheochromocytoma screening), 10-12 imaging studies (brain MRI, MRA, CT chest HHT protocol, bubble echo, pulmonary angiography, abdominal MRI VHL protocol), bevacizumab hold timeline.

- [ ] **Step 2: Verify syntax and run seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/PreSurgicalPatient3_VHL_HHT.php && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 6 demo patients.`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/DemoPatients/PreSurgicalPatient3_VHL_HHT.php
git commit -m "feat: seed demo patient B3 — VHL+HHT posterior fossa (dual genetic syndromes)"
```

---

## Task 9: Seed Patient C1 — EGFR Lung Adenocarcinoma

**Files:**
- Create: `backend/database/seeders/DemoPatients/OncologyPatient1_LungEGFR.php`

**Data source:** Spec section C1 + research agent output for Oncology Case 1.

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-ON-001`. 5-year timeline with 4 treatment lines. ~150 measurements (CEA trending, CBC, LFTs), 14-16 imaging studies with RECIST imaging_measurements (16 CT timepoints + 4 brain MRI), 5-6 genomic variants (EGFR L858R, TP53 R248W, then acquired C797S and MET amp from ctDNA), 4-5 drug eras (osimertinib, amivantamab+lazertinib, carboplatin/pemetrexed, ADC trial).

**Critical**: RECIST target lesion measurements go into `imaging_measurements` table via `$this->addImagingMeasurement()`, NOT `measurements`. The `measurement_type` = 'RECIST', `target_lesion` = true.

- [ ] **Step 2: Verify syntax and run seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/OncologyPatient1_LungEGFR.php && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 7 demo patients.`

- [ ] **Step 3: Verify RECIST data**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan tinker --execute="echo App\Models\Clinical\ImagingMeasurement::whereHas('imagingStudy', fn(\$q) => \$q->whereHas('patient', fn(\$p) => \$p->where('mrn','DEMO-ON-001')))->count() . ' RECIST measurements';"`
Expected: Shows count of RECIST measurements > 0

- [ ] **Step 4: Commit**

```bash
git add backend/database/seeders/DemoPatients/OncologyPatient1_LungEGFR.php
git commit -m "feat: seed demo patient C1 — EGFR lung adenocarcinoma (4 treatment lines, RECIST)"
```

---

## Task 10: Seed Patient C2 — BRAF V600E CRC

**Files:**
- Create: `backend/database/seeders/DemoPatients/OncologyPatient2_CRC_BRAF.php`

**Data source:** Spec section C2 + research agent output for Oncology Case 2.

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-ON-002`. 4-year timeline. CEA tracking (8.4→2.1→34.7→...→145.8), adjuvant CAPOX + 3 metastatic lines, RECIST imaging_measurements for liver target lesions, acquired KRAS G12D resistance on ctDNA, declining albumin/LDH trajectory, transition to BSC.

- [ ] **Step 2: Verify syntax and run seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/OncologyPatient2_CRC_BRAF.php && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 8 demo patients.`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/DemoPatients/OncologyPatient2_CRC_BRAF.php
git commit -m "feat: seed demo patient C2 — BRAF V600E MSS CRC (worst molecular subgroup)"
```

---

## Task 11: Seed Patient C3 — BRCA1 Triple-Negative Breast Cancer

**Files:**
- Create: `backend/database/seeders/DemoPatients/OncologyPatient3_TNBC_BRCA1.php`

**Data source:** Spec section C3 + research agent output for Oncology Case 3.

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-ON-003`. 5-year timeline. KEYNOTE-522 neoadjuvant (pembrolizumab + chemo), non-pCR (RCB-II), adjuvant pembro, germline BRCA1 variant, olaparib 17mo deep response, BRCA1 reversion mutation resistance, sacituzumab govitecan. Breast MRI RECIST + CT RECIST measurements, CA 15-3 trending.

- [ ] **Step 2: Verify syntax and run seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/OncologyPatient3_TNBC_BRCA1.php && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 9 demo patients.`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/DemoPatients/OncologyPatient3_TNBC_BRCA1.php
git commit -m "feat: seed demo patient C3 — BRCA1 TNBC (germline-somatic interplay, PARP resistance)"
```

---

## Task 12: Seed Patient D1 — Erdheim-Chester Disease

**Files:**
- Create: `backend/database/seeders/DemoPatients/UndiagnosedPatient1_ECD.php`

**Data source:** Spec section D1 + research agent output for Undiagnosed Case 1.

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-UD-001`. 2.5-year diagnostic odyssey. 6 specialist visits with wrong working diagnoses documented in clinical notes, imaging showing "hairy kidney" + "coated aorta" + bone sclerosis, BRAF V600E somatic variant, the bone biopsy pathology note should document CD68+/CD1a-/S100- but with the initial "nonspecific" reading.

- [ ] **Step 2: Verify syntax and run seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/UndiagnosedPatient1_ECD.php && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 10 demo patients.`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/DemoPatients/UndiagnosedPatient1_ECD.php
git commit -m "feat: seed demo patient D1 — Erdheim-Chester disease (cross-specialty pattern recognition)"
```

---

## Task 13: Seed Patient D2 — VEXAS Syndrome

**Files:**
- Create: `backend/database/seeders/DemoPatients/UndiagnosedPatient2_VEXAS.php`

**Data source:** Spec section D2 + research agent output for Undiagnosed Case 2.

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-UD-002`. 3-year diagnostic odyssey. 4 wrong diagnoses (PMR, Sweet syndrome, MDS, relapsing polychondritis), UBA1 p.Met41Thr somatic variant (VAF 62%), bone marrow pathology note documenting vacuoles, macrocytic anemia trending, skin biopsy pathology.

- [ ] **Step 2: Verify syntax and run seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/UndiagnosedPatient2_VEXAS.php && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 11 demo patients.`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/DemoPatients/UndiagnosedPatient2_VEXAS.php
git commit -m "feat: seed demo patient D2 — VEXAS syndrome (multi-diagnosis pattern flagging)"
```

---

## Task 14: Seed Patient D3 — APS-1/APECED (Pediatric)

**Files:**
- Create: `backend/database/seeders/DemoPatients/UndiagnosedPatient3_APS1.php`

**Data source:** Spec section D3 + research agent output for Undiagnosed Case 3.

- [ ] **Step 1: Create the patient seeder**

MRN `DEMO-UD-003`. 3-year pediatric diagnostic odyssey (age 8-11). 7 subspecialist visits, AIRE compound heterozygous germline variants (c.769C>T + c.967_979del13), the initial calcium 8.2 "dismissed as artifact" must be a measurement with `abnormal_flag = 'L'`, enamel hypoplasia documented in dental note, autoimmune antibody panels (anti-IFN-omega, anti-IL-17F, parathyroid Ab, 21-hydroxylase Ab, ASMA).

- [ ] **Step 2: Verify syntax and run seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php -l database/seeders/DemoPatients/UndiagnosedPatient3_APS1.php && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 12 demo patients.`

- [ ] **Step 3: Commit**

```bash
git add backend/database/seeders/DemoPatients/UndiagnosedPatient3_APS1.php
git commit -m "feat: seed demo patient D3 — APS-1/APECED pediatric (fragmented care pattern)"
```

---

## Task 15: Final Verification and Integration

**Files:**
- Modify: `backend/database/seeders/DatabaseSeeder.php` (add ClinicalDemoSeeder to the call list, commented out by default so it's opt-in)

- [ ] **Step 1: Add ClinicalDemoSeeder reference to DatabaseSeeder**

Add a comment block to `DatabaseSeeder.php` showing how to run the demo seeder:

```php
// To seed demo clinical patients:
// php artisan db:seed --class=ClinicalDemoSeeder
```

Do NOT add it to the `$this->call()` array — it should be run explicitly, not on every `db:seed`.

- [ ] **Step 2: Run full seeder from clean state**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1`
Expected: All 12 patients seeded successfully.

- [ ] **Step 3: Verify record counts**

Run:
```bash
cd /home/smudoshi/Github/Aurora/backend && php artisan tinker --execute="
\$patients = App\Models\Clinical\ClinicalPatient::where('mrn', 'like', 'DEMO-%')->get();
echo 'Patients: ' . \$patients->count() . PHP_EOL;
echo 'Conditions: ' . App\Models\Clinical\Condition::whereIn('patient_id', \$patients->pluck('id'))->count() . PHP_EOL;
echo 'Medications: ' . App\Models\Clinical\Medication::whereIn('patient_id', \$patients->pluck('id'))->count() . PHP_EOL;
echo 'Measurements: ' . App\Models\Clinical\Measurement::whereIn('patient_id', \$patients->pluck('id'))->count() . PHP_EOL;
echo 'Visits: ' . App\Models\Clinical\Visit::whereIn('patient_id', \$patients->pluck('id'))->count() . PHP_EOL;
echo 'Notes: ' . App\Models\Clinical\ClinicalNote::whereIn('patient_id', \$patients->pluck('id'))->count() . PHP_EOL;
echo 'Imaging: ' . App\Models\Clinical\ImagingStudy::whereIn('patient_id', \$patients->pluck('id'))->count() . PHP_EOL;
echo 'Genomics: ' . App\Models\Clinical\GenomicVariant::whereIn('patient_id', \$patients->pluck('id'))->count() . PHP_EOL;
"
```
Expected: Patients: 12, all other counts > 0 and approximately matching spec volume targets.

- [ ] **Step 4: Run seeder twice to verify idempotency**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan db:seed --class=ClinicalDemoSeeder --force 2>&1 | tail -5`
Expected: `Done! Seeded 12 demo patients.` (same count, not 24)

- [ ] **Step 5: Commit**

```bash
git add backend/database/seeders/DatabaseSeeder.php
git commit -m "feat: add ClinicalDemoSeeder reference to DatabaseSeeder"
```

- [ ] **Step 6: Deploy frontend build and verify patient list loads**

Run:
```bash
cd /home/smudoshi/Github/Aurora/frontend && npm run build && cp -r dist/* ../backend/public/build/
```
Then verify patients appear at aurora.acumenus.net.

---

## Implementation Notes for Agents

### Each patient seeder file follows this exact pattern:

```php
<?php

namespace Database\Seeders\DemoPatients;

class ExamplePatient
{
    use DemoSeederHelper;

    public function seed(): void
    {
        // 1. Create patient
        $patient = $this->createPatient([
            'mrn' => 'DEMO-XX-00N',
            'first_name' => 'First',
            'last_name' => 'Last',
            'date_of_birth' => '1966-03-14',
            'sex' => 'Male',
            'race' => 'Black or African American',
            'ethnicity' => 'Not Hispanic or Latino',
        ]);

        // 2. Add identifiers
        $this->addIdentifier($patient, 'MRN', 'HOSP-123456', 'City General Hospital');
        $this->addIdentifier($patient, 'Insurance', 'INS-987654');

        // 3. Add conditions (all with domain and ICD-10)
        $this->addCondition($patient, [
            'concept_name' => 'Hereditary transthyretin amyloidosis',
            'concept_code' => 'E85.1',
            'vocabulary' => 'ICD10CM',
            'domain' => 'rare_disease',
            'status' => 'active',
            'onset_date' => '2021-06-15',
            'severity' => 'severe',
        ]);

        // 4. Add medications (with real doses)
        $this->addMedication($patient, [
            'drug_name' => 'Tafamidis meglumine',
            'concept_code' => '2377455',
            'vocabulary' => 'RxNorm',
            'route' => 'oral',
            'dose_value' => 61,
            'dose_unit' => 'mg',
            'frequency' => 'once daily',
            'start_date' => '2021-06-20',
            'status' => 'active',
            'prescriber' => 'Dr. Sarah Chen',
        ]);

        // 5. Add visits (with department, provider, type)
        $visit = $this->addVisit($patient, [
            'visit_type' => 'outpatient',
            'facility' => 'University Medical Center',
            'admission_date' => '2018-05-10',
            'department' => 'Cardiology',
            'attending_provider' => 'Dr. James Rodriguez',
        ]);

        // 6. Add notes linked to visits
        $this->addNote($patient, [
            'visit_id' => $visit->id,
            'note_type' => 'Consultation',
            'title' => 'Cardiology Initial Consultation',
            'content' => 'HPI: 52-year-old male presents with...',
            'author' => 'Dr. James Rodriguez',
            'authored_at' => '2018-05-10 14:30:00',
        ]);

        // 7. Add lab panels (batch helper)
        $this->addLabPanel($patient, '2018-05-10 09:00:00', [
            // [name, LOINC code, value, unit, refLow, refHigh, abnormalFlag]
            ['NT-proBNP', '33762-6', 1850, 'pg/mL', null, 125, 'H'],
            ['Troponin T', '6598-7', 0.04, 'ng/mL', null, 0.01, 'H'],
            ['eGFR', '48642-3', 72, 'mL/min/1.73m2', 90, null, 'L'],
        ]);

        // 8. Add imaging with auto-series
        $echo = $this->addImagingStudy($patient, [
            'modality' => 'US',
            'study_date' => '2018-05-10',
            'description' => 'Transthoracic Echocardiogram',
            'body_part' => 'Heart',
            'num_series' => 1,
            'num_instances' => 45,
        ]);

        // 9. Add genomic variants
        $this->addGenomicVariant($patient, [
            'gene' => 'TTR',
            'variant' => 'p.Val142Ile',
            'variant_type' => 'SNV',
            'chromosome' => '18',
            'position' => 31592986,
            'ref_allele' => 'G',
            'alt_allele' => 'A',
            'zygosity' => 'heterozygous',
            'allele_frequency' => 0.50,
            'clinical_significance' => 'pathogenic',
            'actionability' => 'FDA-approved therapy',
        ]);

        // 10. Add observations (risk scores, physical findings)
        $this->addObservation($patient, [
            'observation_name' => 'NYHA Functional Class',
            'value_text' => 'Class III',
            'value_numeric' => 3,
            'observed_at' => '2021-06-15',
            'category' => 'functional_status',
        ]);

        // 11. Add condition eras
        $this->addConditionEra($patient, [
            'concept_name' => 'Heart failure with preserved ejection fraction',
            'era_start' => '2019-01-15',
            'era_end' => null,
            'occurrence_count' => 8,
        ]);

        // 12. Add drug eras
        $this->addDrugEra($patient, [
            'drug_name' => 'Tafamidis meglumine',
            'era_start' => '2021-06-20',
            'era_end' => null,
            'gap_days' => 0,
        ]);
    }
}
```

### Critical Implementation Rules:
1. **Never hardcode patient IDs** — use `$patient->id` from the created patient
2. **Link notes to visits** — use `$visit->id` when a note belongs to a specific encounter
3. **RECIST goes to imaging_measurements** — use `$this->addImagingMeasurement($study, [...])` for oncology target lesion data
4. **Lab values must trend realistically** — use the exact values from the spec/research, not random numbers
5. **Dates must be chronologically consistent** — events referenced later must have later dates
6. **All conditions need domain** — `oncology`, `surgical`, `rare_disease`, or `complex_medical`
7. **All conditions need ICD-10** — use `vocabulary = 'ICD10CM'` and real codes
8. **Genomic variants use HGVS** — `variant` field uses protein notation (p.Val142Ile), `variant_type` is SNV/indel/fusion/CNV
