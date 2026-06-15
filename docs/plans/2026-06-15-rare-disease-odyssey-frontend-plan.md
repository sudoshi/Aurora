# Rare-Disease Diagnostic Odyssey — Frontend, HPO Autocomplete & Phenopacket Import (Plan 2)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the rare-disease diagnostic odyssey usable by a clinician end-to-end in the UI — a worklist, an odyssey detail workspace with a status stepper, HPO-autocomplete deep phenotyping (negation/onset/severity), and GA4GH Phenopackets v2 import/export — backed by an HPO terminology proxy and a Phenopacket importer.

**Architecture:** Additive. Backend adds an HPO search proxy (Laravel `Http` → `ontology.jax.org`, cached) and a `PhenopacketImporter` (reverse of Plan 1's `PhenopacketExporter`), plus a global odyssey worklist endpoint. Frontend adds a `rare-disease` feature module (types + Zod, API client, TanStack Query hooks, components, two pages) wired into the existing router and nav, following the genomics-feature conventions exactly.

**Tech Stack:** Backend — Laravel 11 / PHP 8.4, Pest, `Http::fake`, Pint. Frontend — React 19 + TS strict, Vite, TanStack Query 5, Zustand, Zod 4, React Router 6, Tailwind 4 + design tokens, Lucide icons, Vitest 3 + MSW 2 + Testing Library. E2E — Playwright.

**Plan context — this is Plan 2 of 5 for the rare-disease lead initiative:**
1. Diagnostic Odyssey Foundation — DONE (state machine, HPO phenotyping model, Phenopackets v2 export; API live under `auth:sanctum`).
2. **Frontend + HPO autocomplete + Phenopacket import** *(this plan)*.
3. VRS/CAID + ACMG points engine.
4. Automated reanalysis loop + KB-change alerting.
5. Matchmaker Exchange node + Beacon endpoint.

**Existing API from Plan 1 (consumed here):**
- `GET /api/patients/{patient}/odysseys`, `POST /api/patients/{patient}/odysseys`
- `GET /api/odysseys/{odyssey}` → `{ odyssey, allowed_transitions }`
- `POST /api/odysseys/{odyssey}/transition` (422 on illegal)
- `GET /api/odysseys/{odyssey}/phenotypes`, `POST /api/odysseys/{odyssey}/phenotypes`, `DELETE /api/phenotypes/{phenotype}`
- `GET /api/odysseys/{odyssey}/phenopacket` (export)
- States: `referral → phenotyping → testing → prioritization → mdt_review → matchmaking → diagnosed/reanalysis → closed`.

> **Concurrency note for the executor:** another session is actively committing OIDC work on `v2/phase-0-scaffold`. Run this plan in an exclusive checkout or a dedicated `git worktree`, rebase onto the latest origin tip before starting, and stage files explicitly (never `git add -A`).

---

## UI / UX design contract

**Routes (added to `App.tsx`, inside the authenticated `DashboardLayout`):**
- `/rare-disease` → **RareDiseaseWorklistPage** — global odyssey worklist (table: title, patient, status, progress, phenotype count, updated), a status filter, and a **+ New Odyssey** action (opens a dialog with patient search → title/referral reason).
- `/odysseys/:id` → **OdysseyDetailPage** — the workspace.

**OdysseyDetailPage layout (single column, dark clinical theme):**
1. Header: title, `StatusBadge` (status), progress chip (`in_progress`/`solved`/`unsolved`), patient link, referral reason.
2. **OdysseyStatusStepper** — horizontal stepper of the 9 states; current state highlighted (`--primary #9B1B30`); `allowed_transitions` rendered as action buttons; clicking proposes a transition (optional note) → mutation; illegal/blocked states are non-interactive.
3. **PhenotypeCapturePanel** — HPO deep phenotyping:
   - `HpoAutocomplete` (debounced typeahead on `/api/hpo/search`) to add a term; toggles for **excluded** (explicitly absent) and optional severity term.
   - List of features with `id`, `label`, an "Absent" badge for `excluded`, severity chip; delete per row (creator/admin).
4. **Phenopacket bar** — `PhenopacketExportButton` (downloads `aurora-odyssey-<id>.json`) and `PhenopacketImportDialog` (paste/upload v2 JSON → Zod validate → import → toast with `{imported, skipped}`).
5. **Transitions log** — reverse-chronological audit (`from → to`, actor, note, time).

**Design tokens:** surfaces `--surface-base #0E0E11` / `--surface-raised #151518` / `--surface-elevated #232328`; text `--text-primary/secondary/muted`; accents primary `#9B1B30`, gold `#C9A227`, teal `#2DD4BF`. Use existing `@/components/ui` primitives (`Button`, `Modal`, `Badge`, `EmptyState`, `FormInput`, `SearchBar`, `Skeleton`, `Toast`) — **read each primitive's actual props before use**; tests assert behavior, not styling.

**Interaction states:** every query renders loading (`Skeleton`), empty (`EmptyState`), and error states. Mutations disable their trigger while pending and surface failures via toast. HPO autocomplete only queries at `q.length >= 2`, debounced 300 ms, and shows "No matches" when empty.

**Accessibility:** autocomplete is keyboard-navigable (↑/↓/Enter/Esc), inputs have labels, the stepper exposes the current step via `aria-current`.

---

## File structure

**Backend — create:**
- `backend/app/Services/RareDisease/HpoService.php`
- `backend/app/Http/Controllers/HpoTermController.php`
- `backend/app/Services/RareDisease/InvalidPhenopacketException.php`
- `backend/app/Services/RareDisease/PhenopacketImporter.php`
- `backend/tests/Unit/Services/HpoServiceTest.php`
- `backend/tests/Unit/Services/PhenopacketImporterTest.php`
- `backend/tests/Feature/Api/HpoSearchTest.php`
- `backend/tests/Feature/Api/PhenopacketImportTest.php`
- `backend/tests/Feature/Api/OdysseyWorklistTest.php`

**Backend — modify:**
- `backend/app/Http/Controllers/DiagnosticOdysseyController.php` — add `worklist()` + `importPhenopacket()` (and `PhenopacketImporter` dep)
- `backend/routes/api.php` — add 3 routes (`GET /hpo/search`, `GET /odysseys`, `POST /odysseys/{odyssey}/import-phenopacket`)

**Frontend — create (`frontend/src/features/rare-disease/`):**
- `types/index.ts`, `types/phenopacketSchema.ts`
- `api/rareDiseaseApi.ts`
- `hooks/useRareDisease.ts`, `hooks/useDebounce.ts`
- `components/HpoAutocomplete.tsx`
- `components/OdysseyStatusStepper.tsx`
- `components/PhenotypeCapturePanel.tsx`
- `components/PhenopacketImportDialog.tsx`
- `components/PhenopacketExportButton.tsx`
- `components/CreateOdysseyDialog.tsx`
- `components/__tests__/HpoAutocomplete.test.tsx`
- `components/__tests__/OdysseyStatusStepper.test.tsx`
- `components/__tests__/PhenotypeCapturePanel.test.tsx`
- `components/__tests__/PhenopacketImportDialog.test.tsx`
- `hooks/__tests__/useRareDisease.test.ts`
- `pages/RareDiseaseWorklistPage.tsx`
- `pages/OdysseyDetailPage.tsx`

**Frontend — modify:**
- `frontend/src/App.tsx` — register the two lazy routes
- the section nav (`frontend/src/components/layout/SectionSidebar.tsx` or `TopNav.tsx`) — add a "Rare Disease" item → `/rare-disease`
- `frontend/src/test/mocks/handlers.ts` — add default handlers for the new endpoints

**E2E — create:**
- `e2e/tests/rare-disease.spec.ts`

**Conventions (verified):** API modules unwrap `data.data ?? data`; paginated endpoints return the raw envelope (`{data, meta}` or `{data, current_page,…}`). Hooks live in `hooks/useX.ts`, query keys are arrays (`["odysseys", ...]`), mutations invalidate. Tests import `server` from `@/test/mocks/server` and helpers from `@/test/utils`. Run frontend checks in the container: `docker compose exec -T node sh -c "cd /app && npx tsc --noEmit"` and `… npx vitest run …`; **`npx vite build` is stricter than tsc — run both**. Backend: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter X"`, Pint after each PHP edit.

---

### Task 1: HpoService (HPO search proxy, cached)

**Files:**
- Create: `backend/app/Services/RareDisease/HpoService.php`
- Test: `backend/tests/Unit/Services/HpoServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Services\RareDisease\HpoService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    $this->service = new HpoService;
});

it('normalizes HPO search results and filters non-HP ids', function () {
    Http::fake([
        'ontology.jax.org/*' => Http::response([
            'terms' => [
                ['id' => 'HP:0001250', 'name' => 'Seizure', 'definition' => 'A seizure.', 'synonyms' => ['Seizures']],
                ['id' => 'NOTHP:1', 'name' => 'Bogus', 'definition' => null, 'synonyms' => []],
            ],
        ], 200),
    ]);

    $results = $this->service->search('seizure', 10);

    expect($results)->toHaveCount(1);
    expect($results[0])->toMatchArray([
        'id' => 'HP:0001250',
        'label' => 'Seizure',
    ]);
    expect($results[0]['synonyms'])->toBe(['Seizures']);
});

it('returns an empty array for a blank query without calling the API', function () {
    Http::fake();
    expect($this->service->search('  ', 10))->toBe([]);
    Http::assertNothingSent();
});

it('returns an empty array when the upstream API fails', function () {
    Http::fake(['ontology.jax.org/*' => Http::response('err', 500)]);
    expect($this->service->search('seizure'))->toBe([]);
});

it('caches results so a second identical query hits no HTTP', function () {
    Http::fake([
        'ontology.jax.org/*' => Http::response(['terms' => [
            ['id' => 'HP:0001250', 'name' => 'Seizure', 'definition' => null, 'synonyms' => []],
        ]], 200),
    ]);

    $this->service->search('seizure', 5);
    $this->service->search('seizure', 5);

    Http::assertSentCount(1);
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter HpoServiceTest"`
Expected: FAIL — `Class "App\Services\RareDisease\HpoService" not found`.

- [ ] **Step 3: Implement**

`backend/app/Services/RareDisease/HpoService.php`:

```php
<?php

namespace App\Services\RareDisease;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Proxies the Human Phenotype Ontology search API (ontology.jax.org) with caching.
 * The legacy hpo.jax.org host is deprecated; the current API is ontology.jax.org/api/hp.
 */
class HpoService
{
    private const BASE = 'https://ontology.jax.org/api/hp';

    /**
     * @return array<int, array{id:string,label:string,definition:?string,synonyms:array<int,string>}>
     */
    public function search(string $query, int $limit = 10): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $limit = max(1, min($limit, 25));
        $cacheKey = 'hpo:search:'.md5(mb_strtolower($query).':'.$limit);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query, $limit) {
            $response = Http::timeout(10)->acceptJson()->get(self::BASE.'/search', [
                'q' => $query,
                'limit' => $limit,
            ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('terms', []))
                ->map(fn (array $t): array => [
                    'id' => $t['id'] ?? '',
                    'label' => $t['name'] ?? '',
                    'definition' => $t['definition'] ?? null,
                    'synonyms' => array_values($t['synonyms'] ?? []),
                ])
                ->filter(fn (array $t): bool => (bool) preg_match('/^HP:\d{7}$/', $t['id']))
                ->values()
                ->all();
        });
    }
}
```

- [ ] **Step 4: Run — expect PASS (4)**

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter HpoServiceTest"`

- [ ] **Step 5: Pint + commit**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/RareDisease/HpoService.php backend/tests/Unit/Services/HpoServiceTest.php
git commit -m "feat(rare-disease): add HPO search proxy service (cached)"
```

---

### Task 2: HpoTermController + route

**Files:**
- Create: `backend/app/Http/Controllers/HpoTermController.php`
- Modify: `backend/routes/api.php`
- Test: `backend/tests/Feature/Api/HpoSearchTest.php`

- [ ] **Step 1: Write the failing feature test**

```php
<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('returns normalized HPO terms for a query', function () {
    Http::fake(['ontology.jax.org/*' => Http::response(['terms' => [
        ['id' => 'HP:0001250', 'name' => 'Seizure', 'definition' => null, 'synonyms' => []],
    ]], 200)]);

    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/hpo/search?q=seizure');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.id', 'HP:0001250')
        ->assertJsonPath('data.0.label', 'Seizure');
});

