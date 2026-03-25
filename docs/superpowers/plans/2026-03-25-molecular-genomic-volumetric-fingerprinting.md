# Molecular-Genomic-Volumetric Fingerprinting Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a patient similarity engine using dimensional fingerprints (genomic, volumetric, clinical) with configurable fusion weights, outcome trajectory scoring, clinician assessment overlay, and a 20-patient golden cohort — surfaced as a "Similar Patients" tab on the patient profile page.

**Architecture:** Three specialized encoders (Python/FastAPI) produce 256-dim vectors per patient dimension. Laravel stores fingerprints in pgvector, runs similarity queries with query-time weight fusion, and serves results to a React frontend. Outcome trajectories combine computed sub-scores with clinician annotations. A golden cohort of 20 synthetic patients exercises all dimensions.

**Tech Stack:** Laravel 10 / PHP 8.1+ (backend), React 19 / TypeScript / TanStack Query (frontend), Python FastAPI (AI encoders), PostgreSQL 16 + pgvector (storage), Ollama (LLM for explanations), Tailwind CSS (styling)

**Spec:** `docs/superpowers/specs/2026-03-25-molecular-genomic-volumetric-fingerprinting-design.md`

---

## File Map

### Backend — New Files
- `backend/database/migrations/2026_03_25_200001_create_fingerprint_tables.php` — 4 new tables
- `backend/database/migrations/2026_03_25_200002_create_fingerprint_permissions.php` — RBAC permissions
- `backend/app/Models/Clinical/PatientFingerprint.php` — Fingerprint model
- `backend/app/Models/Clinical/OutcomeTrajectory.php` — Outcome model
- `backend/app/Models/Clinical/SimilaritySearch.php` — Audit log model
- `backend/app/Models/Clinical/FusionWeightConfig.php` — Weight config model
- `backend/app/Services/FingerprintService.php` — Encoding orchestration + similarity search
- `backend/app/Services/OutcomeService.php` — Outcome computation + clinician assessment
- `backend/app/Http/Controllers/FingerprintController.php` — All fingerprint endpoints
- `backend/database/seeders/FusionWeightConfigSeeder.php` — Default weight presets
- `backend/database/seeders/GoldenCohortSeeder.php` — 20 synthetic patients

### Backend — Modified Files
- `backend/routes/api.php` — Add fingerprint route group with permission middleware
- `backend/database/seeders/DatabaseSeeder.php` — Register new seeders
- `backend/app/Models/Clinical/ClinicalPatient.php` — Add fingerprint + outcomeTrajectory relationships

### Spec Deviations (Documented)
- **Synthetic generation endpoints** (`POST /api/ai/fingerprint/synthetic/generate`, `GET /api/ai/fingerprint/synthetic/templates`) — deferred. V1 uses static JSON templates + PHP seeder instead.
- **`dimension_mask boolean[3]`** replaced with three separate boolean columns (`genomic_available`, `volumetric_available`, `clinical_available`) for simpler querying.
- **Weight learning endpoint** (`POST /api/ai/fingerprint/weights/learn`) — deferred to post-V1 (requires 50+ annotated patients).

### AI Service — New Files
- `ai/app/routers/fingerprint.py` — FastAPI router for encoding/outcome/explain
- `ai/app/services/fingerprint_encoder.py` — Three dimension encoders
- `ai/app/services/outcome_computer.py` — Trajectory sub-score computation
- `ai/app/services/fingerprint_explainer.py` — Natural language similarity explanation
- `ai/app/models/fingerprint.py` — Pydantic request/response models
- `ai/tests/test_fingerprint_encoder.py` — Encoder unit tests
- `ai/tests/test_outcome_computer.py` — Outcome computation tests

### AI Service — Modified Files
- `ai/app/main.py` — Register fingerprint router

### Frontend — New Files
- `frontend/src/features/fingerprint/types/index.ts` — TypeScript types
- `frontend/src/features/fingerprint/api/fingerprintApi.ts` — API client
- `frontend/src/features/fingerprint/hooks/useFingerprint.ts` — TanStack Query hooks
- `frontend/src/features/fingerprint/components/SimilarPatientsTab.tsx` — Main tab container
- `frontend/src/features/fingerprint/components/FingerprintBanner.tsx` — Status banner
- `frontend/src/features/fingerprint/components/WeightControls.tsx` — Weight sliders + presets
- `frontend/src/features/fingerprint/components/SimilarPatientCard.tsx` — Result card
- `frontend/src/features/fingerprint/components/DimensionBar.tsx` — Per-dimension similarity bar
- `frontend/src/features/fingerprint/components/OutcomeBadge.tsx` — Color-coded outcome badge
- `frontend/src/features/fingerprint/components/OutcomeSidebar.tsx` — Right sidebar aggregations
- `frontend/src/features/fingerprint/components/OutcomeAssessmentModal.tsx` — Clinician assessment modal
- `frontend/src/features/fingerprint/components/DecisionTagChips.tsx` — Toggleable tag chips

### Frontend — Modified Files
- `frontend/src/features/patient-profile/` — Add Similar Patients tab to patient profile

### Data Files — New
- `backend/database/data/golden-cohort/` — JSON template files for 20 patients

---

## Task 1: Database Migrations

**Files:**
- Create: `backend/database/migrations/2026_03_25_200001_create_fingerprint_tables.php`

- [ ] **Step 1: Create migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure pgvector extension exists
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        // 1. Patient fingerprints
        Schema::create('clinical.patient_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->unique();
            // pgvector columns added via raw SQL below
            $table->boolean('genomic_available')->default(false);
            $table->boolean('volumetric_available')->default(false);
            $table->boolean('clinical_available')->default(false);
            $table->decimal('genomic_confidence', 5, 4)->nullable();
            $table->decimal('volumetric_confidence', 5, 4)->nullable();
            $table->decimal('clinical_confidence', 5, 4)->nullable();
            $table->string('encoder_version', 32)->default('v1.0');
            $table->timestamp('genomic_encoded_at')->nullable();
            $table->timestamp('volumetric_encoded_at')->nullable();
            $table->timestamp('clinical_encoded_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->cascadeOnDelete();
        });

        // Add pgvector columns (not supported by Blueprint)
        DB::statement('ALTER TABLE clinical.patient_fingerprints ADD COLUMN genomic_vector vector(256)');
        DB::statement('ALTER TABLE clinical.patient_fingerprints ADD COLUMN volumetric_vector vector(256)');
        DB::statement('ALTER TABLE clinical.patient_fingerprints ADD COLUMN clinical_vector vector(256)');

        // 2. Outcome trajectories
        Schema::create('clinical.outcome_trajectories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->unique();
            $table->decimal('tumor_response_score', 5, 4)->nullable();
            $table->decimal('treatment_tolerance_score', 5, 4)->nullable();
            $table->decimal('lab_trajectory_score', 5, 4)->nullable();
            $table->decimal('disease_stability_score', 5, 4)->nullable();
            $table->decimal('care_intensity_score', 5, 4)->nullable();
            $table->decimal('composite_score', 5, 4)->nullable();
            $table->string('clinician_rating', 20)->nullable(); // excellent|good|mixed|poor|failure
            $table->text('clinician_factors')->nullable();
            $table->jsonb('decision_tags')->nullable();
            $table->text('hindsight_note')->nullable();
            $table->unsignedBigInteger('assessed_by')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->cascadeOnDelete();
            $table->foreign('assessed_by')->references('id')->on('app.users')->nullOnDelete();
        });

        // 3. Similarity search audit log
        Schema::create('clinical.similarity_searches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('query_patient_id');
            $table->unsignedBigInteger('searched_by');
            $table->jsonb('weights_used');
            $table->boolean('weights_customized')->default(false);
            $table->string('context', 20)->default('point_of_care'); // point_of_care|tumor_board|research
            $table->jsonb('result_patient_ids');
            $table->jsonb('result_scores');
            $table->integer('result_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('query_patient_id')->references('id')->on('clinical.patients')->cascadeOnDelete();
            $table->foreign('searched_by')->references('id')->on('app.users')->cascadeOnDelete();
        });

        // 4. Fusion weight configurations
        Schema::create('clinical.fusion_weight_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('config_type', 20); // preset|learned|custom
            $table->decimal('genomic_weight', 5, 4);
            $table->decimal('volumetric_weight', 5, 4);
            $table->decimal('clinical_weight', 5, 4);
            $table->jsonb('outcome_weights')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('trained_on_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.similarity_searches');
        Schema::dropIfExists('clinical.outcome_trajectories');
        Schema::dropIfExists('clinical.patient_fingerprints');
        Schema::dropIfExists('clinical.fusion_weight_configs');
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan migrate`
Expected: 4 tables created in clinical schema.

- [ ] **Step 3: Verify tables exist**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan tinker --execute="echo \Illuminate\Support\Facades\DB::connection('clinical')->select(\"SELECT table_name FROM information_schema.tables WHERE table_schema = 'clinical' AND table_name LIKE '%fingerprint%' OR table_name LIKE '%outcome%' OR table_name LIKE '%similarity%' OR table_name LIKE '%fusion%'\") ? 'OK' : 'FAIL';"`
Expected: Tables found.

- [ ] **Step 4: Create RBAC permissions migration**

Create `backend/database/migrations/2026_03_25_200002_create_fingerprint_permissions.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'fingerprint.search',
            'fingerprint.view',
            'fingerprint.encode',
            'fingerprint.assess',
            'fingerprint.admin',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'sanctum']);
        }

        // Grant to admin role
        $admin = Role::findByName('admin', 'sanctum');
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // Grant search/view/assess to physician and specialist roles
        foreach (['physician', 'specialist'] as $roleName) {
            $role = Role::findByName($roleName, 'sanctum');
            if ($role) {
                $role->givePermissionTo([
                    'fingerprint.search',
                    'fingerprint.view',
                    'fingerprint.encode',
                    'fingerprint.assess',
                ]);
            }
        }

        // Grant search/view to nurse and other clinical roles
        foreach (['nurse', 'coordinator'] as $roleName) {
            $role = Role::findByName($roleName, 'sanctum');
            if ($role) {
                $role->givePermissionTo(['fingerprint.search', 'fingerprint.view']);
            }
        }
    }

    public function down(): void
    {
        $permissions = ['fingerprint.search', 'fingerprint.view', 'fingerprint.encode', 'fingerprint.assess', 'fingerprint.admin'];
        foreach ($permissions as $name) {
            Permission::where('name', $name)->delete();
        }
    }
};
```

- [ ] **Step 5: Run both migrations**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan migrate`
Expected: Tables created and permissions seeded.

- [ ] **Step 6: Commit**

```bash
git add backend/database/migrations/2026_03_25_200001_create_fingerprint_tables.php \
        backend/database/migrations/2026_03_25_200002_create_fingerprint_permissions.php
git commit -m "feat: add fingerprint tables and RBAC permissions"
```

---

## Task 2: Backend Models

**Files:**
- Create: `backend/app/Models/Clinical/PatientFingerprint.php`
- Create: `backend/app/Models/Clinical/OutcomeTrajectory.php`
- Create: `backend/app/Models/Clinical/SimilaritySearch.php`
- Create: `backend/app/Models/Clinical/FusionWeightConfig.php`

- [ ] **Step 1: Create PatientFingerprint model**

```php
<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFingerprint extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'patient_fingerprints';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'genomic_available' => 'boolean',
            'volumetric_available' => 'boolean',
            'clinical_available' => 'boolean',
            'genomic_confidence' => 'decimal:4',
            'volumetric_confidence' => 'decimal:4',
            'clinical_confidence' => 'decimal:4',
            'genomic_encoded_at' => 'datetime',
            'volumetric_encoded_at' => 'datetime',
            'clinical_encoded_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function getDimensionMaskAttribute(): array
    {
        return [
            $this->genomic_available,
            $this->volumetric_available,
            $this->clinical_available,
        ];
    }

    public function getAvailableDimensionCountAttribute(): int
    {
        return (int) $this->genomic_available
             + (int) $this->volumetric_available
             + (int) $this->clinical_available;
    }
}
```

- [ ] **Step 2: Create OutcomeTrajectory model**

```php
<?php

namespace App\Models\Clinical;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutcomeTrajectory extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'outcome_trajectories';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tumor_response_score' => 'decimal:4',
            'treatment_tolerance_score' => 'decimal:4',
            'lab_trajectory_score' => 'decimal:4',
            'disease_stability_score' => 'decimal:4',
            'care_intensity_score' => 'decimal:4',
            'composite_score' => 'decimal:4',
            'decision_tags' => 'array',
            'assessed_at' => 'datetime',
            'computed_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function getSubScoresAttribute(): array
    {
        return [
            'tumor_response' => $this->tumor_response_score,
            'treatment_tolerance' => $this->treatment_tolerance_score,
            'lab_trajectory' => $this->lab_trajectory_score,
            'disease_stability' => $this->disease_stability_score,
            'care_intensity' => $this->care_intensity_score,
        ];
    }
}
```

- [ ] **Step 3: Create SimilaritySearch model**

```php
<?php

