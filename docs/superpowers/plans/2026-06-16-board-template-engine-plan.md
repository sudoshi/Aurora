# Board-Template Engine v1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Generalize Aurora's oncology-shaped case into a configurable board-template engine by extending the existing `app.case_templates` + `app.cases` tables, so tumor-board / rare-disease / heart-team / complex-medical boards each carry their own data schema, candidacy rubric, agenda, decision types, and (for rare + surgical) an explicit state machine.

**Architecture:** A board template is data, not code — extend the existing `case_templates` rows (model `CaseTemplate`, controller `CaseTemplateController`, seeder `SpecialtyTemplateSeeder`) with engine fields. Bind `app.cases` to a template via `template_id` + `state` + `structured_data`. A `BoardTemplateService` validates case data *softly* (warnings, never rejects); a `CaseStateMachine` reads the template's `state_machine` jsonb and is a no-op when null. Existing oncology cases backfill to the `oncology-tumor-board` template so current behaviour is preserved exactly.

**Tech Stack:** Laravel 11 / PHP 8.4 (Pest), PostgreSQL 16/17 (schema-qualified tables `app.*`, `clinical.*`), React 19 + TypeScript + Vite (Vitest + MSW + TanStack Query), Pint.

**Spec:** `docs/superpowers/specs/2026-06-16-board-template-engine-design.md`

**Branch:** `v2/phase-0-scaffold` (NOT main). All commits use explicit literal pathspecs.

---

## Conventions (read once before starting)

- **Run tests in the php container:** `docker compose exec -T php php artisan test --filter=<Name>`. The data-safety guard in `backend/tests/TestCase.php` redirects all pgsql connections to `aurora_test` — never touches dev/prod data.
- **Pint after every PHP change:** `docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"`.
- **Frontend checks:** `docker compose exec -T node sh -c "cd /app && npx tsc --noEmit"` then `npx vite build` (vite is stricter). Vitest: `npx vitest run <path>`.
- **Schema-qualified tables:** models set `protected $table = 'app.cases'` / `'app.case_templates'`. Migrations use `Schema::table('app.cases', …)`.
- **Commits:** `git add <explicit path> && git commit -m "<msg>"`. Never `git add -A`/`.`/`-p`. Verify `git branch --show-current` is `v2/phase-0-scaffold` before each commit.
- **ApiResponse envelope:** `App\Http\Helpers\ApiResponse::success($data, $msg)` / `::error($msg, $code)`. Soft warnings go in the response `meta`.

## File Structure

**Backend — create:**
- `backend/database/migrations/2026_06_16_030001_add_engine_fields_to_case_templates_table.php`
- `backend/database/migrations/2026_06_16_030002_add_template_binding_to_cases_table.php`
- `backend/app/Services/BoardTemplateService.php`
- `backend/app/Services/CaseStateMachine.php`
- `backend/tests/Unit/Services/BoardTemplateServiceTest.php`
- `backend/tests/Unit/Services/CaseStateMachineTest.php`
- `backend/tests/Feature/Api/BoardTemplateEngineTest.php`

**Backend — modify:**
- `backend/app/Models/CaseTemplate.php` (add casts)
- `backend/app/Models/ClinicalCase.php` (fillable + casts + relation + initial-state hook)
- `backend/database/seeders/SpecialtyTemplateSeeder.php` (engine fields, idempotent)
- `backend/app/Http/Requests/StoreCaseRequest.php` (accept `template_id` + `structured_data`) — verify path in Task 6
- `backend/app/Http/Controllers/CaseController.php` (soft-validate + initial state + warnings meta)
- `backend/app/Http/Controllers/CaseTemplateController.php` (`?active=1` filter)

**Frontend — modify/create:**
- `frontend/src/features/cases/types/case.ts` (engine fields + `CaseTemplate` type)
- `frontend/src/features/cases/api/caseTemplatesApi.ts` (create)
- `frontend/src/features/cases/hooks/useCaseTemplates.ts` (create)
- `frontend/src/features/cases/components/CaseForm.tsx` (template-driven dynamic fields)
- `frontend/src/features/cases/components/__tests__/CaseForm.test.tsx` (create)
- `frontend/src/test/mocks/handlers.ts` (MSW handler for `/case-templates`)

---

## Task 1: Migration — engine fields on `app.case_templates`

**Files:**
- Create: `backend/database/migrations/2026_06_16_030001_add_engine_fields_to_case_templates_table.php`