it('requires a query parameter', function () {
    $this->actingAs($this->user, 'sanctum')->getJson('/api/hpo/search')->assertStatus(422);
});

it('requires authentication', function () {
    $this->getJson('/api/hpo/search?q=seizure')->assertStatus(401);
});
```

- [ ] **Step 2: Run — expect FAIL** (route missing → 404/401 mismatch).

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter HpoSearchTest"`

- [ ] **Step 3: Implement controller**

`backend/app/Http/Controllers/HpoTermController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Services\RareDisease\HpoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HpoTermController extends Controller
{
    public function __construct(private HpoService $hpo) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:100',
            'limit' => 'sometimes|integer|min:1|max:25',
        ]);

        return ApiResponse::success(
            $this->hpo->search($validated['q'], $validated['limit'] ?? 10)
        );
    }
}
```

- [ ] **Step 4: Add the route** — in `backend/routes/api.php`, inside the `auth:sanctum` group, near the odyssey routes:

```php
    // Rare Disease — HPO terminology proxy
    Route::get('/hpo/search', [\App\Http\Controllers\HpoTermController::class, 'search'])
        ->middleware('throttle:60,1');
```

- [ ] **Step 5: Run — expect PASS (3)**, then Pint + commit (stage only your 3 files; if `routes/api.php` shows unrelated hunks, use `git add -p`):

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Http/Controllers/HpoTermController.php backend/tests/Feature/Api/HpoSearchTest.php
git add -p backend/routes/api.php   # select ONLY the /hpo/search hunk
git commit -m "feat(rare-disease): add HPO search endpoint"
```

---

### Task 3: PhenopacketImporter service

**Files:**
- Create: `backend/app/Services/RareDisease/InvalidPhenopacketException.php`
- Create: `backend/app/Services/RareDisease/PhenopacketImporter.php`
- Test: `backend/tests/Unit/Services/PhenopacketImporterTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\User;
use App\Services\RareDisease\InvalidPhenopacketException;
use App\Services\RareDisease\PhenopacketImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->importer = new PhenopacketImporter;
    $this->user = User::factory()->create();
    $this->patient = ClinicalPatient::factory()->create();
    $this->odyssey = DiagnosticOdyssey::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);
});

function samplePacket(): array
{
    return [
        'phenotypicFeatures' => [
            ['type' => ['id' => 'HP:0001250', 'label' => 'Seizure'], 'excluded' => false, 'severity' => ['id' => 'HP:0012828', 'label' => '']],
            ['type' => ['id' => 'HP:0001251', 'label' => 'Ataxia'], 'excluded' => true],
        ],
    ];
}

it('imports observed and excluded phenotype features', function () {
    $result = $this->importer->importInto($this->odyssey, samplePacket(), $this->user->id);

    expect($result)->toBe(['imported' => 2, 'skipped' => 0]);
    expect($this->odyssey->phenotypeFeatures()->count())->toBe(2);

    $seizure = $this->odyssey->phenotypeFeatures()->where('hpo_id', 'HP:0001250')->first();
    expect($seizure->excluded)->toBeFalse();
    expect($seizure->severity_hpo_id)->toBe('HP:0012828');

    $ataxia = $this->odyssey->phenotypeFeatures()->where('hpo_id', 'HP:0001251')->first();
    expect($ataxia->excluded)->toBeTrue();
});

it('is idempotent — re-importing the same packet skips existing terms', function () {
    $this->importer->importInto($this->odyssey, samplePacket(), $this->user->id);
    $result = $this->importer->importInto($this->odyssey->fresh(), samplePacket(), $this->user->id);

    expect($result)->toBe(['imported' => 0, 'skipped' => 2]);
    expect($this->odyssey->phenotypeFeatures()->count())->toBe(2);
});

it('throws when phenotypicFeatures is not an array', function () {
    $this->importer->importInto($this->odyssey, ['phenotypicFeatures' => 'nope'], $this->user->id);
})->throws(InvalidPhenopacketException::class);

it('throws on a malformed HPO id', function () {
    $this->importer->importInto($this->odyssey, [
        'phenotypicFeatures' => [['type' => ['id' => 'seizure', 'label' => 'x']]],
    ], $this->user->id);
})->throws(InvalidPhenopacketException::class);
```

- [ ] **Step 2: Run — expect FAIL** (`PhenopacketImporter` not found).

- [ ] **Step 3: Implement the exception**

`backend/app/Services/RareDisease/InvalidPhenopacketException.php`:

```php
<?php

namespace App\Services\RareDisease;

use RuntimeException;

class InvalidPhenopacketException extends RuntimeException {}
```

- [ ] **Step 4: Implement the importer**

`backend/app/Services/RareDisease/PhenopacketImporter.php`:

```php
<?php

namespace App\Services\RareDisease;

use App\Models\DiagnosticOdyssey;

/**
 * Imports GA4GH Phenopackets v2 phenotypicFeatures into an odyssey.
 * Idempotent on (odyssey_id, hpo_id): terms already present are skipped.
 * Scope: phenotype-level (genomic interpretations land in Plan 3).
 *
 * @phpstan-type ImportResult array{imported:int, skipped:int}
 */