namespace App\Models\Clinical;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimilaritySearch extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'similarity_searches';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'weights_used' => 'array',
            'weights_customized' => 'boolean',
            'result_patient_ids' => 'array',
            'result_scores' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function queryPatient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'query_patient_id');
    }

    public function searcher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'searched_by');
    }
}
```

- [ ] **Step 4: Create FusionWeightConfig model**

```php
<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;

class FusionWeightConfig extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'fusion_weight_configs';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'genomic_weight' => 'decimal:4',
            'volumetric_weight' => 'decimal:4',
            'clinical_weight' => 'decimal:4',
            'outcome_weights' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePresets($query)
    {
        return $query->where('config_type', 'preset');
    }

    public function getDimensionWeightsAttribute(): array
    {
        return [
            'genomic' => (float) $this->genomic_weight,
            'volumetric' => (float) $this->volumetric_weight,
            'clinical' => (float) $this->clinical_weight,
        ];
    }
}
```

- [ ] **Step 5: Add fingerprint relationship to ClinicalPatient**

Modify: `backend/app/Models/Clinical/ClinicalPatient.php`

Add these relationship methods:

```php
public function fingerprint(): HasOne
{
    return $this->hasOne(PatientFingerprint::class, 'patient_id');
}

public function outcomeTrajectory(): HasOne
{
    return $this->hasOne(OutcomeTrajectory::class, 'patient_id');
}
```

- [ ] **Step 6: Commit**

```bash
git add backend/app/Models/Clinical/PatientFingerprint.php \
        backend/app/Models/Clinical/OutcomeTrajectory.php \
        backend/app/Models/Clinical/SimilaritySearch.php \
        backend/app/Models/Clinical/FusionWeightConfig.php \
        backend/app/Models/Clinical/ClinicalPatient.php
git commit -m "feat: add fingerprint, outcome, similarity, and fusion weight models"
```

---

## Task 3: Fusion Weight Presets Seeder

**Files:**
- Create: `backend/database/seeders/FusionWeightConfigSeeder.php`
- Modify: `backend/database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Create the seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Clinical\FusionWeightConfig;
use Illuminate\Database\Seeder;

class FusionWeightConfigSeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            [
                'name' => 'Balanced',
                'config_type' => 'preset',
                'genomic_weight' => 0.3400,
                'volumetric_weight' => 0.3300,
                'clinical_weight' => 0.3300,
                'outcome_weights' => [
                    'tumor_response' => 0.30,
                    'treatment_tolerance' => 0.20,
                    'lab_trajectory' => 0.20,
                    'disease_stability' => 0.15,
                    'care_intensity' => 0.15,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Genomics-First',
                'config_type' => 'preset',
                'genomic_weight' => 0.5000,
                'volumetric_weight' => 0.2500,
                'clinical_weight' => 0.2500,
                'outcome_weights' => [
                    'tumor_response' => 0.30,
                    'treatment_tolerance' => 0.20,
                    'lab_trajectory' => 0.20,
                    'disease_stability' => 0.15,
                    'care_intensity' => 0.15,
                ],
                'is_active' => false,
            ],
            [
                'name' => 'Volumetric',
                'config_type' => 'preset',
                'genomic_weight' => 0.2500,
                'volumetric_weight' => 0.5000,
                'clinical_weight' => 0.2500,
                'outcome_weights' => [
                    'tumor_response' => 0.40,
                    'treatment_tolerance' => 0.15,
                    'lab_trajectory' => 0.15,
                    'disease_stability' => 0.15,
                    'care_intensity' => 0.15,
                ],
                'is_active' => false,
            ],
        ];

        foreach ($presets as $preset) {
            FusionWeightConfig::updateOrCreate(
                ['name' => $preset['name'], 'config_type' => 'preset'],
                $preset
            );
        }

        $this->command->info('Seeded ' . count($presets) . ' fusion weight presets.');
    }
}
```

- [ ] **Step 2: Register in DatabaseSeeder**

Add to the `run()` method in `backend/database/seeders/DatabaseSeeder.php`:

```php
$this->call(FusionWeightConfigSeeder::class);
```

- [ ] **Step 3: Run the seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan db:seed --class=FusionWeightConfigSeeder`
Expected: "Seeded 3 fusion weight presets."

- [ ] **Step 4: Commit**

```bash
git add backend/database/seeders/FusionWeightConfigSeeder.php backend/database/seeders/DatabaseSeeder.php
git commit -m "feat: seed fusion weight presets (balanced, genomics-first, volumetric)"
```

---

## Task 4: Backend Services — FingerprintService

**Files:**
- Create: `backend/app/Services/FingerprintService.php`

- [ ] **Step 1: Create FingerprintService**

```php
<?php

namespace App\Services;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\FusionWeightConfig;
use App\Models\Clinical\PatientFingerprint;
use App\Models\Clinical\SimilaritySearch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FingerprintService
{
    private string $aiBaseUrl;

    public function __construct()
    {
        $this->aiBaseUrl = rtrim(config('services.ai.url', 'http://localhost:8000'), '/');
    }

    /**
     * Encode (or re-encode) a patient's fingerprint across all available dimensions.
     */
    public function encodePatient(int $patientId): PatientFingerprint
    {
        $patient = ClinicalPatient::with([
            'genomicVariants', 'conditions', 'medications', 'drugEras',
            'measurements', 'procedures', 'visits', 'conditionEras',
            'imagingStudies.imagingMeasurements', 'imagingStudies.segmentations',
        ])->findOrFail($patientId);

        $fingerprint = PatientFingerprint::firstOrCreate(
            ['patient_id' => $patientId],
            ['encoder_version' => 'v1.0']
        );

        // Encode each dimension independently — failures don't block others
        $this->encodeGenomicDimension($patient, $fingerprint);
        $this->encodeVolumetricDimension($patient, $fingerprint);
        $this->encodeClinicalDimension($patient, $fingerprint);

        $fingerprint->save();

        return $fingerprint->fresh();
    }

    /**
     * Search for similar patients using dimensional fingerprint fusion.
     */
    public function searchSimilar(
        int $patientId,
        array $weights = [],
        int $limit = 10,
        string $context = 'point_of_care',
    ): array {
        $fingerprint = PatientFingerprint::where('patient_id', $patientId)->first();

        if (! $fingerprint || $fingerprint->available_dimension_count === 0) {
            return ['results' => [], 'meta' => ['error' => 'Patient has no fingerprint data']];
        }

        // Resolve weights: custom overrides or active default
        $resolvedWeights = $this->resolveWeights($weights, $fingerprint);
        $isCustom = ! empty($weights);

        // Build pgvector similarity query per available dimension
        $results = $this->executeSimilarityQuery($fingerprint, $resolvedWeights, $limit);

        // Generate explanations for top results
        $results = $this->enrichWithExplanations($patientId, $results);

        return [
            'results' => $results,
            'meta' => [
                'query_patient_id' => $patientId,
                'weights_used' => $resolvedWeights,
                'weights_customized' => $isCustom,
                'dimensions_available' => $fingerprint->dimension_mask,
                'result_count' => count($results),
            ],
        ];
    }

    /**
     * Execute the multi-dimensional pgvector similarity query.
     *
     * Uses parameterized queries throughout to prevent SQL injection.
     * Weights are cast to float and clamped before use.
     */
    private function executeSimilarityQuery(
        PatientFingerprint $fingerprint,
        array $weights,
        int $limit,
    ): array {
        $patientId = (int) $fingerprint->patient_id;

        // Cast and clamp weights to safe float values
        $gw = max(0.0, min(1.0, (float) ($weights['genomic'] ?? 0)));
        $vw = max(0.0, min(1.0, (float) ($weights['volumetric'] ?? 0)));
        $cw = max(0.0, min(1.0, (float) ($weights['clinical'] ?? 0)));

        $selectParts = [];
        $weightSum = 0.0;

        if ($fingerprint->genomic_available && $gw > 0) {
            $selectParts[] = "(1 - (pf.genomic_vector <=> qf.genomic_vector)) * {$gw} AS genomic_sim";
            $weightSum += $gw;
        }

        if ($fingerprint->volumetric_available && $vw > 0) {
            $selectParts[] = "(1 - (pf.volumetric_vector <=> qf.volumetric_vector)) * {$vw} AS volumetric_sim";
            $weightSum += $vw;
        }

        if ($fingerprint->clinical_available && $cw > 0) {
            $selectParts[] = "(1 - (pf.clinical_vector <=> qf.clinical_vector)) * {$cw} AS clinical_sim";
            $weightSum += $cw;
        }

        if (empty($selectParts) || $weightSum === 0.0) {
            return [];
        }

        $simColumns = implode(",\n                ", $selectParts);
        $compositeTerms = implode(' + ', array_map(
            fn ($part) => explode(' AS ', $part)[0],
            $selectParts
        ));

        // Use a CTE to fetch the query patient's vectors once (parameterized)
        $sql = "
            WITH qf AS (
                SELECT genomic_vector, volumetric_vector, clinical_vector
                FROM clinical.patient_fingerprints
                WHERE patient_id = :query_pid
                LIMIT 1
            )
            SELECT
                pf.patient_id,
                {$simColumns},
                ({$compositeTerms}) / {$weightSum} AS composite_score,
                pf.genomic_confidence,
                pf.volumetric_confidence,
                pf.clinical_confidence,
                pf.genomic_available,
                pf.volumetric_available,
                pf.clinical_available
            FROM clinical.patient_fingerprints pf, qf
            WHERE pf.patient_id != :exclude_pid
              AND (pf.genomic_available OR pf.volumetric_available OR pf.clinical_available)
            ORDER BY composite_score DESC
            LIMIT :lim
        ";

        $rows = DB::connection('pgsql')->select($sql, [
            'query_pid' => $patientId,
            'exclude_pid' => $patientId,
            'lim' => $limit,
        ]);

        return array_map(function ($row) {
            return [
                'patient_id' => $row->patient_id,
                'composite_score' => round((float) $row->composite_score, 4),
                'genomic_similarity' => isset($row->genomic_sim) ? round((float) $row->genomic_sim, 4) : null,
                'volumetric_similarity' => isset($row->volumetric_sim) ? round((float) $row->volumetric_sim, 4) : null,
                'clinical_similarity' => isset($row->clinical_sim) ? round((float) $row->clinical_sim, 4) : null,
                'dimensions_matched' => array_filter([
                    $row->genomic_available ? 'genomic' : null,
                    $row->volumetric_available ? 'volumetric' : null,
                    $row->clinical_available ? 'clinical' : null,
                ]),
            ];
        }, $rows);
    }

    /**
     * Resolve weights from user input or active default.
     */
    private function resolveWeights(array $customWeights, PatientFingerprint $fingerprint): array
    {
        if (! empty($customWeights)) {
            $sum = array_sum($customWeights);
            return $sum > 0 ? array_map(fn ($w) => $w / $sum, $customWeights) : $customWeights;
        }

        $active = FusionWeightConfig::active()->first();

        $weights = $active
            ? $active->dimension_weights
            : ['genomic' => 0.34, 'volumetric' => 0.33, 'clinical' => 0.33];

        // Zero out weights for missing dimensions and renormalize
        if (! $fingerprint->genomic_available) $weights['genomic'] = 0;
        if (! $fingerprint->volumetric_available) $weights['volumetric'] = 0;
        if (! $fingerprint->clinical_available) $weights['clinical'] = 0;

        $sum = array_sum($weights);
        if ($sum > 0) {
            $weights = array_map(fn ($w) => $w / $sum, $weights);
        }

        return $weights;
    }

    /**
     * Call Python AI service to encode genomic dimension.
     */
    private function encodeGenomicDimension(ClinicalPatient $patient, PatientFingerprint $fingerprint): void
    {
        $variants = $patient->genomicVariants;
        if ($variants->isEmpty()) {
            $fingerprint->genomic_available = false;
            return;
        }

        try {
            $response = Http::timeout(30)->post("{$this->aiBaseUrl}/api/ai/fingerprint/encode/genomic", [
                'patient_id' => $patient->id,
                'variants' => $variants->map(fn ($v) => [
                    'gene' => $v->gene,
                    'variant' => $v->variant,
                    'variant_type' => $v->variant_type,
                    'allele_frequency' => $v->allele_frequency,
                    'clinical_significance' => $v->clinical_significance,
                    'zygosity' => $v->zygosity,
                    'actionability' => $v->actionability,
                ])->toArray(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                DB::connection('clinical')->statement(
                    'UPDATE clinical.patient_fingerprints SET genomic_vector = :vector WHERE patient_id = :id',
                    ['vector' => $data['vector'], 'id' => $patient->id]
                );
                $fingerprint->genomic_available = true;
                $fingerprint->genomic_confidence = $data['confidence'] ?? 0.5;
                $fingerprint->genomic_encoded_at = now();
            }
        } catch (\Exception $e) {
            \Log::warning("Genomic encoding failed for patient {$patient->id}: {$e->getMessage()}");
            // Leave dimension unchanged on failure
        }
    }

    /**
     * Call Python AI service to encode volumetric dimension.
     */
    private function encodeVolumetricDimension(ClinicalPatient $patient, PatientFingerprint $fingerprint): void
    {
        $studies = $patient->imagingStudies;
        if ($studies->isEmpty()) {
            $fingerprint->volumetric_available = false;
            return;
        }

        try {
            $response = Http::timeout(30)->post("{$this->aiBaseUrl}/api/ai/fingerprint/encode/volumetric", [
                'patient_id' => $patient->id,
                'studies' => $studies->map(fn ($s) => [
                    'modality' => $s->modality,
                    'body_part' => $s->body_part,
                    'study_date' => $s->study_date,
                    'measurements' => $s->imagingMeasurements->map(fn ($m) => [
                        'measurement_type' => $m->measurement_type,
                        'value_numeric' => $m->value_numeric,
                        'unit' => $m->unit,
                        'target_lesion' => $m->target_lesion,
                        'measured_at' => $m->measured_at,
                    ])->toArray(),
                    'segmentations' => $s->segmentations->map(fn ($seg) => [
                        'volume_mm3' => $seg->volume_mm3,
                        'label' => $seg->label,
                    ])->toArray(),
                ])->toArray(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                DB::connection('clinical')->statement(
                    'UPDATE clinical.patient_fingerprints SET volumetric_vector = :vector WHERE patient_id = :id',
                    ['vector' => $data['vector'], 'id' => $patient->id]
                );
                $fingerprint->volumetric_available = true;
                $fingerprint->volumetric_confidence = $data['confidence'] ?? 0.5;
                $fingerprint->volumetric_encoded_at = now();
            }
        } catch (\Exception $e) {
            \Log::warning("Volumetric encoding failed for patient {$patient->id}: {$e->getMessage()}");
        }
    }

    /**
     * Call Python AI service to encode clinical dimension.
     */
    private function encodeClinicalDimension(ClinicalPatient $patient, PatientFingerprint $fingerprint): void
    {
        $hasData = $patient->conditions->isNotEmpty()
                || $patient->medications->isNotEmpty()
                || $patient->measurements->isNotEmpty();

        if (! $hasData) {
            $fingerprint->clinical_available = false;
            return;
        }

        try {
            $response = Http::timeout(30)->post("{$this->aiBaseUrl}/api/ai/fingerprint/encode/clinical", [
                'patient_id' => $patient->id,
                'conditions' => $patient->conditions->map(fn ($c) => [
                    'concept_name' => $c->concept_name,
                    'concept_code' => $c->concept_code,
                    'domain' => $c->domain,
                    'status' => $c->status,
                    'severity' => $c->severity,
                ])->toArray(),
                'medications' => $patient->medications->map(fn ($m) => [
                    'drug_name' => $m->drug_name,
                    'dose_value' => $m->dose_value,
                    'dose_unit' => $m->dose_unit,
                    'frequency' => $m->frequency,
                    'status' => $m->status,
                    'start_date' => $m->start_date,
                    'end_date' => $m->end_date,
                ])->toArray(),
                'drug_eras' => $patient->drugEras->map(fn ($d) => [
                    'drug_name' => $d->drug_name,
                    'era_start' => $d->era_start,
                    'era_end' => $d->era_end,
                    'gap_days' => $d->gap_days,
                ])->toArray(),
                'measurements' => $patient->measurements->map(fn ($m) => [
                    'measurement_name' => $m->measurement_name,
                    'value_numeric' => $m->value_numeric,
                    'unit' => $m->unit,
                    'measured_at' => $m->measured_at,
                ])->toArray(),
                'visits' => $patient->visits->map(fn ($v) => [
                    'visit_type' => $v->visit_type,
                    'admission_date' => $v->admission_date,
                    'discharge_date' => $v->discharge_date,
                ])->toArray(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                DB::connection('clinical')->statement(
                    'UPDATE clinical.patient_fingerprints SET clinical_vector = :vector WHERE patient_id = :id',
                    ['vector' => $data['vector'], 'id' => $patient->id]
                );
                $fingerprint->clinical_available = true;
                $fingerprint->clinical_confidence = $data['confidence'] ?? 0.5;
                $fingerprint->clinical_encoded_at = now();
            }
        } catch (\Exception $e) {
            \Log::warning("Clinical encoding failed for patient {$patient->id}: {$e->getMessage()}");
        }
    }

    /**
     * Call Python AI to generate explanation for each similar patient pair.
     */
    private function enrichWithExplanations(int $queryPatientId, array $results): array
    {
        if (empty($results)) {
            return $results;
        }

        try {
            $response = Http::timeout(60)->post("{$this->aiBaseUrl}/api/ai/fingerprint/explain", [
                'query_patient_id' => $queryPatientId,
                'similar_patient_ids' => array_column($results, 'patient_id'),
            ]);

            if ($response->successful()) {
                $explanations = $response->json('explanations') ?? [];
                foreach ($results as $i => &$result) {
                    $result['explanation'] = $explanations[$i] ?? null;
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Explanation generation failed: {$e->getMessage()}");
        }

        return $results;
    }

    /**
     * Log a similarity search for audit.
     */
    public function logSearch(
        int $queryPatientId,
        int $searchedBy,
        array $weightsUsed,
        bool $weightsCustomized,
        string $context,
        array $results,
    ): void {
        SimilaritySearch::create([
            'query_patient_id' => $queryPatientId,
            'searched_by' => $searchedBy,
            'weights_used' => $weightsUsed,
            'weights_customized' => $weightsCustomized,
            'context' => $context,
            'result_patient_ids' => array_column($results, 'patient_id'),
            'result_scores' => array_map(fn ($r) => [
                'composite' => $r['composite_score'],
                'genomic' => $r['genomic_similarity'] ?? null,
                'volumetric' => $r['volumetric_similarity'] ?? null,
                'clinical' => $r['clinical_similarity'] ?? null,
            ], $results),
            'result_count' => count($results),
        ]);
    }

    /**
     * Get fingerprint stats.
     */
    public function getStats(): array
    {
        $total = PatientFingerprint::count();
        $genomic = PatientFingerprint::where('genomic_available', true)->count();
        $volumetric = PatientFingerprint::where('volumetric_available', true)->count();
        $clinical = PatientFingerprint::where('clinical_available', true)->count();
        $full = PatientFingerprint::where('genomic_available', true)
            ->where('volumetric_available', true)
            ->where('clinical_available', true)
            ->count();

        return [
            'total_fingerprinted' => $total,
            'genomic_coverage' => $genomic,
            'volumetric_coverage' => $volumetric,
            'clinical_coverage' => $clinical,
            'full_coverage' => $full,
            'outcomes_annotated' => \App\Models\Clinical\OutcomeTrajectory::whereNotNull('clinician_rating')->count(),
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add backend/app/Services/FingerprintService.php
git commit -m "feat: add FingerprintService with encoding, similarity search, and stats"
```

