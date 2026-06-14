# Rare-Disease Diagnostic Odyssey Foundation — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a first-class rare-disease "diagnostic odyssey" to Aurora — a patient-linked case with an explicit, auditable state machine, deep HPO phenotyping (with onset/severity/negation), and GA4GH Phenopackets v2 export.

**Architecture:** Pure additive Laravel backend on the existing multi-schema Postgres (`app` schema, FKs to `clinical.patients` / `app.users`). A state-machine service governs odyssey transitions and derives Phenopackets `progressStatus`; an exporter service emits v2-shaped Phenopacket JSON. No existing tables, auth, or oncology code are touched. Frontend, HPO term autocomplete, and Phenopacket *import* are deferred to Plan 2; variant model + reanalysis loop + Matchmaker Exchange to Plans 3–5.

**Tech Stack:** Laravel 11 / PHP 8.4, PostgreSQL 16 (schemas `app`, `clinical`), Pest 3 (feature + unit), Spatie RBAC (existing), Pint (PSR-12). Tests run in Docker against `aurora_test` with `DatabaseTruncation` (already configured in `tests/Pest.php`).

**This is the parent strategy's §5 lead initiative — first of five sequential plans:**
1. **Diagnostic Odyssey Foundation** *(this plan)* — state machine + HPO phenotyping + Phenopackets v2 export.
2. Frontend odyssey UI + HPO term autocomplete (ontology.jax.org proxy) + Phenopacket import.
3. Variant model: GA4GH VRS canonicalization + ClinGen CAID + ACMG/AMP points engine.
4. Automated reanalysis loop + knowledge-base change alerting.
5. Matchmaker Exchange node + Beacon v2 endpoint.

---

## File Structure

**Backend (create):**
- `backend/database/migrations/2026_06_14_010001_create_diagnostic_odyssey_tables.php` — `app.diagnostic_odysseys` + `app.odyssey_status_transitions`
- `backend/database/migrations/2026_06_14_010002_create_phenotype_features_table.php` — `app.phenotype_features`
- `backend/app/Models/DiagnosticOdyssey.php`
- `backend/app/Models/OdysseyStatusTransition.php`
- `backend/app/Models/PhenotypeFeature.php`
- `backend/database/factories/DiagnosticOdysseyFactory.php`
- `backend/database/factories/PhenotypeFeatureFactory.php`
- `backend/app/Services/RareDisease/OdysseyStateMachine.php` — allowed transitions + progressStatus derivation
- `backend/app/Services/RareDisease/InvalidOdysseyTransitionException.php`
- `backend/app/Services/RareDisease/OdysseyService.php` — create + transition (audited)
- `backend/app/Services/RareDisease/PhenopacketExporter.php` — v2 JSON exporter
- `backend/app/Http/Requests/StoreOdysseyRequest.php`
- `backend/app/Http/Requests/TransitionOdysseyRequest.php`
- `backend/app/Http/Requests/StorePhenotypeFeatureRequest.php`
- `backend/app/Http/Controllers/DiagnosticOdysseyController.php`
- `backend/app/Http/Controllers/PhenotypeFeatureController.php`
- `backend/tests/Unit/Services/OdysseyStateMachineTest.php`
- `backend/tests/Unit/Services/OdysseyServiceTest.php`
- `backend/tests/Unit/Services/PhenopacketExporterTest.php`
- `backend/tests/Feature/Api/DiagnosticOdysseyTest.php`
- `backend/tests/Feature/Api/PhenotypeFeatureTest.php`

**Backend (modify):**
- `backend/routes/api.php` — add 8 routes inside the existing `auth:sanctum` group

**Conventions to follow (verified in repo):**
- Schema-qualified table names on the default connection, e.g. `Schema::create('app.diagnostic_odysseys', …)`; FKs to `clinical.patients` and `app.users` (see `2026_03_22_200001_create_patient_flags_table.php`).
- Controllers return `App\Http\Helpers\ApiResponse::success(...)` / `::error(...)`.
- Models in `App\Models\*` auto-resolve to `Database\Factories\*Factory` (no `newFactory()` needed).
- Feature tests: `beforeEach` seeds `Database\Seeders\SuperuserSeeder`, then `actingAs($this->user, 'sanctum')` (see `tests/Feature/Api/CaseControllerTest.php`).
- Pint after every PHP edit: `docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"`.

---

### Task 1: Migration — odyssey + transition tables

**Files:**
- Create: `backend/database/migrations/2026_06_14_010001_create_diagnostic_odyssey_tables.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.diagnostic_odysseys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('case_id')->nullable();
            $table->string('title');
            $table->string('status')->default('referral'); // referral, phenotyping, testing, prioritization, mdt_review, matchmaking, diagnosed, reanalysis, closed
            $table->string('progress_status')->default('in_progress'); // Phenopackets: in_progress, solved, unsolved
            $table->text('referral_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('solved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->onDelete('cascade');
            $table->foreign('case_id')->references('id')->on('app.cases')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('app.users');

            $table->index('patient_id');
            $table->index('status');
        });

        Schema::create('app.odyssey_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odyssey_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->unsignedBigInteger('actor_id');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('odyssey_id')->references('id')->on('app.diagnostic_odysseys')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('app.users');

            $table->index('odyssey_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.odyssey_status_transitions');
        Schema::dropIfExists('app.diagnostic_odysseys');
    }
};
```

- [ ] **Step 2: Run the migration against the test DB to verify it applies**