class PhenopacketImporter
{
    /**
     * @param  array<string, mixed>  $packet
     * @return array{imported:int, skipped:int}
     */
    public function importInto(DiagnosticOdyssey $odyssey, array $packet, int $actorId): array
    {
        $features = $packet['phenotypicFeatures'] ?? [];
        if (! is_array($features)) {
            throw new InvalidPhenopacketException('phenotypicFeatures must be an array.');
        }

        $existing = $odyssey->phenotypeFeatures()->pluck('hpo_id')->all();
        $imported = 0;
        $skipped = 0;

        foreach ($features as $feature) {
            $hpoId = $feature['type']['id'] ?? null;
            if (! is_string($hpoId) || ! preg_match('/^HP:\d{7}$/', $hpoId)) {
                throw new InvalidPhenopacketException('Invalid HPO id in phenotypicFeatures: '.json_encode($hpoId));
            }

            if (in_array($hpoId, $existing, true)) {
                $skipped++;

                continue;
            }

            $odyssey->phenotypeFeatures()->create([
                'hpo_id' => $hpoId,
                'hpo_label' => $feature['type']['label'] ?? $hpoId,
                'excluded' => (bool) ($feature['excluded'] ?? false),
                'onset_hpo_id' => $feature['onset']['ontologyClass']['id'] ?? null,
                'severity_hpo_id' => $feature['severity']['id'] ?? null,
                'frequency_hpo_id' => $feature['frequency']['id'] ?? null,
                'evidence' => null,
                'recorded_by' => $actorId,
            ]);

            $existing[] = $hpoId;
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
```

- [ ] **Step 5: Run — expect PASS (4)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/RareDisease/InvalidPhenopacketException.php backend/app/Services/RareDisease/PhenopacketImporter.php backend/tests/Unit/Services/PhenopacketImporterTest.php
git commit -m "feat(rare-disease): add Phenopackets v2 importer service"
```

---

### Task 4: Worklist + import endpoints on DiagnosticOdysseyController

**Files:**
- Modify: `backend/app/Http/Controllers/DiagnosticOdysseyController.php`
- Modify: `backend/routes/api.php`
- Test: `backend/tests/Feature/Api/OdysseyWorklistTest.php`, `backend/tests/Feature/Api/PhenopacketImportTest.php`

- [ ] **Step 1: Write the failing tests**

`backend/tests/Feature/Api/OdysseyWorklistTest.php`:

```php
<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('lists all odysseys with patient + phenotype count, paginated', function () {
    $patient = ClinicalPatient::factory()->create();
    DiagnosticOdyssey::factory()->count(2)->create([
        'patient_id' => $patient->id,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/odysseys');

    $response->assertStatus(200)->assertJsonPath('success', true);
    expect($response->json('data'))->toBeArray();
    expect($response->json('meta.total'))->toBeGreaterThanOrEqual(2);
});

it('filters the worklist by status', function () {
    $patient = ClinicalPatient::factory()->create();
    DiagnosticOdyssey::factory()->create(['patient_id' => $patient->id, 'created_by' => $this->user->id, 'status' => 'phenotyping']);
    DiagnosticOdyssey::factory()->create(['patient_id' => $patient->id, 'created_by' => $this->user->id, 'status' => 'referral']);

    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/odysseys?status=phenotyping');

    $response->assertStatus(200);
    foreach ($response->json('data') as $row) {
        expect($row['status'])->toBe('phenotyping');
    }
});

it('requires authentication', function () {
    $this->getJson('/api/odysseys')->assertStatus(401);
});
```

`backend/tests/Feature/Api/PhenopacketImportTest.php`:

```php
<?php

use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
    $this->patient = ClinicalPatient::factory()->create();
    $this->odyssey = DiagnosticOdyssey::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);
});

it('imports phenotypic features from a phenopacket', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/import-phenopacket", [
            'phenotypicFeatures' => [
                ['type' => ['id' => 'HP:0001250', 'label' => 'Seizure'], 'excluded' => false],
                ['type' => ['id' => 'HP:0001251', 'label' => 'Ataxia'], 'excluded' => true],
            ],
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.imported', 2)
        ->assertJsonPath('data.skipped', 0);
    expect($this->odyssey->phenotypeFeatures()->count())->toBe(2);
});

it('returns 422 for a malformed phenopacket', function () {
    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/odysseys/{$this->odyssey->id}/import-phenopacket", [
            'phenotypicFeatures' => [['type' => ['id' => 'not-hpo']]],
        ])->assertStatus(422);
});

it('requires authentication', function () {
    $this->postJson("/api/odysseys/{$this->odyssey->id}/import-phenopacket", [])->assertStatus(401);
});
```

- [ ] **Step 2: Run — expect FAIL** for both filters.

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter 'OdysseyWorklistTest|PhenopacketImportTest'"`

- [ ] **Step 3: Modify the controller** — `backend/app/Http/Controllers/DiagnosticOdysseyController.php`:

Add imports:

```php
use App\Http\Helpers\ApiResponse;
use App\Services\RareDisease\InvalidPhenopacketException;
use App\Services\RareDisease\PhenopacketImporter;
use Illuminate\Http\Request;
```

Extend the constructor to inject the importer:

```php
    public function __construct(
        private OdysseyService $service,
        private OdysseyStateMachine $machine,
        private PhenopacketExporter $exporter,
        private PhenopacketImporter $importer,
    ) {}
```

Add these two methods (after `phenopacket()`):

```php
    public function worklist(Request $request): JsonResponse
    {
        $query = DiagnosticOdyssey::query()
            ->with('patient:id,name')
            ->withCount('phenotypeFeatures')
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('progress_status')) {
            $query->where('progress_status', $request->string('progress_status'));
        }

        return ApiResponse::paginated($query->paginate($request->integer('per_page', 25)));
    }

    public function importPhenopacket(Request $request, int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::findOrFail($odyssey);

        try {
            $result = $this->importer->importInto($model, $request->all(), $request->user()->id);
        } catch (InvalidPhenopacketException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($result);
    }
```

> Note: `patient:id,name` assumes `clinical.patients` has a `name` column (confirmed — `ClinicalPatientFactory` sets `name`). If your patient display column differs, select that instead.

- [ ] **Step 4: Add routes** — in `backend/routes/api.php`, inside the `auth:sanctum` group, with the odyssey routes:

```php
    Route::get('/odysseys', [\App\Http\Controllers\DiagnosticOdysseyController::class, 'worklist']);
    Route::post('/odysseys/{odyssey}/import-phenopacket', [\App\Http\Controllers\DiagnosticOdysseyController::class, 'importPhenopacket']);
```

> Ensure `GET /odysseys` is registered **before** `GET /odysseys/{odyssey}` is matched as a literal — Laravel matches `/odysseys` and `/odysseys/{odyssey}` distinctly, so order is fine, but keep `/odysseys` (no param) grouped with the others.

- [ ] **Step 5: Run — expect PASS (6)**, then Pint + commit (surgically stage the 2 route lines):

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Http/Controllers/DiagnosticOdysseyController.php backend/tests/Feature/Api/OdysseyWorklistTest.php backend/tests/Feature/Api/PhenopacketImportTest.php
git add -p backend/routes/api.php   # select ONLY your 2 new route lines
git commit -m "feat(rare-disease): add odyssey worklist + phenopacket import endpoints"
```

---

### Task 5: Frontend types + Phenopacket Zod schema

**Files:**
- Create: `frontend/src/features/rare-disease/types/index.ts`
- Create: `frontend/src/features/rare-disease/types/phenopacketSchema.ts`

- [ ] **Step 1: Write types** — `frontend/src/features/rare-disease/types/index.ts`:

```ts
export type OdysseyStatus =
  | "referral" | "phenotyping" | "testing" | "prioritization"
  | "mdt_review" | "matchmaking" | "diagnosed" | "reanalysis" | "closed";

export type ProgressStatus = "in_progress" | "solved" | "unsolved";

export const ODYSSEY_STATES: readonly OdysseyStatus[] = [
  "referral", "phenotyping", "testing", "prioritization",
  "mdt_review", "matchmaking", "diagnosed", "reanalysis", "closed",
];

export interface OdysseyTransition {
  id: number;
  from_status: string | null;
  to_status: OdysseyStatus;
  actor_id: number;
  note: string | null;
  created_at: string;
  actor?: { id: number; name: string };
}

export interface PhenotypeFeature {
  id: number;
  odyssey_id: number;
  hpo_id: string;
  hpo_label: string;
  excluded: boolean;
  onset_hpo_id: string | null;
  severity_hpo_id: string | null;
  frequency_hpo_id: string | null;
  evidence: string | null;
  created_at: string;
}

export interface DiagnosticOdyssey {
  id: number;
  patient_id: number;
  case_id: number | null;
  title: string;
  status: OdysseyStatus;
  progress_status: ProgressStatus;
  referral_reason: string | null;
  created_by: number;
  solved_at: string | null;
  created_at: string;
  updated_at: string;
  phenotype_features_count?: number;
  patient?: { id: number; name: string };
  transitions?: OdysseyTransition[];
  phenotype_features?: PhenotypeFeature[];
}

export interface OdysseyDetail {
  odyssey: DiagnosticOdyssey;
  allowed_transitions: OdysseyStatus[];
}

export interface HpoTerm {
  id: string;
  label: string;
  definition: string | null;
  synonyms: string[];
}

export interface PhenopacketImportResult {
  imported: number;
  skipped: number;
}

export interface CreateOdysseyInput {
  patient_id: number;
  title: string;
  referral_reason?: string;
}

export interface CreatePhenotypeInput {
  hpo_id: string;
  hpo_label: string;
  excluded?: boolean;
  severity_hpo_id?: string;
  onset_hpo_id?: string;
  frequency_hpo_id?: string;
}
```

- [ ] **Step 2: Write the Zod schema** (validates user-pasted/uploaded Phenopacket JSON before sending) — `frontend/src/features/rare-disease/types/phenopacketSchema.ts`:

```ts
import { z } from "zod";

const hpoId = z.string().regex(/^HP:\d{7}$/, "Each phenotype type.id must be a valid HPO id (HP:nnnnnnn)");

export const phenopacketImportSchema = z
  .object({
    phenotypicFeatures: z
      .array(
        z.object({
          type: z.object({ id: hpoId, label: z.string().optional() }),
          excluded: z.boolean().optional(),
        }).passthrough(),
      )
      .min(1, "Phenopacket has no phenotypicFeatures to import"),
  })
  .passthrough();

export type ValidatedPhenopacket = z.infer<typeof phenopacketImportSchema>;
```

- [ ] **Step 3: Type-check + commit** (no test of its own; exercised by hooks/components):

```bash
docker compose exec -T node sh -c "cd /app && npx tsc --noEmit"
git add frontend/src/features/rare-disease/types/index.ts frontend/src/features/rare-disease/types/phenopacketSchema.ts
git commit -m "feat(rare-disease): add frontend types + phenopacket import schema"
```

---

### Task 6: useDebounce util + API client module

**Files:**
- Create: `frontend/src/features/rare-disease/hooks/useDebounce.ts`
- Create: `frontend/src/features/rare-disease/api/rareDiseaseApi.ts`

- [ ] **Step 1: Write `useDebounce`** — `frontend/src/features/rare-disease/hooks/useDebounce.ts`:

```ts
import { useEffect, useState } from "react";

export function useDebounce<T>(value: T, delayMs = 300): T {
  const [debounced, setDebounced] = useState<T>(value);
  useEffect(() => {
    const handle = setTimeout(() => setDebounced(value), delayMs);
    return () => clearTimeout(handle);
  }, [value, delayMs]);
  return debounced;
}
```

- [ ] **Step 2: Write the API module** — `frontend/src/features/rare-disease/api/rareDiseaseApi.ts`:

```ts
import apiClient from "@/lib/api-client";
import type {
  CreateOdysseyInput,
  CreatePhenotypeInput,
  DiagnosticOdyssey,
  HpoTerm,
  OdysseyDetail,
  OdysseyStatus,
  PhenopacketImportResult,
  PhenotypeFeature,
} from "../types";

interface Paginated<T> {
  data: T[];
  meta: { total: number; page: number; per_page: number; last_page: number };
}

export async function getOdysseyWorklist(params?: {
  status?: OdysseyStatus;
  progress_status?: string;
  per_page?: number;
  page?: number;
}): Promise<Paginated<DiagnosticOdyssey>> {
  const { data } = await apiClient.get("/odysseys", { params });
  return data;
}

export async function getOdyssey(id: number): Promise<OdysseyDetail> {
  const { data } = await apiClient.get(`/odysseys/${id}`);
  return data.data ?? data;
}

export async function createOdyssey(input: CreateOdysseyInput): Promise<DiagnosticOdyssey> {
  const { patient_id, ...body } = input;
  const { data } = await apiClient.post(`/patients/${patient_id}/odysseys`, body);
  return data.data ?? data;
}

export async function transitionOdyssey(
  id: number,
  to_status: OdysseyStatus,
  note?: string,
): Promise<DiagnosticOdyssey> {
  const { data } = await apiClient.post(`/odysseys/${id}/transition`, { to_status, note });
  return data.data ?? data;
}

export async function listPhenotypes(odysseyId: number): Promise<PhenotypeFeature[]> {
  const { data } = await apiClient.get(`/odysseys/${odysseyId}/phenotypes`);
  return data.data ?? data;
}

export async function addPhenotype(
  odysseyId: number,
  input: CreatePhenotypeInput,
): Promise<PhenotypeFeature> {
  const { data } = await apiClient.post(`/odysseys/${odysseyId}/phenotypes`, input);
  return data.data ?? data;
}

export async function deletePhenotype(phenotypeId: number): Promise<void> {
  await apiClient.delete(`/phenotypes/${phenotypeId}`);
}

export async function exportPhenopacket(odysseyId: number): Promise<Record<string, unknown>> {
  const { data } = await apiClient.get(`/odysseys/${odysseyId}/phenopacket`);
  return data.data ?? data;
}

export async function importPhenopacket(
  odysseyId: number,
  packet: Record<string, unknown>,
): Promise<PhenopacketImportResult> {
  const { data } = await apiClient.post(`/odysseys/${odysseyId}/import-phenopacket`, packet);
  return data.data ?? data;
}

export async function searchHpo(q: string, limit = 10): Promise<HpoTerm[]> {
  const { data } = await apiClient.get("/hpo/search", { params: { q, limit } });
  return data.data ?? data;
}
```

- [ ] **Step 3: Type-check + commit:**

```bash
docker compose exec -T node sh -c "cd /app && npx tsc --noEmit"
git add frontend/src/features/rare-disease/hooks/useDebounce.ts frontend/src/features/rare-disease/api/rareDiseaseApi.ts
git commit -m "feat(rare-disease): add useDebounce + API client module"
```

---

### Task 7: TanStack Query hooks (+ tests)

**Files:**
- Create: `frontend/src/features/rare-disease/hooks/useRareDisease.ts`
- Test: `frontend/src/features/rare-disease/hooks/__tests__/useRareDisease.test.ts`

- [ ] **Step 1: Write the failing hook test**

`frontend/src/features/rare-disease/hooks/__tests__/useRareDisease.test.ts`:

```ts
import { describe, it, expect, afterEach } from "vitest";
import { waitFor, act } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderHookWithProviders, resetStores } from "@/test/utils";
import { useOdyssey, useHpoSearch, useTransitionOdyssey } from "../useRareDisease";

afterEach(() => resetStores());

describe("useOdyssey", () => {
  it("fetches an odyssey detail with allowed_transitions", async () => {
    server.use(
      http.get("/api/odysseys/5", () =>
        HttpResponse.json({
          success: true,
          data: {
            odyssey: { id: 5, title: "Undiagnosed", status: "referral", progress_status: "in_progress" },
            allowed_transitions: ["phenotyping"],
          },
        }),
      ),
    );

    const { result } = renderHookWithProviders(() => useOdyssey(5));
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data!.odyssey.id).toBe(5);
    expect(result.current.data!.allowed_transitions).toEqual(["phenotyping"]);
  });
});

describe("useHpoSearch", () => {
  it("does not fire when the query is shorter than 2 chars", () => {
    const { result } = renderHookWithProviders(() => useHpoSearch("a"));
    expect(result.current.fetchStatus).toBe("idle");
  });

  it("returns HPO terms for a valid query", async () => {
    server.use(
      http.get("/api/hpo/search", () =>
        HttpResponse.json({ success: true, data: [{ id: "HP:0001250", label: "Seizure", definition: null, synonyms: [] }] }),
      ),
    );
    const { result } = renderHookWithProviders(() => useHpoSearch("seizure"));
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data![0].id).toBe("HP:0001250");
  });
});