---

## Task 5: Backend Services — OutcomeService

**Files:**
- Create: `backend/app/Services/OutcomeService.php`

- [ ] **Step 1: Create OutcomeService**

```php
<?php

namespace App\Services;

use App\Models\Clinical\OutcomeTrajectory;
use Illuminate\Support\Facades\Http;

class OutcomeService
{
    private string $aiBaseUrl;

    public function __construct()
    {
        $this->aiBaseUrl = rtrim(config('services.ai.url', 'http://localhost:8000'), '/');
    }

    /**
     * Compute trajectory sub-scores for a patient via Python AI service.
     */
    public function computeTrajectory(int $patientId): OutcomeTrajectory
    {
        $trajectory = OutcomeTrajectory::firstOrCreate(
            ['patient_id' => $patientId],
            ['computed_at' => now()]
        );

        try {
            $response = Http::timeout(30)->post("{$this->aiBaseUrl}/api/ai/fingerprint/outcome/compute", [
                'patient_id' => $patientId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $trajectory->update([
                    'tumor_response_score' => $data['tumor_response'] ?? null,
                    'treatment_tolerance_score' => $data['treatment_tolerance'] ?? null,
                    'lab_trajectory_score' => $data['lab_trajectory'] ?? null,
                    'disease_stability_score' => $data['disease_stability'] ?? null,
                    'care_intensity_score' => $data['care_intensity'] ?? null,
                    'composite_score' => $data['composite'] ?? null,
                    'computed_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning("Outcome computation failed for patient {$patientId}: {$e->getMessage()}");
        }

        return $trajectory->fresh();
    }

    /**
     * Save a clinician's outcome assessment.
     */
    public function saveAssessment(int $patientId, int $assessedBy, array $data): OutcomeTrajectory
    {
        $trajectory = OutcomeTrajectory::firstOrCreate(
            ['patient_id' => $patientId],
            ['computed_at' => now()]
        );

        $trajectory->update([
            'clinician_rating' => $data['clinician_rating'],
            'clinician_factors' => $data['clinician_factors'] ?? null,
            'decision_tags' => $data['decision_tags'] ?? null,
            'hindsight_note' => $data['hindsight_note'] ?? null,
            'assessed_by' => $assessedBy,
            'assessed_at' => now(),
        ]);

        return $trajectory->fresh();
    }

    /**
     * Get outcome trajectory for a patient, including enrichment with patient context.
     */
    public function getTrajectory(int $patientId): ?array
    {
        $trajectory = OutcomeTrajectory::with('assessor')->where('patient_id', $patientId)->first();

        if (! $trajectory) {
            return null;
        }

        return [
            'patient_id' => $patientId,
            'computed' => [
                'composite_score' => $trajectory->composite_score,
                'sub_scores' => $trajectory->sub_scores,
                'computed_at' => $trajectory->computed_at,
            ],
            'assessment' => $trajectory->clinician_rating ? [
                'rating' => $trajectory->clinician_rating,
                'factors' => $trajectory->clinician_factors,
                'decision_tags' => $trajectory->decision_tags ?? [],
                'hindsight_note' => $trajectory->hindsight_note,
                'assessed_by' => $trajectory->assessor?->name,
                'assessed_at' => $trajectory->assessed_at,
            ] : null,
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add backend/app/Services/OutcomeService.php
git commit -m "feat: add OutcomeService with computation, assessment, and retrieval"
```

---

## Task 6: Backend Controller + Routes

**Files:**
- Create: `backend/app/Http/Controllers/FingerprintController.php`
- Modify: `backend/routes/api.php`