Run: `docker compose exec -T php php artisan migrate --database=pgsql --env=testing`
Expected: `Migrating: 2026_06_14_010001_create_diagnostic_odyssey_tables` … `DONE`. No errors.

- [ ] **Step 3: Commit**

```bash
git add backend/database/migrations/2026_06_14_010001_create_diagnostic_odyssey_tables.php
git commit -m "feat(rare-disease): add diagnostic odyssey + transition tables"
```

---

### Task 2: Migration — phenotype features table

**Files:**
- Create: `backend/database/migrations/2026_06_14_010002_create_phenotype_features_table.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.phenotype_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odyssey_id');
            $table->string('hpo_id');            // e.g. "HP:0001250"
            $table->string('hpo_label');
            $table->boolean('excluded')->default(false); // negation: phenotype explicitly absent
            $table->string('onset_hpo_id')->nullable();
            $table->string('severity_hpo_id')->nullable();
            $table->string('frequency_hpo_id')->nullable();
            $table->string('evidence')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamps();

            $table->foreign('odyssey_id')->references('id')->on('app.diagnostic_odysseys')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('app.users');

            $table->unique(['odyssey_id', 'hpo_id']);
            $table->index('odyssey_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.phenotype_features');
    }
};
```

- [ ] **Step 2: Run the migration to verify it applies**

Run: `docker compose exec -T php php artisan migrate --database=pgsql --env=testing`
Expected: `Migrating: 2026_06_14_010002_create_phenotype_features_table` … `DONE`.

- [ ] **Step 3: Commit**

```bash
git add backend/database/migrations/2026_06_14_010002_create_phenotype_features_table.php
git commit -m "feat(rare-disease): add phenotype_features table"
```

---

### Task 3: Models + factories

**Files:**
- Create: `backend/app/Models/DiagnosticOdyssey.php`
- Create: `backend/app/Models/OdysseyStatusTransition.php`
- Create: `backend/app/Models/PhenotypeFeature.php`
- Create: `backend/database/factories/DiagnosticOdysseyFactory.php`
- Create: `backend/database/factories/PhenotypeFeatureFactory.php`
- Test: `backend/tests/Feature/FactorySmokeTest.php` (modify — add 2 cases)