describe("useTransitionOdyssey", () => {
  it("posts a transition", async () => {
    server.use(
      http.post("/api/odysseys/5/transition", () =>
        HttpResponse.json({ success: true, data: { id: 5, status: "phenotyping", progress_status: "in_progress" } }),
      ),
    );
    const { result } = renderHookWithProviders(() => useTransitionOdyssey(5));
    await act(async () => { result.current.mutate({ to_status: "phenotyping" }); });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data!.status).toBe("phenotyping");
  });
});
```

- [ ] **Step 2: Run — expect FAIL.**

Run: `docker compose exec -T node sh -c "cd /app && npx vitest run src/features/rare-disease/hooks/__tests__/useRareDisease.test.ts"`

- [ ] **Step 3: Implement the hooks** — `frontend/src/features/rare-disease/hooks/useRareDisease.ts`:

```ts
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  addPhenotype,
  createOdyssey,
  deletePhenotype,
  exportPhenopacket,
  getOdyssey,
  getOdysseyWorklist,
  importPhenopacket,
  listPhenotypes,
  searchHpo,
  transitionOdyssey,
} from "../api/rareDiseaseApi";
import { useDebounce } from "./useDebounce";
import type { CreateOdysseyInput, CreatePhenotypeInput, OdysseyStatus } from "../types";

const KEY = "rare-disease";

export function useOdysseyWorklist(params?: { status?: OdysseyStatus; per_page?: number; page?: number }) {
  return useQuery({
    queryKey: [KEY, "worklist", params],
    queryFn: () => getOdysseyWorklist(params),
  });
}

export function useOdyssey(id: number) {
  return useQuery({
    queryKey: [KEY, "odyssey", id],
    queryFn: () => getOdyssey(id),
    enabled: Number.isFinite(id) && id > 0,
  });
}

export function useCreateOdyssey() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CreateOdysseyInput) => createOdyssey(input),
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, "worklist"] }),
  });
}

export function useTransitionOdyssey(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { to_status: OdysseyStatus; note?: string }) =>
      transitionOdyssey(id, payload.to_status, payload.note),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [KEY, "odyssey", id] });
      qc.invalidateQueries({ queryKey: [KEY, "worklist"] });
    },
  });
}

export function usePhenotypes(odysseyId: number) {
  return useQuery({
    queryKey: [KEY, "phenotypes", odysseyId],
    queryFn: () => listPhenotypes(odysseyId),
    enabled: Number.isFinite(odysseyId) && odysseyId > 0,
  });
}

export function useAddPhenotype(odysseyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CreatePhenotypeInput) => addPhenotype(odysseyId, input),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [KEY, "phenotypes", odysseyId] });
      qc.invalidateQueries({ queryKey: [KEY, "odyssey", odysseyId] });
    },
  });
}

export function useDeletePhenotype(odysseyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (phenotypeId: number) => deletePhenotype(phenotypeId),
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, "phenotypes", odysseyId] }),
  });
}

export function useImportPhenopacket(odysseyId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (packet: Record<string, unknown>) => importPhenopacket(odysseyId, packet),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: [KEY, "phenotypes", odysseyId] });
      qc.invalidateQueries({ queryKey: [KEY, "odyssey", odysseyId] });
    },
  });
}

export function useExportPhenopacket() {
  return useMutation({ mutationFn: (odysseyId: number) => exportPhenopacket(odysseyId) });
}

/** Debounced HPO autocomplete; only queries at >= 2 chars. */
export function useHpoSearch(query: string) {
  const debounced = useDebounce(query.trim(), 300);
  return useQuery({
    queryKey: [KEY, "hpo", debounced],
    queryFn: () => searchHpo(debounced),
    enabled: debounced.length >= 2,
    staleTime: 5 * 60 * 1000,
  });
}
```

- [ ] **Step 4: Run — expect PASS (4)**, then type-check + commit:

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/rare-disease/hooks/__tests__/useRareDisease.test.ts"
docker compose exec -T node sh -c "cd /app && npx tsc --noEmit"
git add frontend/src/features/rare-disease/hooks/useRareDisease.ts frontend/src/features/rare-disease/hooks/__tests__/useRareDisease.test.ts
git commit -m "feat(rare-disease): add TanStack Query hooks"
```

---

### Task 8: HpoAutocomplete component

**Files:**
- Create: `frontend/src/features/rare-disease/components/HpoAutocomplete.tsx`
- Test: `frontend/src/features/rare-disease/components/__tests__/HpoAutocomplete.test.tsx`

> First read `@/components/ui/FormInput` (or `SearchBar`) to match props; the code below uses a plain labeled `<input>` to stay dependency-light — swap in the primitive if its API fits.

- [ ] **Step 1: Write the failing test**