- [ ] **Step 1: Create FingerprintController**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Clinical\FusionWeightConfig;
use App\Models\Clinical\OutcomeTrajectory;
use App\Models\Clinical\PatientFingerprint;
use App\Services\FingerprintService;
use App\Services\OutcomeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FingerprintController extends Controller
{
    public function __construct(
        private readonly FingerprintService $fingerprintService,
        private readonly OutcomeService $outcomeService,
    ) {}

    /**
     * POST /api/fingerprint/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'patient_id' => 'required|integer|exists:clinical.patients,id',
            'weights' => 'sometimes|array',
            'weights.genomic' => 'sometimes|numeric|min:0|max:1',
            'weights.volumetric' => 'sometimes|numeric|min:0|max:1',
            'weights.clinical' => 'sometimes|numeric|min:0|max:1',
            'limit' => 'sometimes|integer|min:1|max:50',
            'context' => 'sometimes|string|in:point_of_care,tumor_board,research',
        ]);

        $result = $this->fingerprintService->searchSimilar(
            patientId: $request->input('patient_id'),
            weights: $request->input('weights', []),
            limit: $request->input('limit', 10),
            context: $request->input('context', 'point_of_care'),
        );

        // Enrich results with outcome and patient data
        $enriched = $this->enrichSearchResults($result['results']);

        // Log the search
        $this->fingerprintService->logSearch(
            queryPatientId: $request->input('patient_id'),
            searchedBy: auth()->id(),
            weightsUsed: $result['meta']['weights_used'],
            weightsCustomized: $result['meta']['weights_customized'],
            context: $request->input('context', 'point_of_care'),
            results: $result['results'],
        );

        return ApiResponse::success([
            'results' => $enriched,
            'meta' => $result['meta'],
        ], 'Similar patients found');
    }

    /**
     * GET /api/fingerprint/patients/{id}
     */
    public function showFingerprint(int $id): JsonResponse
    {
        $fingerprint = PatientFingerprint::where('patient_id', $id)->first();

        if (! $fingerprint) {
            return ApiResponse::success([
                'patient_id' => $id,
                'has_fingerprint' => false,
                'dimensions' => ['genomic' => false, 'volumetric' => false, 'clinical' => false],
            ], 'No fingerprint for this patient');
        }

        return ApiResponse::success([
            'patient_id' => $id,
            'has_fingerprint' => true,
            'dimensions' => [
                'genomic' => $fingerprint->genomic_available,
                'volumetric' => $fingerprint->volumetric_available,
                'clinical' => $fingerprint->clinical_available,
            ],
            'confidence' => [
                'genomic' => $fingerprint->genomic_confidence,
                'volumetric' => $fingerprint->volumetric_confidence,
                'clinical' => $fingerprint->clinical_confidence,
            ],
            'encoded_at' => [
                'genomic' => $fingerprint->genomic_encoded_at,
                'volumetric' => $fingerprint->volumetric_encoded_at,
                'clinical' => $fingerprint->clinical_encoded_at,
            ],
            'encoder_version' => $fingerprint->encoder_version,
            'dimension_count' => $fingerprint->available_dimension_count,
        ], 'Fingerprint retrieved');
    }

    /**
     * POST /api/fingerprint/patients/{id}/encode
     */
    public function encode(int $id): JsonResponse
    {
        $fingerprint = $this->fingerprintService->encodePatient($id);

        return ApiResponse::success([
            'patient_id' => $id,
            'dimensions' => [
                'genomic' => $fingerprint->genomic_available,
                'volumetric' => $fingerprint->volumetric_available,
                'clinical' => $fingerprint->clinical_available,
            ],
            'confidence' => [
                'genomic' => $fingerprint->genomic_confidence,
                'volumetric' => $fingerprint->volumetric_confidence,
                'clinical' => $fingerprint->clinical_confidence,
            ],
            'dimension_count' => $fingerprint->available_dimension_count,
        ], 'Patient fingerprint encoded');
    }

    /**
     * POST /api/fingerprint/encode-batch
     */
    public function encodeBatch(Request $request): JsonResponse
    {
        $request->validate([
            'patient_ids' => 'required|array|min:1|max:100',
            'patient_ids.*' => 'integer|exists:clinical.patients,id',
        ]);

        $results = [];
        foreach ($request->input('patient_ids') as $patientId) {
            $fp = $this->fingerprintService->encodePatient($patientId);
            $results[] = [
                'patient_id' => $patientId,
                'dimension_count' => $fp->available_dimension_count,
            ];
        }

        return ApiResponse::success($results, count($results) . ' patients encoded');
    }

    /**
     * GET /api/fingerprint/patients/{id}/outcome
     */
    public function showOutcome(int $id): JsonResponse
    {
        $trajectory = $this->outcomeService->getTrajectory($id);

        if (! $trajectory) {
            return ApiResponse::success([
                'patient_id' => $id,
                'has_outcome' => false,
            ], 'No outcome data for this patient');
        }

        return ApiResponse::success(
            array_merge(['has_outcome' => true], $trajectory),
            'Outcome trajectory retrieved'
        );
    }

    /**
     * PUT /api/fingerprint/patients/{id}/outcome/assess
     */
    public function assessOutcome(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'clinician_rating' => 'required|string|in:excellent,good,mixed,poor,failure',
            'clinician_factors' => 'sometimes|string|max:5000',
            'decision_tags' => 'sometimes|array',
            'decision_tags.*' => 'string|max:50',
            'hindsight_note' => 'sometimes|string|max:5000',
        ]);

        $trajectory = $this->outcomeService->saveAssessment(
            patientId: $id,
            assessedBy: auth()->id(),
            data: $request->only(['clinician_rating', 'clinician_factors', 'decision_tags', 'hindsight_note']),
        );

        return ApiResponse::success([
            'patient_id' => $id,
            'clinician_rating' => $trajectory->clinician_rating,
            'assessed_at' => $trajectory->assessed_at,
        ], 'Outcome assessment saved');
    }

    /**
     * GET /api/fingerprint/weights
     */
    public function listWeights(): JsonResponse
    {
        $configs = FusionWeightConfig::presets()->get();

        return ApiResponse::success($configs, 'Weight presets retrieved');
    }

    /**
     * GET /api/fingerprint/weights/active
     */
    public function activeWeights(): JsonResponse
    {
        $active = FusionWeightConfig::active()->first();

        return ApiResponse::success($active, 'Active weight config retrieved');
    }

    /**
     * GET /api/fingerprint/stats
     */
    public function stats(): JsonResponse
    {
        return ApiResponse::success(
            $this->fingerprintService->getStats(),
            'Fingerprint stats retrieved'
        );
    }

    /**
     * Enrich search results with patient demographics and outcome data.
     */
    private function enrichSearchResults(array $results): array
    {
        if (empty($results)) {
            return [];
        }

        $patientIds = array_column($results, 'patient_id');

        $patients = \App\Models\Clinical\ClinicalPatient::whereIn('id', $patientIds)
            ->with(['conditions' => fn ($q) => $q->where('domain', 'oncology')->limit(3)])
            ->get()
            ->keyBy('id');

        $outcomes = OutcomeTrajectory::whereIn('patient_id', $patientIds)
            ->get()
            ->keyBy('patient_id');

        return array_map(function ($result) use ($patients, $outcomes) {
            $patient = $patients[$result['patient_id']] ?? null;
            $outcome = $outcomes[$result['patient_id']] ?? null;

            $result['patient'] = $patient ? [
                'id' => $patient->id,
                'mrn' => $patient->mrn,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'sex' => $patient->sex,
                'date_of_birth' => $patient->date_of_birth,
                'primary_conditions' => $patient->conditions->pluck('concept_name')->toArray(),
            ] : null;

            $result['outcome'] = $outcome ? [
                'composite_score' => $outcome->composite_score,
                'clinician_rating' => $outcome->clinician_rating,
                'decision_tags' => $outcome->decision_tags ?? [],
                'hindsight_note' => $outcome->hindsight_note,
                'sub_scores' => $outcome->sub_scores,
            ] : null;

            return $result;
        }, $results);
    }
}
```

- [ ] **Step 2: Add routes to api.php**

Add to `backend/routes/api.php` inside the `auth:sanctum` middleware group:

```php
// ── Fingerprint (Similarity Engine) ──────────────────────────────────
Route::prefix('fingerprint')->group(function () {
    // View-level access (any authenticated clinician)
    Route::get('/weights', [FingerprintController::class, 'listWeights']);
    Route::get('/weights/active', [FingerprintController::class, 'activeWeights']);
    Route::get('/stats', [FingerprintController::class, 'stats']);
    Route::get('/patients/{id}', [FingerprintController::class, 'showFingerprint']);
    Route::get('/patients/{id}/outcome', [FingerprintController::class, 'showOutcome']);

    // Search access
    Route::post('/search', [FingerprintController::class, 'search'])->middleware('permission:fingerprint.search');

    // Encode access (attending physician or admin)
    Route::post('/patients/{id}/encode', [FingerprintController::class, 'encode'])->middleware('permission:fingerprint.encode');
    Route::post('/encode-batch', [FingerprintController::class, 'encodeBatch'])->middleware('permission:fingerprint.admin');

    // Assessment access (attending physician, specialist, or admin)
    Route::put('/patients/{id}/outcome/assess', [FingerprintController::class, 'assessOutcome'])->middleware('permission:fingerprint.assess');
});
```

Also add the import at top:

```php
use App\Http\Controllers\FingerprintController;
```

- [ ] **Step 3: Verify routes registered**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan route:list --path=fingerprint`
Expected: 9 routes listed.

- [ ] **Step 4: Commit**

```bash
git add backend/app/Http/Controllers/FingerprintController.php backend/routes/api.php
git commit -m "feat: add FingerprintController with search, encode, outcome, and weight endpoints"
```

---

## Task 7: Python AI — Pydantic Models

**Files:**
- Create: `ai/app/models/fingerprint.py`

- [ ] **Step 1: Create Pydantic models**

```python
"""Pydantic request/response models for the fingerprint encoding system."""

from __future__ import annotations

from pydantic import BaseModel, Field


# ── Genomic Encoding ──────────────────────────────────────────────────


class VariantInput(BaseModel):
    gene: str
    variant: str | None = None
    variant_type: str | None = None
    allele_frequency: float | None = None
    clinical_significance: str | None = None
    zygosity: str | None = None
    actionability: str | None = None


class GenomicEncodeRequest(BaseModel):
    patient_id: int
    variants: list[VariantInput]


class EncodeResponse(BaseModel):
    patient_id: int
    vector: str  # pgvector-compatible string: "[0.1, 0.2, ...]"
    confidence: float = Field(ge=0.0, le=1.0)
    dimension: str


# ── Volumetric Encoding ──────────────────────────────────────────────


class MeasurementInput(BaseModel):
    measurement_type: str | None = None
    value_numeric: float | None = None
    unit: str | None = None
    target_lesion: bool = False
    measured_at: str | None = None


class SegmentationInput(BaseModel):
    volume_mm3: float | None = None
    label: str | None = None


class StudyInput(BaseModel):
    modality: str | None = None
    body_part: str | None = None
    study_date: str | None = None
    measurements: list[MeasurementInput] = []
    segmentations: list[SegmentationInput] = []


class VolumetricEncodeRequest(BaseModel):
    patient_id: int
    studies: list[StudyInput]


# ── Clinical Encoding ────────────────────────────────────────────────


class ConditionInput(BaseModel):
    concept_name: str
    concept_code: str | None = None
    domain: str | None = None
    status: str | None = None
    severity: str | None = None


class MedicationInput(BaseModel):
    drug_name: str
    dose_value: float | None = None
    dose_unit: str | None = None
    frequency: str | None = None
    status: str | None = None
    start_date: str | None = None
    end_date: str | None = None


class DrugEraInput(BaseModel):
    drug_name: str
    era_start: str | None = None
    era_end: str | None = None
    gap_days: int | None = None


class VisitInput(BaseModel):
    visit_type: str | None = None
    admission_date: str | None = None
    discharge_date: str | None = None


class ClinicalEncodeRequest(BaseModel):
    patient_id: int
    conditions: list[ConditionInput] = []
    medications: list[MedicationInput] = []
    drug_eras: list[DrugEraInput] = []
    measurements: list[dict] = []  # flexible structure
    visits: list[VisitInput] = []


# ── Outcome Computation ──────────────────────────────────────────────


class OutcomeComputeRequest(BaseModel):
    patient_id: int


class OutcomeComputeResponse(BaseModel):
    patient_id: int
    tumor_response: float | None = None
    treatment_tolerance: float | None = None
    lab_trajectory: float | None = None
    disease_stability: float | None = None
    care_intensity: float | None = None
    composite: float | None = None


# ── Explanation ──────────────────────────────────────────────────────


class ExplainRequest(BaseModel):
    query_patient_id: int
    similar_patient_ids: list[int]


class ExplainResponse(BaseModel):
    explanations: list[str | None]
```

- [ ] **Step 2: Commit**

```bash
git add ai/app/models/fingerprint.py
git commit -m "feat: add Pydantic models for fingerprint encoding system"
```

---

## Task 8: Python AI — Fingerprint Encoders

**Files:**
- Create: `ai/app/services/fingerprint_encoder.py`

- [ ] **Step 1: Create the encoder service**