- [ ] **Step 1: Write the failing factory smoke test** (append inside the existing file's top-level)

Add to `backend/tests/Feature/FactorySmokeTest.php`:

```php
it('creates a DiagnosticOdyssey via factory', function () {
    $odyssey = \App\Models\DiagnosticOdyssey::factory()->create();
    expect($odyssey->id)->toBeInt();
    expect($odyssey->status)->toBe('referral');
});

it('creates a PhenotypeFeature via factory', function () {
    $feature = \App\Models\PhenotypeFeature::factory()->create();
    expect($feature->id)->toBeInt();
    expect($feature->hpo_id)->toStartWith('HP:');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec -T php php artisan test --filter FactorySmokeTest`
Expected: FAIL — `Class "App\Models\DiagnosticOdyssey" not found`.

- [ ] **Step 3: Write `DiagnosticOdyssey` model**

`backend/app/Models/DiagnosticOdyssey.php`:

```php
<?php

namespace App\Models;

use App\Models\Clinical\ClinicalPatient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiagnosticOdyssey extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app.diagnostic_odysseys';

    protected $fillable = [
        'patient_id',
        'case_id',
        'title',
        'status',
        'progress_status',
        'referral_reason',
        'created_by',
        'solved_at',
    ];

    protected function casts(): array
    {
        return [
            'solved_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(ClinicalCase::class, 'case_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(OdysseyStatusTransition::class, 'odyssey_id');
    }

    public function phenotypeFeatures(): HasMany
    {
        return $this->hasMany(PhenotypeFeature::class, 'odyssey_id');
    }
}
```

- [ ] **Step 4: Write `OdysseyStatusTransition` model**

`backend/app/Models/OdysseyStatusTransition.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdysseyStatusTransition extends Model
{
    protected $table = 'app.odyssey_status_transitions';

    protected $fillable = [
        'odyssey_id',
        'from_status',
        'to_status',
        'actor_id',
        'note',
    ];

    public function odyssey(): BelongsTo
    {
        return $this->belongsTo(DiagnosticOdyssey::class, 'odyssey_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
```

- [ ] **Step 5: Write `PhenotypeFeature` model**

`backend/app/Models/PhenotypeFeature.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhenotypeFeature extends Model
{
    use HasFactory;

    protected $table = 'app.phenotype_features';

    protected $fillable = [
        'odyssey_id',
        'hpo_id',
        'hpo_label',
        'excluded',
        'onset_hpo_id',
        'severity_hpo_id',
        'frequency_hpo_id',
        'evidence',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'excluded' => 'boolean',
        ];
    }

    public function odyssey(): BelongsTo
    {
        return $this->belongsTo(DiagnosticOdyssey::class, 'odyssey_id');
    }
}
```

- [ ] **Step 6: Write factories**

`backend/database/factories/DiagnosticOdysseyFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\DiagnosticOdyssey;
use App\Models\User;
use Database\Factories\Clinical\ClinicalPatientFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiagnosticOdysseyFactory extends Factory
{
    protected $model = DiagnosticOdyssey::class;

    public function definition(): array
    {
        return [
            'patient_id' => ClinicalPatientFactory::new(),
            'title' => 'Undiagnosed multisystem disorder',
            'status' => 'referral',
            'progress_status' => 'in_progress',
            'referral_reason' => $this->faker->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
```

`backend/database/factories/PhenotypeFeatureFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhenotypeFeatureFactory extends Factory
{
    protected $model = PhenotypeFeature::class;

    public function definition(): array
    {
        return [
            'odyssey_id' => DiagnosticOdyssey::factory(),
            'hpo_id' => 'HP:0001250', // Seizure
            'hpo_label' => 'Seizure',
            'excluded' => false,
            'recorded_by' => User::factory(),
        ];
    }
}
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter FactorySmokeTest`
Expected: PASS (all smoke cases green).

- [ ] **Step 8: Pint, then commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Models/DiagnosticOdyssey.php backend/app/Models/OdysseyStatusTransition.php backend/app/Models/PhenotypeFeature.php backend/database/factories/DiagnosticOdysseyFactory.php backend/database/factories/PhenotypeFeatureFactory.php backend/tests/Feature/FactorySmokeTest.php
git commit -m "feat(rare-disease): add odyssey + phenotype models and factories"
```

---

### Task 4: OdysseyStateMachine service

**Files:**
- Create: `backend/app/Services/RareDisease/OdysseyStateMachine.php`
- Test: `backend/tests/Unit/Services/OdysseyStateMachineTest.php`

- [ ] **Step 1: Write the failing test**

`backend/tests/Unit/Services/OdysseyStateMachineTest.php`:

```php
<?php

use App\Services\RareDisease\OdysseyStateMachine;

beforeEach(function () {
    $this->machine = new OdysseyStateMachine;
});

it('allows referral to phenotyping', function () {
    expect($this->machine->canTransition('referral', 'phenotyping'))->toBeTrue();
});

it('rejects referral straight to diagnosed', function () {
    expect($this->machine->canTransition('referral', 'diagnosed'))->toBeFalse();
});

it('allows mdt_review to reanalysis', function () {
    expect($this->machine->canTransition('mdt_review', 'reanalysis'))->toBeTrue();
});

it('treats closed as terminal', function () {
    expect($this->machine->allowedFrom('closed'))->toBe([]);
});

it('derives solved progress status for diagnosed', function () {
    expect($this->machine->progressStatusFor('diagnosed'))->toBe('solved');
});

it('derives unsolved progress status for reanalysis', function () {
    expect($this->machine->progressStatusFor('reanalysis'))->toBe('unsolved');
});

it('derives in_progress for intermediate states', function () {
    expect($this->machine->progressStatusFor('testing'))->toBe('in_progress');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec -T php php artisan test --filter OdysseyStateMachineTest`
Expected: FAIL — `Class "App\Services\RareDisease\OdysseyStateMachine" not found`.

- [ ] **Step 3: Write the service**

`backend/app/Services/RareDisease/OdysseyStateMachine.php`:

```php
<?php

namespace App\Services\RareDisease;

class OdysseyStateMachine
{
    public const STATES = [
        'referral', 'phenotyping', 'testing', 'prioritization',
        'mdt_review', 'matchmaking', 'diagnosed', 'reanalysis', 'closed',
    ];

    private const TRANSITIONS = [
        'referral' => ['phenotyping'],
        'phenotyping' => ['testing', 'mdt_review'],
        'testing' => ['prioritization'],
        'prioritization' => ['mdt_review'],
        'mdt_review' => ['matchmaking', 'diagnosed', 'reanalysis', 'testing'],
        'matchmaking' => ['mdt_review', 'diagnosed', 'reanalysis'],
        'reanalysis' => ['mdt_review', 'diagnosed'],
        'diagnosed' => ['closed', 'reanalysis'],
        'closed' => [],
    ];

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /** @return string[] */
    public function allowedFrom(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public function progressStatusFor(string $to): string
    {
        return match ($to) {
            'diagnosed' => 'solved',
            'reanalysis' => 'unsolved',
            default => 'in_progress',
        };
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter OdysseyStateMachineTest`
Expected: PASS (7 passing).

- [ ] **Step 5: Pint, then commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/RareDisease/OdysseyStateMachine.php backend/tests/Unit/Services/OdysseyStateMachineTest.php
git commit -m "feat(rare-disease): add odyssey state machine service"
```

---

### Task 5: OdysseyService (create + audited transition)

**Files:**
- Create: `backend/app/Services/RareDisease/InvalidOdysseyTransitionException.php`
- Create: `backend/app/Services/RareDisease/OdysseyService.php`
- Test: `backend/tests/Unit/Services/OdysseyServiceTest.php`

- [ ] **Step 1: Write the failing test**

`backend/tests/Unit/Services/OdysseyServiceTest.php`:

```php
<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\User;
use App\Services\RareDisease\InvalidOdysseyTransitionException;
use App\Services\RareDisease\OdysseyService;
use App\Services\RareDisease\OdysseyStateMachine;

beforeEach(function () {
    $this->service = new OdysseyService(new OdysseyStateMachine);
    $this->user = User::factory()->create();
    $this->patient = ClinicalPatient::factory()->create();
});

it('creates an odyssey in referral with an initial transition row', function () {
    $odyssey = $this->service->create([
        'patient_id' => $this->patient->id,
        'title' => 'Undiagnosed ataxia',
    ], $this->user->id);

    expect($odyssey->status)->toBe('referral');
    expect($odyssey->progress_status)->toBe('in_progress');
    expect($odyssey->transitions()->count())->toBe(1);
    expect($odyssey->transitions()->first()->to_status)->toBe('referral');
});

it('transitions through allowed states and records audit rows', function () {
    $odyssey = $this->service->create([
        'patient_id' => $this->patient->id,
        'title' => 'Undiagnosed ataxia',
    ], $this->user->id);

    $odyssey = $this->service->transition($odyssey, 'phenotyping', $this->user->id, 'Started phenotyping');

    expect($odyssey->status)->toBe('phenotyping');
    expect($odyssey->transitions()->count())->toBe(2);
});

it('sets solved progress and solved_at when diagnosed', function () {
    $odyssey = $this->service->create([
        'patient_id' => $this->patient->id,
        'title' => 'Undiagnosed ataxia',
    ], $this->user->id);
    $odyssey = $this->service->transition($odyssey, 'phenotyping', $this->user->id);
    $odyssey = $this->service->transition($odyssey, 'mdt_review', $this->user->id);
    $odyssey = $this->service->transition($odyssey, 'diagnosed', $this->user->id);

    expect($odyssey->status)->toBe('diagnosed');
    expect($odyssey->progress_status)->toBe('solved');
    expect($odyssey->solved_at)->not->toBeNull();
});

it('throws on an illegal transition', function () {
    $odyssey = $this->service->create([
        'patient_id' => $this->patient->id,
        'title' => 'Undiagnosed ataxia',
    ], $this->user->id);

    $this->service->transition($odyssey, 'diagnosed', $this->user->id);
})->throws(InvalidOdysseyTransitionException::class);
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec -T php php artisan test --filter OdysseyServiceTest`
Expected: FAIL — `Class "App\Services\RareDisease\OdysseyService" not found`.

- [ ] **Step 3: Write the exception**

`backend/app/Services/RareDisease/InvalidOdysseyTransitionException.php`:

```php
<?php

namespace App\Services\RareDisease;

use RuntimeException;

class InvalidOdysseyTransitionException extends RuntimeException
{
    public function __construct(public string $from, public string $to)
    {
        parent::__construct("Illegal odyssey transition: {$from} → {$to}");
    }
}
```

- [ ] **Step 4: Write the service**

`backend/app/Services/RareDisease/OdysseyService.php`:

```php
<?php

namespace App\Services\RareDisease;

use App\Models\DiagnosticOdyssey;
use Illuminate\Support\Facades\DB;

class OdysseyService
{
    public function __construct(private OdysseyStateMachine $machine) {}

    public function create(array $data, int $actorId): DiagnosticOdyssey
    {
        return DB::transaction(function () use ($data, $actorId) {
            $odyssey = DiagnosticOdyssey::create([
                'patient_id' => $data['patient_id'],
                'case_id' => $data['case_id'] ?? null,
                'title' => $data['title'],
                'referral_reason' => $data['referral_reason'] ?? null,
                'status' => 'referral',
                'progress_status' => 'in_progress',
                'created_by' => $actorId,
            ]);

            $odyssey->transitions()->create([
                'from_status' => null,
                'to_status' => 'referral',
                'actor_id' => $actorId,
                'note' => 'Odyssey created',
            ]);

            return $odyssey;
        });
    }

    public function transition(DiagnosticOdyssey $odyssey, string $to, int $actorId, ?string $note = null): DiagnosticOdyssey
    {
        $from = $odyssey->status;

        if (! $this->machine->canTransition($from, $to)) {
            throw new InvalidOdysseyTransitionException($from, $to);
        }

        return DB::transaction(function () use ($odyssey, $from, $to, $actorId, $note) {
            $odyssey->update([
                'status' => $to,
                'progress_status' => $this->machine->progressStatusFor($to),
                'solved_at' => $to === 'diagnosed' ? now() : $odyssey->solved_at,
            ]);

            $odyssey->transitions()->create([
                'from_status' => $from,
                'to_status' => $to,
                'actor_id' => $actorId,
                'note' => $note,
            ]);

            return $odyssey->fresh(['transitions']);
        });
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter OdysseyServiceTest`
Expected: PASS (4 passing).

- [ ] **Step 6: Pint, then commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/RareDisease/InvalidOdysseyTransitionException.php backend/app/Services/RareDisease/OdysseyService.php backend/tests/Unit/Services/OdysseyServiceTest.php
git commit -m "feat(rare-disease): add odyssey service with audited transitions"
```

---

### Task 6: Form Requests

**Files:**
- Create: `backend/app/Http/Requests/StoreOdysseyRequest.php`
- Create: `backend/app/Http/Requests/TransitionOdysseyRequest.php`
- Create: `backend/app/Http/Requests/StorePhenotypeFeatureRequest.php`

- [ ] **Step 1: Write `StoreOdysseyRequest`**

`backend/app/Http/Requests/StoreOdysseyRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOdysseyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'referral_reason' => 'nullable|string|max:2000',
            'case_id' => 'nullable|integer|exists:app.cases,id',
        ];
    }
}
```

- [ ] **Step 2: Write `TransitionOdysseyRequest`**

`backend/app/Http/Requests/TransitionOdysseyRequest.php`:

```php
<?php

namespace App\Http\Requests;

use App\Services\RareDisease\OdysseyStateMachine;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionOdysseyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_status' => ['required', 'string', Rule::in(OdysseyStateMachine::STATES)],
            'note' => 'nullable|string|max:2000',
        ];
    }
}
```

- [ ] **Step 3: Write `StorePhenotypeFeatureRequest`**

`backend/app/Http/Requests/StorePhenotypeFeatureRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhenotypeFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hpo_id' => ['required', 'string', 'regex:/^HP:\d{7}$/'],
            'hpo_label' => 'required|string|max:255',
            'excluded' => 'sometimes|boolean',
            'onset_hpo_id' => ['nullable', 'string', 'regex:/^HP:\d{7}$/'],
            'severity_hpo_id' => ['nullable', 'string', 'regex:/^HP:\d{7}$/'],
            'frequency_hpo_id' => ['nullable', 'string', 'regex:/^HP:\d{7}$/'],
            'evidence' => 'nullable|string|max:255',
        ];
    }
}
```

- [ ] **Step 4: Pint, then commit** (no test alone; validated via Tasks 7–8)

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Http/Requests/StoreOdysseyRequest.php backend/app/Http/Requests/TransitionOdysseyRequest.php backend/app/Http/Requests/StorePhenotypeFeatureRequest.php
git commit -m "feat(rare-disease): add odyssey + phenotype form requests"
```

