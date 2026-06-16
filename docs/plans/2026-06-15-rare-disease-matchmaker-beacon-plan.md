# Matchmaker Exchange Node + Beacon v2 Endpoint Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the diagnostic-odyssey loop from *tracking* undiagnosed cases into *ending* them, by exposing Aurora's rare-disease data through two GA4GH discovery standards: a **Matchmaker Exchange (MME) node** (phenotype+genotype patient matching across institutions) and a **Beacon v2 endpoint** (privacy-preserving variant discovery).

**Architecture:** Two independent, separately-shippable subsystems in the Laravel backend.
- **Phase A — MME node:** an inbound `POST /api/mme/v1/match` endpoint (authenticates a peer by token, scores Aurora odysseys against the incoming phenotype/genotype profile, returns ranked matches) + an outbound search service (serialize an Aurora odyssey to an MME patient profile, query configured peer nodes, persist results). Reuses `PhenotypeFeature` (HPO), `GenomicVariant`, `DiagnosticOdyssey` (whose `status` enum already has a `matchmaking` state). Frontend: a matches panel on the odyssey detail page.
- **Phase B — Beacon v2:** a read-only, public GA4GH Beacon v2 API over `clinical.genomic_variants` — the framework metadata endpoints plus a `g_variants` query returning `responseSummary.exists` / `numTotalResults` at boolean or count granularity (record granularity withheld for privacy).

Each phase produces working, testable software on its own and can be shipped independently.

**Tech Stack:** Laravel 11 / PHP 8.4, Pest tests, PostgreSQL (`app` + `clinical` schemas), Spatie RBAC/Sanctum, `ApiResponse` helper; React 19 + TypeScript + Vite + TanStack Query + Vitest/MSW for the MME matches panel.

---

## Scope note — two phases of the rare-disease lead initiative (Plan 5)

This is Plan 5 (capstone) of the rare-disease lead initiative. Plans 1–4 (odyssey foundation, HPO/Phenopacket frontend, ACMG engine, reanalysis loop + VRS + ClinGen GDV) are shipped. Phase A (MME) is the higher-value capstone; Phase B (Beacon) is a lighter, independent read-only API. They may be executed/shipped separately.

**Reuse map (verified against the codebase — do not rebuild these):**
- `App\Models\PhenotypeFeature` — `app.phenotype_features` (`odyssey_id, hpo_id, hpo_label, excluded, onset_hpo_id, severity_hpo_id, frequency_hpo_id, evidence`). Belongs to `DiagnosticOdyssey`.
- `App\Models\DiagnosticOdyssey` — `app.diagnostic_odysseys` (`patient_id, case_id, title, status, progress_status, …`); relations `patient()`, `phenotypeFeatures()`. Status enum includes `matchmaking`.
- `App\Models\Clinical\GenomicVariant` — `clinical.genomic_variants` (`patient_id, gene, variant, variant_type, chromosome, position, ref_allele, alt_allele, zygosity, clinical_significance`). Relation `patient()`; `gene_symbol` accessor aliases `gene`.
- `App\Models\Clinical\ClinicalPatient` — `clinical.patients` (`mrn, first_name, last_name, date_of_birth, sex`; NO `name` column); `genomicVariants()`, `odysseys()`.
- `App\Models\Clinical\PatientEmbedding` — pgvector(768) per patient (available for future similarity scoring; Phase A uses HPO-overlap scoring to stay dependency-free).
- `App\Http\Helpers\ApiResponse` — `success(mixed $data, string $message='Success', int $code=200)`, `error(string $message, int $code, mixed $errors=null)`, `paginated(LengthAwarePaginator $p, string $message='Success')`. Standard Aurora endpoints use this; **Beacon and MME endpoints DON'T** — they must return the exact GA4GH envelopes (raw `response()->json(...)`), not the Aurora envelope.
- `App\Services\RareDisease\HpoService` — `search(string $q, int $limit)`; HPO id validation regex `/^HP:\d{7}$/`.
- Routing: `routes/api.php` has an `auth:sanctum` group; public routes sit outside it. Config lives in `config/services.php` (e.g. `clingen_ar`, `anyvar` blocks). Form Requests under `app/Http/Requests/`. Service classes under `app/Services/`.
- Frontend feature module pattern: `src/features/<feature>/{types,api,hooks,components,pages}`; pages are **default exports** lazy-loaded in `src/App.tsx`; nav in `src/config/navigation.ts`; api uses default-imported `apiClient` from `@/lib/api-client`; tests use `renderWithProviders`/`renderHookWithProviders`/`resetStores` from `@/test/utils` + MSW `server` from `@/test/mocks/server` + default handlers in `@/test/mocks/handlers.ts`. `zod` is NOT available — hand-roll validation. No `any`; named exports except page components.