- [ ] **Step 1: Write the migration**

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
        Schema::table('app.case_templates', function (Blueprint $table) {
            $table->string('time_model')->default('episodic'); // episodic | episode_of_care | longitudinal | diagnostic_odyssey
            $table->jsonb('data_schema')->default('[]');
            $table->jsonb('candidacy_rubric')->nullable();
            $table->jsonb('agenda')->default('[]');
            $table->jsonb('state_machine')->nullable();
            $table->boolean('is_active')->default(true);
        });

        DB::statement(
            "ALTER TABLE app.case_templates ADD CONSTRAINT case_templates_time_model_check ".
            "CHECK (time_model IN ('episodic','episode_of_care','longitudinal','diagnostic_odyssey'))"
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE app.case_templates DROP CONSTRAINT IF EXISTS case_templates_time_model_check');
        Schema::table('app.case_templates', function (Blueprint $table) {
            $table->dropColumn(['time_model', 'data_schema', 'candidacy_rubric', 'agenda', 'state_machine', 'is_active']);
        });
    }
};
```

- [ ] **Step 2: Run the migration against the test DB to verify it applies**

Run: `docker compose exec -T php php artisan migrate --path=database/migrations/2026_06_16_030001_add_engine_fields_to_case_templates_table.php --database=pgsql --env=testing`
Expected: `DONE` (or run the full suite in Task 3 which migrates fresh). If the env flag is awkward, rely on the Pest `RefreshDatabase`/migration in Task 3 to exercise it.

- [ ] **Step 3: Commit**

```bash
git add backend/database/migrations/2026_06_16_030001_add_engine_fields_to_case_templates_table.php
git commit -m "feat(cases): add board-engine fields to case_templates"
```

---

## Task 2: Migration — bind `app.cases` to a template

**Files:**
- Create: `backend/database/migrations/2026_06_16_030002_add_template_binding_to_cases_table.php`

- [ ] **Step 1: Write the migration (additive + backfill)**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** case_type/specialty → template slug */
    private array $map = [
        'tumor_board' => 'oncology-tumor-board',
        'surgical_review' => 'complex-surgical-planning',
        'surgical_planning' => 'complex-surgical-planning',
        'rare_disease' => 'rare-disease-diagnostic-odyssey',
        'diagnostic_odyssey' => 'rare-disease-diagnostic-odyssey',
        'medical_complex' => 'complex-medical-case-review',
        'medical_review' => 'complex-medical-case-review',
    ];

    public function up(): void
    {
        Schema::table('app.cases', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->after('case_type');
            $table->string('state')->nullable()->after('status');
            $table->jsonb('structured_data')->default('{}');

            $table->foreign('template_id')->references('id')->on('app.case_templates')->onDelete('set null');
            $table->index('template_id');
        });

        // Backfill template_id by case_type, defaulting unknown types to the oncology template.
        $default = DB::table('app.case_templates')->where('slug', 'oncology-tumor-board')->value('id');
        foreach ($this->map as $caseType => $slug) {
            $id = DB::table('app.case_templates')->where('slug', $slug)->value('id');
            if ($id) {
                DB::table('app.cases')->where('case_type', $caseType)->whereNull('template_id')->update(['template_id' => $id]);
            }
        }
        if ($default) {
            DB::table('app.cases')->whereNull('template_id')->update(['template_id' => $default]);
        }
    }

    public function down(): void
    {
        Schema::table('app.cases', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['template_id', 'state', 'structured_data']);
        });
    }
};
```

- [ ] **Step 2: Commit**

```bash
git add backend/database/migrations/2026_06_16_030002_add_template_binding_to_cases_table.php
git commit -m "feat(cases): bind cases to a board template (template_id/state/structured_data)"
```

---

## Task 3: Extend `CaseTemplate` model + seeder with engine fields

**Files:**
- Modify: `backend/app/Models/CaseTemplate.php`
- Modify: `backend/database/seeders/SpecialtyTemplateSeeder.php`
- Test: `backend/tests/Feature/Api/BoardTemplateEngineTest.php` (seeder section)

- [ ] **Step 1: Write the failing test (seeder idempotency + engine fields)**

Create `backend/tests/Feature/Api/BoardTemplateEngineTest.php`:

```php
<?php

use App\Models\CaseTemplate;
use Database\Seeders\SpecialtyTemplateSeeder;

use function Pest\Laravel\seed;

it('seeds four templates idempotently with engine fields', function () {
    seed(SpecialtyTemplateSeeder::class);
    seed(SpecialtyTemplateSeeder::class); // run twice — must not duplicate

    expect(CaseTemplate::count())->toBe(4);

    $rare = CaseTemplate::where('slug', 'rare-disease-diagnostic-odyssey')->first();
    expect($rare->time_model)->toBe('diagnostic_odyssey');
    expect($rare->state_machine['initial'])->toBe('referral');
    expect(collect($rare->state_machine['states']))->toContain('reanalysis');

    $onc = CaseTemplate::where('slug', 'oncology-tumor-board')->first();
    expect($onc->time_model)->toBe('episodic');
    expect($onc->state_machine)->toBeNull();
    expect($onc->is_active)->toBeTrue();
    expect($onc->data_schema)->toBeArray();

    $surg = CaseTemplate::where('slug', 'complex-surgical-planning')->first();
    expect($surg->time_model)->toBe('episode_of_care');
    expect($surg->candidacy_rubric)->toBeArray();
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `docker compose exec -T php php artisan test --filter=BoardTemplateEngineTest`
Expected: FAIL — `time_model`/`state_machine` null or undefined (seeder not yet setting engine fields; casts missing).

- [ ] **Step 3: Add casts to `CaseTemplate`**

Edit `backend/app/Models/CaseTemplate.php` — extend the `casts()` array:

```php
    protected function casts(): array
    {
        return [
            'recommended_tabs' => 'array',
            'decision_types' => 'array',
            'guideline_sets' => 'array',
            'default_team_roles' => 'array',
            'data_schema' => 'array',
            'candidacy_rubric' => 'array',
            'agenda' => 'array',
            'state_machine' => 'array',
            'is_active' => 'boolean',
        ];
    }