---

### Task 7: DiagnosticOdysseyController + routes

**Files:**
- Create: `backend/app/Http/Controllers/DiagnosticOdysseyController.php`
- Modify: `backend/routes/api.php` (inside the `auth:sanctum` group)
- Test: `backend/tests/Feature/Api/DiagnosticOdysseyTest.php`

- [ ] **Step 1: Write the failing feature test**

`backend/tests/Feature/Api/DiagnosticOdysseyTest.php`:

```php
<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
    $this->user = User::where('email', 'admin@acumenus.net')->first();
    $this->patient = ClinicalPatient::factory()->create();
});

describe('POST /api/patients/{patient}/odysseys', function () {
    it('creates an odyssey in referral', function () {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/patients/{$this->patient->id}/odysseys", [
                'title' => 'Undiagnosed myopathy',
                'referral_reason' => 'Progressive weakness, normal initial workup',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'referral')
            ->assertJsonPath('data.progress_status', 'in_progress');
    });

    it('requires a title', function () {
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/patients/{$this->patient->id}/odysseys", [])
            ->assertStatus(422);
    });

    it('requires authentication', function () {
        $this->postJson("/api/patients/{$this->patient->id}/odysseys", ['title' => 'x'])
            ->assertStatus(401);
    });
});

describe('GET /api/patients/{patient}/odysseys', function () {
    it('lists odysseys for a patient', function () {
        DiagnosticOdyssey::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/patients/{$this->patient->id}/odysseys");

        $response->assertStatus(200)->assertJsonPath('success', true);
        expect($response->json('data'))->toHaveCount(1);
    });
});

describe('POST /api/odysseys/{odyssey}/transition', function () {
    it('advances through an allowed transition', function () {
        $odyssey = DiagnosticOdyssey::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/odysseys/{$odyssey->id}/transition", [
                'to_status' => 'phenotyping',
                'note' => 'Begin deep phenotyping',
            ]);

        $response->assertStatus(200)->assertJsonPath('data.status', 'phenotyping');
    });

    it('rejects an illegal transition with 422', function () {
        $odyssey = DiagnosticOdyssey::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
            'status' => 'referral',
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/odysseys/{$odyssey->id}/transition", ['to_status' => 'diagnosed'])
            ->assertStatus(422);
    });
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec -T php php artisan test --filter DiagnosticOdysseyTest`
Expected: FAIL — route/controller not found (404 / class not found).