```tsx
import { describe, it, expect, vi, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { HpoAutocomplete } from "../HpoAutocomplete";

afterEach(() => resetStores());

describe("HpoAutocomplete", () => {
  it("shows results and fires onSelect with the chosen term", async () => {
    server.use(
      http.get("/api/hpo/search", () =>
        HttpResponse.json({ success: true, data: [{ id: "HP:0001250", label: "Seizure", definition: null, synonyms: [] }] }),
      ),
    );
    const onSelect = vi.fn();
    renderWithProviders(<HpoAutocomplete onSelect={onSelect} />);

    fireEvent.change(screen.getByLabelText(/hpo term/i), { target: { value: "seizure" } });

    const option = await screen.findByText(/Seizure/);
    fireEvent.click(option);

    await waitFor(() =>
      expect(onSelect).toHaveBeenCalledWith(
        expect.objectContaining({ id: "HP:0001250", label: "Seizure" }),
      ),
    );
  });

  it("does not query for inputs shorter than 2 chars", async () => {
    renderWithProviders(<HpoAutocomplete onSelect={vi.fn()} />);
    fireEvent.change(screen.getByLabelText(/hpo term/i), { target: { value: "s" } });
    // No results dropdown
    await waitFor(() => expect(screen.queryByRole("listbox")).toBeNull());
  });
});
```

- [ ] **Step 2: Run — expect FAIL.**

Run: `docker compose exec -T node sh -c "cd /app && npx vitest run src/features/rare-disease/components/__tests__/HpoAutocomplete.test.tsx"`

- [ ] **Step 3: Implement** — `frontend/src/features/rare-disease/components/HpoAutocomplete.tsx`:

```tsx
import { useState } from "react";
import { useHpoSearch } from "../hooks/useRareDisease";
import type { HpoTerm } from "../types";

interface HpoAutocompleteProps {
  onSelect: (term: HpoTerm) => void;
  placeholder?: string;
}

export function HpoAutocomplete({ onSelect, placeholder = "Search HPO terms…" }: HpoAutocompleteProps) {
  const [query, setQuery] = useState("");
  const [open, setOpen] = useState(false);
  const { data: terms, isFetching } = useHpoSearch(query);

  function choose(term: HpoTerm) {
    onSelect(term);
    setQuery("");
    setOpen(false);
  }

  return (
    <div className="relative">
      <label htmlFor="hpo-autocomplete" className="sr-only">HPO term</label>
      <input
        id="hpo-autocomplete"
        type="text"
        value={query}
        placeholder={placeholder}
        autoComplete="off"
        onChange={(e) => { setQuery(e.target.value); setOpen(true); }}
        onFocus={() => setOpen(true)}
        onKeyDown={(e) => { if (e.key === "Escape") setOpen(false); }}
        className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-raised)] px-3 py-2 text-[var(--text-primary)]"
      />
      {open && query.trim().length >= 2 && (
        <ul role="listbox" className="absolute z-10 mt-1 max-h-64 w-full overflow-auto rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-raised)] shadow-lg">
          {isFetching && <li className="px-3 py-2 text-sm text-[var(--text-muted)]">Searching…</li>}
          {!isFetching && (terms?.length ?? 0) === 0 && (
            <li className="px-3 py-2 text-sm text-[var(--text-muted)]">No matches</li>
          )}
          {terms?.map((term) => (
            <li key={term.id}>
              <button
                type="button"
                onClick={() => choose(term)}
                className="block w-full px-3 py-2 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--surface-elevated)]"
              >
                <span className="font-medium">{term.label}</span>{" "}
                <span className="font-mono text-xs text-[var(--text-muted)]">{term.id}</span>
              </button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
```