```

- [ ] **Step 4: Make the seeder idempotent + set engine fields**

Edit `backend/database/seeders/SpecialtyTemplateSeeder.php`. Replace the insert with an idempotent `updateOrInsert` keyed by `slug`, and add the engine fields to each of the 4 rows. Change the persistence loop to:

```php
        foreach ($templates as $t) {
            DB::table('app.case_templates')->updateOrInsert(
                ['slug' => $t['slug']],
                array_merge($t, ['updated_at' => $now]) + ['created_at' => $now],
            );
        }
```

Add these engine fields into each template array (keep existing fields):

```php
// oncology-tumor-board
'time_model' => 'episodic',
'data_schema' => json_encode([
    ['key' => 'primary_site', 'label' => 'Primary site', 'type' => 'string', 'required' => true],
    ['key' => 'histology', 'label' => 'Histology', 'type' => 'string', 'required' => false],
    ['key' => 'stage', 'label' => 'Stage', 'type' => 'string', 'required' => false],
    ['key' => 'key_biomarkers', 'label' => 'Key biomarkers', 'type' => 'string', 'required' => false],
]),
'candidacy_rubric' => null,
'agenda' => json_encode(['Presentation', 'Imaging review', 'Molecular review', 'Recommendation']),
'state_machine' => null,
'is_active' => true,

// rare-disease-diagnostic-odyssey
'time_model' => 'diagnostic_odyssey',
'data_schema' => json_encode([
    ['key' => 'hpo_terms', 'label' => 'HPO terms', 'type' => 'string', 'required' => false],
    ['key' => 'candidate_genes', 'label' => 'Candidate genes', 'type' => 'string', 'required' => false],
    ['key' => 'prior_testing', 'label' => 'Prior testing', 'type' => 'string', 'required' => false],
]),
'candidacy_rubric' => null,
'agenda' => json_encode(['Phenotype review', 'Prior testing', 'Differential', 'Next test / matchmaking']),
'state_machine' => json_encode([
    'initial' => 'referral',
    'states' => ['referral', 'deep_phenotyping', 'testing', 'mdt_review', 'matchmaking', 'diagnosed', 'undiagnosed', 'reanalysis'],
    'transitions' => [
        ['from' => 'referral', 'to' => 'deep_phenotyping', 'event' => 'phenotype'],
        ['from' => 'deep_phenotyping', 'to' => 'testing', 'event' => 'order_testing'],
        ['from' => 'testing', 'to' => 'mdt_review', 'event' => 'results_in'],
        ['from' => 'mdt_review', 'to' => 'matchmaking', 'event' => 'seek_matches'],
        ['from' => 'mdt_review', 'to' => 'diagnosed', 'event' => 'diagnose'],
        ['from' => 'matchmaking', 'to' => 'diagnosed', 'event' => 'diagnose'],
        ['from' => 'matchmaking', 'to' => 'undiagnosed', 'event' => 'close_unsolved'],
        ['from' => 'undiagnosed', 'to' => 'reanalysis', 'event' => 'reanalyze'],
        ['from' => 'reanalysis', 'to' => 'mdt_review', 'event' => 'new_findings'],
    ],
]),
'is_active' => true,

// complex-surgical-planning
'time_model' => 'episode_of_care',
'data_schema' => json_encode([
    ['key' => 'procedure', 'label' => 'Planned procedure', 'type' => 'string', 'required' => true],
    ['key' => 'asa_class', 'label' => 'ASA class', 'type' => 'string', 'required' => false],
]),
'candidacy_rubric' => json_encode([
    ['key' => 'cardiology_clearance', 'label' => 'Cardiology clearance', 'required' => true],
    ['key' => 'anesthesia_review', 'label' => 'Anesthesia review', 'required' => true],
    ['key' => 'frailty_assessment', 'label' => 'Frailty assessment', 'required' => false],
]),
'agenda' => json_encode(['Candidacy', 'Imaging', 'Risk', 'Plan']),
'state_machine' => json_encode([
    'initial' => 'referred',
    'states' => ['referred', 'workup', 'optimization', 'decision', 'procedure', 'recovery', 'closed'],
    'transitions' => [
        ['from' => 'referred', 'to' => 'workup', 'event' => 'begin_workup'],
        ['from' => 'workup', 'to' => 'optimization', 'event' => 'optimize'],
        ['from' => 'optimization', 'to' => 'decision', 'event' => 'review'],
        ['from' => 'decision', 'to' => 'procedure', 'event' => 'proceed'],
        ['from' => 'procedure', 'to' => 'recovery', 'event' => 'operate'],
        ['from' => 'recovery', 'to' => 'closed', 'event' => 'discharge'],
    ],
]),
'is_active' => true,