- [ ] **Step 3: Write the controller**

`backend/app/Http/Controllers/DiagnosticOdysseyController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StoreOdysseyRequest;
use App\Http\Requests\TransitionOdysseyRequest;
use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Services\RareDisease\InvalidOdysseyTransitionException;
use App\Services\RareDisease\OdysseyService;
use App\Services\RareDisease\OdysseyStateMachine;
use Illuminate\Http\JsonResponse;

class DiagnosticOdysseyController extends Controller
{
    public function __construct(
        private OdysseyService $service,
        private OdysseyStateMachine $machine,
    ) {}

    public function index(int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);

        $odysseys = $patientModel->hasMany(DiagnosticOdyssey::class, 'patient_id')
            ->withCount('phenotypeFeatures')
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success($odysseys);
    }

    public function store(StoreOdysseyRequest $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);

        $odyssey = $this->service->create([
            ...$request->validated(),
            'patient_id' => $patientModel->id,
        ], $request->user()->id);

        return ApiResponse::success($odyssey->load('transitions'), 'Created', 201);
    }

    public function show(int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::with(['transitions.actor:id,name', 'phenotypeFeatures'])
            ->findOrFail($odyssey);

        return ApiResponse::success([
            'odyssey' => $model,
            'allowed_transitions' => $this->machine->allowedFrom($model->status),
        ]);
    }

    public function transition(TransitionOdysseyRequest $request, int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::findOrFail($odyssey);

        try {
            $updated = $this->service->transition(
                $model,
                $request->validated()['to_status'],
                $request->user()->id,
                $request->validated()['note'] ?? null,
            );
        } catch (InvalidOdysseyTransitionException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($updated);
    }
}
```

- [ ] **Step 4: Add routes** — in `backend/routes/api.php`, inside the existing `Route::middleware('auth:sanctum')->group(function () { … });` block (place near the patient flag/task routes around line 93). Add:

```php
    // ── Rare Disease — Diagnostic Odyssey ──────────────────────────────
    Route::get('/patients/{patient}/odysseys', [\App\Http\Controllers\DiagnosticOdysseyController::class, 'index']);
    Route::post('/patients/{patient}/odysseys', [\App\Http\Controllers\DiagnosticOdysseyController::class, 'store']);
    Route::get('/odysseys/{odyssey}', [\App\Http\Controllers\DiagnosticOdysseyController::class, 'show']);
    Route::post('/odysseys/{odyssey}/transition', [\App\Http\Controllers\DiagnosticOdysseyController::class, 'transition']);
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter DiagnosticOdysseyTest`
Expected: PASS (6 passing).

- [ ] **Step 6: Pint, then commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Http/Controllers/DiagnosticOdysseyController.php backend/routes/api.php backend/tests/Feature/Api/DiagnosticOdysseyTest.php
git commit -m "feat(rare-disease): add diagnostic odyssey API (CRUD + transition)"
```

---

### Task 8: PhenotypeFeatureController + routes

**Files:**
- Create: `backend/app/Http/Controllers/PhenotypeFeatureController.php`
- Modify: `backend/routes/api.php` (inside `auth:sanctum` group)
- Test: `backend/tests/Feature/Api/PhenotypeFeatureTest.php`

- [ ] **Step 1: Write the failing feature test**

`backend/tests/Feature/Api/PhenotypeFeatureTest.php`:

```php
<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use App\Models\User;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\SuperuserSeeder']);
    $this->user = User::where('email', 'admin@acumenus.net')->first();
    $this->patient = ClinicalPatient::factory()->create();
    $this->odyssey = DiagnosticOdyssey::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);
});

it('adds an observed phenotype feature', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/phenotypes", [
            'hpo_id' => 'HP:0001250',
            'hpo_label' => 'Seizure',
            'severity_hpo_id' => 'HP:0012828',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.hpo_id', 'HP:0001250')
        ->assertJsonPath('data.excluded', false);
});

it('records an explicitly excluded (absent) phenotype', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/phenotypes", [
            'hpo_id' => 'HP:0001251',
            'hpo_label' => 'Ataxia',
            'excluded' => true,
        ]);

    $response->assertStatus(201)->assertJsonPath('data.excluded', true);
});

it('rejects a malformed HPO id', function () {
    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/phenotypes", [
            'hpo_id' => 'seizure',
            'hpo_label' => 'Seizure',
        ])->assertStatus(422);
});