```python
"""
Fingerprint encoders — three specialized encoders that produce 256-dim vectors
for genomic, volumetric, and clinical patient dimensions.

V1 approach: structured feature hashing + text embedding hybrid.
Each encoder extracts structured features, builds a text representation,
and uses Ollama embeddings to produce a dense vector. Confidence is
derived from data completeness.
"""

import hashlib
import logging
import struct
from typing import Any

import numpy as np

from app.services.embedding_service import compute_embedding

logger = logging.getLogger(__name__)

VECTOR_DIM = 256


def _normalize(vec: np.ndarray) -> np.ndarray:
    """L2-normalize a vector."""
    norm = np.linalg.norm(vec)
    if norm == 0:
        return vec
    return vec / norm


def _to_pgvector_string(vec: np.ndarray) -> str:
    """Convert numpy array to pgvector-compatible string."""
    return "[" + ",".join(f"{v:.6f}" for v in vec) + "]"


def _hash_to_vector(text: str, dim: int = VECTOR_DIM) -> np.ndarray:
    """Deterministic hash of text to a fixed-dimension vector."""
    h = hashlib.sha256(text.encode()).digest()
    # Extend hash to fill dimension
    extended = h * ((dim * 4 // len(h)) + 1)
    floats = struct.unpack(f"{dim}f", extended[: dim * 4])
    return _normalize(np.array(floats, dtype=np.float32))


async def encode_genomic(
    patient_id: int,
    variants: list[dict[str, Any]],
) -> tuple[str, float]:
    """Encode genomic profile into a 256-dim vector.

    Combines:
    1. Structured features: variant count, actionable count, significance distribution
    2. Text embedding: gene+variant descriptions via Ollama

    Returns (pgvector_string, confidence).
    """
    if not variants:
        raise ValueError("No variants to encode")

    # Build structured feature vector (first 64 dims)
    n_variants = len(variants)
    genes = {v.get("gene", "") for v in variants}
    actionable = sum(
        1 for v in variants if v.get("clinical_significance") in ("pathogenic", "likely_pathogenic")
    )
    vus_count = sum(
        1 for v in variants if v.get("clinical_significance") in ("VUS", "uncertain significance")
    )

    # Variant type distribution
    type_counts: dict[str, int] = {}
    for v in variants:
        vtype = v.get("variant_type", "unknown")
        type_counts[vtype] = type_counts.get(vtype, 0) + 1

    structured = np.zeros(64, dtype=np.float32)
    structured[0] = min(n_variants / 50.0, 1.0)  # normalized variant count
    structured[1] = min(actionable / 10.0, 1.0)   # normalized actionable count
    structured[2] = min(vus_count / 20.0, 1.0)     # normalized VUS count
    structured[3] = len(genes) / max(n_variants, 1)  # gene diversity
    structured[4] = type_counts.get("SNV", 0) / max(n_variants, 1)
    structured[5] = type_counts.get("indel", 0) / max(n_variants, 1)
    structured[6] = type_counts.get("fusion", 0) / max(n_variants, 1)
    structured[7] = type_counts.get("CNV", 0) / max(n_variants, 1)

    # Mean allele frequency
    afs = [v.get("allele_frequency") for v in variants if v.get("allele_frequency")]
    structured[8] = np.mean(afs) if afs else 0.0

    # Build text representation for embedding (remaining 192 dims)
    gene_variant_strs = []
    for v in variants:
        parts = [v.get("gene", "")]
        if v.get("variant"):
            parts.append(v["variant"])
        if v.get("clinical_significance"):
            parts.append(v["clinical_significance"])
        gene_variant_strs.append(" ".join(parts))

    text = f"Genomic profile: {n_variants} variants, {actionable} actionable. " + "; ".join(
        gene_variant_strs[:15]  # cap to avoid token limits
    )

    try:
        raw_embedding = await compute_embedding(text)
        # Truncate or pad to 192 dims
        emb = np.array(raw_embedding[:192], dtype=np.float32)
        if len(emb) < 192:
            emb = np.pad(emb, (0, 192 - len(emb)))
    except Exception:
        logger.warning("Ollama embedding failed for patient %d, using hash fallback", patient_id)
        emb = _hash_to_vector(text, 192)

    # Concatenate: [structured(64) | embedding(192)] = 256
    combined = _normalize(np.concatenate([structured, emb]))

    # Confidence based on data richness
    confidence = min(1.0, 0.3 + (n_variants / 15.0) * 0.4 + (actionable / 3.0) * 0.3)

    return _to_pgvector_string(combined), round(confidence, 4)


async def encode_volumetric(
    patient_id: int,
    studies: list[dict[str, Any]],
) -> tuple[str, float]:
    """Encode imaging/volumetric data into a 256-dim vector.

    Combines:
    1. Structured features: study count, modality mix, tumor volumes, RECIST
    2. Text embedding: imaging summary via Ollama

    Returns (pgvector_string, confidence).
    """
    if not studies:
        raise ValueError("No imaging studies to encode")

    # Structured features (first 64 dims)
    structured = np.zeros(64, dtype=np.float32)
    structured[0] = min(len(studies) / 10.0, 1.0)  # study count

    modalities = [s.get("modality", "") for s in studies]
    structured[1] = 1.0 if "CT" in modalities else 0.0
    structured[2] = 1.0 if "MRI" in modalities else 0.0
    structured[3] = 1.0 if "PET" in modalities else 0.0

    # Aggregate measurements and segmentations
    all_volumes = []
    all_recist = []
    total_measurements = 0

    for study in studies:
        for seg in study.get("segmentations", []):
            vol = seg.get("volume_mm3")
            if vol is not None:
                all_volumes.append(vol)

        for meas in study.get("measurements", []):
            total_measurements += 1
            if meas.get("measurement_type") == "RECIST":
                val = meas.get("value_numeric")
                if val is not None:
                    all_recist.append(val)

    if all_volumes:
        structured[4] = min(np.sum(all_volumes) / 100000.0, 1.0)  # total tumor burden
        structured[5] = min(np.max(all_volumes) / 50000.0, 1.0)   # largest lesion
        structured[6] = min(len(all_volumes) / 10.0, 1.0)          # lesion count

    if all_recist:
        structured[7] = min(np.mean(all_recist) / 100.0, 1.0)

    structured[8] = min(total_measurements / 20.0, 1.0)

    # Text representation
    body_parts = {s.get("body_part", "unknown") for s in studies}
    text = (
        f"Imaging profile: {len(studies)} studies, modalities: {', '.join(set(modalities))}. "
        f"Body parts: {', '.join(body_parts)}. "
        f"Lesions: {len(all_volumes)}, total volume: {sum(all_volumes):.0f}mm³. "
        f"Measurements: {total_measurements}."
    )

    try:
        raw_embedding = await compute_embedding(text)
        emb = np.array(raw_embedding[:192], dtype=np.float32)
        if len(emb) < 192:
            emb = np.pad(emb, (0, 192 - len(emb)))
    except Exception:
        logger.warning("Ollama embedding failed for patient %d volumetric, using hash fallback", patient_id)
        emb = _hash_to_vector(text, 192)

    combined = _normalize(np.concatenate([structured, emb]))

    confidence = min(1.0, 0.2 + (len(studies) / 4.0) * 0.3 + (len(all_volumes) / 5.0) * 0.3 + (total_measurements / 10.0) * 0.2)

    return _to_pgvector_string(combined), round(confidence, 4)


async def encode_clinical(
    patient_id: int,
    conditions: list[dict],
    medications: list[dict],
    drug_eras: list[dict],
    measurements: list[dict],
    visits: list[dict],
) -> tuple[str, float]:
    """Encode clinical trajectory into a 256-dim vector.

    Returns (pgvector_string, confidence).
    """
    has_any = conditions or medications or measurements

    if not has_any:
        raise ValueError("No clinical data to encode")

    # Structured features (first 64 dims)
    structured = np.zeros(64, dtype=np.float32)
    structured[0] = min(len(conditions) / 10.0, 1.0)
    structured[1] = min(len(medications) / 10.0, 1.0)
    structured[2] = min(len(drug_eras) / 5.0, 1.0)
    structured[3] = min(len(measurements) / 20.0, 1.0)
    structured[4] = min(len(visits) / 10.0, 1.0)

    # Condition domains
    domains = {c.get("domain", "") for c in conditions}
    structured[5] = 1.0 if "oncology" in domains else 0.0
    structured[6] = 1.0 if "surgical" in domains else 0.0
    structured[7] = 1.0 if "rare_disease" in domains else 0.0

    # Visit type distribution
    visit_types = [v.get("visit_type", "") for v in visits]
    structured[8] = sum(1 for t in visit_types if t == "emergency") / max(len(visits), 1)
    structured[9] = sum(1 for t in visit_types if t == "inpatient") / max(len(visits), 1)

    # Medication status distribution
    med_statuses = [m.get("status", "") for m in medications]
    structured[10] = sum(1 for s in med_statuses if s == "active") / max(len(medications), 1)
    structured[11] = sum(1 for s in med_statuses if s == "discontinued") / max(len(medications), 1)

    # Text representation
    condition_names = [c.get("concept_name", "") for c in conditions[:10]]
    drug_names = [m.get("drug_name", "") for m in medications[:10]]

    text = (
        f"Clinical profile: {len(conditions)} conditions ({', '.join(condition_names)}), "
        f"{len(medications)} medications ({', '.join(drug_names)}), "
        f"{len(visits)} visits, {len(measurements)} lab measurements."
    )

    try:
        raw_embedding = await compute_embedding(text)
        emb = np.array(raw_embedding[:192], dtype=np.float32)
        if len(emb) < 192:
            emb = np.pad(emb, (0, 192 - len(emb)))
    except Exception:
        logger.warning("Ollama embedding failed for patient %d clinical, using hash fallback", patient_id)
        emb = _hash_to_vector(text, 192)

    combined = _normalize(np.concatenate([structured, emb]))

    data_points = len(conditions) + len(medications) + len(measurements) + len(visits)
    confidence = min(1.0, 0.2 + (data_points / 30.0) * 0.8)

    return _to_pgvector_string(combined), round(confidence, 4)
```

- [ ] **Step 2: Create encoder tests**

Create: `ai/tests/test_fingerprint_encoder.py`

```python
"""Tests for fingerprint encoders."""

import pytest

from app.services.fingerprint_encoder import (
    _hash_to_vector,
    _normalize,
    _to_pgvector_string,
    encode_clinical,
    encode_genomic,
    encode_volumetric,
)


def test_normalize_zero_vector():
    import numpy as np
    vec = np.zeros(10)
    result = _normalize(vec)
    assert all(v == 0.0 for v in result)


def test_normalize_unit_vector():
    import numpy as np
    vec = np.array([3.0, 4.0])
    result = _normalize(vec)
    assert abs(np.linalg.norm(result) - 1.0) < 1e-6


def test_hash_to_vector_deterministic():
    v1 = _hash_to_vector("test", 256)
    v2 = _hash_to_vector("test", 256)
    assert (v1 == v2).all()


def test_hash_to_vector_different_inputs():
    v1 = _hash_to_vector("test_a", 256)
    v2 = _hash_to_vector("test_b", 256)
    assert not (v1 == v2).all()


def test_to_pgvector_string():
    import numpy as np
    vec = np.array([0.1, 0.2, 0.3])
    result = _to_pgvector_string(vec)
    assert result.startswith("[")
    assert result.endswith("]")
    assert "0.100000" in result


@pytest.mark.asyncio
async def test_encode_genomic_empty_raises():
    with pytest.raises(ValueError, match="No variants"):
        await encode_genomic(1, [])


@pytest.mark.asyncio
async def test_encode_genomic_produces_vector():
    variants = [
        {"gene": "BRAF", "variant": "V600E", "variant_type": "SNV",
         "allele_frequency": 0.45, "clinical_significance": "pathogenic"},
        {"gene": "TP53", "variant": "R175H", "variant_type": "SNV",
         "allele_frequency": 0.3, "clinical_significance": "pathogenic"},
    ]
    vector_str, confidence = await encode_genomic(1, variants)
    assert vector_str.startswith("[")
    assert 0.0 < confidence <= 1.0
    # Verify 256 dimensions
    values = vector_str.strip("[]").split(",")
    assert len(values) == 256


@pytest.mark.asyncio
async def test_encode_volumetric_empty_raises():
    with pytest.raises(ValueError, match="No imaging"):
        await encode_volumetric(1, [])


@pytest.mark.asyncio
async def test_encode_volumetric_produces_vector():
    studies = [
        {
            "modality": "CT",
            "body_part": "chest",
            "study_date": "2026-01-01",
            "measurements": [{"measurement_type": "RECIST", "value_numeric": 25.0, "unit": "mm"}],
            "segmentations": [{"volume_mm3": 15000.0, "label": "tumor"}],
        }
    ]
    vector_str, confidence = await encode_volumetric(1, studies)
    assert vector_str.startswith("[")
    assert 0.0 < confidence <= 1.0


@pytest.mark.asyncio
async def test_encode_clinical_empty_raises():
    with pytest.raises(ValueError, match="No clinical"):
        await encode_clinical(1, [], [], [], [], [])


@pytest.mark.asyncio
async def test_encode_clinical_produces_vector():
    vector_str, confidence = await encode_clinical(
        patient_id=1,
        conditions=[{"concept_name": "NSCLC", "domain": "oncology", "status": "active"}],
        medications=[{"drug_name": "pembrolizumab", "status": "active"}],
        drug_eras=[],
        measurements=[],
        visits=[{"visit_type": "outpatient"}],
    )
    assert vector_str.startswith("[")
    assert 0.0 < confidence <= 1.0
    values = vector_str.strip("[]").split(",")
    assert len(values) == 256
```

- [ ] **Step 3: Run tests**