// complex-medical-case-review
'time_model' => 'longitudinal',
'data_schema' => json_encode([
    ['key' => 'problem_list', 'label' => 'Problem list', 'type' => 'string', 'required' => false],
    ['key' => 'goals_of_care', 'label' => 'Goals of care', 'type' => 'string', 'required' => false],
]),
'candidacy_rubric' => null,
'agenda' => json_encode(['Problem list', 'Med reconciliation', 'Goals of care', 'Plan']),
'state_machine' => null,
'is_active' => true,
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter=BoardTemplateEngineTest`
Expected: PASS (the seeder test in this file).

- [ ] **Step 6: Pint + commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint app/Models/CaseTemplate.php database/seeders/SpecialtyTemplateSeeder.php tests/Feature/Api/BoardTemplateEngineTest.php"
git add backend/app/Models/CaseTemplate.php backend/database/seeders/SpecialtyTemplateSeeder.php backend/tests/Feature/Api/BoardTemplateEngineTest.php
git commit -m "feat(cases): seed board-template engine fields (idempotent)"
```

---

## Task 4: `BoardTemplateService` — soft validation

**Files:**
- Create: `backend/app/Services/BoardTemplateService.php`
- Test: `backend/tests/Unit/Services/BoardTemplateServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\CaseTemplate;
use App\Services\BoardTemplateService;

beforeEach(function () {
    $this->svc = new BoardTemplateService();
    $this->tpl = new CaseTemplate(['slug' => 'x', 'data_schema' => [
        ['key' => 'primary_site', 'label' => 'Primary site', 'type' => 'string', 'required' => true],
        ['key' => 'stage', 'label' => 'Stage', 'type' => 'string', 'required' => false],
    ]]);
});

it('returns no warnings when required fields are present and typed', function () {
    $warnings = $this->svc->validate($this->tpl, ['primary_site' => 'lung', 'stage' => 'IV']);
    expect($warnings)->toBe([]);
});

it('warns (but does not throw) on a missing required field', function () {
    $warnings = $this->svc->validate($this->tpl, ['stage' => 'IV']);
    expect($warnings)->toHaveCount(1);
    expect($warnings[0])->toContain('primary_site');
});

it('warns on a wrong-typed field', function () {
    $warnings = $this->svc->validate($this->tpl, ['primary_site' => 123]);
    expect(collect($warnings)->implode(' '))->toContain('primary_site');
});

it('treats a null/empty data_schema as anything-goes', function () {
    $tpl = new CaseTemplate(['slug' => 'y', 'data_schema' => []]);
    expect($this->svc->validate($tpl, ['whatever' => 1]))->toBe([]);
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `docker compose exec -T php php artisan test --filter=BoardTemplateServiceTest`
Expected: FAIL — class `App\Services\BoardTemplateService` not found.

- [ ] **Step 3: Implement the service**

```php
<?php

namespace App\Services;

use App\Models\CaseTemplate;

class BoardTemplateService
{
    public function resolve(string $slug): ?CaseTemplate
    {
        return CaseTemplate::where('slug', $slug)->first();
    }

    /**
     * Soft validation: returns a list of human-readable warnings. Never throws,
     * never rejects — callers persist regardless and surface warnings in meta.
     *
     * @return list<string>
     */
    public function validate(CaseTemplate $template, array $data): array
    {
        $schema = $template->data_schema ?? [];
        $warnings = [];

        foreach ($schema as $field) {
            $key = $field['key'] ?? null;
            if ($key === null) {
                continue;
            }
            $present = array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '';

            if (! empty($field['required']) && ! $present) {
                $warnings[] = "Missing required field '{$key}'.";

                continue;
            }
            if ($present && ! $this->typeMatches($field['type'] ?? 'string', $data[$key])) {
                $warnings[] = "Field '{$key}' expected type ".($field['type'] ?? 'string').'.';
            }
        }

        return $warnings;
    }