- [ ] **Step 4: Run — expect PASS (2)**, then tsc + commit:

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/rare-disease/components/__tests__/HpoAutocomplete.test.tsx && npx tsc --noEmit"
git add frontend/src/features/rare-disease/components/HpoAutocomplete.tsx frontend/src/features/rare-disease/components/__tests__/HpoAutocomplete.test.tsx
git commit -m "feat(rare-disease): add HpoAutocomplete component"
```

---

### Task 9: OdysseyStatusStepper component

**Files:**
- Create: `frontend/src/features/rare-disease/components/OdysseyStatusStepper.tsx`
- Test: `frontend/src/features/rare-disease/components/__tests__/OdysseyStatusStepper.test.tsx`

- [ ] **Step 1: Write the failing test**

```tsx
import { describe, it, expect, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { OdysseyStatusStepper } from "../OdysseyStatusStepper";

describe("OdysseyStatusStepper", () => {
  it("marks the current status and renders allowed transitions as buttons", () => {
    const onTransition = vi.fn();
    render(
      <OdysseyStatusStepper
        current="phenotyping"
        allowed={["testing", "mdt_review"]}
        onTransition={onTransition}
        isPending={false}
      />,
    );

    expect(screen.getByText(/phenotyping/i).closest("[aria-current]")).toHaveAttribute("aria-current", "step");

    fireEvent.click(screen.getByRole("button", { name: /testing/i }));
    expect(onTransition).toHaveBeenCalledWith("testing");
  });

  it("disables transition buttons while a transition is pending", () => {
    render(
      <OdysseyStatusStepper current="referral" allowed={["phenotyping"]} onTransition={vi.fn()} isPending />,
    );
    expect(screen.getByRole("button", { name: /phenotyping/i })).toBeDisabled();
  });
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** — `frontend/src/features/rare-disease/components/OdysseyStatusStepper.tsx`:

```tsx
import { ODYSSEY_STATES, type OdysseyStatus } from "../types";

interface OdysseyStatusStepperProps {
  current: OdysseyStatus;
  allowed: OdysseyStatus[];
  onTransition: (to: OdysseyStatus) => void;
  isPending: boolean;
}

const LABELS: Record<OdysseyStatus, string> = {
  referral: "Referral",
  phenotyping: "Phenotyping",
  testing: "Testing",
  prioritization: "Prioritization",
  mdt_review: "MDT Review",
  matchmaking: "Matchmaking",
  diagnosed: "Diagnosed",
  reanalysis: "Reanalysis",
  closed: "Closed",
};

export function OdysseyStatusStepper({ current, allowed, onTransition, isPending }: OdysseyStatusStepperProps) {
  return (
    <div>
      <ol className="flex flex-wrap gap-2">
        {ODYSSEY_STATES.map((state) => {
          const isCurrent = state === current;
          return (
            <li
              key={state}
              aria-current={isCurrent ? "step" : undefined}
              className={
                "rounded-full px-3 py-1 text-xs " +
                (isCurrent
                  ? "bg-[var(--primary)] text-white"
                  : "bg-[var(--surface-raised)] text-[var(--text-muted)]")
              }
            >
              {LABELS[state]}
            </li>
          );
        })}
      </ol>

      {allowed.length > 0 && (
        <div className="mt-3 flex flex-wrap items-center gap-2">
          <span className="text-sm text-[var(--text-secondary)]">Advance to:</span>
          {allowed.map((to) => (
            <button
              key={to}
              type="button"
              disabled={isPending}
              onClick={() => onTransition(to)}
              className="rounded-md border border-[var(--accent)] px-3 py-1 text-sm text-[var(--accent)] hover:bg-[var(--surface-elevated)] disabled:opacity-50"
            >
              {LABELS[to]}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
```

- [ ] **Step 4: Run — expect PASS (2)**, then tsc + commit:

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/rare-disease/components/__tests__/OdysseyStatusStepper.test.tsx && npx tsc --noEmit"
git add frontend/src/features/rare-disease/components/OdysseyStatusStepper.tsx frontend/src/features/rare-disease/components/__tests__/OdysseyStatusStepper.test.tsx
git commit -m "feat(rare-disease): add OdysseyStatusStepper component"
```

---

### Task 10: PhenotypeCapturePanel component

**Files:**
- Create: `frontend/src/features/rare-disease/components/PhenotypeCapturePanel.tsx`
- Test: `frontend/src/features/rare-disease/components/__tests__/PhenotypeCapturePanel.test.tsx`

- [ ] **Step 1: Write the failing test**

```tsx
import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { PhenotypeCapturePanel } from "../PhenotypeCapturePanel";

afterEach(() => resetStores());

describe("PhenotypeCapturePanel", () => {
  it("lists existing features with an Absent badge for excluded ones", async () => {
    server.use(
      http.get("/api/odysseys/7/phenotypes", () =>
        HttpResponse.json({
          success: true,
          data: [
            { id: 1, odyssey_id: 7, hpo_id: "HP:0001250", hpo_label: "Seizure", excluded: false, onset_hpo_id: null, severity_hpo_id: null, frequency_hpo_id: null, evidence: null, created_at: "2026-06-15T00:00:00Z" },
            { id: 2, odyssey_id: 7, hpo_id: "HP:0001251", hpo_label: "Ataxia", excluded: true, onset_hpo_id: null, severity_hpo_id: null, frequency_hpo_id: null, evidence: null, created_at: "2026-06-15T00:00:00Z" },
          ],
        }),
      ),
    );

    renderWithProviders(<PhenotypeCapturePanel odysseyId={7} />);

    await waitFor(() => expect(screen.getByText("Seizure")).toBeInTheDocument());
    expect(screen.getByText("Ataxia")).toBeInTheDocument();
    expect(screen.getByText(/absent/i)).toBeInTheDocument();
  });

  it("adds a phenotype after selecting an HPO term", async () => {
    server.use(
      http.get("/api/odysseys/7/phenotypes", () => HttpResponse.json({ success: true, data: [] })),
      http.get("/api/hpo/search", () =>
        HttpResponse.json({ success: true, data: [{ id: "HP:0001250", label: "Seizure", definition: null, synonyms: [] }] }),
      ),
      http.post("/api/odysseys/7/phenotypes", async ({ request }) => {
        const body = (await request.json()) as Record<string, unknown>;
        return HttpResponse.json(
          { success: true, data: { id: 9, odyssey_id: 7, excluded: false, onset_hpo_id: null, severity_hpo_id: null, frequency_hpo_id: null, evidence: null, created_at: "2026-06-15T00:00:00Z", ...body } },
          { status: 201 },
        );
      }),
    );

    renderWithProviders(<PhenotypeCapturePanel odysseyId={7} />);

    fireEvent.change(await screen.findByLabelText(/hpo term/i), { target: { value: "seizure" } });
    fireEvent.click(await screen.findByText(/Seizure/));
    fireEvent.click(screen.getByRole("button", { name: /add phenotype/i }));

    await waitFor(() => expect(screen.getByText("Seizure")).toBeInTheDocument());
  });
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** — `frontend/src/features/rare-disease/components/PhenotypeCapturePanel.tsx`:

```tsx
import { useState } from "react";
import { Trash2 } from "lucide-react";
import { HpoAutocomplete } from "./HpoAutocomplete";
import { useAddPhenotype, useDeletePhenotype, usePhenotypes } from "../hooks/useRareDisease";
import type { HpoTerm } from "../types";

interface PhenotypeCapturePanelProps {
  odysseyId: number;
}

export function PhenotypeCapturePanel({ odysseyId }: PhenotypeCapturePanelProps) {
  const { data: features, isLoading } = usePhenotypes(odysseyId);
  const add = useAddPhenotype(odysseyId);
  const remove = useDeletePhenotype(odysseyId);

  const [selected, setSelected] = useState<HpoTerm | null>(null);
  const [excluded, setExcluded] = useState(false);

  function submit() {
    if (!selected) return;
    add.mutate(
      { hpo_id: selected.id, hpo_label: selected.label, excluded },
      { onSuccess: () => { setSelected(null); setExcluded(false); } },
    );
  }

  return (
    <section className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
      <h3 className="mb-3 text-sm font-semibold text-[var(--text-primary)]">Phenotype (HPO)</h3>

      <div className="mb-2"><HpoAutocomplete onSelect={setSelected} /></div>

      {selected && (
        <div className="mb-3 flex items-center gap-3 rounded-md bg-[var(--surface-elevated)] px-3 py-2">
          <span className="text-sm text-[var(--text-primary)]">
            {selected.label} <span className="font-mono text-xs text-[var(--text-muted)]">{selected.id}</span>
          </span>
          <label className="flex items-center gap-1 text-xs text-[var(--text-secondary)]">
            <input type="checkbox" checked={excluded} onChange={(e) => setExcluded(e.target.checked)} />
            Explicitly absent
          </label>
          <button
            type="button"
            onClick={submit}
            disabled={add.isPending}
            className="ml-auto rounded-md bg-[var(--accent)] px-3 py-1 text-sm text-black disabled:opacity-50"
          >
            Add phenotype
          </button>
        </div>
      )}

      {isLoading && <p className="text-sm text-[var(--text-muted)]">Loading…</p>}
      {!isLoading && (features?.length ?? 0) === 0 && (
        <p className="text-sm text-[var(--text-muted)]">No phenotypes recorded yet.</p>
      )}

      <ul className="divide-y divide-[var(--surface-elevated)]">
        {features?.map((f) => (
          <li key={f.id} className="flex items-center gap-2 py-2">
            <span className="text-sm text-[var(--text-primary)]">{f.hpo_label}</span>
            <span className="font-mono text-xs text-[var(--text-muted)]">{f.hpo_id}</span>
            {f.excluded && (
              <span className="rounded bg-[var(--surface-elevated)] px-1.5 py-0.5 text-xs text-[var(--accent)]">Absent</span>
            )}
            {f.severity_hpo_id && (
              <span className="font-mono text-xs text-[var(--text-muted)]">sev:{f.severity_hpo_id}</span>
            )}
            <button
              type="button"
              aria-label={`Remove ${f.hpo_label}`}
              onClick={() => remove.mutate(f.id)}
              className="ml-auto text-[var(--text-muted)] hover:text-[var(--primary)]"
            >
              <Trash2 size={16} />
            </button>
          </li>
        ))}
      </ul>
    </section>
  );
}
```

- [ ] **Step 4: Run — expect PASS (2)**, then tsc + commit:

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/rare-disease/components/__tests__/PhenotypeCapturePanel.test.tsx && npx tsc --noEmit"
git add frontend/src/features/rare-disease/components/PhenotypeCapturePanel.tsx frontend/src/features/rare-disease/components/__tests__/PhenotypeCapturePanel.test.tsx
git commit -m "feat(rare-disease): add PhenotypeCapturePanel"
```

---

### Task 11: Phenopacket import dialog + export button

**Files:**
- Create: `frontend/src/features/rare-disease/components/PhenopacketImportDialog.tsx`
- Create: `frontend/src/features/rare-disease/components/PhenopacketExportButton.tsx`
- Test: `frontend/src/features/rare-disease/components/__tests__/PhenopacketImportDialog.test.tsx`

> Read `@/components/ui/Modal` first; the dialog below assumes `<Modal open onClose>{children}</Modal>` — adapt to the real prop names.

- [ ] **Step 1: Write the failing test**

```tsx
import { describe, it, expect, afterEach, vi } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { PhenopacketImportDialog } from "../PhenopacketImportDialog";

afterEach(() => resetStores());

describe("PhenopacketImportDialog", () => {
  it("rejects invalid JSON / schema before sending", async () => {
    renderWithProviders(<PhenopacketImportDialog odysseyId={7} open onClose={vi.fn()} />);
    fireEvent.change(screen.getByLabelText(/phenopacket json/i), { target: { value: "{ not json" } });
    fireEvent.click(screen.getByRole("button", { name: /^import$/i }));
    expect(await screen.findByText(/invalid json|valid hpo|phenotypicfeatures/i)).toBeInTheDocument();
  });

  it("imports a valid phenopacket and reports the result", async () => {
    server.use(
      http.post("/api/odysseys/7/import-phenopacket", () =>
        HttpResponse.json({ success: true, data: { imported: 1, skipped: 0 } }),
      ),
    );
    const onClose = vi.fn();
    renderWithProviders(<PhenopacketImportDialog odysseyId={7} open onClose={onClose} />);

    const valid = JSON.stringify({ phenotypicFeatures: [{ type: { id: "HP:0001250", label: "Seizure" } }] });
    fireEvent.change(screen.getByLabelText(/phenopacket json/i), { target: { value: valid } });
    fireEvent.click(screen.getByRole("button", { name: /^import$/i }));

    await waitFor(() => expect(screen.getByText(/imported 1/i)).toBeInTheDocument());
  });
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement the dialog** — `frontend/src/features/rare-disease/components/PhenopacketImportDialog.tsx`:

```tsx
import { useState } from "react";
import { useImportPhenopacket } from "../hooks/useRareDisease";
import { phenopacketImportSchema } from "../types/phenopacketSchema";

interface PhenopacketImportDialogProps {
  odysseyId: number;
  open: boolean;
  onClose: () => void;
}

export function PhenopacketImportDialog({ odysseyId, open, onClose }: PhenopacketImportDialogProps) {
  const [raw, setRaw] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<string | null>(null);
  const importMut = useImportPhenopacket(odysseyId);

  if (!open) return null;

  function submit() {
    setError(null);
    setResult(null);
    let parsed: unknown;
    try {
      parsed = JSON.parse(raw);
    } catch {
      setError("Invalid JSON");
      return;
    }
    const check = phenopacketImportSchema.safeParse(parsed);
    if (!check.success) {
      setError(check.error.issues[0]?.message ?? "Invalid phenopacket");
      return;
    }
    importMut.mutate(check.data as Record<string, unknown>, {
      onSuccess: (r) => setResult(`Imported ${r.imported}, skipped ${r.skipped}`),
      onError: () => setError("Import failed"),
    });
  }

  return (
    <div role="dialog" aria-modal="true" className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="w-full max-w-lg rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <h3 className="mb-2 text-sm font-semibold text-[var(--text-primary)]">Import Phenopacket (GA4GH v2)</h3>
        <label htmlFor="pp-json" className="mb-1 block text-xs text-[var(--text-secondary)]">Phenopacket JSON</label>
        <textarea
          id="pp-json"
          rows={10}
          value={raw}
          onChange={(e) => setRaw(e.target.value)}
          className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] p-2 font-mono text-xs text-[var(--text-primary)]"
        />
        {error && <p className="mt-2 text-sm text-[var(--primary)]">{error}</p>}
        {result && <p className="mt-2 text-sm text-[var(--teal)]">{result}</p>}
        <div className="mt-3 flex justify-end gap-2">
          <button type="button" onClick={onClose} className="rounded-md px-3 py-1 text-sm text-[var(--text-secondary)]">Close</button>
          <button
            type="button"
            onClick={submit}
            disabled={importMut.isPending}
            className="rounded-md bg-[var(--accent)] px-3 py-1 text-sm text-black disabled:opacity-50"
          >
            Import
          </button>
        </div>
      </div>
    </div>
  );
}
```

- [ ] **Step 4: Implement the export button** — `frontend/src/features/rare-disease/components/PhenopacketExportButton.tsx`:

```tsx
import { Download } from "lucide-react";
import { useExportPhenopacket } from "../hooks/useRareDisease";

export function PhenopacketExportButton({ odysseyId }: { odysseyId: number }) {
  const exportMut = useExportPhenopacket();

  function run() {
    exportMut.mutate(odysseyId, {
      onSuccess: (packet) => {
        const blob = new Blob([JSON.stringify(packet, null, 2)], { type: "application/json" });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `aurora-odyssey-${odysseyId}.json`;
        a.click();
        URL.revokeObjectURL(url);
      },
    });
  }

  return (
    <button
      type="button"
      onClick={run}
      disabled={exportMut.isPending}
      className="inline-flex items-center gap-1 rounded-md border border-[var(--surface-elevated)] px-3 py-1 text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-elevated)] disabled:opacity-50"
    >
      <Download size={14} /> Export Phenopacket
    </button>
  );
}
```

- [ ] **Step 5: Run — expect PASS (2)**, then tsc + commit:

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/rare-disease/components/__tests__/PhenopacketImportDialog.test.tsx && npx tsc --noEmit"
git add frontend/src/features/rare-disease/components/PhenopacketImportDialog.tsx frontend/src/features/rare-disease/components/PhenopacketExportButton.tsx frontend/src/features/rare-disease/components/__tests__/PhenopacketImportDialog.test.tsx
git commit -m "feat(rare-disease): add Phenopacket import dialog + export button"
```

---

### Task 12: OdysseyDetailPage + route

**Files:**
- Create: `frontend/src/features/rare-disease/pages/OdysseyDetailPage.tsx`
- Modify: `frontend/src/App.tsx`

> No new bespoke logic — this page composes the tested components. A render smoke test is included.

- [ ] **Step 1: Implement the page** — `frontend/src/features/rare-disease/pages/OdysseyDetailPage.tsx`:

```tsx
import { useState } from "react";
import { useParams, Link } from "react-router-dom";
import { OdysseyStatusStepper } from "../components/OdysseyStatusStepper";
import { PhenotypeCapturePanel } from "../components/PhenotypeCapturePanel";
import { PhenopacketExportButton } from "../components/PhenopacketExportButton";
import { PhenopacketImportDialog } from "../components/PhenopacketImportDialog";
import { useOdyssey, useTransitionOdyssey } from "../hooks/useRareDisease";

export default function OdysseyDetailPage() {
  const { id } = useParams<{ id: string }>();
  const odysseyId = Number(id);
  const { data, isLoading, isError } = useOdyssey(odysseyId);
  const transition = useTransitionOdyssey(odysseyId);
  const [importOpen, setImportOpen] = useState(false);

  if (isLoading) return <p className="text-[var(--text-muted)]">Loading odyssey…</p>;
  if (isError || !data) return <p className="text-[var(--primary)]">Could not load this odyssey.</p>;

  const { odyssey, allowed_transitions } = data;

  return (
    <div className="space-y-4">
      <div>
        <Link to="/rare-disease" className="text-xs text-[var(--text-muted)] hover:text-[var(--text-secondary)]">← Rare-disease worklist</Link>
        <h1 className="mt-1 text-2xl font-semibold text-[var(--text-primary)]">{odyssey.title}</h1>
        <div className="mt-1 flex items-center gap-2 text-sm">
          <span className="rounded bg-[var(--surface-elevated)] px-2 py-0.5 text-[var(--text-secondary)]">{odyssey.status}</span>
          <span className="rounded bg-[var(--surface-elevated)] px-2 py-0.5 text-[var(--accent)]">{odyssey.progress_status}</span>
          {odyssey.patient && (
            <Link to={`/profiles/${odyssey.patient_id}`} className="text-[var(--teal)] hover:underline">{odyssey.patient.name}</Link>
          )}
        </div>
        {odyssey.referral_reason && <p className="mt-2 text-sm text-[var(--text-secondary)]">{odyssey.referral_reason}</p>}
      </div>

      <OdysseyStatusStepper
        current={odyssey.status}
        allowed={allowed_transitions}
        isPending={transition.isPending}
        onTransition={(to) => transition.mutate({ to_status: to })}
      />

      <div className="flex flex-wrap gap-2">
        <PhenopacketExportButton odysseyId={odysseyId} />
        <button
          type="button"
          onClick={() => setImportOpen(true)}
          className="rounded-md border border-[var(--surface-elevated)] px-3 py-1 text-sm text-[var(--text-secondary)] hover:bg-[var(--surface-elevated)]"
        >
          Import Phenopacket
        </button>
      </div>

      <PhenotypeCapturePanel odysseyId={odysseyId} />

      {odyssey.transitions && odyssey.transitions.length > 0 && (
        <section className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
          <h3 className="mb-2 text-sm font-semibold text-[var(--text-primary)]">History</h3>
          <ul className="space-y-1 text-xs text-[var(--text-muted)]">
            {odyssey.transitions.map((t) => (
              <li key={t.id}>
                {t.from_status ?? "—"} → <span className="text-[var(--text-secondary)]">{t.to_status}</span>
                {t.actor ? ` · ${t.actor.name}` : ""} {t.note ? `· ${t.note}` : ""}
              </li>
            ))}
          </ul>
        </section>
      )}

      <PhenopacketImportDialog odysseyId={odysseyId} open={importOpen} onClose={() => setImportOpen(false)} />
    </div>
  );
}
```

- [ ] **Step 2: Register the route** — in `frontend/src/App.tsx`:

Add the lazy import alongside the others:

```tsx
const OdysseyDetailPage = lazy(() => import("@/features/rare-disease/pages/OdysseyDetailPage"));
```

Add the route inside the `DashboardLayout` route block (near genomics):

```tsx
                {/* Rare Disease */}
                <Route path="odysseys/:id" element={<OdysseyDetailPage />} />
```

- [ ] **Step 3: Verify build + commit:**

```bash
docker compose exec -T node sh -c "cd /app && npx tsc --noEmit && npx vite build"
git add frontend/src/features/rare-disease/pages/OdysseyDetailPage.tsx frontend/src/App.tsx
git commit -m "feat(rare-disease): add OdysseyDetailPage + route"
```

---

### Task 13: RareDiseaseWorklistPage + CreateOdysseyDialog + route + nav

**Files:**
- Create: `frontend/src/features/rare-disease/components/CreateOdysseyDialog.tsx`
- Create: `frontend/src/features/rare-disease/pages/RareDiseaseWorklistPage.tsx`
- Modify: `frontend/src/App.tsx`
- Modify: the section nav (`frontend/src/components/layout/SectionSidebar.tsx` or `TopNav.tsx`)

> The create dialog needs a patient. Reuse the existing patient search endpoint `GET /api/patients/search?q=` (used by the patient feature). Read that feature's search hook/endpoint to match its response shape; the code below assumes results of `{ id, name }`.

- [ ] **Step 1: Implement CreateOdysseyDialog** — `frontend/src/features/rare-disease/components/CreateOdysseyDialog.tsx`:

```tsx
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useQuery } from "@tanstack/react-query";
import apiClient from "@/lib/api-client";
import { useDebounce } from "../hooks/useDebounce";
import { useCreateOdyssey } from "../hooks/useRareDisease";

interface PatientHit { id: number; name: string }

function usePatientSearch(q: string) {
  const debounced = useDebounce(q.trim(), 300);
  return useQuery({
    queryKey: ["patient-search", debounced],
    queryFn: async (): Promise<PatientHit[]> => {
      const { data } = await apiClient.get("/patients/search", { params: { q: debounced } });
      return data.data ?? data;
    },
    enabled: debounced.length >= 2,
  });
}

export function CreateOdysseyDialog({ open, onClose }: { open: boolean; onClose: () => void }) {
  const navigate = useNavigate();
  const create = useCreateOdyssey();
  const [q, setQ] = useState("");
  const [patient, setPatient] = useState<PatientHit | null>(null);
  const [title, setTitle] = useState("");
  const [reason, setReason] = useState("");
  const { data: hits } = usePatientSearch(q);

  if (!open) return null;

  function submit() {
    if (!patient || title.trim() === "") return;
    create.mutate(
      { patient_id: patient.id, title: title.trim(), referral_reason: reason || undefined },
      { onSuccess: (o) => { onClose(); navigate(`/odysseys/${o.id}`); } },
    );
  }

  return (
    <div role="dialog" aria-modal="true" className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="w-full max-w-md rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <h3 className="mb-3 text-sm font-semibold text-[var(--text-primary)]">New Diagnostic Odyssey</h3>

        {!patient ? (
          <div>
            <label htmlFor="patient-q" className="mb-1 block text-xs text-[var(--text-secondary)]">Patient</label>
            <input id="patient-q" value={q} onChange={(e) => setQ(e.target.value)} placeholder="Search patients…"
              className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-3 py-2 text-[var(--text-primary)]" />
            <ul className="mt-1 max-h-40 overflow-auto">
              {hits?.map((p) => (
                <li key={p.id}>
                  <button type="button" onClick={() => setPatient(p)} className="block w-full px-2 py-1 text-left text-sm text-[var(--text-primary)] hover:bg-[var(--surface-elevated)]">{p.name}</button>
                </li>
              ))}
            </ul>
          </div>
        ) : (
          <p className="mb-2 text-sm text-[var(--text-secondary)]">Patient: <span className="text-[var(--text-primary)]">{patient.name}</span>{" "}
            <button type="button" onClick={() => setPatient(null)} className="text-xs text-[var(--text-muted)] underline">change</button></p>
        )}

        <label htmlFor="od-title" className="mb-1 mt-3 block text-xs text-[var(--text-secondary)]">Title</label>
        <input id="od-title" value={title} onChange={(e) => setTitle(e.target.value)}
          className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-3 py-2 text-[var(--text-primary)]" />

        <label htmlFor="od-reason" className="mb-1 mt-3 block text-xs text-[var(--text-secondary)]">Referral reason (optional)</label>
        <textarea id="od-reason" rows={2} value={reason} onChange={(e) => setReason(e.target.value)}
          className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-3 py-2 text-[var(--text-primary)]" />

        <div className="mt-4 flex justify-end gap-2">
          <button type="button" onClick={onClose} className="rounded-md px-3 py-1 text-sm text-[var(--text-secondary)]">Cancel</button>
          <button type="button" onClick={submit} disabled={!patient || title.trim() === "" || create.isPending}
            className="rounded-md bg-[var(--accent)] px-3 py-1 text-sm text-black disabled:opacity-50">Create</button>
        </div>
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Implement the worklist page** — `frontend/src/features/rare-disease/pages/RareDiseaseWorklistPage.tsx`:

```tsx
import { useState } from "react";
import { Link } from "react-router-dom";
import { useOdysseyWorklist } from "../hooks/useRareDisease";
import { CreateOdysseyDialog } from "../components/CreateOdysseyDialog";

export default function RareDiseaseWorklistPage() {
  const { data, isLoading } = useOdysseyWorklist();
  const [createOpen, setCreateOpen] = useState(false);

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold text-[var(--text-primary)]">Rare-Disease Odysseys</h1>
        <button type="button" onClick={() => setCreateOpen(true)}
          className="rounded-md bg-[var(--primary)] px-3 py-1.5 text-sm text-white">+ New Odyssey</button>
      </div>

      {isLoading && <p className="text-[var(--text-muted)]">Loading worklist…</p>}
      {!isLoading && (data?.data.length ?? 0) === 0 && (
        <p className="text-[var(--text-muted)]">No odysseys yet. Create one to begin a diagnostic odyssey.</p>
      )}

      {data && data.data.length > 0 && (
        <table className="w-full text-left text-sm">
          <thead className="text-xs text-[var(--text-muted)]">
            <tr><th className="py-2">Title</th><th>Patient</th><th>Status</th><th>Progress</th><th>Phenotypes</th></tr>
          </thead>
          <tbody>
            {data.data.map((o) => (
              <tr key={o.id} className="border-t border-[var(--surface-elevated)]">
                <td className="py-2"><Link to={`/odysseys/${o.id}`} className="text-[var(--teal)] hover:underline">{o.title}</Link></td>
                <td className="text-[var(--text-secondary)]">{o.patient?.name ?? "—"}</td>
                <td className="text-[var(--text-secondary)]">{o.status}</td>
                <td className="text-[var(--text-secondary)]">{o.progress_status}</td>
                <td className="text-[var(--text-secondary)]">{o.phenotype_features_count ?? 0}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      <CreateOdysseyDialog open={createOpen} onClose={() => setCreateOpen(false)} />
    </div>
  );
}
```

- [ ] **Step 3: Register route + nav** — in `frontend/src/App.tsx` add the lazy import and route:

```tsx
const RareDiseaseWorklistPage = lazy(() => import("@/features/rare-disease/pages/RareDiseaseWorklistPage"));
```
```tsx
                <Route path="rare-disease" element={<RareDiseaseWorklistPage />} />
```

Then add a nav item to the section nav. **Read `frontend/src/components/layout/SectionSidebar.tsx` first** to match its item shape (icon + label + `to`); add an entry like `{ to: "/rare-disease", label: "Rare Disease", icon: Dna }` (import `Dna` from `lucide-react`).

- [ ] **Step 4: Verify build + commit:**

```bash
docker compose exec -T node sh -c "cd /app && npx tsc --noEmit && npx vite build"
git add frontend/src/features/rare-disease/components/CreateOdysseyDialog.tsx frontend/src/features/rare-disease/pages/RareDiseaseWorklistPage.tsx frontend/src/App.tsx frontend/src/components/layout/SectionSidebar.tsx
git commit -m "feat(rare-disease): add worklist page + create dialog + route + nav"
```

---

### Task 14: Default MSW handlers (keep the wider frontend suite green)

**Files:**
- Modify: `frontend/src/test/mocks/handlers.ts`

- [ ] **Step 1: Add default handlers** so any component/page rendered in unrelated tests has safe defaults. Append to the `handlers` array in `frontend/src/test/mocks/handlers.ts`:

```ts
  http.get("/api/odysseys", () =>
    HttpResponse.json({ success: true, data: [], meta: { total: 0, page: 1, per_page: 25, last_page: 1 } }),
  ),
  http.get("/api/hpo/search", () => HttpResponse.json({ success: true, data: [] })),
```

- [ ] **Step 2: Run the full frontend unit suite** to confirm no regressions:

Run: `docker compose exec -T node sh -c "cd /app && npx vitest run"`
Expected: all green (existing + new rare-disease tests).

- [ ] **Step 3: Commit:**

```bash
git add frontend/src/test/mocks/handlers.ts
git commit -m "test(rare-disease): add default MSW handlers for odyssey + hpo endpoints"
```

---

### Task 15: E2E Playwright flow

**Files:**
- Create: `e2e/tests/rare-disease.spec.ts`

> Read an existing spec (e.g. `e2e/tests/case-lifecycle.spec.ts`) to match the project's auth `storageState` setup and base URL. The spec below assumes an authenticated context like the existing specs.

- [ ] **Step 1: Write the E2E spec** — `e2e/tests/rare-disease.spec.ts`:

```ts
import { test, expect } from "@playwright/test";

test.describe("Rare-disease diagnostic odyssey", () => {
  test("worklist loads and the New Odyssey dialog opens", async ({ page }) => {
    await page.goto("/rare-disease");
    await expect(page.getByRole("heading", { name: /rare-disease odysseys/i })).toBeVisible();
    await page.getByRole("button", { name: /new odyssey/i }).click();
    await expect(page.getByRole("dialog")).toBeVisible();
    await expect(page.getByText(/new diagnostic odyssey/i)).toBeVisible();
  });
});
```

- [ ] **Step 2: Run against the stack** (if the e2e harness is configured to run; otherwise document as a manual check):

Run: `docker compose exec -T node sh -c "cd /app && npx playwright test rare-disease.spec.ts"` *(or the repo's documented e2e command)*
Expected: PASS, or a documented skip if e2e isn't wired in CI.

- [ ] **Step 3: Commit:**

```bash
git add e2e/tests/rare-disease.spec.ts
git commit -m "test(rare-disease): add E2E worklist + create-dialog flow"
```

---

### Task 16: Full verification

**Files:** none (verification only)

- [ ] **Step 1: Backend — rare-disease + regression** (exclude the pre-existing `Event`/`CaseDiscussion` failures, which are unrelated):

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter 'Hpo|Phenopacket|OdysseyWorklist|DiagnosticOdyssey|Phenotype|Odyssey'"`
Then Pint: `docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint --test"`

- [ ] **Step 2: Frontend — type, build, unit:**

```bash
docker compose exec -T node sh -c "cd /app && npx tsc --noEmit"
docker compose exec -T node sh -c "cd /app && npx vite build"     # stricter than tsc — catches UNRESOLVED_IMPORT
docker compose exec -T node sh -c "cd /app && npx vitest run"
```
Expected: all green.

- [ ] **Step 3: Commit any final fixes** (only if a step surfaced an issue), e.g.:

```bash
git add -A frontend/ backend/
git commit -m "chore(rare-disease): satisfy tsc/vite/pint for frontend plan"
```

---

## Self-Review

**1. Spec coverage:**
- HPO autocomplete → Tasks 1, 2 (proxy + endpoint), 7 (`useHpoSearch` debounced ≥2 chars), 8 (component). ✓
- Phenopacket import → Tasks 3 (importer, idempotent), 4 (endpoint, 422 on malformed), 5 (Zod), 11 (dialog with client-side validation). ✓
- Phenopacket export → Task 11 (`PhenopacketExportButton`, downloads `aurora-odyssey-<id>.json`) consuming the Plan 1 endpoint. ✓
- Worklist + create → Tasks 4 (`GET /odysseys`), 13 (page + create dialog w/ patient search). ✓
- Status stepper + transitions → Tasks 9, 12. ✓
- Deep phenotyping with negation/severity → Tasks 8, 10. ✓
- Routing + nav → Tasks 12, 13. ✓
- Test integrity (no regressions) → Tasks 14, 16. ✓
- *Deferred (explicit, to later plans):* HPO label backfill for imported empty labels, onset/frequency capture UI (only severity + excluded in the add form — model + import support all), genomic interpretations (Plan 3), reanalysis (Plan 4), Matchmaker/Beacon (Plan 5), deep patient-profile tab integration (a small follow-up; worklist + create dialog provide the entry point now).

**2. Placeholder scan:** Every code step contains complete code; every test step has real assertions + exact run commands. Two intentional "read the primitive/spec first" notes (Modal props, patient-search shape, SectionSidebar item shape) are integration touch-points where reading existing code is required — flagged explicitly, not left vague.

**3. Type/name consistency:** `OdysseyStatus`/`ODYSSEY_STATES` shared across stepper, hooks, api; `useHpoSearch` enabled gate (≥2 chars) matches the component's `query.trim().length >= 2` dropdown gate and the hook test; `importInto(odyssey, packet, actorId)` signature matches controller call (`$request->all()`) and the importer test; `getOdyssey` returns `OdysseyDetail` ({odyssey, allowed_transitions}) consumed identically by `useOdyssey` and `OdysseyDetailPage`; `/api/hpo/search` returns `{success,data:[…]}` unwrapped via `data.data ?? data`. The controller constructor is widened in Task 4 to add `PhenopacketImporter` (container-resolved — no manual construction sites). ✓

**4. Risk notes:** `routes/api.php` has concurrent OIDC edits — every routes task says to stage hunks surgically (`git add -p`). `vite build` is run (stricter than tsc) in Tasks 12, 13, 16 to catch unresolved imports tsc misses.

---

## Execution Handoff

Plan complete and saved to `docs/plans/2026-06-15-rare-disease-odyssey-frontend-plan.md`. Two execution options:

1. **Subagent-Driven (recommended)** — fresh subagent per task, spec + quality review between tasks. **Run in an exclusive checkout or a dedicated `git worktree`** given the concurrent OIDC session on this branch.
2. **Inline Execution** — execute tasks here with checkpoints (only once this checkout is no longer being concurrently mutated).

Which approach?