Run: `cd /home/smudoshi/Github/Aurora/ai && python -m pytest tests/test_fingerprint_encoder.py -v`
Expected: All tests pass (Ollama may not be running; hash fallback covers that).

- [ ] **Step 4: Commit**

```bash
git add ai/app/services/fingerprint_encoder.py ai/tests/test_fingerprint_encoder.py
git commit -m "feat: add three-dimensional fingerprint encoders with tests"
```

---

## Task 9: Python AI — Outcome Computer + Explainer

**Files:**
- Create: `ai/app/services/outcome_computer.py`
- Create: `ai/app/services/fingerprint_explainer.py`

- [ ] **Step 1: Create outcome_computer.py**

```python
"""Compute outcome trajectory sub-scores from patient clinical data."""

import logging
from typing import Any

from sqlalchemy import text

from app.db import get_session

logger = logging.getLogger(__name__)

# Default outcome sub-score weights
OUTCOME_WEIGHTS: dict[str, float] = {
    "tumor_response": 0.30,
    "treatment_tolerance": 0.20,
    "lab_trajectory": 0.20,
    "disease_stability": 0.15,
    "care_intensity": 0.15,
}


async def compute_outcome(patient_id: int) -> dict[str, float | None]:
    """Compute all five trajectory sub-scores for a patient.

    Returns dict with keys: tumor_response, treatment_tolerance,
    lab_trajectory, disease_stability, care_intensity, composite.
    """
    scores: dict[str, float | None] = {}

    async with get_session() as session:
        scores["tumor_response"] = await _tumor_response(session, patient_id)
        scores["treatment_tolerance"] = await _treatment_tolerance(session, patient_id)
        scores["lab_trajectory"] = await _lab_trajectory(session, patient_id)
        scores["disease_stability"] = await _disease_stability(session, patient_id)
        scores["care_intensity"] = await _care_intensity(session, patient_id)

    # Composite = weighted sum of available scores
    available = {k: v for k, v in scores.items() if v is not None}
    if available:
        total_weight = sum(OUTCOME_WEIGHTS[k] for k in available)
        if total_weight > 0:
            scores["composite"] = round(
                sum(v * OUTCOME_WEIGHTS[k] / total_weight for k, v in available.items()),
                4,
            )
        else:
            scores["composite"] = None
    else:
        scores["composite"] = None

    return scores


async def _tumor_response(session: Any, patient_id: int) -> float | None:
    """RECIST category + volume change adjustment. Clamp to [0, 1]."""
    result = await session.execute(
        text("""
            SELECT im.measurement_type, im.value_numeric,
                   iseg.volume_mm3
            FROM clinical.imaging_studies ist
            LEFT JOIN clinical.imaging_measurements im ON im.imaging_study_id = ist.id
            LEFT JOIN clinical.imaging_segmentations iseg ON iseg.imaging_study_id = ist.id
            WHERE ist.patient_id = :pid
            ORDER BY ist.study_date DESC
        """),
        {"pid": patient_id},
    )
    rows = result.fetchall()
    if not rows:
        return None

    # Simple RECIST mapping — find best response
    recist_map = {"CR": 1.0, "PR": 0.75, "SD": 0.5, "PD": 0.0}
    best = 0.0
    for row in rows:
        if row.measurement_type == "RECIST" and row.value_numeric is not None:
            # Map string-like values
            for key, val in recist_map.items():
                if val > best:
                    best = val

    return round(max(0.0, min(1.0, best)), 4)


async def _treatment_tolerance(session: Any, patient_id: int) -> float | None:
    """Drug era completion ratio."""
    result = await session.execute(
        text("""
            SELECT drug_name, era_start, era_end, gap_days
            FROM clinical.drug_eras
            WHERE patient_id = :pid AND era_start IS NOT NULL
        """),
        {"pid": patient_id},
    )
    eras = result.fetchall()
    if not eras:
        return None

    completion_ratios = []
    for era in eras:
        if era.era_start and era.era_end:
            days = (era.era_end - era.era_start).days
            # Simple heuristic: longer era = better tolerance
            completion_ratios.append(min(days / 180.0, 1.0))

    if not completion_ratios:
        return None

    return round(sum(completion_ratios) / len(completion_ratios), 4)


async def _lab_trajectory(session: Any, patient_id: int) -> float | None:
    """Key markers trending toward normal. Simplified: proportion in range."""
    result = await session.execute(
        text("""
            SELECT measurement_name, value_numeric, reference_range_low, reference_range_high
            FROM clinical.measurements
            WHERE patient_id = :pid AND value_numeric IS NOT NULL
            ORDER BY measured_at DESC
            LIMIT 20
        """),
        {"pid": patient_id},
    )
    measurements = result.fetchall()
    if not measurements:
        return None

    in_range = 0
    total = 0
    for m in measurements:
        if m.reference_range_low is not None and m.reference_range_high is not None:
            total += 1
            if m.reference_range_low <= m.value_numeric <= m.reference_range_high:
                in_range += 1

    if total == 0:
        return 0.5  # no reference ranges available

    return round(in_range / total, 4)


async def _disease_stability(session: Any, patient_id: int) -> float | None:
    """Fewer active/new conditions = higher stability."""
    result = await session.execute(
        text("""
            SELECT status, COUNT(*) as cnt
            FROM clinical.conditions
            WHERE patient_id = :pid
            GROUP BY status
        """),
        {"pid": patient_id},
    )
    rows = result.fetchall()
    if not rows:
        return None

    status_counts = {row.status: row.cnt for row in rows}
    total = sum(status_counts.values())
    active = status_counts.get("active", 0)
    resolved = status_counts.get("resolved", 0)

    if total == 0:
        return None

    return round((resolved + 0.5 * (total - active - resolved)) / total, 4)


async def _care_intensity(session: Any, patient_id: int) -> float | None:
    """Lower care intensity = better. Score = 1 - normalized_intensity."""
    result = await session.execute(
        text("""
            SELECT visit_type, COUNT(*) as cnt
            FROM clinical.visits
            WHERE patient_id = :pid
            GROUP BY visit_type
        """),
        {"pid": patient_id},
    )
    rows = result.fetchall()
    if not rows:
        return None

    type_counts = {row.visit_type: row.cnt for row in rows}
    emergency = type_counts.get("emergency", 0)
    inpatient = type_counts.get("inpatient", 0)
    outpatient = type_counts.get("outpatient", 0)

    # Weighted intensity score (higher = more intensive care)
    intensity = emergency * 3 + inpatient * 2 + outpatient * 0.5
    # Normalize: typical patient might have intensity ~5
    normalized = min(intensity / 10.0, 1.0)

    return round(1.0 - normalized, 4)
```

- [ ] **Step 2: Create fingerprint_explainer.py**

```python
"""Generate natural language similarity explanations using Ollama."""

import logging
from typing import Any

from sqlalchemy import text

from app.db import get_session
from app.services.ollama_client import generate_concept_mapping

logger = logging.getLogger(__name__)


async def explain_similarity(
    query_patient_id: int,
    similar_patient_ids: list[int],
) -> list[str | None]:
    """Generate a brief explanation for each similar patient pair.

    Returns a list of explanation strings (one per similar patient).
    """
    explanations: list[str | None] = []

    async with get_session() as session:
        query_context = await _get_patient_context(session, query_patient_id)

        for pid in similar_patient_ids:
            try:
                similar_context = await _get_patient_context(session, pid)
                explanation = await _generate_explanation(query_context, similar_context)
                explanations.append(explanation)
            except Exception as exc:
                logger.warning("Explanation failed for patient %d: %s", pid, exc)
                explanations.append(None)

    return explanations


async def _get_patient_context(session: Any, patient_id: int) -> dict[str, Any]:
    """Fetch key clinical facts for explanation generation."""
    # Conditions
    result = await session.execute(
        text("SELECT concept_name, domain, status FROM clinical.conditions WHERE patient_id = :pid LIMIT 5"),
        {"pid": patient_id},
    )
    conditions = [{"name": r.concept_name, "domain": r.domain, "status": r.status} for r in result.fetchall()]

    # Key variants
    result = await session.execute(
        text("SELECT gene, variant, clinical_significance FROM clinical.genomic_variants WHERE patient_id = :pid ORDER BY clinical_significance LIMIT 5"),
        {"pid": patient_id},
    )
    variants = [{"gene": r.gene, "variant": r.variant, "significance": r.clinical_significance} for r in result.fetchall()]

    # Top medications
    result = await session.execute(
        text("SELECT drug_name, status FROM clinical.medications WHERE patient_id = :pid LIMIT 5"),
        {"pid": patient_id},
    )
    medications = [{"drug": r.drug_name, "status": r.status} for r in result.fetchall()]

    return {
        "patient_id": patient_id,
        "conditions": conditions,
        "variants": variants,
        "medications": medications,
    }


async def _generate_explanation(
    query: dict[str, Any],
    similar: dict[str, Any],
) -> str:
    """Use Ollama to generate a brief similarity explanation."""
    prompt = f"""Compare these two patients and explain why they are similar in 1-2 clinical sentences.
Focus on shared mutations, conditions, and treatments. Be concise and clinically relevant.

Patient A (query):
- Conditions: {', '.join(c['name'] for c in query['conditions'])}
- Variants: {', '.join(f"{v['gene']} {v['variant'] or ''} ({v['significance']})" for v in query['variants'])}
- Medications: {', '.join(m['drug'] for m in query['medications'])}

Patient B (similar):
- Conditions: {', '.join(c['name'] for c in similar['conditions'])}
- Variants: {', '.join(f"{v['gene']} {v['variant'] or ''} ({v['significance']})" for v in similar['variants'])}
- Medications: {', '.join(m['drug'] for m in similar['medications'])}

Explanation:"""

    try:
        result = await generate_concept_mapping(prompt, context="patient similarity explanation")
        explanation = result.get("mapping", result.get("result", str(result)))
        return explanation.strip()
    except Exception:
        # Fallback: deterministic text-based explanation
        shared_genes = {v["gene"] for v in query["variants"]} & {v["gene"] for v in similar["variants"]}
        shared_drugs = {m["drug"] for m in query["medications"]} & {m["drug"] for m in similar["medications"]}

        parts = []
        if shared_genes:
            parts.append(f"Shared mutations in {', '.join(shared_genes)}")
        if shared_drugs:
            parts.append(f"Both treated with {', '.join(shared_drugs)}")
        if not parts:
            parts.append("Similar clinical trajectory")

        return ". ".join(parts) + "."
```

- [ ] **Step 3: Commit**

```bash
git add ai/app/services/outcome_computer.py ai/app/services/fingerprint_explainer.py
git commit -m "feat: add outcome trajectory computer and similarity explainer services"
```

---

## Task 10: Python AI — FastAPI Router + Registration

**Files:**
- Create: `ai/app/routers/fingerprint.py`
- Modify: `ai/app/main.py`

- [ ] **Step 1: Create the fingerprint router**