    private function typeMatches(string $type, mixed $value): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'number' => is_int($value) || is_float($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            default => true,
        };
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter=BoardTemplateServiceTest`
Expected: PASS (4 assertions).

- [ ] **Step 5: Pint + commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint app/Services/BoardTemplateService.php tests/Unit/Services/BoardTemplateServiceTest.php"
git add backend/app/Services/BoardTemplateService.php backend/tests/Unit/Services/BoardTemplateServiceTest.php
git commit -m "feat(cases): BoardTemplateService soft data_schema validation"
```

---

## Task 5: `CaseStateMachine` — null-safe transitions

**Files:**
- Create: `backend/app/Services/CaseStateMachine.php`
- Test: `backend/tests/Unit/Services/CaseStateMachineTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\CaseTemplate;
use App\Services\CaseStateMachine;

beforeEach(function () {
    $this->sm = new CaseStateMachine();
    $this->fsm = new CaseTemplate(['slug' => 'r', 'state_machine' => [
        'initial' => 'referral',
        'states' => ['referral', 'testing', 'mdt_review'],
        'transitions' => [
            ['from' => 'referral', 'to' => 'testing', 'event' => 'order'],
            ['from' => 'testing', 'to' => 'mdt_review', 'event' => 'results'],
        ],
    ]]);
    $this->stateless = new CaseTemplate(['slug' => 's', 'state_machine' => null]);
});

it('returns the initial state for a stateful template', function () {
    expect($this->sm->initialState($this->fsm))->toBe('referral');
});

it('returns null initial state for a stateless template', function () {
    expect($this->sm->initialState($this->stateless))->toBeNull();
});

it('allows a declared transition and rejects an undeclared one', function () {
    expect($this->sm->canTransition($this->fsm, 'referral', 'testing'))->toBeTrue();
    expect($this->sm->canTransition($this->fsm, 'referral', 'mdt_review'))->toBeFalse();
});

it('treats every transition as a no-op (allowed) for a stateless template', function () {
    expect($this->sm->canTransition($this->stateless, 'anything', 'whatever'))->toBeTrue();
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `docker compose exec -T php php artisan test --filter=CaseStateMachineTest`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement**

```php
<?php

namespace App\Services;

use App\Models\CaseTemplate;

class CaseStateMachine
{
    public function initialState(CaseTemplate $template): ?string
    {
        return $template->state_machine['initial'] ?? null;
    }