**Environment guardrails (every task):**
- Tests auto-target the `aurora_test` DB via `tests/TestCase::createApplication()` — run `php artisan test …` normally; NEVER pass `DB_DATABASE` or run `migrate`/`db:seed` on the default connection (it's the live dev DB). For a new migration, validate it only against the test DB: `docker compose exec -T -e DB_DATABASE=aurora_test php sh -c "cd /var/www/html && php artisan migrate --force"`.
- Run Pint after PHP edits: `docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint <files>"`. Run frontend in the `node` container; verify with `npx tsc --noEmit` AND `npx vite build` AND `npx vitest run`. No `npm install`.
- Branch `v2/phase-0-scaffold`. Commit via explicit literal pathspec (`git commit -m "…" -- <paths>`, options before `--`); new files need an explicit `git add <file>` first; NEVER `git add -A`/`.`/`-p`/`reset`/`checkout`; a concurrent session has unrelated uncommitted files — never touch them.
- `clinical` schema is NOT truncated between feature tests — clinical rows leak; use test-only sentinels (gene `TESTGENEX`, HPO `HP:0000001`, an `MME-TEST` peer token) and clean them in `beforeEach`.

---

## Domain reference — GA4GH schemas (bake in exactly)

### MME `/match` (api `application/vnd.ga4gh.matchmaker.v1.0+json`)
Request body `{ "patient": { … } }`:
- `id` (string, **req**), `label` (string, opt, no PII), `contact` (**req**: `{ name (req), href (req URL), institution?, email? }`), `species` (default `NCBITaxon:9606`), `sex` (FEMALE|MALE|OTHER|MIXED_SAMPLE|NOT_APPLICABLE, opt), `ageOfOnset` (HP term, opt), `inheritanceMode` (HP term, opt), `disorders` (array, opt).
- `features` (array, opt*): `{ id: "HP:#######" (req), label?, observed: "yes"|"no" (default "yes"), ageOfOnset? }`.
- `genomicFeatures` (array, opt*): `{ gene: { id: HGNC symbol|ensembl|entrez (req) }, variant?: { assembly, referenceName(1-22|X|Y), start(0-based), end?, referenceBases?, alternateBases? }, zygosity?: 1|2, type?: { id: "SO:#######", label? } }`.
- At least one of `features` / `genomicFeatures` is required.

Response `{ "results": [ { "score": { "patient": 0.0-1.0 }, "patient": { …same shape… } } ], "disclaimer"?, "terms"? }`.
Auth: an API key header — Aurora uses `X-Auth-Token: <token>` matched against a registered peer. Errors return `{ "message": "…" }` with 400/401/406/422.

### Beacon v2 (framework + `g_variants`)
- Framework GET endpoints: `/` and `/info` (Beacon metadata), `/service-info` (GA4GH service-info), `/configuration`, `/map`, `/entry_types`, `/filtering_terms`.
- `GET /g_variants?referenceName=&start=&referenceBases=&alternateBases=&requestedGranularity=boolean|count` — `referenceName` like `17` or `refseq:NC_000017.11`; `start` 0-based.
- Response: `{ "meta": { "beaconId", "apiVersion": "v2.0.0", "returnedGranularity", "receivedRequestSummary" }, "responseSummary": { "exists": bool, "numTotalResults"?: int } }`. Default granularity **boolean** (privacy). `numTotalResults` only when granularity=count.

---

## File structure

**Phase A — MME (backend):**
- `backend/database/migrations/2026_06_16_010001_create_mme_peers_table.php` — `app.mme_peers`
- `backend/database/migrations/2026_06_16_010002_create_mme_matches_table.php` — `app.mme_matches`
- `backend/app/Models/MmePeer.php`, `backend/app/Models/MmeMatch.php` (+ factories)
- `backend/app/Services/Matchmaker/MmeProfileSerializer.php` — odyssey → MME patient JSON
- `backend/app/Services/Matchmaker/MmeMatchService.php` — score local odysseys vs a request profile
- `backend/app/Services/Matchmaker/MmeOutboundService.php` — query peers for an odyssey, persist matches
- `backend/app/Http/Controllers/Mme/MatchController.php` — inbound `POST /api/mme/v1/match`
- `backend/app/Http/Controllers/Mme/MmeSearchController.php` — outbound trigger + match list
- `backend/app/Http/Middleware/AuthenticateMmePeer.php` — X-Auth-Token → MmePeer
- `backend/config/services.php` — `mme` block (this institution's contact + outbound peers)
- routes in `backend/routes/api.php`

**Phase A — MME (frontend):** `frontend/src/features/matchmaker/{types,api,hooks,components,pages}` + route in `src/App.tsx` + (panel embedded on `OdysseyDetailPage`).

**Phase B — Beacon (backend):**
- `backend/app/Services/Beacon/BeaconService.php` — metadata + g_variants query
- `backend/app/Http/Controllers/Beacon/BeaconController.php` — framework + g_variants endpoints
- `backend/config/services.php` — `beacon` block
- public routes in `backend/routes/api.php`

---

# PHASE A — Matchmaker Exchange node

### Task A1: Migrations — `mme_peers` + `mme_matches`

**Files:** Create the two migrations above. **Test:** `backend/tests/Feature/FactorySmokeTest.php` (add two cases later in A2).

- [ ] **Step 1: Write `app.mme_peers`** (mirror the schema-qualified pattern of `2026_06_15_030002_create_kb_change_alerts_table.php`):

```php
Schema::create('app.mme_peers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('base_url')->nullable();          // outbound: peer's MME base; null for inbound-only
    $table->text('auth_token');                       // shared secret (encrypted cast on the model)
    $table->string('direction')->default('both');     // inbound | outbound | both
    $table->boolean('active')->default(true);
    $table->string('contact_email')->nullable();
    $table->timestamp('last_seen_at')->nullable();
    $table->timestamps();
    $table->index('active');
});
```

- [ ] **Step 2: Write `app.mme_matches`**:

```php
Schema::create('app.mme_matches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('odyssey_id')->constrained('app.diagnostic_odysseys')->cascadeOnDelete();
    $table->string('direction');                      // outbound (we queried a peer) | inbound (a peer queried us)
    $table->foreignId('peer_id')->nullable()->constrained('app.mme_peers')->nullOnDelete();
    $table->decimal('score', 5, 4);                   // 0.0000–1.0000
    $table->string('matched_label')->nullable();
    $table->string('matched_contact_name')->nullable();
    $table->string('matched_contact_href')->nullable();
    $table->jsonb('matched_profile');                 // the full MME patient object returned
    $table->string('status')->default('new');         // new | reviewed | contacted | dismissed
    $table->timestamps();
    $table->index(['odyssey_id', 'status']);
});
```

- [ ] **Step 3:** Validate against the test DB: `docker compose exec -T -e DB_DATABASE=aurora_test php sh -c "cd /var/www/html && php artisan migrate --force"`. Expect both migrate cleanly.

- [ ] **Step 4: Commit** the two migration files via explicit pathspec.

---

### Task A2: Models + factories

**Files:** Create `MmePeer.php`, `MmeMatch.php`, `database/factories/MmePeerFactory.php`, `database/factories/MmeMatchFactory.php`. Modify `tests/Feature/FactorySmokeTest.php`.

- [ ] **Step 1: `MmePeer`** — `$table='app.mme_peers'`; `$fillable=['name','base_url','auth_token','direction','active','contact_email','last_seen_at']`; cast `auth_token => 'encrypted'`, `active => 'boolean'`, `last_seen_at => 'datetime'`; `use HasFactory`. Scope `scopeActive($q) => $q->where('active', true)`. Scope `scopeOutbound($q) => $q->whereIn('direction', ['outbound','both'])->whereNotNull('base_url')`. (Encrypted cast on a `text` column is correct — see project notes; do NOT use jsonb for encrypted data.)

- [ ] **Step 2: `MmeMatch`** — `$table='app.mme_matches'`; `$fillable` all columns; cast `matched_profile => 'array'`, `score => 'float'`; `use HasFactory`; relations `odyssey(): BelongsTo(DiagnosticOdyssey)`, `peer(): BelongsTo(MmePeer)`.

- [ ] **Step 3: Factories** — mirror `KbChangeAlertFactory` (explicit `newFactory()` on each model if that's the local pattern). `MmePeerFactory`: name word, base_url a fake url, auth_token a random 40-char string, direction 'both', active true. `MmeMatchFactory`: creates a `DiagnosticOdyssey` + `MmePeer`, score 0.8, matched_profile `['id'=>'ext-1','contact'=>['name'=>'Dr X','href'=>'mailto:x@y']]`, status 'new', direction 'outbound'.

- [ ] **Step 4:** Add to `FactorySmokeTest.php`: `it('creates an MmePeer via factory')` and `it('creates an MmeMatch via factory')` asserting `->exists`. Run `php artisan test tests/Feature/FactorySmokeTest.php` → green. Pint. Commit.

---

### Task A3: `MmeProfileSerializer` — odyssey → MME patient JSON

**Files:** Create `backend/app/Services/Matchmaker/MmeProfileSerializer.php`. Test: `backend/tests/Unit/Services/Matchmaker/MmeProfileSerializerTest.php` (unit → add `uses(RefreshDatabase::class)` since it touches the DB; Pest binds reset traits to Feature only).

- [ ] **Step 1: Write the failing test** — build an odyssey with 2 phenotype features (one excluded) and a patient with 1 genomic variant, serialize, assert the MME shape:

```php
use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use App\Models\Clinical\GenomicVariant;
use App\Services\Matchmaker\MmeProfileSerializer;
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('serializes an odyssey into an MME patient profile', function () {
    $odyssey = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $odyssey->id, 'hpo_id' => 'HP:0001250', 'hpo_label' => 'Seizure', 'excluded' => false]);
    PhenotypeFeature::factory()->create(['odyssey_id' => $odyssey->id, 'hpo_id' => 'HP:0001263', 'excluded' => true]);
    GenomicVariant::factory()->create(['patient_id' => $odyssey->patient_id, 'gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A']);

    $profile = app(MmeProfileSerializer::class)->serialize($odyssey);

    expect($profile['patient']['id'])->toBe('aurora-odyssey-'.$odyssey->id);
    expect($profile['patient']['contact']['name'])->not->toBe('');
    expect($profile['patient']['features'])->toHaveCount(2);
    expect(collect($profile['patient']['features'])->firstWhere('id', 'HP:0001250')['observed'])->toBe('yes');
    expect(collect($profile['patient']['features'])->firstWhere('id', 'HP:0001263')['observed'])->toBe('no');
    expect($profile['patient']['genomicFeatures'][0]['gene']['id'])->toBe('TESTGENEX');
    expect($profile['patient']['genomicFeatures'][0]['variant']['referenceName'])->toBe('17');
});
```
(If `DiagnosticOdyssey::factory()` doesn't set `patient_id`, read the factory and attach a `ClinicalPatient::factory()` patient — match how other rare-disease tests do it.)

- [ ] **Step 2:** Run → FAIL. **Step 3: Implement:**

```php
namespace App\Services\Matchmaker;

use App\Models\Clinical\GenomicVariant;
use App\Models\DiagnosticOdyssey;

class MmeProfileSerializer
{
    /** @return array{patient: array<string,mixed>} */
    public function serialize(DiagnosticOdyssey $odyssey): array
    {
        $contact = config('services.mme.contact');

        $features = $odyssey->phenotypeFeatures->map(fn ($f) => array_filter([
            'id' => $f->hpo_id,
            'label' => $f->hpo_label,
            'observed' => $f->excluded ? 'no' : 'yes',
            'ageOfOnset' => $f->onset_hpo_id,
        ], fn ($v) => $v !== null && $v !== ''))->values()->all();

        $genomicFeatures = GenomicVariant::where('patient_id', $odyssey->patient_id)
            ->whereNotNull('gene')
            ->get()
            ->map(fn ($v) => array_filter([
                'gene' => ['id' => $v->gene],
                'variant' => array_filter([
                    'assembly' => 'GRCh38',
                    'referenceName' => $v->chromosome ? ltrim((string) $v->chromosome, 'chr') : null,
                    'start' => $v->position !== null ? (int) $v->position - 1 : null, // MME start is 0-based
                    'referenceBases' => $v->ref_allele,
                    'alternateBases' => $v->alt_allele,
                ], fn ($x) => $x !== null && $x !== ''),
                'zygosity' => str_contains(strtolower((string) $v->zygosity), 'homo') ? 2 : 1,
            ], fn ($x) => $x !== null && $x !== []))->values()->all();

        return [
            'patient' => array_filter([
                'id' => 'aurora-odyssey-'.$odyssey->id,
                'label' => $odyssey->title,
                'contact' => $contact,
                'species' => 'NCBITaxon:9606',
                'features' => $features,
                'genomicFeatures' => $genomicFeatures,
            ], fn ($v) => $v !== null && $v !== []),
        ];
    }
}
```

- [ ] **Step 4:** Add the `mme` config block (Task A6 adds the rest): in `config/services.php` add `'mme' => ['contact' => ['name' => env('MME_CONTACT_NAME', 'Aurora MDT'), 'href' => env('MME_CONTACT_HREF', 'mailto:mdt@example.org'), 'institution' => env('MME_CONTACT_INSTITUTION', 'Aurora')]]`. Add the three `MME_CONTACT_*` keys to `.env.example`.

- [ ] **Step 5:** Run → PASS. Pint. Commit.

---

### Task A4: `MmeMatchService` — score local odysseys vs a request profile

**Files:** Create `backend/app/Services/Matchmaker/MmeMatchService.php`. Test: `backend/tests/Unit/Services/Matchmaker/MmeMatchServiceTest.php` (`uses(RefreshDatabase::class)`).

Scoring (defensible, dependency-free): for each local odyssey with ≥1 phenotype feature, `phenoScore = |sharedObservedHPO| / |unionObservedHPO|` (Jaccard over observed terms); `geneMatch = 1.0` if the request's genes intersect the odyssey patient's variant genes else `0.0`; `score = round(0.6*phenoScore + 0.4*geneMatch, 4)`. Return MME `results` for odysseys with `score >= 0.05`, sorted desc, capped at 50, each `{ score: { patient: <score> }, patient: <serialized via MmeProfileSerializer> }`.

- [ ] **Step 1: Write the failing test** — one local odyssey sharing 1 of 2 HPO terms + the gene with the request → expect a result with score in (0,1]; an unrelated odyssey → not returned.

```php
it('scores and returns local matches for a request profile', function () {
    $od = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $od->id, 'hpo_id' => 'HP:0001250', 'excluded' => false]);
    GenomicVariant::factory()->create(['patient_id' => $od->patient_id, 'gene' => 'TESTGENEX']);

    $request = ['patient' => [
        'id' => 'ext-1',
        'features' => [['id' => 'HP:0001250', 'observed' => 'yes'], ['id' => 'HP:0009999', 'observed' => 'yes']],
        'genomicFeatures' => [['gene' => ['id' => 'TESTGENEX']]],
    ]];

    $results = app(App\Services\Matchmaker\MmeMatchService::class)->matchAgainstLocal($request);

    expect($results)->not->toBeEmpty();
    expect($results[0]['score']['patient'])->toBeGreaterThan(0.0)->toBeLessThanOrEqual(1.0);
    expect($results[0]['patient']['id'])->toBe('aurora-odyssey-'.$od->id);
});
```

- [ ] **Step 2:** FAIL. **Step 3: Implement** `matchAgainstLocal(array $request): array`:

```php
namespace App\Services\Matchmaker;

use App\Models\Clinical\GenomicVariant;
use App\Models\DiagnosticOdyssey;

class MmeMatchService
{
    public function __construct(private MmeProfileSerializer $serializer) {}

    /** @param array<string,mixed> $request @return list<array<string,mixed>> */
    public function matchAgainstLocal(array $request): array
    {
        $reqFeatures = collect($request['patient']['features'] ?? [])
            ->filter(fn ($f) => ($f['observed'] ?? 'yes') === 'yes')
            ->pluck('id')->filter()->map('strval')->unique();
        $reqGenes = collect($request['patient']['genomicFeatures'] ?? [])
            ->pluck('gene.id')->filter()->map(fn ($g) => strtoupper((string) $g))->unique();

        if ($reqFeatures->isEmpty() && $reqGenes->isEmpty()) {
            return [];
        }

        $results = [];
        DiagnosticOdyssey::with('phenotypeFeatures')->whereHas('phenotypeFeatures')
            ->chunkById(200, function ($odysseys) use (&$results, $reqFeatures, $reqGenes) {
                foreach ($odysseys as $odyssey) {
                    $localObserved = $odyssey->phenotypeFeatures->where('excluded', false)->pluck('hpo_id')->filter()->map('strval')->unique();
                    $union = $reqFeatures->merge($localObserved)->unique();
                    $shared = $reqFeatures->intersect($localObserved);
                    $pheno = $union->isEmpty() ? 0.0 : $shared->count() / $union->count();

                    $localGenes = GenomicVariant::where('patient_id', $odyssey->patient_id)->whereNotNull('gene')
                        ->pluck('gene')->map(fn ($g) => strtoupper((string) $g))->unique();
                    $geneMatch = $reqGenes->intersect($localGenes)->isNotEmpty() ? 1.0 : 0.0;

                    $score = round(0.6 * $pheno + 0.4 * $geneMatch, 4);
                    if ($score >= 0.05) {
                        $results[] = ['odyssey' => $odyssey, 'score' => $score];
                    }
                }
            });

        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        return collect(array_slice($results, 0, 50))->map(fn ($r) => [
            'score' => ['patient' => $r['score']],
            'patient' => $this->serializer->serialize($r['odyssey'])['patient'],
        ])->all();
    }
}
```

- [ ] **Step 4:** PASS. Pint. Commit.

---

### Task A5: Peer auth middleware

**Files:** Create `backend/app/Http/Middleware/AuthenticateMmePeer.php`; register an alias `mme.peer` in `bootstrap/app.php` (next to the existing `role`/`permission` aliases). Test covered by A6's feature test.

- [ ] **Step 1: Implement** — read `X-Auth-Token`; find an active `MmePeer` whose decrypted `auth_token` matches (constant-time compare); 401 `{message}` if none; else stash the peer on the request (`$request->attributes->set('mmePeer', $peer)`) and continue. Because `auth_token` is encrypted at rest you cannot SQL-match it — load active peers and `hash_equals` each:

```php
public function handle(Request $request, Closure $next): Response
{
    $token = (string) $request->header('X-Auth-Token', '');
    $peer = $token === '' ? null : MmePeer::query()->active()
        ->get()->first(fn ($p) => hash_equals((string) $p->auth_token, $token));
    if (! $peer) {
        return response()->json(['message' => 'Unauthorized: invalid or missing X-Auth-Token.'], 401);
    }
    $peer->forceFill(['last_seen_at' => now()])->save();
    $request->attributes->set('mmePeer', $peer);
    return $next($request);
}
```

- [ ] **Step 2:** Register the `mme.peer` alias in `bootstrap/app.php` `withMiddleware(...->alias([...]))`. Pint. Commit (middleware + bootstrap/app.php; bootstrap/app.php is shared — add ONLY the one alias line, verify the diff).

---

### Task A6: Inbound match controller + outbound search + routes + config

**Files:** Create `backend/app/Http/Controllers/Mme/MatchController.php`, `backend/app/Http/Controllers/Mme/MmeSearchController.php`, `backend/app/Services/Matchmaker/MmeOutboundService.php`, `backend/app/Http/Requests/MmeMatchRequest.php`. Modify `routes/api.php`, `config/services.php`, `.env.example`. Test: `backend/tests/Feature/Api/MmeMatchTest.php`.

The MME content-type is `application/vnd.ga4gh.matchmaker.v1.0+json`. Inbound endpoint is **public** (peer-token auth via `mme.peer` middleware, NOT sanctum); outbound trigger is under `auth:sanctum`.

- [ ] **Step 1: Write the failing feature test:**

```php
use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use App\Models\MmePeer;
use App\Models\MmeMatch;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    MmePeer::where('name', 'MME-TEST')->delete();
});

it('answers an inbound match with ranked local results', function () {
    $peer = MmePeer::factory()->create(['name' => 'MME-TEST', 'auth_token' => 'secret-token-123', 'active' => true]);
    $od = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $od->id, 'hpo_id' => 'HP:0001250', 'excluded' => false]);

    $response = $this->withHeaders(['X-Auth-Token' => 'secret-token-123', 'Content-Type' => 'application/vnd.ga4gh.matchmaker.v1.0+json'])
        ->postJson('/api/mme/v1/match', ['patient' => ['id' => 'ext-1', 'contact' => ['name' => 'Dr X', 'href' => 'mailto:x@y'], 'features' => [['id' => 'HP:0001250', 'observed' => 'yes']]]]);

    $response->assertStatus(200)->assertJsonStructure(['results' => [['score' => ['patient'], 'patient' => ['id']]]]);
});

it('rejects an inbound match without a valid token', function () {
    $this->postJson('/api/mme/v1/match', ['patient' => ['id' => 'x', 'contact' => ['name' => 'a', 'href' => 'b'], 'features' => [['id' => 'HP:0001250']]]])
        ->assertStatus(401);
});

it('runs an outbound search and stores peer matches', function () {
    $user = User::where('email', 'admin@acumenus.net')->first();
    $peer = MmePeer::factory()->create(['name' => 'MME-TEST', 'base_url' => 'https://peer.test/mme', 'direction' => 'both', 'active' => true]);
    $od = DiagnosticOdyssey::factory()->create();
    PhenotypeFeature::factory()->create(['odyssey_id' => $od->id, 'hpo_id' => 'HP:0001250']);
    Http::fake(['peer.test/*' => Http::response(['results' => [['score' => ['patient' => 0.9], 'patient' => ['id' => 'peer-1', 'label' => 'Case 1', 'contact' => ['name' => 'Dr Y', 'href' => 'mailto:y@z']]]]], 200)]);

    $this->actingAs($user, 'sanctum')->postJson("/api/odysseys/{$od->id}/mme-search")->assertStatus(200);

    expect(MmeMatch::where('odyssey_id', $od->id)->where('direction', 'outbound')->count())->toBe(1);
});
```

- [ ] **Step 2:** FAIL. **Step 3: `MmeMatchRequest`** — `authorize(): true`; `rules()`: `patient` required array; `patient.id` required string; `patient.features` and `patient.genomicFeatures` nullable arrays; custom `withValidator` or `after` ensuring at least one of features/genomicFeatures present; `patient.features.*.id` `regex:/^HP:\d{7}$/`. On failure the controller must emit `{message}` (override `failedValidation` to throw an `HttpResponseException` with a 422 `{message}` JSON, NOT the Laravel default envelope).

- [ ] **Step 4: `MatchController::match(MmeMatchRequest $request)`** — `return response()->json($this->matchService->resultsEnvelope($request->validated()), 200, ['Content-Type' => 'application/vnd.ga4gh.matchmaker.v1.0+json']);` where you add `MmeMatchService::resultsEnvelope(array $req): array` returning `['results' => $this->matchAgainstLocal($req), 'disclaimer' => config('services.mme.disclaimer')]` (disclaimer nullable → array_filter).

- [ ] **Step 5: `MmeOutboundService::searchForOdyssey(DiagnosticOdyssey $odyssey): int`** — serialize the odyssey; for each `MmePeer::active()->outbound()->get()` POST `{base_url}/match` with header `X-Auth-Token: {peer->auth_token}` + the MME content-type, `Http::timeout(20)->withBody(json_encode($profile), 'application/vnd.ga4gh.matchmaker.v1.0+json')->post(...)`; on success, upsert an `MmeMatch` per returned result (dedupe on `odyssey_id+peer_id+matched_profile['id']` via a deterministic check), storing `score`, `matched_label`, `matched_contact_name/href`, `matched_profile`, `direction='outbound'`, `status='new'`. Degrade per-peer on `\Throwable` (Log::warning, continue). Return count of stored matches.

- [ ] **Step 6: `MmeSearchController`** — `search(int $odyssey)`: `MmeMatch`-creating call `$n = $this->outbound->searchForOdyssey(DiagnosticOdyssey::findOrFail($odyssey)); return ApiResponse::success(['stored' => $n]);`. `list(int $odyssey)`: `return ApiResponse::success(MmeMatch::where('odyssey_id',$odyssey)->orderByDesc('score')->get());`.

- [ ] **Step 7: Routes** in `routes/api.php`:
  - Public (outside sanctum group): `Route::post('/mme/v1/match', [\App\Http\Controllers\Mme\MatchController::class, 'match'])->middleware('mme.peer');`
  - Inside the `auth:sanctum` group: `Route::post('/odysseys/{odyssey}/mme-search', [\App\Http\Controllers\Mme\MmeSearchController::class, 'search']); Route::get('/odysseys/{odyssey}/mme-matches', [\App\Http\Controllers\Mme\MmeSearchController::class, 'list']);`

- [ ] **Step 8: Config** — extend the `mme` block: `'disclaimer' => env('MME_DISCLAIMER')` and (peers are rows in `mme_peers`, seeded/managed out-of-band). **Step 9:** Run the test → PASS (3). Pint on all new PHP. Commit (controllers, service, request, middleware-not-here, routes/api.php, config/services.php, .env.example).

---

### Task A7: Frontend — MME matches panel on the odyssey page

**Files:** Create `frontend/src/features/matchmaker/types/index.ts`, `api/matchmakerApi.ts`, `hooks/useMatchmaker.ts`, `components/MmeMatchesPanel.tsx`; modify `frontend/src/features/rare-disease/pages/OdysseyDetailPage.tsx` and `frontend/src/test/mocks/handlers.ts`. Test: `components/__tests__/MmeMatchesPanel.test.tsx`.

- [ ] **Step 1: types** — `MmeMatch { id; odyssey_id; direction: string; peer_id: number | null; score: number; matched_label: string | null; matched_contact_name: string | null; matched_contact_href: string | null; status: string; created_at: string; }`.
- [ ] **Step 2: api** — `getMmeMatches(odysseyId): Promise<MmeMatch[]>` (GET `/odysseys/${id}/mme-matches`, `data.data ?? data`); `runMmeSearch(odysseyId): Promise<{stored:number}>` (POST `/odysseys/${id}/mme-search`, `data.data ?? data`).
- [ ] **Step 3: hooks** — `useMmeMatches(odysseyId)` (useQuery, enabled when id>0); `useRunMmeSearch(odysseyId)` (useMutation → `qc.invalidateQueries({queryKey:['matchmaker','matches',odysseyId]})`).
- [ ] **Step 4: Write the failing test** — MSW GET returns one match (score 0.9, matched_label 'Case 1', contact 'Dr Y'), POST returns `{stored:1}`; render `<MmeMatchesPanel odysseyId={5} />`; assert it lists 'Case 1' and the score; clicking "Search Matchmaker Exchange" calls the POST.
- [ ] **Step 5: `MmeMatchesPanel`** — a "Search Matchmaker Exchange" button (calls `useRunMmeSearch`), a loading state, an empty state ("No matchmaker matches yet."), and a list of matches: score (as a percentage), `matched_label`, a `mailto`/contact link (`matched_contact_href`), and a status chip. Use the established token classes (mirror `ReanalysisAlertsPanel`).
- [ ] **Step 6: Embed** — in `OdysseyDetailPage.tsx`, after the Reanalysis-alerts section, add a section: `<h3>Matchmaker</h3><MmeMatchesPanel odysseyId={odysseyId} />` (use the page's `odysseyId`). Add default MSW handlers for `GET /api/odysseys/:id/mme-matches` (→ `{success:true,data:[]}`) and `POST /api/odysseys/:id/mme-search` (→ `{success:true,data:{stored:0}}`) to `handlers.ts`.
- [ ] **Step 7:** `npx vitest run src/features/matchmaker && npx tsc --noEmit && npx vite build` → green. Commit.

---

# PHASE B — Beacon v2 endpoint

### Task B1: `BeaconService` — metadata + g_variants query

**Files:** Create `backend/app/Services/Beacon/BeaconService.php`. Modify `config/services.php`, `.env.example`. Test: `backend/tests/Unit/Services/Beacon/BeaconServiceTest.php` (`uses(RefreshDatabase::class)`).

- [ ] **Step 1: Config** — `'beacon' => ['id' => env('BEACON_ID', 'org.aurora.beacon'), 'name' => env('BEACON_NAME', 'Aurora Beacon'), 'org_id' => env('BEACON_ORG_ID', 'org.aurora'), 'org_name' => env('BEACON_ORG_NAME', 'Aurora'), 'welcome_url' => env('BEACON_WELCOME_URL', 'https://aurora.example.org'), 'default_granularity' => env('BEACON_DEFAULT_GRANULARITY', 'boolean')]`. Add the keys to `.env.example`.

- [ ] **Step 2: Write the failing test** — create 2 `GenomicVariant` rows (gene TESTGENEX, chr 17, pos 43045712, G>A); query g_variants for that locus → `exists true`, count 2 at count granularity; a non-matching locus → `exists false`.

```php
it('reports variant existence and count for g_variants', function () {
    GenomicVariant::factory()->count(2)->create(['gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A']);
    $svc = app(App\Services\Beacon\BeaconService::class);

    $hit = $svc->queryGVariants(['referenceName' => '17', 'start' => 43045711, 'referenceBases' => 'G', 'alternateBases' => 'A'], 'count');
    expect($hit['responseSummary']['exists'])->toBeTrue();
    expect($hit['responseSummary']['numTotalResults'])->toBe(2);

    $miss = $svc->queryGVariants(['referenceName' => '17', 'start' => 1, 'referenceBases' => 'C', 'alternateBases' => 'T'], 'boolean');
    expect($miss['responseSummary']['exists'])->toBeFalse();
    expect($miss['responseSummary'])->not->toHaveKey('numTotalResults');
});
```
Note Beacon `start` is 0-based; Aurora `position` is 1-based → query `position = start + 1`. `referenceName` may be `17` or `refseq:NC_000017.11` → normalize to the bare chromosome (strip `refseq:NC_0000` accession to the contig number, or accept the bare form; for the test, bare `17`).

- [ ] **Step 3: Implement** `queryGVariants(array $params, string $granularity): array` (build the `responseSummary` + `meta`) and `info(): array` / `serviceInfo(): array` / `configuration(): array` / `entryTypes(): array` / `map(): array` / `filteringTerms(): array` from config (return the GA4GH framework metadata objects; keep them minimal but spec-shaped: `meta.apiVersion='v2.0.0'`, `meta.beaconId=config('services.beacon.id')`). Chromosome normalization: `$chr = preg_replace('/^refseq:NC_0+(\d+)\..*/', '$1', $referenceName)` then strip leading `chr`. Existence: `GenomicVariant::where('chromosome', $chr)->where('position', $start + 1)->when($refBases, fn($q)=>$q->where('ref_allele',$refBases))->when($altBases, fn($q)=>$q->where('alt_allele',$altBases))->count()`.

- [ ] **Step 4:** PASS. Pint. Commit.

---

### Task B2: Beacon controller + public routes

**Files:** Create `backend/app/Http/Controllers/Beacon/BeaconController.php`. Modify `routes/api.php`. Test: `backend/tests/Feature/Api/BeaconTest.php`.

Beacon endpoints are **public** (no auth) and return raw GA4GH JSON (not the Aurora `ApiResponse` envelope).

- [ ] **Step 1: Write the failing feature test:**

```php
it('serves the beacon info document', function () {
    $this->getJson('/api/beacon/')->assertStatus(200)->assertJsonPath('meta.apiVersion', 'v2.0.0')->assertJsonStructure(['meta' => ['beaconId'], 'response' => ['id', 'name']]);
});

it('answers a g_variants boolean query', function () {
    \App\Models\Clinical\GenomicVariant::factory()->create(['chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A']);
    $this->getJson('/api/beacon/g_variants?referenceName=17&start=43045711&referenceBases=G&alternateBases=A')
        ->assertStatus(200)->assertJsonPath('responseSummary.exists', true);
});

it('defaults g_variants to boolean granularity (no count leaked)', function () {
    $this->getJson('/api/beacon/g_variants?referenceName=17&start=1&referenceBases=C&alternateBases=T')
        ->assertStatus(200)->assertJsonPath('responseSummary.exists', false)->assertJsonMissingPath('responseSummary.numTotalResults');
});
```

- [ ] **Step 2:** FAIL. **Step 3: Implement** controller methods `index/info` (→ `BeaconService::info()`), `serviceInfo`, `configuration`, `map`, `entryTypes`, `filteringTerms`, `gVariants(Request $request)` (granularity from `?requestedGranularity=` defaulting to `config('services.beacon.default_granularity')`, clamp to boolean|count — never record/aggregated — then `BeaconService::queryGVariants($request->query(), $granularity)`). All return `response()->json(...)`.

- [ ] **Step 4: Routes** (public, outside the sanctum group), e.g. a `Route::prefix('beacon')->group(...)`:
```php
Route::prefix('beacon')->group(function () {
    Route::get('/', [\App\Http\Controllers\Beacon\BeaconController::class, 'index']);
    Route::get('/info', [\App\Http\Controllers\Beacon\BeaconController::class, 'info']);
    Route::get('/service-info', [\App\Http\Controllers\Beacon\BeaconController::class, 'serviceInfo']);
    Route::get('/configuration', [\App\Http\Controllers\Beacon\BeaconController::class, 'configuration']);
    Route::get('/map', [\App\Http\Controllers\Beacon\BeaconController::class, 'map']);
    Route::get('/entry_types', [\App\Http\Controllers\Beacon\BeaconController::class, 'entryTypes']);
    Route::get('/filtering_terms', [\App\Http\Controllers\Beacon\BeaconController::class, 'filteringTerms']);
    Route::get('/g_variants', [\App\Http\Controllers\Beacon\BeaconController::class, 'gVariants'])->middleware('throttle:60,1');
});
```

- [ ] **Step 5:** Run → PASS (3). Pint. Commit.

---

## Self-Review

**1. Spec coverage:**
- MME inbound `/match` (auth, scoring, GA4GH envelope + content-type) → A5, A6. ✓
- MME outbound query + persistence → A6 (`MmeOutboundService`). ✓
- MME profile serialization (HPO features + genomic features, 0-based coords) → A3. ✓
- MME match UI → A7. ✓
- Beacon framework endpoints + g_variants existence/count with boolean default → B1, B2. ✓
- Privacy: Beacon defaults to boolean granularity, never returns record-level data → B2 Step 3 clamp + test. ✓

**2. Placeholder scan:** every task has the failing test + the implementation code or precise signatures. No "add validation"/"handle edge cases" placeholders.

**3. Type consistency:** `MmeProfileSerializer::serialize` returns `{patient:…}`; `MmeMatchService::matchAgainstLocal` consumes `$request['patient']` and calls `serialize($odyssey)['patient']`; `resultsEnvelope` wraps `results`. `BeaconService::queryGVariants` returns `{meta, responseSummary}`; the controller passes `$request->query()` (referenceName/start/referenceBases/alternateBases). Names are consistent across tasks.

**Risk points for focused review during execution:** A4 (scoring correctness), A6 (auth + GA4GH envelope/content-type + outbound dedupe), B2 (granularity clamp / no record-level leak).

## Execution Handoff

Two options:
1. **Subagent-Driven (recommended)** — fresh subagent per task, spec+quality review at the risk points (A4, A6, B2), continuous execution.
2. **Inline execution** — batch with checkpoints.

Phases are independent: Phase A (MME, A1–A7) is the matchmaking capstone; Phase B (Beacon, B1–B2) is a standalone read-only API. Either can ship first.