```python
"""Fingerprint router — encoding, outcome computation, and explanation endpoints."""

import logging

from fastapi import APIRouter

from app.models.fingerprint import (
    ClinicalEncodeRequest,
    EncodeResponse,
    ExplainRequest,
    ExplainResponse,
    GenomicEncodeRequest,
    OutcomeComputeRequest,
    OutcomeComputeResponse,
    VolumetricEncodeRequest,
)
from app.services.fingerprint_encoder import encode_clinical, encode_genomic, encode_volumetric
from app.services.fingerprint_explainer import explain_similarity
from app.services.outcome_computer import compute_outcome

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/fingerprint", tags=["fingerprint"])


@router.post("/encode/genomic", response_model=EncodeResponse)
async def encode_genomic_endpoint(request: GenomicEncodeRequest) -> EncodeResponse:
    """Encode a patient's genomic profile into a 256-dim vector."""
    try:
        vector_str, confidence = await encode_genomic(
            patient_id=request.patient_id,
            variants=[v.model_dump() for v in request.variants],
        )
        return EncodeResponse(
            patient_id=request.patient_id,
            vector=vector_str,
            confidence=confidence,
            dimension="genomic",
        )
    except ValueError as exc:
        logger.warning("Genomic encoding failed: %s", exc)
        return EncodeResponse(
            patient_id=request.patient_id,
            vector="",
            confidence=0.0,
            dimension="genomic",
        )
    except Exception as exc:
        logger.error("Genomic encoding error: %s", exc)
        return EncodeResponse(
            patient_id=request.patient_id,
            vector="",
            confidence=0.0,
            dimension="genomic",
        )


@router.post("/encode/volumetric", response_model=EncodeResponse)
async def encode_volumetric_endpoint(request: VolumetricEncodeRequest) -> EncodeResponse:
    """Encode a patient's imaging/volumetric data into a 256-dim vector."""
    try:
        vector_str, confidence = await encode_volumetric(
            patient_id=request.patient_id,
            studies=[s.model_dump() for s in request.studies],
        )
        return EncodeResponse(
            patient_id=request.patient_id,
            vector=vector_str,
            confidence=confidence,
            dimension="volumetric",
        )
    except (ValueError, Exception) as exc:
        logger.error("Volumetric encoding error: %s", exc)
        return EncodeResponse(
            patient_id=request.patient_id,
            vector="",
            confidence=0.0,
            dimension="volumetric",
        )


@router.post("/encode/clinical", response_model=EncodeResponse)
async def encode_clinical_endpoint(request: ClinicalEncodeRequest) -> EncodeResponse:
    """Encode a patient's clinical trajectory into a 256-dim vector."""
    try:
        vector_str, confidence = await encode_clinical(
            patient_id=request.patient_id,
            conditions=[c.model_dump() for c in request.conditions],
            medications=[m.model_dump() for m in request.medications],
            drug_eras=[d.model_dump() for d in request.drug_eras],
            measurements=request.measurements,
            visits=[v.model_dump() for v in request.visits],
        )
        return EncodeResponse(
            patient_id=request.patient_id,
            vector=vector_str,
            confidence=confidence,
            dimension="clinical",
        )
    except (ValueError, Exception) as exc:
        logger.error("Clinical encoding error: %s", exc)
        return EncodeResponse(
            patient_id=request.patient_id,
            vector="",
            confidence=0.0,
            dimension="clinical",
        )


@router.post("/outcome/compute", response_model=OutcomeComputeResponse)
async def compute_outcome_endpoint(request: OutcomeComputeRequest) -> OutcomeComputeResponse:
    """Compute trajectory sub-scores for a patient."""
    try:
        scores = await compute_outcome(request.patient_id)
        return OutcomeComputeResponse(patient_id=request.patient_id, **scores)
    except Exception as exc:
        logger.error("Outcome computation error: %s", exc)
        return OutcomeComputeResponse(patient_id=request.patient_id)


@router.post("/explain", response_model=ExplainResponse)
async def explain_endpoint(request: ExplainRequest) -> ExplainResponse:
    """Generate natural language similarity explanations."""
    try:
        explanations = await explain_similarity(
            query_patient_id=request.query_patient_id,
            similar_patient_ids=request.similar_patient_ids,
        )
        return ExplainResponse(explanations=explanations)
    except Exception as exc:
        logger.error("Explanation generation error: %s", exc)
        return ExplainResponse(
            explanations=[None] * len(request.similar_patient_ids),
        )
```

- [ ] **Step 2: Register router in main.py**

Add to `ai/app/main.py`:

Import:
```python
from .routers.fingerprint import router as fingerprint_router
```

Registration (after the last `app.include_router` line):
```python
app.include_router(fingerprint_router, prefix="/api/ai")
```

- [ ] **Step 3: Verify routes**

Run: `cd /home/smudoshi/Github/Aurora/ai && python -c "from app.main import app; [print(r.path, r.methods) for r in app.routes if 'fingerprint' in str(r.path)]"`
Expected: 5 fingerprint routes listed.

- [ ] **Step 4: Commit**

```bash
git add ai/app/routers/fingerprint.py ai/app/main.py
git commit -m "feat: add fingerprint FastAPI router with encode, outcome, and explain endpoints"
```

---

## Task 11: Frontend — Types

**Files:**
- Create: `frontend/src/features/fingerprint/types/index.ts`

- [ ] **Step 1: Create TypeScript types**

```typescript
// ── Enums & Literals ────────────────────────────────────────────────

export type ClinicianRating = 'excellent' | 'good' | 'mixed' | 'poor' | 'failure';
export type SearchContext = 'point_of_care' | 'tumor_board' | 'research';
export type WeightConfigType = 'preset' | 'learned' | 'custom';

// ── Fingerprint ─────────────────────────────────────────────────────

export interface DimensionState {
  genomic: boolean;
  volumetric: boolean;
  clinical: boolean;
}

export interface DimensionConfidence {
  genomic: number | null;
  volumetric: number | null;
  clinical: number | null;
}

export interface DimensionTimestamps {
  genomic: string | null;
  volumetric: string | null;
  clinical: string | null;
}

export interface PatientFingerprint {
  patient_id: number;
  has_fingerprint: boolean;
  dimensions: DimensionState;
  confidence: DimensionConfidence;
  encoded_at: DimensionTimestamps;
  encoder_version: string;
  dimension_count: number;
}

// ── Similarity Search ───────────────────────────────────────────────

export interface DimensionWeights {
  genomic: number;
  volumetric: number;
  clinical: number;
}

export interface SimilarPatientResult {
  patient_id: number;
  composite_score: number;
  genomic_similarity: number | null;
  volumetric_similarity: number | null;
  clinical_similarity: number | null;
  dimensions_matched: string[];
  explanation: string | null;
  patient: {
    id: number;
    mrn: string;
    first_name: string;
    last_name: string;
    sex: string;
    date_of_birth: string;
    primary_conditions: string[];
  } | null;
  outcome: {
    composite_score: number | null;
    clinician_rating: ClinicianRating | null;
    decision_tags: string[];
    hindsight_note: string | null;
    sub_scores: Record<string, number | null>;
  } | null;
}

export interface SearchMeta {
  query_patient_id: number;
  weights_used: DimensionWeights;
  weights_customized: boolean;
  dimensions_available: boolean[];
  result_count: number;
}

export interface SimilaritySearchResponse {
  results: SimilarPatientResult[];
  meta: SearchMeta;
}

// ── Outcome ─────────────────────────────────────────────────────────

export interface OutcomeSubScores {
  tumor_response: number | null;
  treatment_tolerance: number | null;
  lab_trajectory: number | null;
  disease_stability: number | null;
  care_intensity: number | null;
}

export interface OutcomeTrajectory {
  patient_id: number;
  has_outcome: boolean;
  computed: {
    composite_score: number | null;
    sub_scores: OutcomeSubScores;
    computed_at: string | null;
  } | null;
  assessment: {
    rating: ClinicianRating;
    factors: string | null;
    decision_tags: string[];
    hindsight_note: string | null;
    assessed_by: string | null;
    assessed_at: string | null;
  } | null;
}

export interface OutcomeAssessmentPayload {
  clinician_rating: ClinicianRating;
  clinician_factors?: string;
  decision_tags?: string[];
  hindsight_note?: string;
}

// ── Weight Config ───────────────────────────────────────────────────

export interface FusionWeightConfig {
  id: number;
  name: string;
  config_type: WeightConfigType;
  genomic_weight: number;
  volumetric_weight: number;
  clinical_weight: number;
  outcome_weights: Record<string, number> | null;
  is_active: boolean;
  trained_on_count: number | null;
}

// ── Stats ───────────────────────────────────────────────────────────

export interface FingerprintStats {
  total_fingerprinted: number;
  genomic_coverage: number;
  volumetric_coverage: number;
  clinical_coverage: number;
  full_coverage: number;
  outcomes_annotated: number;
}
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/fingerprint/types/index.ts
git commit -m "feat: add TypeScript types for fingerprint feature"
```

---

## Task 12: Frontend — API Client + Hooks

**Files:**
- Create: `frontend/src/features/fingerprint/api/fingerprintApi.ts`
- Create: `frontend/src/features/fingerprint/hooks/useFingerprint.ts`

- [ ] **Step 1: Create API client**

```typescript
import apiClient from "@/lib/api-client";
import type {
  DimensionWeights,
  FingerprintStats,
  FusionWeightConfig,
  OutcomeAssessmentPayload,
  OutcomeTrajectory,
  PatientFingerprint,
  SearchContext,
  SimilaritySearchResponse,
} from "../types";

const BASE = "/fingerprint";

// -- Search -----------------------------------------------------------------

export async function searchSimilar(params: {
  patient_id: number;
  weights?: Partial<DimensionWeights>;
  limit?: number;
  context?: SearchContext;
}): Promise<SimilaritySearchResponse> {
  const { data } = await apiClient.post(`${BASE}/search`, params);
  return data.data;
}

// -- Fingerprint ------------------------------------------------------------

export async function getFingerprint(patientId: number): Promise<PatientFingerprint> {
  const { data } = await apiClient.get(`${BASE}/patients/${patientId}`);
  return data.data;
}

export async function encodePatient(patientId: number): Promise<PatientFingerprint> {
  const { data } = await apiClient.post(`${BASE}/patients/${patientId}/encode`);
  return data.data;
}

export async function encodeBatch(patientIds: number[]): Promise<{ patient_id: number; dimension_count: number }[]> {
  const { data } = await apiClient.post(`${BASE}/encode-batch`, { patient_ids: patientIds });
  return data.data;
}

// -- Outcomes ---------------------------------------------------------------

export async function getOutcome(patientId: number): Promise<OutcomeTrajectory> {
  const { data } = await apiClient.get(`${BASE}/patients/${patientId}/outcome`);
  return data.data;
}

export async function assessOutcome(
  patientId: number,
  payload: OutcomeAssessmentPayload,
): Promise<{ patient_id: number; clinician_rating: string; assessed_at: string }> {
  const { data } = await apiClient.put(`${BASE}/patients/${patientId}/outcome/assess`, payload);
  return data.data;
}

// -- Weights ----------------------------------------------------------------

export async function listWeights(): Promise<FusionWeightConfig[]> {
  const { data } = await apiClient.get(`${BASE}/weights`);
  return data.data ?? data;
}

export async function getActiveWeights(): Promise<FusionWeightConfig> {
  const { data } = await apiClient.get(`${BASE}/weights/active`);
  return data.data;
}

// -- Stats ------------------------------------------------------------------

export async function getFingerprintStats(): Promise<FingerprintStats> {
  const { data } = await apiClient.get(`${BASE}/stats`);
  return data.data ?? data;
}
```

- [ ] **Step 2: Create hooks**

```typescript
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  assessOutcome,
  encodeBatch,
  encodePatient,
  getActiveWeights,
  getFingerprint,
  getFingerprintStats,
  getOutcome,
  listWeights,
  searchSimilar,
} from "../api/fingerprintApi";
import type { DimensionWeights, OutcomeAssessmentPayload, SearchContext } from "../types";

// -- Search -----------------------------------------------------------------

export function useSimilarPatients(params: {
  patient_id: number;
  weights?: Partial<DimensionWeights>;
  limit?: number;
  context?: SearchContext;
}) {
  return useQuery({
    queryKey: ["fingerprint", "search", params],
    queryFn: () => searchSimilar(params),
    enabled: params.patient_id > 0,
    refetchOnWindowFocus: false, // POST endpoint logs searches — avoid duplicate audit entries
    staleTime: 5 * 60 * 1000, // 5 minutes — similarity results don't change frequently
  });
}

// -- Fingerprint ------------------------------------------------------------

export function usePatientFingerprint(patientId: number) {
  return useQuery({
    queryKey: ["fingerprint", "patient", patientId],
    queryFn: () => getFingerprint(patientId),
    enabled: patientId > 0,
  });
}

export function useEncodePatient() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (patientId: number) => encodePatient(patientId),
    onSuccess: (_data, patientId) => {
      qc.invalidateQueries({ queryKey: ["fingerprint", "patient", patientId] });
      qc.invalidateQueries({ queryKey: ["fingerprint", "search"] });
      qc.invalidateQueries({ queryKey: ["fingerprint", "stats"] });
    },
  });
}

export function useEncodeBatch() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (patientIds: number[]) => encodeBatch(patientIds),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["fingerprint"] });
    },
  });
}

// -- Outcomes ---------------------------------------------------------------

export function usePatientOutcome(patientId: number) {
  return useQuery({
    queryKey: ["fingerprint", "outcome", patientId],
    queryFn: () => getOutcome(patientId),
    enabled: patientId > 0,
  });
}

export function useAssessOutcome() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ patientId, payload }: { patientId: number; payload: OutcomeAssessmentPayload }) =>
      assessOutcome(patientId, payload),
    onSuccess: (_data, { patientId }) => {
      qc.invalidateQueries({ queryKey: ["fingerprint", "outcome", patientId] });
      qc.invalidateQueries({ queryKey: ["fingerprint", "search"] });
    },
  });
}

// -- Weights ----------------------------------------------------------------

export function useWeightPresets() {
  return useQuery({
    queryKey: ["fingerprint", "weights"],
    queryFn: listWeights,
  });
}

export function useActiveWeights() {
  return useQuery({
    queryKey: ["fingerprint", "weights", "active"],
    queryFn: getActiveWeights,
  });
}

// -- Stats ------------------------------------------------------------------

export function useFingerprintStats() {
  return useQuery({
    queryKey: ["fingerprint", "stats"],
    queryFn: getFingerprintStats,
  });
}
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/features/fingerprint/api/fingerprintApi.ts \
        frontend/src/features/fingerprint/hooks/useFingerprint.ts
git commit -m "feat: add fingerprint API client and TanStack Query hooks"
```