it('lists phenotype features for an odyssey', function () {
    PhenotypeFeature::factory()->create([
        'odyssey_id' => $this->odyssey->id,
        'recorded_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/odysseys/{$this->odyssey->id}/phenotypes");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
});

it('deletes a phenotype feature', function () {
    $feature = PhenotypeFeature::factory()->create([
        'odyssey_id' => $this->odyssey->id,
        'recorded_by' => $this->user->id,
    ]);

    $this->actingAs($this->user, 'sanctum')
        ->deleteJson("/api/phenotypes/{$feature->id}")
        ->assertStatus(200);

    expect(PhenotypeFeature::find($feature->id))->toBeNull();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec -T php php artisan test --filter PhenotypeFeatureTest`
Expected: FAIL — route/controller not found.

- [ ] **Step 3: Write the controller**

`backend/app/Http/Controllers/PhenotypeFeatureController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StorePhenotypeFeatureRequest;
use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhenotypeFeatureController extends Controller
{
    public function index(int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::findOrFail($odyssey);

        return ApiResponse::success(
            $model->phenotypeFeatures()->orderBy('hpo_id')->get()
        );
    }

    public function store(StorePhenotypeFeatureRequest $request, int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::findOrFail($odyssey);

        $feature = $model->phenotypeFeatures()->create([
            ...$request->validated(),
            'excluded' => $request->boolean('excluded'),
            'recorded_by' => $request->user()->id,
        ]);

        return ApiResponse::success($feature, 'Created', 201);
    }

    public function destroy(Request $request, int $phenotype): JsonResponse
    {
        $feature = PhenotypeFeature::findOrFail($phenotype);

        if ($feature->recorded_by !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $feature->delete();

        return ApiResponse::success(null, 'Deleted', 200);
    }
}
```

- [ ] **Step 4: Add routes** — in `backend/routes/api.php`, inside the `auth:sanctum` group, directly after the odyssey routes from Task 7:

```php
    Route::get('/odysseys/{odyssey}/phenotypes', [\App\Http\Controllers\PhenotypeFeatureController::class, 'index']);
    Route::post('/odysseys/{odyssey}/phenotypes', [\App\Http\Controllers\PhenotypeFeatureController::class, 'store']);
    Route::delete('/phenotypes/{phenotype}', [\App\Http\Controllers\PhenotypeFeatureController::class, 'destroy']);
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter PhenotypeFeatureTest`
Expected: PASS (5 passing).

- [ ] **Step 6: Pint, then commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Http/Controllers/PhenotypeFeatureController.php backend/routes/api.php backend/tests/Feature/Api/PhenotypeFeatureTest.php
git commit -m "feat(rare-disease): add phenotype feature API (HPO capture with negation)"
```

---

### Task 9: PhenopacketExporter service

**Files:**
- Create: `backend/app/Services/RareDisease/PhenopacketExporter.php`
- Test: `backend/tests/Unit/Services/PhenopacketExporterTest.php`

- [ ] **Step 1: Write the failing test**

`backend/tests/Unit/Services/PhenopacketExporterTest.php`:

```php
<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use App\Models\User;
use App\Services\RareDisease\PhenopacketExporter;

beforeEach(function () {
    $this->exporter = new PhenopacketExporter;
    $this->user = User::factory()->create();
    $this->patient = ClinicalPatient::factory()->create();
    $this->odyssey = DiagnosticOdyssey::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);
});

it('exports a v2-shaped phenopacket with subject and schema version', function () {
    $packet = $this->exporter->export($this->odyssey);

    expect($packet['id'])->toBe('aurora-odyssey-'.$this->odyssey->id);
    expect($packet['subject']['id'])->toBe((string) $this->patient->id);
    expect($packet['metaData']['phenopacketSchemaVersion'])->toBe('2.0');
    expect($packet['metaData']['resources'][0]['namespacePrefix'])->toBe('HP');
});

it('maps observed and excluded phenotype features', function () {
    PhenotypeFeature::factory()->create([
        'odyssey_id' => $this->odyssey->id,
        'hpo_id' => 'HP:0001250',
        'hpo_label' => 'Seizure',
        'excluded' => false,
        'severity_hpo_id' => 'HP:0012828',
        'recorded_by' => $this->user->id,
    ]);
    PhenotypeFeature::factory()->create([
        'odyssey_id' => $this->odyssey->id,
        'hpo_id' => 'HP:0001251',
        'hpo_label' => 'Ataxia',
        'excluded' => true,
        'recorded_by' => $this->user->id,
    ]);

    $packet = $this->exporter->export($this->odyssey->fresh());
    $features = collect($packet['phenotypicFeatures']);

    expect($features)->toHaveCount(2);
    $seizure = $features->firstWhere('type.id', 'HP:0001250');
    expect($seizure['excluded'])->toBeFalse();
    expect($seizure['severity']['id'])->toBe('HP:0012828');
    $ataxia = $features->firstWhere('type.id', 'HP:0001251');
    expect($ataxia['excluded'])->toBeTrue();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec -T php php artisan test --filter PhenopacketExporterTest`
Expected: FAIL — `Class "App\Services\RareDisease\PhenopacketExporter" not found`.

- [ ] **Step 3: Write the exporter**

`backend/app/Services/RareDisease/PhenopacketExporter.php`:

```php
<?php

namespace App\Services\RareDisease;

use App\Models\DiagnosticOdyssey;

/**
 * Emits a GA4GH Phenopackets v2-shaped JSON structure for a diagnostic odyssey.
 * Scope here is phenotype-level interchange; genomic interpretations are added in Plan 3.
 */
class PhenopacketExporter
{
    public function export(DiagnosticOdyssey $odyssey): array
    {
        $odyssey->loadMissing(['phenotypeFeatures']);

        $features = $odyssey->phenotypeFeatures->map(function ($f): array {
            $feature = [
                'type' => ['id' => $f->hpo_id, 'label' => $f->hpo_label],
                'excluded' => (bool) $f->excluded,
            ];

            if ($f->onset_hpo_id) {
                $feature['onset'] = ['ontologyClass' => ['id' => $f->onset_hpo_id, 'label' => '']];
            }
            if ($f->severity_hpo_id) {
                $feature['severity'] = ['id' => $f->severity_hpo_id, 'label' => ''];
            }
            if ($f->frequency_hpo_id) {
                $feature['frequency'] = ['ontologyClass' => ['id' => $f->frequency_hpo_id, 'label' => '']];
            }
            if ($f->evidence) {
                $feature['evidence'] = [['evidenceCode' => ['id' => 'ECO:0000033', 'label' => 'author statement supported by traceable reference']]];
            }

            return $feature;
        })->values()->all();

        return [
            'id' => 'aurora-odyssey-'.$odyssey->id,
            'subject' => [
                'id' => (string) $odyssey->patient_id,
            ],
            'phenotypicFeatures' => $features,
            'metaData' => [
                'created' => now()->toIso8601String(),
                'createdBy' => 'Aurora',
                'phenopacketSchemaVersion' => '2.0',
                'resources' => [[
                    'id' => 'hp',
                    'name' => 'Human Phenotype Ontology',
                    'url' => 'http://purl.obolibrary.org/obo/hp.owl',
                    'version' => 'latest',
                    'namespacePrefix' => 'HP',
                    'iriPrefix' => 'http://purl.obolibrary.org/obo/HP_',
                ]],
            ],
        ];
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter PhenopacketExporterTest`
Expected: PASS (2 passing).

- [ ] **Step 5: Pint, then commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/RareDisease/PhenopacketExporter.php backend/tests/Unit/Services/PhenopacketExporterTest.php
git commit -m "feat(rare-disease): add Phenopackets v2 exporter service"
```

---

### Task 10: Phenopacket export endpoint

**Files:**
- Modify: `backend/app/Http/Controllers/DiagnosticOdysseyController.php` (add `phenopacket` method + constructor dep)
- Modify: `backend/routes/api.php` (one route)
- Test: `backend/tests/Feature/Api/DiagnosticOdysseyTest.php` (add one `describe` block)

- [ ] **Step 1: Add the failing test** — append to `backend/tests/Feature/Api/DiagnosticOdysseyTest.php`:

```php
describe('GET /api/odysseys/{odyssey}/phenopacket', function () {
    it('exports a phenopacket with the patient as subject', function () {
        $odyssey = DiagnosticOdyssey::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/odysseys/{$odyssey->id}/phenopacket");

        $response->assertStatus(200)
            ->assertJsonPath('data.subject.id', (string) $this->patient->id)
            ->assertJsonPath('data.metaData.phenopacketSchemaVersion', '2.0');
    });
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker compose exec -T php php artisan test --filter DiagnosticOdysseyTest`
Expected: FAIL — new `phenopacket` case 404s (route missing).

- [ ] **Step 3: Add the controller method** — in `backend/app/Http/Controllers/DiagnosticOdysseyController.php`, add the import and constructor dependency and method:

Add import near the other `use` lines:

```php
use App\Services\RareDisease\PhenopacketExporter;
```

Update the constructor signature to inject the exporter:

```php
    public function __construct(
        private OdysseyService $service,
        private OdysseyStateMachine $machine,
        private PhenopacketExporter $exporter,
    ) {}
```

Add the method (after `transition`):

```php
    public function phenopacket(int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::with('phenotypeFeatures')->findOrFail($odyssey);

        return ApiResponse::success($this->exporter->export($model));
    }
```

- [ ] **Step 4: Add the route** — in `backend/routes/api.php`, inside the `auth:sanctum` group with the other odyssey routes:

```php
    Route::get('/odysseys/{odyssey}/phenopacket', [\App\Http\Controllers\DiagnosticOdysseyController::class, 'phenopacket']);
```

- [ ] **Step 5: Run the full odyssey + exporter suite to verify everything passes**

Run: `docker compose exec -T php php artisan test --filter "DiagnosticOdysseyTest|PhenopacketExporterTest|PhenotypeFeatureTest|OdysseyServiceTest|OdysseyStateMachineTest|FactorySmokeTest"`
Expected: PASS (all green).

- [ ] **Step 6: Pint, then commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Http/Controllers/DiagnosticOdysseyController.php backend/routes/api.php backend/tests/Feature/Api/DiagnosticOdysseyTest.php
git commit -m "feat(rare-disease): add Phenopacket export endpoint"
```

---

### Task 11: Full-suite regression + PHPStan

**Files:** none (verification only)

- [ ] **Step 1: Run the entire backend test suite** (ensure no regressions in existing 150+ tests)

Run: `docker compose exec -T php php artisan test`
Expected: PASS — all prior tests plus the ~24 new tests from this plan.

- [ ] **Step 2: Run static analysis** (the strategy posture demands type safety)

Run: `docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/phpstan analyse --memory-limit=512M"`
Expected: No new errors introduced by these files. (If the project baseline has pre-existing errors, confirm the new files add none.)

- [ ] **Step 3: Final Pint check**

Run: `docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint --test"`
Expected: `PASS` — no style violations.

- [ ] **Step 4: Commit any final fixes** (only if Steps 1–3 surfaced issues)

```bash
git add -A backend/
git commit -m "chore(rare-disease): satisfy phpstan + pint for odyssey foundation"
```

---

## Self-Review

**1. Spec coverage (against strategy §5.1–5.2):**
- §5.1 diagnostic-odyssey state machine → Tasks 1, 4, 5, 7 ✓ (referral → … → reanalysis loop, with `progressStatus` IN_PROGRESS/SOLVED/UNSOLVED per Phenopackets).
- §5.2 deep phenotyping (HPO + onset/severity/frequency + **explicit excluded**) → Tasks 2, 3, 6, 8 ✓.
- §5.2 Phenopackets v2 export → Tasks 9, 10 ✓.
- *Deferred (explicitly, to later plans, not gaps):* HPO term autocomplete via ontology.jax.org (Plan 2), Phenopacket **import** (Plan 2), VRS/CAID + ACMG variant model (Plan 3), reanalysis loop + KB alerting (Plan 4), Matchmaker Exchange + Beacon (Plan 5), GREGoR participant/family data model and frontend UI (Plan 2).

**2. Placeholder scan:** No "TBD"/"add validation"/"handle edge cases" — every code step contains complete code; every test step contains real assertions and exact run commands with expected output. ✓

**3. Type/name consistency:** `OdysseyStateMachine::STATES` (public const) referenced by `TransitionOdysseyRequest`; `canTransition`/`allowedFrom`/`progressStatusFor` signatures match between Tasks 4, 5, 7. `InvalidOdysseyTransitionException` thrown in Task 5, caught in Task 7. Table names `app.diagnostic_odysseys` / `app.odyssey_status_transitions` / `app.phenotype_features` consistent across migrations, models, factories. `phenotypeFeatures()` relation name consistent across model, controllers, exporter, tests. Constructor of `DiagnosticOdysseyController` is widened in Task 10 to add `PhenopacketExporter` — confirmed no other call sites construct it manually (resolved via Laravel container). ✓

**Note on migration ordering:** timestamps `2026_06_14_010001/010002` sort after the existing `2026_06_14_000001..3` auth migrations, so `migrate` runs them last. ✓

---

## Execution Handoff

Plan complete and saved to `docs/plans/2026-06-14-rare-disease-odyssey-foundation-plan.md`. Two execution options:

1. **Subagent-Driven (recommended)** — dispatch a fresh subagent per task, review between tasks, fast iteration.
2. **Inline Execution** — execute tasks in this session using executing-plans, batch execution with checkpoints.

Which approach?