    /**
     * Null state_machine ⇒ stateless template ⇒ no constraints (always true).
     */
    public function canTransition(CaseTemplate $template, string $from, string $to): bool
    {
        $fsm = $template->state_machine;
        if (empty($fsm) || empty($fsm['transitions'])) {
            return true;
        }

        foreach ($fsm['transitions'] as $t) {
            if (($t['from'] ?? null) === $from && ($t['to'] ?? null) === $to) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter=CaseStateMachineTest`
Expected: PASS.

- [ ] **Step 5: Pint + commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint app/Services/CaseStateMachine.php tests/Unit/Services/CaseStateMachineTest.php"
git add backend/app/Services/CaseStateMachine.php backend/tests/Unit/Services/CaseStateMachineTest.php
git commit -m "feat(cases): CaseStateMachine null-safe transition guard"
```

---

## Task 6: Generalize `ClinicalCase` + template-driven create

**Files:**
- Modify: `backend/app/Models/ClinicalCase.php`
- Modify: `backend/app/Http/Controllers/CaseController.php`
- Modify: `backend/app/Http/Requests/StoreCaseRequest.php` (confirm exact path first — see Step 0)
- Modify: `backend/app/Http/Controllers/CaseTemplateController.php`
- Test: `backend/tests/Feature/Api/BoardTemplateEngineTest.php` (append API cases)

- [ ] **Step 0: Locate the store-validation + create path**

Run: `grep -rn "function store" backend/app/Http/Controllers/CaseController.php; ls backend/app/Http/Requests/ | grep -i case`
The case-store request may be `StoreCaseRequest.php` or validation may be inline in `CaseController::store`. Apply the rule additions (Step 4) to whichever holds the validation. Do **not** alter unrelated validation rules.

- [ ] **Step 1: Write the failing test (append to `BoardTemplateEngineTest.php`)**

```php
it('creates a case bound to a template with the template initial state', function () {
    seed(\Database\Seeders\SpecialtyTemplateSeeder::class);
    $user = \App\Models\User::factory()->create();
    $tpl = \App\Models\CaseTemplate::where('slug', 'rare-disease-diagnostic-odyssey')->first();

    $res = $this->actingAs($user)->postJson('/api/cases', [
        'title' => 'Undiagnosed myopathy',
        'specialty' => 'rare_disease',
        'case_type' => 'rare_disease',
        'template_id' => $tpl->id,
        'structured_data' => ['hpo_terms' => 'HP:0003198'],
    ]);

    $res->assertCreated();
    $case = \App\Models\ClinicalCase::first();
    expect($case->template_id)->toBe($tpl->id);
    expect($case->state)->toBe('referral'); // from the FSM initial
    expect($case->structured_data)->toBe(['hpo_terms' => 'HP:0003198']);
});

it('surfaces soft validation warnings in meta without rejecting', function () {
    seed(\Database\Seeders\SpecialtyTemplateSeeder::class);
    $user = \App\Models\User::factory()->create();
    $tpl = \App\Models\CaseTemplate::where('slug', 'complex-surgical-planning')->first();

    $res = $this->actingAs($user)->postJson('/api/cases', [
        'title' => 'Whipple candidacy',
        'specialty' => 'surgical',
        'case_type' => 'surgical_review',
        'template_id' => $tpl->id,
        'structured_data' => [], // 'procedure' is required by the schema → warning
    ]);

    $res->assertCreated();
    expect($res->json('meta.warnings.0'))->toContain('procedure');
});

it('lists only active templates when ?active=1', function () {
    seed(\Database\Seeders\SpecialtyTemplateSeeder::class);
    \App\Models\CaseTemplate::where('slug', 'complex-medical-case-review')->update(['is_active' => false]);
    $user = \App\Models\User::factory()->create();

    $res = $this->actingAs($user)->getJson('/api/case-templates?active=1');
    $res->assertOk();
    $slugs = collect($res->json('data'))->pluck('slug');
    expect($slugs)->not->toContain('complex-medical-case-review');
    expect($slugs)->toContain('oncology-tumor-board');
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `docker compose exec -T php php artisan test --filter=BoardTemplateEngineTest`
Expected: FAIL — `template_id`/`state`/`structured_data` not persisted; no `meta.warnings`; `?active=1` not filtered.

- [ ] **Step 3: Update `ClinicalCase`**

Add to `$fillable`: `'template_id'`, `'state'`, `'structured_data'`. Add to `casts()`: `'structured_data' => 'array'`. Add the relation:

```php
    public function template(): BelongsTo
    {
        return $this->belongsTo(CaseTemplate::class, 'template_id');
    }
```

(`use App\Models\CaseTemplate;` at top if not already imported — it is in `App\Models`, same namespace, so no import needed.)

- [ ] **Step 4: Accept the new fields in store validation**

In the store request/controller validation array, add:

```php
            'template_id' => ['nullable', 'integer', 'exists:app.case_templates,id'],
            'structured_data' => ['nullable', 'array'],
```

- [ ] **Step 5: Set initial state + soft-validate in `CaseController::store`**

After resolving the validated data and before/around persisting the case, inject (using the services):

```php
        $warnings = [];
        $validated = $request->validated();

        if (! empty($validated['template_id'])) {
            $template = \App\Models\CaseTemplate::find($validated['template_id']);
            if ($template) {
                $warnings = app(\App\Services\BoardTemplateService::class)
                    ->validate($template, $validated['structured_data'] ?? []);
                $validated['state'] = app(\App\Services\CaseStateMachine::class)->initialState($template);
            }
        }

        $case = ClinicalCase::create($validated + ['created_by' => $request->user()->id]);

        return ApiResponse::success($case, 'Case created', 201, ['warnings' => $warnings]);
```

> Match the existing `store` structure — if it already builds `$validated`/`created_by`, fold these lines in rather than duplicating. If `ApiResponse::success` does not accept a meta arg in this codebase, check its signature (`grep -n "function success" backend/app/Http/Helpers/ApiResponse.php`) and pass meta the way the helper supports; if it has no meta param, return `ApiResponse::success($case, 'Case created', 201)` and attach warnings via `->additional(['meta' => ['warnings' => $warnings]])` or the helper's documented mechanism. Confirm the 201 path matches existing case-create tests.

- [ ] **Step 6: Add the `?active=1` filter to `CaseTemplateController::index`**

In `index()`, after the existing specialty/case_type filters:

```php
        if ($request->boolean('active')) {
            $query->where('is_active', true);
        }
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `docker compose exec -T php php artisan test --filter=BoardTemplateEngineTest`
Expected: PASS (all sections). Then run the existing case suite to confirm no regressions:
Run: `docker compose exec -T php php artisan test --filter=CaseControllerTest`
Expected: PASS (oncology behaviour preserved).

- [ ] **Step 8: Pint + commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint app/Models/ClinicalCase.php app/Http/Controllers/CaseController.php app/Http/Controllers/CaseTemplateController.php tests/Feature/Api/BoardTemplateEngineTest.php"
git add backend/app/Models/ClinicalCase.php backend/app/Http/Controllers/CaseController.php backend/app/Http/Controllers/CaseTemplateController.php backend/tests/Feature/Api/BoardTemplateEngineTest.php
# include the request file if it was the one modified:
# git add backend/app/Http/Requests/StoreCaseRequest.php
git commit -m "feat(cases): template-driven case create with soft warnings + initial state"
```

---

## Task 7: Frontend — template types, api, hook

**Files:**
- Modify: `frontend/src/features/cases/types/case.ts`
- Create: `frontend/src/features/cases/api/caseTemplatesApi.ts`
- Create: `frontend/src/features/cases/hooks/useCaseTemplates.ts`
- Modify: `frontend/src/test/mocks/handlers.ts`
- Test: covered via the hook test in Task 8's CaseForm test fixture (MSW)

- [ ] **Step 0: Read the existing types + api unwrap pattern**

Run: `sed -n '1,60p' frontend/src/features/cases/types/case.ts; sed -n '1,25p' frontend/src/features/cases/api/casesApi.ts`
Reuse the existing `unwrap` envelope pattern and the `apiClient` import.

- [ ] **Step 1: Add the `CaseTemplate` type + `structured_data` to case types**

Append to `frontend/src/features/cases/types/case.ts`:

```ts
export interface BoardTemplateField {
  key: string;
  label: string;
  type: "string" | "number" | "boolean" | "array";
  required: boolean;
  options?: string[];
}

export interface CaseTemplate {
  id: number;
  slug: string;
  name: string;
  specialty: string;
  case_type: string;
  description: string;
  time_model: "episodic" | "episode_of_care" | "longitudinal" | "diagnostic_odyssey";
  data_schema: BoardTemplateField[];
  candidacy_rubric: { key: string; label: string; required: boolean }[] | null;
  decision_types: string[];
  agenda: string[];
  state_machine: { initial: string; states: string[] } | null;
  is_active: boolean;
}
```

Add `template_id?: number` and `structured_data?: Record<string, unknown>` to the existing `CreateCaseData` interface (locate it in the same file and extend it — do not redefine).

- [ ] **Step 2: Create the api module**

`frontend/src/features/cases/api/caseTemplatesApi.ts`:

```ts
import apiClient from "@/lib/api-client";
import type { CaseTemplate } from "../types/case";

function unwrap<T>(response: { data: { data?: T; success?: boolean } | T }): T {
  const d = response.data;
  if (d && typeof d === "object" && "success" in d && "data" in d) {
    return (d as { data: T }).data;
  }
  return d as T;
}

export const getCaseTemplates = (activeOnly = true): Promise<CaseTemplate[]> =>
  apiClient
    .get("/case-templates", { params: activeOnly ? { active: 1 } : {} })
    .then((r) => unwrap<CaseTemplate[]>(r));
```

- [ ] **Step 3: Create the hook**

`frontend/src/features/cases/hooks/useCaseTemplates.ts`:

```ts
import { useQuery } from "@tanstack/react-query";
import { getCaseTemplates } from "../api/caseTemplatesApi";

export function useCaseTemplates(activeOnly = true) {
  return useQuery({
    queryKey: ["case-templates", { activeOnly }],
    queryFn: () => getCaseTemplates(activeOnly),
    staleTime: 5 * 60 * 1000,
  });
}
```

- [ ] **Step 4: Add an MSW handler**

In `frontend/src/test/mocks/handlers.ts`, add a handler for `GET /case-templates` returning a 2-template fixture (oncology + rare) in the `{ success, data }` envelope. Match the existing handler style in that file (read the top of the file first to mirror the base URL + `HttpResponse.json` pattern).

```ts
http.get("*/case-templates", () =>
  HttpResponse.json({
    success: true,
    data: [
      {
        id: 1, slug: "oncology-tumor-board", name: "Oncology Tumor Board",
        specialty: "oncology", case_type: "tumor_board", description: "",
        time_model: "episodic",
        data_schema: [
          { key: "primary_site", label: "Primary site", type: "string", required: true },
        ],
        candidacy_rubric: null, decision_types: [], agenda: [],
        state_machine: null, is_active: true,
      },
      {
        id: 2, slug: "rare-disease-diagnostic-odyssey", name: "Rare Disease Diagnostic Odyssey",
        specialty: "rare_disease", case_type: "diagnostic_odyssey", description: "",
        time_model: "diagnostic_odyssey",
        data_schema: [
          { key: "hpo_terms", label: "HPO terms", type: "string", required: false },
        ],
        candidacy_rubric: null, decision_types: [], agenda: [],
        state_machine: { initial: "referral", states: ["referral"] }, is_active: true,
      },
    ],
  }),
),
```

- [ ] **Step 5: Typecheck + commit**

```bash
docker compose exec -T node sh -c "cd /app && npx tsc --noEmit"
git add frontend/src/features/cases/types/case.ts frontend/src/features/cases/api/caseTemplatesApi.ts frontend/src/features/cases/hooks/useCaseTemplates.ts frontend/src/test/mocks/handlers.ts
git commit -m "feat(cases): case-template types, api, and useCaseTemplates hook"
```

---

## Task 8: Frontend — template-driven dynamic CaseForm

**Files:**
- Modify: `frontend/src/features/cases/components/CaseForm.tsx`
- Create: `frontend/src/features/cases/components/__tests__/CaseForm.test.tsx`

- [ ] **Step 1: Write the failing test**

`frontend/src/features/cases/components/__tests__/CaseForm.test.tsx`:

```tsx
import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { CaseForm } from "../CaseForm";

function renderForm() {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  const onSubmit = vi.fn();
  render(
    <QueryClientProvider client={qc}>
      <CaseForm onSubmit={onSubmit} onClose={() => {}} />
    </QueryClientProvider>,
  );
  return { onSubmit };
}

describe("CaseForm template-driven fields", () => {
  it("renders the selected template's structured-data fields", async () => {
    renderForm();
    // The template <select> is populated from useCaseTemplates (MSW fixture).
    const tplSelect = await screen.findByLabelText(/board template/i);
    fireEvent.change(tplSelect, { target: { value: "oncology-tumor-board" } });
    // data_schema field 'primary_site' should now render.
    expect(await screen.findByLabelText(/primary site/i)).toBeInTheDocument();
  });

  it("submits structured_data and template_id", async () => {
    const { onSubmit } = renderForm();
    fireEvent.change(await screen.findByLabelText(/title/i), { target: { value: "Case A" } });
    fireEvent.change(await screen.findByLabelText(/board template/i), { target: { value: "oncology-tumor-board" } });
    fireEvent.change(await screen.findByLabelText(/primary site/i), { target: { value: "lung" } });
    fireEvent.click(screen.getByRole("button", { name: /create|save/i }));
    await waitFor(() =>
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          template_id: 1,
          structured_data: expect.objectContaining({ primary_site: "lung" }),
        }),
      ),
    );
  });
});
```

- [ ] **Step 2: Run it to verify it fails**

Run: `docker compose exec -T node sh -c "cd /app && npx vitest run src/features/cases/components/__tests__/CaseForm.test.tsx"`
Expected: FAIL — no "board template" select; no dynamic fields.

- [ ] **Step 3: Wire the template selector + dynamic fields into `CaseForm`**

In `CaseForm.tsx`:
1. `import { useCaseTemplates } from "../hooks/useCaseTemplates";` and `import type { CaseTemplate } from "../types/case";`
2. `const { data: templates = [] } = useCaseTemplates();`
3. Add state: `const [templateSlug, setTemplateSlug] = useState(clinicalCase?.template?.slug ?? "");` and `const [structured, setStructured] = useState<Record<string, unknown>>(clinicalCase?.structured_data ?? {});`
4. `const selected = templates.find((t) => t.slug === templateSlug) ?? null;`
5. Render a labelled `<select id="board-template">` (label text "Board Template") whose options are `templates.map(t => <option value={t.slug}>{t.name}</option>)`.
6. After the select, render `selected?.data_schema.map((f) => (...))` — a labelled `<input>` per field, label = `f.label`, value = `String(structured[f.key] ?? "")`, onChange updates `structured` immutably: `setStructured((s) => ({ ...s, [f.key]: e.target.value }))`.
7. In `handleSubmit`, add to the `data` object: `template_id: selected?.id, structured_data: structured`.

Keep all existing fields (title, specialty, case_type, urgency, clinical_question, summary, patient_id) untouched — the template fields are additive. Use the existing `form-group`/`form-label`/`form-input` classes.

- [ ] **Step 4: Run the test to verify it passes**

Run: `docker compose exec -T node sh -c "cd /app && npx vitest run src/features/cases/components/__tests__/CaseForm.test.tsx"`
Expected: PASS.

- [ ] **Step 5: Full frontend gate**

Run: `docker compose exec -T node sh -c "cd /app && npx tsc --noEmit && npx vite build"`
Expected: both succeed (vite is the strict gate).

- [ ] **Step 6: Commit**

```bash
git add frontend/src/features/cases/components/CaseForm.tsx frontend/src/features/cases/components/__tests__/CaseForm.test.tsx
git commit -m "feat(cases): template-driven dynamic fields in CaseForm"
```

---

## Task 9: Full-suite verification

- [ ] **Step 1: Backend suite**

Run: `docker compose exec -T php php artisan test`
Expected: all green (≥ prior 439 + new tests). If any pre-existing unrelated failures appear, confirm they predate this branch (do not fix here).

- [ ] **Step 2: Frontend suite + build**

Run: `docker compose exec -T node sh -c "cd /app && npx vitest run && npx tsc --noEmit && npx vite build"`
Expected: all green.

- [ ] **Step 3: Pint clean**

Run: `docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint --test"`
Expected: no changes needed (or run without `--test` and commit any formatting).

- [ ] **Step 4: Final commit if Pint changed anything**

```bash
# only if pint reformatted files:
git add backend/app backend/database backend/tests
git commit -m "style: pint formatting for board-template engine"
```

---

## Done criteria

- `app.case_templates` carries `time_model`/`data_schema`/`candidacy_rubric`/`agenda`/`state_machine`/`is_active`; 4 rows seeded idempotently with engine data.
- `app.cases` bound via `template_id`/`state`/`structured_data`; existing cases backfilled to `oncology-tumor-board`; oncology create/edit/decision flows unchanged.
- `BoardTemplateService.validate` returns soft warnings; `CaseStateMachine` is null-safe.
- `POST /cases` accepts `template_id` + `structured_data`, sets initial state, returns warnings in meta.
- `GET /case-templates?active=1` filters; `CaseForm` renders template fields and submits `structured_data`.
- Backend + frontend suites green; Pint clean.

## Out of scope (later Phase A plans)

Closed-loop FHIR `Task` engine (§3.B); OMOP risk auto-compute at creation (§3.C); SMART/CDS-Hooks/US-Core interop scaffold (§3.F). Do not build these here.