---

## Task 13: Frontend — UI Components

**Files:**
- Create all components listed in File Map under `frontend/src/features/fingerprint/components/`

This is the largest task. Each component should be built as a focused, self-contained unit. The implementation agent should:

- [ ] **Step 1: Create `OutcomeBadge.tsx`** — Small color-coded badge for clinician ratings. Maps: excellent→green, good→lime, mixed→yellow, poor→orange, failure→red. Props: `rating: ClinicianRating | null`.

- [ ] **Step 2: Create `DimensionBar.tsx`** — Horizontal progress bar showing per-dimension similarity (0-1). Props: `label: string`, `value: number | null`, `color: string`. Renders a labeled bar with numeric value.

- [ ] **Step 3: Create `DecisionTagChips.tsx`** — Toggleable chip group for decision point tags. Props: `tags: string[]`, `selected: string[]`, `onChange: (tags: string[]) => void`, `allowCustom?: boolean`. Default tag set: drug-switch, dose-reduction, surgical-candidate, immunotherapy-ae, palliative-transition, complete-response.

- [ ] **Step 4: Create `FingerprintBanner.tsx`** — Status banner at top of Similar Patients tab. Uses `usePatientFingerprint(patientId)` hook. Shows dimension availability (3 colored indicators), confidence scores, encoding freshness, and an "Encode" button that triggers re-encoding.

- [ ] **Step 5: Create `WeightControls.tsx`** — Preset buttons + three range sliders. Uses `useWeightPresets()` hook. Props: `weights: DimensionWeights`, `onChange: (weights: DimensionWeights) => void`. Preset buttons load saved configs; Custom mode enables sliders. Sliders auto-normalize to sum to 1.0.

- [ ] **Step 6: Create `SimilarPatientCard.tsx`** — Result card for one similar patient. Props: `result: SimilarPatientResult`. Shows: patient demographics, diagnosis, composite score (large), three DimensionBars, OutcomeBadge, explanation text, cautionary flag if outcome is poor/failure.

- [ ] **Step 7: Create `OutcomeSidebar.tsx`** — Right sidebar with aggregated intelligence. Props: `results: SimilarPatientResult[]`. Shows: outcome distribution stacked bar, Abby's Insight (synthesized narrative from results), treatment response rates ("what worked"), aggregated hindsight notes.

- [ ] **Step 8: Create `OutcomeAssessmentModal.tsx`** — Modal dialog for clinician outcome assessment. Uses `useAssessOutcome()` mutation. Fields: rating selector (5 buttons), DecisionTagChips, key factors textarea, hindsight note textarea, Save/Cancel buttons.

- [ ] **Step 9: Create `SimilarPatientsTab.tsx`** — Main container component. Uses `useSimilarPatients()`, `usePatientFingerprint()`. Orchestrates: FingerprintBanner, WeightControls, list of SimilarPatientCards (left), OutcomeSidebar (right). Manages weight state, passes to search hook. Two-column layout (70/30).

- [ ] **Step 10: Integrate into patient profile**

Add "Similar Patients" tab to the patient profile page's tab navigation. The tab renders `<SimilarPatientsTab patientId={patientId} />`. Find the existing tab bar in the patient profile feature and add alongside existing tabs (Overview, Genomics, Imaging, Timeline, Tumor Board).

- [ ] **Step 11: Build and verify**

Run: `cd /home/smudoshi/Github/Aurora/frontend && npx tsc --noEmit`
Expected: No TypeScript errors.

Run: `cd /home/smudoshi/Github/Aurora/frontend && npm run build`
Expected: Build succeeds.

- [ ] **Step 12: Commit**

```bash
git add frontend/src/features/fingerprint/
git commit -m "feat: add Similar Patients tab with fingerprint UI components"
```

---

## Task 14: Golden Cohort — JSON Templates + Seeder

**Files:**
- Create: `backend/database/data/golden-cohort/` directory
- Create: `backend/database/data/golden-cohort/nsclc.json` (5 patients)
- Create: `backend/database/data/golden-cohort/rcc.json` (5 patients)
- Create: `backend/database/data/golden-cohort/breast.json` (5 patients)
- Create: `backend/database/data/golden-cohort/pdac.json` (5 patients)
- Create: `backend/database/seeders/GoldenCohortSeeder.php`

- [ ] **Step 1: Create JSON template for one cancer type (NSCLC)**

Each patient JSON includes: demographics, conditions, medications, drug_eras, genomic_variants, imaging_studies (with measurements and segmentations), measurements (labs), visits, procedures, clinical_notes, condition_eras, outcome_trajectory, and gene_drug_interactions.

Follow the patient definitions from the spec (Section 6):
- GC-NSCLC-01: BRAF V600E, Pembrolizumab → CR, Excellent
- GC-NSCLC-02: BRAF V600E + TP53, Dabrafenib+Trametinib → PR, Good
- GC-NSCLC-03: EGFR L858R, Osimertinib → PR, Good
- GC-NSCLC-04: KRAS G12C, Sotorasib → Mixed
- GC-NSCLC-05: BRAF V600E, Carboplatin+Pemetrexed → PD, Poor

Each patient needs 8-15 variants, 2-4 imaging studies with segmentations, 6-10 lab measurements, 3-5 visits, full medication eras, and pre-seeded outcome annotations.

The implementation agent should use the LLM (or handcraft) clinically plausible data following the data density requirements from the spec.

- [ ] **Step 2: Create JSON templates for remaining cancer types** (RCC, Breast, PDAC) — same structure, following spec definitions.

- [ ] **Step 3: Create GoldenCohortSeeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\Condition;
use App\Models\Clinical\ConditionEra;
use App\Models\Clinical\DrugEra;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\ImagingMeasurement;
use App\Models\Clinical\ImagingSegmentation;
use App\Models\Clinical\ImagingStudy;
use App\Models\Clinical\Measurement;
use App\Models\Clinical\Medication;
use App\Models\Clinical\OutcomeTrajectory;
use App\Models\Clinical\Procedure;
use App\Models\Clinical\Visit;
use Illuminate\Database\Seeder;

class GoldenCohortSeeder extends Seeder
{
    public function run(): void
    {
        $files = glob(database_path('data/golden-cohort/*.json'));

        foreach ($files as $file) {
            $patients = json_decode(file_get_contents($file), true);

            foreach ($patients as $patientData) {
                $this->seedPatient($patientData);
            }
        }

        $this->command->info('Golden cohort seeded: ' . ClinicalPatient::where('source_type', 'golden_cohort')->count() . ' patients.');
    }

    private function seedPatient(array $data): void
    {
        // Upsert patient by MRN (idempotent)
        $patient = ClinicalPatient::updateOrCreate(
            ['mrn' => $data['mrn']],
            array_merge($data['demographics'], ['source_type' => 'golden_cohort'])
        );

        // Seed each data layer
        $this->seedConditions($patient, $data['conditions'] ?? []);
        $this->seedMedications($patient, $data['medications'] ?? []);
        $this->seedDrugEras($patient, $data['drug_eras'] ?? []);
        $this->seedVariants($patient, $data['genomic_variants'] ?? []);
        $this->seedImagingStudies($patient, $data['imaging_studies'] ?? []);
        $this->seedMeasurements($patient, $data['measurements'] ?? []);
        $this->seedVisits($patient, $data['visits'] ?? []);
        $this->seedOutcome($patient, $data['outcome_trajectory'] ?? null);
    }

    private function seedConditions(ClinicalPatient $patient, array $conditions): void
    {
        foreach ($conditions as $c) {
            Condition::updateOrCreate(
                ['patient_id' => $patient->id, 'concept_name' => $c['concept_name'], 'source_type' => 'golden_cohort'],
                $c
            );
        }
    }

    private function seedMedications(ClinicalPatient $patient, array $medications): void
    {
        foreach ($medications as $m) {
            Medication::updateOrCreate(
                ['patient_id' => $patient->id, 'drug_name' => $m['drug_name'], 'start_date' => $m['start_date'] ?? null, 'source_type' => 'golden_cohort'],
                $m
            );
        }
    }

    private function seedDrugEras(ClinicalPatient $patient, array $eras): void
    {
        foreach ($eras as $e) {
            DrugEra::updateOrCreate(
                ['patient_id' => $patient->id, 'drug_name' => $e['drug_name'], 'era_start' => $e['era_start']],
                $e
            );
        }
    }

    private function seedVariants(ClinicalPatient $patient, array $variants): void
    {
        foreach ($variants as $v) {
            GenomicVariant::updateOrCreate(
                ['patient_id' => $patient->id, 'gene' => $v['gene'], 'variant' => $v['variant'] ?? null, 'source_type' => 'golden_cohort'],
                $v
            );
        }
    }

    private function seedImagingStudies(ClinicalPatient $patient, array $studies): void
    {
        foreach ($studies as $s) {
            $study = ImagingStudy::updateOrCreate(
                ['patient_id' => $patient->id, 'study_uid' => $s['study_uid'], 'source_type' => 'golden_cohort'],
                $s['study']
            );

            foreach ($s['measurements'] ?? [] as $m) {
                ImagingMeasurement::updateOrCreate(
                    ['imaging_study_id' => $study->id, 'measurement_type' => $m['measurement_type'], 'measured_at' => $m['measured_at'] ?? null],
                    $m
                );
            }

            foreach ($s['segmentations'] ?? [] as $seg) {
                ImagingSegmentation::updateOrCreate(
                    ['imaging_study_id' => $study->id, 'segmentation_uid' => $seg['segmentation_uid']],
                    $seg
                );
            }
        }
    }

    private function seedMeasurements(ClinicalPatient $patient, array $measurements): void
    {
        foreach ($measurements as $m) {
            Measurement::updateOrCreate(
                ['patient_id' => $patient->id, 'measurement_name' => $m['measurement_name'], 'measured_at' => $m['measured_at'], 'source_type' => 'golden_cohort'],
                $m
            );
        }
    }

    private function seedVisits(ClinicalPatient $patient, array $visits): void
    {
        foreach ($visits as $v) {
            Visit::updateOrCreate(
                ['patient_id' => $patient->id, 'visit_type' => $v['visit_type'], 'admission_date' => $v['admission_date'], 'source_type' => 'golden_cohort'],
                $v
            );
        }
    }

    private function seedOutcome(ClinicalPatient $patient, ?array $outcome): void
    {
        if (! $outcome) {
            return;
        }

        OutcomeTrajectory::updateOrCreate(
            ['patient_id' => $patient->id],
            array_merge($outcome, ['computed_at' => now()])
        );
    }
}
```

- [ ] **Step 4: Register seeder**

Add to `DatabaseSeeder.php`:
```php
$this->call(GoldenCohortSeeder::class);
```

- [ ] **Step 5: Run the seeder**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan db:seed --class=GoldenCohortSeeder`
Expected: "Golden cohort seeded: 20 patients."

- [ ] **Step 6: Encode all golden cohort patients**

Use the batch encode API endpoint (or artisan command) to generate fingerprints for all 20 patients.

- [ ] **Step 7: Commit**

```bash
git add backend/database/data/golden-cohort/ backend/database/seeders/GoldenCohortSeeder.php backend/database/seeders/DatabaseSeeder.php
git commit -m "feat: add golden cohort seeder with 20 synthetic patients across 4 cancer types"
```

---

## Task 15: Integration Testing + Deploy

- [ ] **Step 1: Test the full flow end-to-end**

1. Login as admin@acumenus.net
2. Pick a golden cohort patient
3. Call `POST /api/fingerprint/patients/{id}/encode` to generate fingerprint
4. Call `POST /api/fingerprint/search` with that patient's ID
5. Verify results return with composite scores, dimensional breakdowns, patient demographics, and outcome data
6. Call `PUT /api/fingerprint/patients/{id}/outcome/assess` to submit a clinician assessment
7. Verify the assessment appears in subsequent search results

- [ ] **Step 2: Build frontend for production**

Run: `cd /home/smudoshi/Github/Aurora/frontend && npm run build && rm -rf ../backend/public/build && cp -r dist ../backend/public/build`
Expected: Build succeeds, assets deployed.

- [ ] **Step 3: Verify at aurora.acumenus.net**

Open the patient profile for a golden cohort patient. Navigate to the "Similar Patients" tab. Verify the fingerprint banner, weight controls, and result cards render correctly.

- [ ] **Step 4: Final commit**

```bash
git add backend/ ai/ frontend/src/features/fingerprint/
git commit -m "feat: molecular-genomic-volumetric fingerprinting v1 complete"
```
