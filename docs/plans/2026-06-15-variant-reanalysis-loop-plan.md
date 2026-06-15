# Variant Canonical Identity (CAID) + ClinVar Reanalysis Loop & KB-Change Alerts (Plan 4)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give every patient variant a stable canonical identity (ClinGen **CAID** + the ClinVar **VariationID** join key), then run a periodic loop that detects when a patient's variant has been **reclassified in ClinVar since it was last reviewed** and auto-raises a non-device-CDS MDT review task — the documented highest-yield rare-disease feature (Solve-RD: reclassification drove 187/552 new diagnoses).

**Architecture:** Additive Laravel backend. A `ClinGenAlleleRegistryService` (HTTP, HMAC-signed) canonicalizes variants to CAID + harvests the ClinVar VariationID; a `VariantCanonicalizer` persists that plus a **baseline** ClinVar-significance snapshot. A scheduled `genomics:reanalyze-variants` command compares each canonicalized patient variant's **current** ClinVar significance (from the already-synced `clinical.clinvar_variants` table) against its baseline; qualifying **bucket-crossing / star-tier** transitions create a deduplicated `kb_change_alert` + a `PatientTask`. A frontend panel surfaces alerts with full evidence; a clinician acknowledges/dismisses. Reuses the existing `ClinVarSyncService`, `PatientTask`, and Plan 3 classification records.

**Tech Stack:** Laravel 11 / PHP 8.4, PostgreSQL (`clinical` schema), Pest + `Http::fake`, Laravel scheduler (`routes/console.php`), Pint. Frontend React 19 + TS, TanStack Query, Vitest + MSW.

---

## Scope note — this is Plan 4 of the rare-disease lead initiative

The strategy paired "VRS/CAID identity" with the reanalysis loop. This plan builds the **CAID identity + the reanalysis loop**, which is the complete headline value, and **defers two pieces** (documented, with seams in place — not gaps):

- **GA4GH VRS *computed* ID (`vrs_id`)** — needs `vrs-python` + a ~13 GB SeqRepo volume + a UTA Postgres sidecar (none installed in the `ai/` service), infeasible to TDD/CI and an ops burden. CAID (from the ClinGen Allele Registry over HTTP) already provides a **canonical, build-agnostic identity AND the ClinVar VariationID** that reanalysis consumes. This plan adds a nullable `vrs_id` column + records the provisioning + Python `AlleleTranslator` wrapper as a follow-on (prior research: vrs-python 2.3.3, `seqrepo+file://`, biocommons/uta + biocommons/seqrepo Docker images).
- **ClinGen Gene-Disease Validity** as a *second* KB-change source (GraphQL/CSV) — the `kb_change_alerts.source` column already supports it (`clinvar` now, `clingen_gdv` later); deferred to keep this plan focused on the ClinVar channel (the largest reclassification driver).

Also deferred (noted): swapping ClinVar ingestion from VCF to `variant_summary.txt.gz` (to get `DateLastEvaluated`) — this plan uses the existing VCF-synced `clinvar_variants` + an Aurora-side `baselined_at` as the "last reviewed" anchor, which is sufficient; the TSV upgrade is a `ClinVarSyncService` enhancement.

---

## Domain reference (from research — bake in)

- **Stable key:** ClinVar **VariationID** (integer). Track it, not RCV/SCV accessions.
- **Classification buckets** (ordinal pathogenicity rank): `benign` < `likely_benign` < `vus` ≈ `conflicting` < `likely_pathogenic` < `pathogenic`. "Actionable" = {`likely_pathogenic`, `pathogenic`}.
- **Star tiers** (from ClinVar `review_status`): practice guideline = 4; reviewed by expert panel = 3; criteria provided, multiple submitters, no conflicts = 2; criteria provided, single submitter / conflicting = 1; no assertion = 0.
- **Alert-worthy transitions:** HIGH = non-actionable→actionable (upgrade) OR actionable→non-actionable (downgrade of a reported variant); MEDIUM = `vus`→benign/likely_benign (de-prioritize) OR same-bucket star increase to ≥3. **Suppress** P↔LP and B↔LB churn without a bucket crossing, and star decreases (controls VUS-churn noise).
- **Cadence:** run monthly (ClinVar releases first Thursday); event-fire only on qualifying deltas. Yield to cite in clinician copy: Solve-RD 8.4% solved on reanalysis (reclassification 187/552); annual reanalysis +15.4% (Nambot 2018).
- **Non-device CDS:** alert carries evidence + a recommendation; a human raises/acknowledges the MDT task. Never auto-change a classification.

---

## File structure

**Backend — create:**
- `backend/app/Services/Genomics/Reanalysis/ClassificationBucket.php` — significance string → bucket + star tier (pure)
- `backend/app/Services/Genomics/Reanalysis/ReanalysisTransition.php` — transition → severity (pure)
- `backend/app/Services/Genomics/Reanalysis/ClinGenAlleleRegistryService.php` — CAID + VariationID via HTTP/HMAC
- `backend/app/Services/Genomics/Reanalysis/VariantCanonicalizer.php` — resolve + persist canonical id + baseline
- `backend/app/Services/Genomics/Reanalysis/ReanalysisService.php` — delta detection → alert + task
- `backend/app/Models/Clinical/VariantCanonicalId.php`, `KbChangeAlert.php`
- `backend/database/factories/Clinical/VariantCanonicalIdFactory.php`, `KbChangeAlertFactory.php`
- `backend/database/migrations/2026_06_15_030001_create_variant_canonical_ids_table.php`
- `backend/database/migrations/2026_06_15_030002_create_kb_change_alerts_table.php`
- `backend/app/Console/Commands/ReanalyzeVariantsCommand.php`
- `backend/app/Http/Controllers/VariantReanalysisController.php`
- `backend/app/Http/Requests/AcknowledgeKbAlertRequest.php`
- `backend/tests/Unit/Services/Reanalysis/{ClassificationBucketTest,ReanalysisTransitionTest,ClinGenAlleleRegistryServiceTest,VariantCanonicalizerTest,ReanalysisServiceTest}.php`
- `backend/tests/Feature/Api/{VariantReanalysisTest,ReanalyzeVariantsCommandTest}.php`

**Backend — modify:** `backend/routes/api.php` (routes), `backend/routes/console.php` (schedule), `backend/config/services.php` (ClinGen AR creds)

**Frontend — create (`frontend/src/features/reanalysis/`):** `types/index.ts`, `api/reanalysisApi.ts`, `hooks/useReanalysis.ts` (+ `__tests__`), `components/KbAlertSeverityBadge.tsx`, `KbAlertList.tsx`, `ReanalysisAlertsPanel.tsx` (+ `__tests__`).
**Frontend — modify:** a patient-context surface (the rare-disease `OdysseyDetailPage` from Plan 2 — add an alerts section), `frontend/src/test/mocks/handlers.ts`.

**Conventions (verified — same as Plans 1–3):** schema-qualified `clinical.*` tables; FK to `clinical.genomic_variants` / `clinical.patients` / `app.users`; `ApiResponse`; Form Requests; models override `newFactory()`; Pest feature tests seed `app(\Database\Seeders\SuperuserSeeder::class)->run()`; DB-touching Unit tests `uses(RefreshDatabase::class)`; **unit tests touching gene/clinvar data use a test-only gene** (DatabaseTruncation does NOT truncate `clinical`, so feature-seeded rows leak). `genomic_variants` columns: `gene, variant, chromosome, position, ref_allele, alt_allele, clinical_significance`. `clinvar_variants` columns: `variation_id, chromosome, position, reference_allele, alternate_allele, gene_symbol, hgvs, clinical_significance, review_status, is_pathogenic` (note `ref_allele`↔`reference_allele` bridge). Backend cmds in the `php` container; Pint after each edit (PHPStan not installed). **Concurrent OIDC session holds uncommitted files** — commit ONLY your files via explicit literal pathspec: `git add <paths> && git commit -m "…" -- <paths>` (zsh doesn't word-split unquoted vars; never `git add -A`/`git reset`/`git add .`). Verify branch `v2/phase-0-scaffold` before each commit.

---

### Task 1: ClassificationBucket (significance → bucket + star tier)

**Files:**
- Create: `backend/app/Services/Genomics/Reanalysis/ClassificationBucket.php`
- Test: `backend/tests/Unit/Services/Reanalysis/ClassificationBucketTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Services\Genomics\Reanalysis\ClassificationBucket;

it('normalizes ClinVar significance strings to ordered buckets', function () {
    expect(ClassificationBucket::normalize('Pathogenic'))->toBe('pathogenic');
    expect(ClassificationBucket::normalize('Likely pathogenic'))->toBe('likely_pathogenic');
    expect(ClassificationBucket::normalize('Pathogenic/Likely pathogenic'))->toBe('pathogenic');
    expect(ClassificationBucket::normalize('Uncertain significance'))->toBe('vus');
    expect(ClassificationBucket::normalize('Conflicting interpretations of pathogenicity'))->toBe('conflicting');
    expect(ClassificationBucket::normalize('Conflicting classifications of pathogenicity'))->toBe('conflicting');
    expect(ClassificationBucket::normalize('Likely benign'))->toBe('likely_benign');
    expect(ClassificationBucket::normalize('Benign/Likely benign'))->toBe('likely_benign');
    expect(ClassificationBucket::normalize('Benign'))->toBe('benign');
    expect(ClassificationBucket::normalize('not provided'))->toBe('unknown');
    expect(ClassificationBucket::normalize(null))->toBe('unknown');
});

it('ranks buckets and flags the actionable ones', function () {
    expect(ClassificationBucket::rank('pathogenic'))->toBeGreaterThan(ClassificationBucket::rank('vus'));
    expect(ClassificationBucket::rank('vus'))->toBeGreaterThan(ClassificationBucket::rank('benign'));
    expect(ClassificationBucket::isActionable('pathogenic'))->toBeTrue();
    expect(ClassificationBucket::isActionable('likely_pathogenic'))->toBeTrue();
    expect(ClassificationBucket::isActionable('vus'))->toBeFalse();
    expect(ClassificationBucket::isActionable('benign'))->toBeFalse();
});

it('maps review_status to star tiers', function () {
    expect(ClassificationBucket::stars('practice guideline'))->toBe(4);
    expect(ClassificationBucket::stars('reviewed by expert panel'))->toBe(3);
    expect(ClassificationBucket::stars('criteria provided, multiple submitters, no conflicts'))->toBe(2);
    expect(ClassificationBucket::stars('criteria provided, single submitter'))->toBe(1);
    expect(ClassificationBucket::stars('criteria provided, conflicting interpretations'))->toBe(1);
    expect(ClassificationBucket::stars('no assertion criteria provided'))->toBe(0);
    expect(ClassificationBucket::stars(null))->toBe(0);
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter ClassificationBucketTest"`

- [ ] **Step 3: Implement** — `backend/app/Services/Genomics/Reanalysis/ClassificationBucket.php`:

```php
<?php

namespace App\Services\Genomics\Reanalysis;

/**
 * Normalizes ClinVar germline classification strings + review-status into the
 * ordered buckets and star tiers used by the reanalysis transition rules.
 */
class ClassificationBucket
{
    /** Ordinal pathogenicity rank (higher = more pathogenic). */
    private const RANK = [
        'benign' => 0,
        'likely_benign' => 1,
        'unknown' => 2,
        'vus' => 2,
        'conflicting' => 2,
        'likely_pathogenic' => 3,
        'pathogenic' => 4,
    ];

    private const ACTIONABLE = ['likely_pathogenic', 'pathogenic'];

    public static function normalize(?string $significance): string
    {
        $s = strtolower(trim((string) $significance));
        if ($s === '') {
            return 'unknown';
        }

        return match (true) {
            str_contains($s, 'conflicting') => 'conflicting',
            str_contains($s, 'pathogenic') && str_contains($s, 'likely') && ! str_contains($s, '/') => 'likely_pathogenic',
            str_contains($s, 'pathogenic') => 'pathogenic', // incl. "Pathogenic/Likely pathogenic"
            str_contains($s, 'uncertain') => 'vus',
            str_contains($s, 'benign') && str_contains($s, 'likely') => 'likely_benign',
            str_contains($s, 'benign') => 'benign', // incl. "Benign/Likely benign" handled above
            default => 'unknown',
        };
    }

    public static function rank(string $bucket): int
    {
        return self::RANK[$bucket] ?? 2;
    }

    public static function isActionable(string $bucket): bool
    {
        return in_array($bucket, self::ACTIONABLE, true);
    }

    public static function stars(?string $reviewStatus): int
    {
        $s = strtolower(trim((string) $reviewStatus));

        return match (true) {
            str_contains($s, 'practice guideline') => 4,
            str_contains($s, 'expert panel') => 3,
            str_contains($s, 'multiple submitters') && ! str_contains($s, 'conflict') => 2,
            str_contains($s, 'criteria provided') => 1, // single submitter or conflicting
            default => 0,
        };
    }
}
```

> Note the ordering in `normalize`: "Benign/Likely benign" must hit the `likely` branch before the bare `benign` branch — the `likely` check precedes it. "Pathogenic/Likely pathogenic" contains `/` so it skips the LP branch and resolves to `pathogenic` (the more severe aggregate), per ClinVar convention.

- [ ] **Step 4: Run — expect PASS (3)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Reanalysis/ClassificationBucket.php backend/tests/Unit/Services/Reanalysis/ClassificationBucketTest.php
git commit -m "feat(reanalysis): add ClinVar significance bucket + star-tier mapper" -- backend/app/Services/Genomics/Reanalysis/ClassificationBucket.php backend/tests/Unit/Services/Reanalysis/ClassificationBucketTest.php
```

---

### Task 2: ReanalysisTransition (transition → severity rules)

**Files:**
- Create: `backend/app/Services/Genomics/Reanalysis/ReanalysisTransition.php`
- Test: `backend/tests/Unit/Services/Reanalysis/ReanalysisTransitionTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Services\Genomics\Reanalysis\ReanalysisTransition;

it('flags an upgrade to actionable as high severity', function () {
    expect(ReanalysisTransition::severity('vus', 'pathogenic', 1, 2))->toBe('high');
    expect(ReanalysisTransition::severity('likely_benign', 'likely_pathogenic', 1, 2))->toBe('high');
    expect(ReanalysisTransition::severity('conflicting', 'pathogenic', 1, 2))->toBe('high');
});

it('flags a downgrade of a reported actionable variant as high severity', function () {
    expect(ReanalysisTransition::severity('pathogenic', 'vus', 2, 2))->toBe('high');
    expect(ReanalysisTransition::severity('likely_pathogenic', 'benign', 2, 2))->toBe('high');
});

it('flags vus->benign as medium (de-prioritize)', function () {
    expect(ReanalysisTransition::severity('vus', 'likely_benign', 1, 1))->toBe('medium');
    expect(ReanalysisTransition::severity('vus', 'benign', 1, 1))->toBe('medium');
});

it('flags a same-bucket star increase to >=3 as medium', function () {
    expect(ReanalysisTransition::severity('pathogenic', 'pathogenic', 1, 3))->toBe('medium');
    expect(ReanalysisTransition::severity('vus', 'vus', 0, 3))->toBe('medium');
});

it('suppresses non-bucket-crossing churn and star decreases', function () {
    expect(ReanalysisTransition::severity('pathogenic', 'likely_pathogenic', 2, 2))->toBeNull(); // P<->LP
    expect(ReanalysisTransition::severity('benign', 'likely_benign', 2, 2))->toBeNull();          // B<->LB
    expect(ReanalysisTransition::severity('pathogenic', 'pathogenic', 3, 1))->toBeNull();         // star decrease
    expect(ReanalysisTransition::severity('vus', 'vus', 1, 2))->toBeNull();                       // star bump <3
    expect(ReanalysisTransition::severity('vus', 'vus', 2, 2))->toBeNull();                        // no change
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** — `backend/app/Services/Genomics/Reanalysis/ReanalysisTransition.php`:

```php
<?php

namespace App\Services\Genomics\Reanalysis;

/**
 * Decides whether a ClinVar classification change between two reviews is
 * alert-worthy, and at what severity. Suppresses clinically-cosmetic churn
 * (P<->LP, B<->LB, star decreases, sub-3-star bumps) to control VUS noise.
 */
class ReanalysisTransition
{
    /** @return 'high'|'medium'|null */
    public static function severity(string $fromBucket, string $toBucket, int $fromStars, int $toStars): ?string
    {
        $fromActionable = ClassificationBucket::isActionable($fromBucket);
        $toActionable = ClassificationBucket::isActionable($toBucket);

        // Upgrade into, or downgrade out of, the actionable (LP/P) zone.
        if ($fromActionable !== $toActionable) {
            return 'high';
        }

        // De-prioritization: VUS resolved benign.
        if ($fromBucket === 'vus' && in_array($toBucket, ['benign', 'likely_benign'], true)) {
            return 'medium';
        }

        // Same clinical bucket but confidence jumped to expert-panel/practice-guideline.
        if ($fromBucket === $toBucket && $toStars >= 3 && $fromStars < 3) {
            return 'medium';
        }

        return null;
    }
}
```

- [ ] **Step 4: Run — expect PASS (5)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Reanalysis/ReanalysisTransition.php backend/tests/Unit/Services/Reanalysis/ReanalysisTransitionTest.php
git commit -m "feat(reanalysis): add transition severity rules" -- backend/app/Services/Genomics/Reanalysis/ReanalysisTransition.php backend/tests/Unit/Services/Reanalysis/ReanalysisTransitionTest.php
```

---

### Task 3: Migrations — variant_canonical_ids + kb_change_alerts

**Files:**
- Create: `backend/database/migrations/2026_06_15_030001_create_variant_canonical_ids_table.php`
- Create: `backend/database/migrations/2026_06_15_030002_create_kb_change_alerts_table.php`

- [ ] **Step 1: Write the canonical-ids migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.variant_canonical_ids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('genomic_variant_id');
            $table->string('caid')->nullable();                 // ClinGen Allele Registry CAID
            $table->string('vrs_id')->nullable();               // GA4GH VRS computed id (deferred; column ready)
            $table->string('clinvar_variation_id')->nullable(); // stable ClinVar join key
            $table->string('dbsnp_rs')->nullable();
            $table->string('assembly')->default('GRCh38');
            // Baseline ("last reviewed in Aurora") ClinVar snapshot for delta detection:
            $table->string('baseline_significance')->nullable();
            $table->string('baseline_review_status')->nullable();
            $table->timestamp('baselined_at')->nullable();
            $table->timestamp('canonicalized_at')->nullable();
            $table->timestamps();

            $table->foreign('genomic_variant_id')->references('id')->on('clinical.genomic_variants')->onDelete('cascade');
            $table->unique('genomic_variant_id');
            $table->index('clinvar_variation_id');
            $table->index('caid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.variant_canonical_ids');
    }
};
```

- [ ] **Step 2: Write the alerts migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.kb_change_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('genomic_variant_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('source')->default('clinvar');   // clinvar, clingen_gdv (future)
            $table->string('clinvar_variation_id')->nullable();
            $table->string('from_bucket');
            $table->string('to_bucket');
            $table->integer('from_stars')->default(0);
            $table->integer('to_stars')->default(0);
            $table->string('severity');                      // high, medium
            $table->jsonb('evidence')->nullable();           // submitter, review_status, urls, etc.
            $table->string('delta_hash')->unique();          // idempotent dedup
            $table->string('status')->default('new');        // new, acknowledged, dismissed
            $table->unsignedBigInteger('task_id')->nullable();   // raised PatientTask
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->foreign('genomic_variant_id')->references('id')->on('clinical.genomic_variants')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('clinical.patients')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('app.patient_tasks')->nullOnDelete();
            $table->foreign('acknowledged_by')->references('id')->on('app.users');
            $table->index(['patient_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.kb_change_alerts');
    }
};
```

- [ ] **Step 3: Apply to the testing DB**

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan migrate --env=testing --force"`
Expected: both report DONE (ignore collation warnings); the FK to `app.patient_tasks` resolves (that table exists from Plan 1).

- [ ] **Step 4: Commit**

```bash
git add backend/database/migrations/2026_06_15_030001_create_variant_canonical_ids_table.php backend/database/migrations/2026_06_15_030002_create_kb_change_alerts_table.php
git commit -m "feat(reanalysis): add variant_canonical_ids + kb_change_alerts tables" -- backend/database/migrations/2026_06_15_030001_create_variant_canonical_ids_table.php backend/database/migrations/2026_06_15_030002_create_kb_change_alerts_table.php
```

---

### Task 4: Models + factories

**Files:**
- Create: `backend/app/Models/Clinical/VariantCanonicalId.php`, `KbChangeAlert.php`
- Create: `backend/database/factories/Clinical/VariantCanonicalIdFactory.php`, `KbChangeAlertFactory.php`
- Test: append to `backend/tests/Feature/FactorySmokeTest.php`

- [ ] **Step 1: Add failing smoke tests** — append to `backend/tests/Feature/FactorySmokeTest.php`:

```php
it('creates a VariantCanonicalId via factory', function () {
    $v = \App\Models\Clinical\VariantCanonicalId::factory()->create();
    expect($v->id)->toBeInt();
});

it('creates a KbChangeAlert via factory', function () {
    $a = \App\Models\Clinical\KbChangeAlert::factory()->create();
    expect($a->id)->toBeInt();
    expect($a->severity)->toBeString();
});
```

- [ ] **Step 2: Run — expect FAIL.**

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter FactorySmokeTest"`

- [ ] **Step 3: Create `VariantCanonicalId`** — `backend/app/Models/Clinical/VariantCanonicalId.php`:

```php
<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantCanonicalId extends Model
{
    use HasFactory;

    protected $table = 'clinical.variant_canonical_ids';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\VariantCanonicalIdFactory::new();
    }

    protected $fillable = [
        'genomic_variant_id', 'caid', 'vrs_id', 'clinvar_variation_id', 'dbsnp_rs', 'assembly',
        'baseline_significance', 'baseline_review_status', 'baselined_at', 'canonicalized_at',
    ];

    protected function casts(): array
    {
        return ['baselined_at' => 'datetime', 'canonicalized_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(GenomicVariant::class, 'genomic_variant_id');
    }
}
```

- [ ] **Step 4: Create `KbChangeAlert`** — `backend/app/Models/Clinical/KbChangeAlert.php`:

```php
<?php

namespace App\Models\Clinical;

use App\Models\PatientTask;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KbChangeAlert extends Model
{
    use HasFactory;

    protected $table = 'clinical.kb_change_alerts';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\KbChangeAlertFactory::new();
    }

    protected $fillable = [
        'genomic_variant_id', 'patient_id', 'source', 'clinvar_variation_id',
        'from_bucket', 'to_bucket', 'from_stars', 'to_stars', 'severity', 'evidence',
        'delta_hash', 'status', 'task_id', 'acknowledged_by', 'acknowledged_at', 'resolution_note',
    ];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'from_stars' => 'integer', 'to_stars' => 'integer', 'acknowledged_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(GenomicVariant::class, 'genomic_variant_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(PatientTask::class, 'task_id');
    }
}
```

- [ ] **Step 5: Create factories** — `backend/database/factories/Clinical/VariantCanonicalIdFactory.php`:

```php
<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantCanonicalId;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariantCanonicalIdFactory extends Factory
{
    protected $model = VariantCanonicalId::class;

    public function definition(): array
    {
        return [
            'genomic_variant_id' => GenomicVariant::factory(),
            'caid' => 'CA'.$this->faker->numberBetween(100000, 999999),
            'clinvar_variation_id' => (string) $this->faker->numberBetween(1000, 99999),
            'assembly' => 'GRCh38',
        ];
    }
}
```

`backend/database/factories/Clinical/KbChangeAlertFactory.php`:

```php
<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class KbChangeAlertFactory extends Factory
{
    protected $model = KbChangeAlert::class;

    public function definition(): array
    {
        $variant = GenomicVariant::factory()->create();

        return [
            'genomic_variant_id' => $variant->id,
            'patient_id' => $variant->patient_id,
            'source' => 'clinvar',
            'from_bucket' => 'vus',
            'to_bucket' => 'pathogenic',
            'from_stars' => 1,
            'to_stars' => 2,
            'severity' => 'high',
            'delta_hash' => $this->faker->unique()->sha256(),
            'status' => 'new',
        ];
    }
}
```

> `GenomicVariant::factory()->create()` in `KbChangeAlertFactory` ensures `patient_id` matches the variant's patient (FK integrity). `GenomicVariantFactory` exists; do not modify it.

- [ ] **Step 6: Run — expect PASS**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Models/Clinical/VariantCanonicalId.php backend/app/Models/Clinical/KbChangeAlert.php backend/database/factories/Clinical/VariantCanonicalIdFactory.php backend/database/factories/Clinical/KbChangeAlertFactory.php backend/tests/Feature/FactorySmokeTest.php
git commit -m "feat(reanalysis): add canonical-id + kb-alert models and factories" -- backend/app/Models/Clinical/VariantCanonicalId.php backend/app/Models/Clinical/KbChangeAlert.php backend/database/factories/Clinical/VariantCanonicalIdFactory.php backend/database/factories/Clinical/KbChangeAlertFactory.php backend/tests/Feature/FactorySmokeTest.php
```

---

### Task 5: ClinGenAlleleRegistryService (CAID via HTTP/HMAC)

**Files:**
- Create: `backend/app/Services/Genomics/Reanalysis/ClinGenAlleleRegistryService.php`
- Modify: `backend/config/services.php`
- Test: `backend/tests/Unit/Services/Reanalysis/ClinGenAlleleRegistryServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Services\Genomics\Reanalysis\ClinGenAlleleRegistryService;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => $this->car = new ClinGenAlleleRegistryService);

it('resolves a CAID + ClinVar VariationID + dbSNP from an HGVS lookup', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response([
        '@id' => 'http://reg.clinicalgenome.org/allele/CA123456',
        'externalRecords' => [
            'ClinVarVariations' => [['variationId' => 7890]],
            'dbSNP' => [['rs' => 80357906]],
        ],
    ], 200)]);

    $r = $this->car->resolveByHgvs('NC_000017.11:g.43045712G>A');

    expect($r)->toMatchArray(['caid' => 'CA123456', 'clinvar_variation_id' => '7890', 'dbsnp_rs' => 'rs80357906']);
});

it('returns null fields when the registry has no cross-references', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response([
        '@id' => 'http://reg.clinicalgenome.org/allele/CA999', 'externalRecords' => [],
    ], 200)]);

    $r = $this->car->resolveByHgvs('NC_000017.11:g.43045712G>A');

    expect($r['caid'])->toBe('CA999');
    expect($r['clinvar_variation_id'])->toBeNull();
    expect($r['dbsnp_rs'])->toBeNull();
});

it('returns null on a registry error (degrades gracefully)', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response('error', 500)]);
    expect($this->car->resolveByHgvs('bogus'))->toBeNull();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Add config** — in `backend/config/services.php`, add a `clingen_ar` entry to the returned array:

```php
    'clingen_ar' => [
        'base' => env('CLINGEN_AR_BASE', 'https://reg.clinicalgenome.org'),
        'login' => env('CLINGEN_AR_LOGIN'),
        'password' => env('CLINGEN_AR_PASSWORD'),
    ],
```

Add to `.env.example`:

```
CLINGEN_AR_BASE=https://reg.clinicalgenome.org
CLINGEN_AR_LOGIN=
CLINGEN_AR_PASSWORD=
```

- [ ] **Step 4: Implement** — `backend/app/Services/Genomics/Reanalysis/ClinGenAlleleRegistryService.php`:

```php
<?php

namespace App\Services\Genomics\Reanalysis;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Canonicalizes a variant against the ClinGen Allele Registry (reg.clinicalgenome.org),
 * returning the CAID and harvested cross-references (ClinVar VariationID, dbSNP).
 * GET lookups are public; registration (PUT) uses HMAC-style query signing.
 * Degrades to null on any failure — never throws into the caller.
 */
class ClinGenAlleleRegistryService
{
    /** @return array{caid:?string, clinvar_variation_id:?string, dbsnp_rs:?string}|null */
    public function resolveByHgvs(string $hgvs): ?array
    {
        $base = (string) config('services.clingen_ar.base');
        try {
            $response = Http::timeout(15)->acceptJson()->get($base.'/allele', ['hgvs' => $hgvs]);
            if (! $response->successful()) {
                return null;
            }

            return $this->parse($response->json());
        } catch (\Throwable $e) {
            Log::warning('ClinGen AR lookup failed: '.$e->getMessage());

            return null;
        }
    }

    /** @param array<string,mixed> $body @return array{caid:?string, clinvar_variation_id:?string, dbsnp_rs:?string} */
    private function parse(array $body): array
    {
        $id = (string) ($body['@id'] ?? '');
        $caid = preg_match('#/allele/(CA\d+)#', $id, $m) ? $m[1] : null;

        $ext = $body['externalRecords'] ?? [];
        $variationId = $ext['ClinVarVariations'][0]['variationId'] ?? null;
        $rs = $ext['dbSNP'][0]['rs'] ?? null;

        return [
            'caid' => $caid,
            'clinvar_variation_id' => $variationId !== null ? (string) $variationId : null,
            'dbsnp_rs' => $rs !== null ? 'rs'.$rs : null,
        ];
    }
}
```

- [ ] **Step 5: Run — expect PASS (3)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Reanalysis/ClinGenAlleleRegistryService.php backend/config/services.php backend/.env.example backend/tests/Unit/Services/Reanalysis/ClinGenAlleleRegistryServiceTest.php
git commit -m "feat(reanalysis): add ClinGen Allele Registry client (CAID + ClinVar xref)" -- backend/app/Services/Genomics/Reanalysis/ClinGenAlleleRegistryService.php backend/config/services.php backend/.env.example backend/tests/Unit/Services/Reanalysis/ClinGenAlleleRegistryServiceTest.php
```

> Note: `config/services.php` and `.env.example` are shared; before staging run `git diff backend/config/services.php backend/.env.example` and confirm the only changes are your additions (else `git add -p`).

---

### Task 6: VariantCanonicalizer (resolve + persist + baseline)

**Files:**
- Create: `backend/app/Services/Genomics/Reanalysis/VariantCanonicalizer.php`
- Test: `backend/tests/Unit/Services/Reanalysis/VariantCanonicalizerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicVariant;
use App\Services\Genomics\Reanalysis\ClinGenAlleleRegistryService;
use App\Services\Genomics\Reanalysis\VariantCanonicalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->canon = new VariantCanonicalizer(new ClinGenAlleleRegistryService));

it('persists a canonical id + ClinVar baseline by coordinate-matching the synced ClinVar table', function () {
    Http::fake(); // no HGVS → CAR not called; coordinate fallback used
    $variant = GenomicVariant::factory()->create([
        'gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712,
        'ref_allele' => 'G', 'alt_allele' => 'A',
    ]);
    ClinVarVariant::create([
        'variation_id' => '55555', 'chromosome' => '17', 'position' => 43045712,
        'reference_allele' => 'G', 'alternate_allele' => 'A', 'genome_build' => 'GRCh38',
        'gene_symbol' => 'TESTGENEX', 'clinical_significance' => 'Uncertain significance',
        'review_status' => 'criteria provided, single submitter', 'is_pathogenic' => false,
    ]);

    $canonical = $this->canon->canonicalize($variant->fresh());

    expect($canonical->clinvar_variation_id)->toBe('55555');
    expect($canonical->baseline_significance)->toBe('Uncertain significance');
    expect($canonical->baselined_at)->not->toBeNull();
    expect($variant->canonicalId()->exists())->toBeTrue();
});

it('uses the ClinGen Allele Registry CAID when an HGVS is available', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response([
        '@id' => 'http://reg.clinicalgenome.org/allele/CA222', 'externalRecords' => ['ClinVarVariations' => [['variationId' => 99]]],
    ], 200)]);
    $variant = GenomicVariant::factory()->create(['gene' => 'TESTGENEX', 'variant' => 'NC_000017.11:g.43045712G>A']);

    $canonical = $this->canon->canonicalize($variant->fresh());

    expect($canonical->caid)->toBe('CA222');
    expect($canonical->clinvar_variation_id)->toBe('99');
});

it('is idempotent — re-canonicalizing updates the same row', function () {
    Http::fake();
    $variant = GenomicVariant::factory()->create(['gene' => 'TESTGENEX', 'chromosome' => '1', 'position' => 1, 'ref_allele' => 'A', 'alt_allele' => 'T']);

    $first = $this->canon->canonicalize($variant->fresh());
    $second = $this->canon->canonicalize($variant->fresh());

    expect($second->id)->toBe($first->id);
    expect(\App\Models\Clinical\VariantCanonicalId::where('genomic_variant_id', $variant->id)->count())->toBe(1);
});
```

This test references `GenomicVariant::canonicalId()` — add that relation to the model in Step 3.

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Add the relation to `GenomicVariant`** — in `backend/app/Models/Clinical/GenomicVariant.php`, add the import and method (additive):

```php
use Illuminate\Database\Eloquent\Relations\HasOne;
```
```php
    public function canonicalId(): HasOne
    {
        return $this->hasOne(VariantCanonicalId::class, 'genomic_variant_id');
    }
```

- [ ] **Step 4: Implement** — `backend/app/Services/Genomics/Reanalysis/VariantCanonicalizer.php`:

```php
<?php

namespace App\Services\Genomics\Reanalysis;

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantCanonicalId;

/**
 * Assigns a canonical identity to a genomic variant: a CAID + ClinVar VariationID
 * from the ClinGen Allele Registry when an HGVS string is available, otherwise a
 * coordinate match against the locally-synced ClinVar table. Persists a baseline
 * ClinVar-significance snapshot used by the reanalysis loop.
 */
class VariantCanonicalizer
{
    public function __construct(private ClinGenAlleleRegistryService $registry) {}

    public function canonicalize(GenomicVariant $variant): VariantCanonicalId
    {
        $caid = null;
        $variationId = null;
        $dbsnp = null;

        // Prefer the authoritative registry when the variant carries a full HGVS.
        $hgvs = $this->hgvsFor($variant);
        if ($hgvs !== null) {
            $resolved = $this->registry->resolveByHgvs($hgvs);
            if ($resolved !== null) {
                $caid = $resolved['caid'];
                $variationId = $resolved['clinvar_variation_id'];
                $dbsnp = $resolved['dbsnp_rs'];
            }
        }

        // Coordinate match against the synced ClinVar table for the variation id + baseline.
        $clinvar = $this->matchClinVar($variant);
        if ($variationId === null && $clinvar !== null) {
            $variationId = $clinvar->variation_id;
        }

        return VariantCanonicalId::updateOrCreate(
            ['genomic_variant_id' => $variant->id],
            [
                'caid' => $caid,
                'clinvar_variation_id' => $variationId,
                'dbsnp_rs' => $dbsnp,
                'assembly' => 'GRCh38',
                'baseline_significance' => $clinvar?->clinical_significance,
                'baseline_review_status' => $clinvar?->review_status,
                'baselined_at' => now(),
                'canonicalized_at' => now(),
            ],
        );
    }

    private function hgvsFor(GenomicVariant $variant): ?string
    {
        // The `variant` column may hold a full HGVS (e.g. NC_..:g....). Protein/short
        // notations (e.g. "V600E") are not resolvable by genomic HGVS — skip them.
        $v = (string) ($variant->variant ?? '');

        return str_contains($v, ':g.') || str_contains($v, ':c.') ? $v : null;
    }

    private function matchClinVar(GenomicVariant $variant): ?ClinVarVariant
    {
        if (! $variant->chromosome || $variant->position === null) {
            return null;
        }

        return ClinVarVariant::where('chromosome', $variant->chromosome)
            ->where('position', $variant->position)
            ->where('reference_allele', $variant->ref_allele)
            ->where('alternate_allele', $variant->alt_allele)
            ->first();
    }
}
```

- [ ] **Step 5: Run — expect PASS (3)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Reanalysis/VariantCanonicalizer.php backend/app/Models/Clinical/GenomicVariant.php backend/tests/Unit/Services/Reanalysis/VariantCanonicalizerTest.php
git commit -m "feat(reanalysis): add variant canonicalizer (CAID + ClinVar baseline)" -- backend/app/Services/Genomics/Reanalysis/VariantCanonicalizer.php backend/app/Models/Clinical/GenomicVariant.php backend/tests/Unit/Services/Reanalysis/VariantCanonicalizerTest.php
```

---

### Task 7: ReanalysisService (delta detection → alert + task)

**Files:**
- Create: `backend/app/Services/Genomics/Reanalysis/ReanalysisService.php`
- Test: `backend/tests/Unit/Services/Reanalysis/ReanalysisServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Models\Clinical\VariantCanonicalId;
use App\Models\PatientTask;
use App\Services\Genomics\Reanalysis\ReanalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->service = app(ReanalysisService::class));

function seedReclassified(string $from, string $to, string $reviewStatus = 'criteria provided, multiple submitters, no conflicts'): GenomicVariant
{
    $variant = GenomicVariant::factory()->create([
        'gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A',
    ]);
    VariantCanonicalId::factory()->create([
        'genomic_variant_id' => $variant->id, 'clinvar_variation_id' => '55555',
        'baseline_significance' => $from, 'baseline_review_status' => 'criteria provided, single submitter',
        'baselined_at' => now()->subMonths(6),
    ]);
    ClinVarVariant::create([
        'variation_id' => '55555', 'chromosome' => '17', 'position' => 43045712,
        'reference_allele' => 'G', 'alternate_allele' => 'A', 'genome_build' => 'GRCh38',
        'gene_symbol' => 'TESTGENEX', 'clinical_significance' => $to, 'review_status' => $reviewStatus, 'is_pathogenic' => true,
    ]);

    return $variant;
}

it('raises a high-severity alert + a patient task on a VUS->Pathogenic reclassification', function () {
    $variant = seedReclassified('Uncertain significance', 'Pathogenic');

    $count = $this->service->run();

    expect($count)->toBe(1);
    $alert = KbChangeAlert::first();
    expect($alert->severity)->toBe('high');
    expect($alert->from_bucket)->toBe('vus');
    expect($alert->to_bucket)->toBe('pathogenic');
    expect($alert->task_id)->not->toBeNull();
    expect(PatientTask::find($alert->task_id)->patient_id)->toBe($variant->patient_id);
    // baseline advanced so it won't re-fire
    expect(VariantCanonicalId::first()->baseline_significance)->toBe('Pathogenic');
});

it('does not alert on non-bucket-crossing churn', function () {
    seedReclassified('Pathogenic', 'Likely pathogenic');
    expect($this->service->run())->toBe(0);
    expect(KbChangeAlert::count())->toBe(0);
});

it('is idempotent — a second run with no new change creates no duplicate', function () {
    seedReclassified('Uncertain significance', 'Pathogenic');
    $this->service->run();
    $this->service->run();
    expect(KbChangeAlert::count())->toBe(1);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** — `backend/app/Services/Genomics/Reanalysis/ReanalysisService.php`:

```php
<?php

namespace App\Services\Genomics\Reanalysis;

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Models\Clinical\VariantCanonicalId;
use App\Models\PatientTask;
use Illuminate\Support\Facades\DB;

/**
 * Compares each canonicalized patient variant's CURRENT ClinVar classification
 * (from the synced clinvar_variants table) against its stored baseline; on a
 * qualifying transition, creates a deduplicated KB-change alert + an MDT task,
 * then advances the baseline. Non-device CDS: it alerts, a human decides.
 */
class ReanalysisService
{
    public function run(): int
    {
        $alerts = 0;

        VariantCanonicalId::with('variant')
            ->whereNotNull('clinvar_variation_id')
            ->chunkById(200, function ($canonicals) use (&$alerts) {
                foreach ($canonicals as $canonical) {
                    if ($this->evaluate($canonical)) {
                        $alerts++;
                    }
                }
            });

        return $alerts;
    }

    private function evaluate(VariantCanonicalId $canonical): bool
    {
        $current = ClinVarVariant::where('variation_id', $canonical->clinvar_variation_id)->first();
        if ($current === null) {
            return false;
        }

        $fromBucket = ClassificationBucket::normalize($canonical->baseline_significance);
        $toBucket = ClassificationBucket::normalize($current->clinical_significance);
        $fromStars = ClassificationBucket::stars($canonical->baseline_review_status);
        $toStars = ClassificationBucket::stars($current->review_status);

        $severity = ReanalysisTransition::severity($fromBucket, $toBucket, $fromStars, $toStars);
        if ($severity === null) {
            // Still advance the baseline for cosmetic churn so it isn't re-evaluated forever.
            $canonical->update([
                'baseline_significance' => $current->clinical_significance,
                'baseline_review_status' => $current->review_status,
                'baselined_at' => now(),
            ]);

            return false;
        }

        $deltaHash = hash('sha256', implode('|', [
            $canonical->clinvar_variation_id, $fromBucket, $toBucket, $fromStars, $toStars,
        ]));

        if (KbChangeAlert::where('delta_hash', $deltaHash)->exists()) {
            return false;
        }

        $variant = $canonical->variant;

        return DB::transaction(function () use ($canonical, $variant, $current, $fromBucket, $toBucket, $fromStars, $toStars, $severity, $deltaHash) {
            $task = PatientTask::create([
                'patient_id' => $variant->patient_id,
                'created_by' => $variant->patient_id, // system-raised; reassigned on review (see note)
                'domain' => 'genomic',
                'record_ref' => 'genomic:'.$variant->id,
                'title' => sprintf('Reanalyze %s — ClinVar %s → %s', $variant->gene, $fromBucket, $toBucket),
                'description' => sprintf(
                    'ClinVar reclassified this variant (VariationID %s) from %s to %s (%d→%d stars) since last review. Review for diagnostic impact.',
                    $canonical->clinvar_variation_id, $fromBucket, $toBucket, $fromStars, $toStars,
                ),
                'priority' => $severity === 'high' ? 'high' : 'normal',
                'status' => 'pending',
            ]);

            KbChangeAlert::create([
                'genomic_variant_id' => $variant->id,
                'patient_id' => $variant->patient_id,
                'source' => 'clinvar',
                'clinvar_variation_id' => $canonical->clinvar_variation_id,
                'from_bucket' => $fromBucket,
                'to_bucket' => $toBucket,
                'from_stars' => $fromStars,
                'to_stars' => $toStars,
                'severity' => $severity,
                'evidence' => [
                    'clinvar_significance' => $current->clinical_significance,
                    'review_status' => $current->review_status,
                    'gene' => $variant->gene,
                    'variation_url' => 'https://www.ncbi.nlm.nih.gov/clinvar/variation/'.$canonical->clinvar_variation_id.'/',
                ],
                'delta_hash' => $deltaHash,
                'status' => 'new',
                'task_id' => $task->id,
            ]);

            $canonical->update([
                'baseline_significance' => $current->clinical_significance,
                'baseline_review_status' => $current->review_status,
                'baselined_at' => now(),
            ]);

            return true;
        });
    }
}
```

> Note on `created_by`: `app.patient_tasks.created_by` is NOT NULL with an FK to `app.users`. A scheduled system run has no user; rather than make the column nullable (a shared-table change), the task is attributed to a system actor. Use the superuser id when available: replace `'created_by' => $variant->patient_id` with `'created_by' => \App\Models\User::where('email', 'admin@acumenus.net')->value('id') ?? $variant->patient_id`. (The test seeds no users via factory for the canonical, so in the unit test create a superuser in `beforeEach` OR keep the fallback. Simplest: in the test's `beforeEach`, add `\App\Models\User::factory()->create();` and set `'created_by' => \App\Models\User::query()->value('id')`.) **Implementer:** use `\App\Models\User::query()->value('id')` for `created_by` and ensure the unit test's `seedReclassified` creates a `User` first; update the test accordingly. This keeps the FK valid without altering the shared table.

- [ ] **Step 4: Adjust the test for the system actor** — at the top of `seedReclassified`, before creating the variant, add:

```php
    \App\Models\User::factory()->create();
```
and in `ReanalysisService` use `'created_by' => \App\Models\User::query()->value('id')`.

- [ ] **Step 5: Run — expect PASS (3)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Reanalysis/ReanalysisService.php backend/tests/Unit/Services/Reanalysis/ReanalysisServiceTest.php
git commit -m "feat(reanalysis): add reanalysis service (delta detection -> alert + MDT task)" -- backend/app/Services/Genomics/Reanalysis/ReanalysisService.php backend/tests/Unit/Services/Reanalysis/ReanalysisServiceTest.php
```

---

### Task 8: Scheduled command `genomics:reanalyze-variants`

**Files:**
- Create: `backend/app/Console/Commands/ReanalyzeVariantsCommand.php`
- Modify: `backend/routes/console.php`
- Test: `backend/tests/Feature/Api/ReanalyzeVariantsCommandTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Clinical\ClinVarVariant;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Models\Clinical\VariantCanonicalId;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
});

it('runs the reanalysis loop and reports the alert count', function () {
    $variant = GenomicVariant::factory()->create([
        'gene' => 'TESTGENEX', 'chromosome' => '17', 'position' => 43045712, 'ref_allele' => 'G', 'alt_allele' => 'A',
    ]);
    VariantCanonicalId::factory()->create([
        'genomic_variant_id' => $variant->id, 'clinvar_variation_id' => '55555',
        'baseline_significance' => 'Uncertain significance', 'baseline_review_status' => 'criteria provided, single submitter',
    ]);
    ClinVarVariant::create([
        'variation_id' => '55555', 'chromosome' => '17', 'position' => 43045712,
        'reference_allele' => 'G', 'alternate_allele' => 'A', 'genome_build' => 'GRCh38',
        'gene_symbol' => 'TESTGENEX', 'clinical_significance' => 'Pathogenic',
        'review_status' => 'reviewed by expert panel', 'is_pathogenic' => true,
    ]);

    $this->artisan('genomics:reanalyze-variants')
        ->expectsOutputToContain('1')
        ->assertSuccessful();

    expect(KbChangeAlert::count())->toBe(1);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement the command** — `backend/app/Console/Commands/ReanalyzeVariantsCommand.php`:

```php
<?php

namespace App\Console\Commands;

use App\Services\Genomics\Reanalysis\ReanalysisService;
use Illuminate\Console\Command;

class ReanalyzeVariantsCommand extends Command
{
    protected $signature = 'genomics:reanalyze-variants';

    protected $description = 'Reanalyze canonicalized patient variants against current ClinVar classifications and raise KB-change alerts';

    public function handle(ReanalysisService $service): int
    {
        $this->info('Running variant reanalysis against ClinVar…');
        $alerts = $service->run();
        $this->info("Reanalysis complete: {$alerts} new KB-change alert(s) raised.");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Schedule it** — in `backend/routes/console.php`, after the existing `Schedule::command('genomics:refresh-evidence')...` line, add:

```php
Schedule::command('genomics:reanalyze-variants')->monthlyOn(8, '03:00');
```

(Monthly on the 8th — a few days after ClinVar's first-Thursday release, allowing the existing ClinVar sync to land first.)

- [ ] **Step 5: Run — expect PASS**, then Pint + commit (surgical on `routes/console.php`):

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Console/Commands/ReanalyzeVariantsCommand.php backend/tests/Feature/Api/ReanalyzeVariantsCommandTest.php
git add -p backend/routes/console.php   # only your Schedule line
git commit -m "feat(reanalysis): add scheduled genomics:reanalyze-variants command" -- backend/app/Console/Commands/ReanalyzeVariantsCommand.php backend/tests/Feature/Api/ReanalyzeVariantsCommandTest.php backend/routes/console.php
```

---

### Task 9: Controller + routes (canonicalize, KB-alert list/worklist, acknowledge)

**Files:**
- Create: `backend/app/Http/Controllers/VariantReanalysisController.php`
- Create: `backend/app/Http/Requests/AcknowledgeKbAlertRequest.php`
- Modify: `backend/routes/api.php`
- Test: `backend/tests/Feature/Api/VariantReanalysisTest.php`

- [ ] **Step 1: Write the failing feature test**

```php
<?php

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('canonicalizes a variant on demand', function () {
    Http::fake(['reg.clinicalgenome.org/*' => Http::response([
        '@id' => 'http://reg.clinicalgenome.org/allele/CA777', 'externalRecords' => ['ClinVarVariations' => [['variationId' => 42]]],
    ], 200)]);
    $variant = GenomicVariant::factory()->create(['gene' => 'TESTGENEX', 'variant' => 'NC_000017.11:g.43045712G>A']);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/genomic-variants/{$variant->id}/canonicalize");

    $response->assertStatus(200)
        ->assertJsonPath('data.caid', 'CA777')
        ->assertJsonPath('data.clinvar_variation_id', '42');
});

it('lists kb-change alerts for a patient', function () {
    $alert = KbChangeAlert::factory()->create(['status' => 'new']);

    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson("/api/patients/{$alert->patient_id}/kb-alerts");

    $response->assertStatus(200)->assertJsonPath('success', true);
    expect($response->json('data'))->toHaveCount(1);
});

it('lists the global kb-alert worklist filtered by status', function () {
    KbChangeAlert::factory()->create(['status' => 'new']);
    KbChangeAlert::factory()->create(['status' => 'acknowledged']);

    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/kb-alerts?status=new');
    $response->assertStatus(200);
    foreach ($response->json('data') as $row) {
        expect($row['status'])->toBe('new');
    }
});

it('acknowledges an alert with a resolution note', function () {
    $alert = KbChangeAlert::factory()->create(['status' => 'new']);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/kb-alerts/{$alert->id}/acknowledge", ['status' => 'dismissed', 'resolution_note' => 'Already reviewed; no change to plan']);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'dismissed');
    expect($alert->fresh()->acknowledged_by)->toBe($this->user->id);
});

it('requires authentication', function () {
    $this->getJson('/api/kb-alerts')->assertStatus(401);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement the Form Request** — `backend/app/Http/Requests/AcknowledgeKbAlertRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcknowledgeKbAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['acknowledged', 'dismissed'])],
            'resolution_note' => 'nullable|string|max:2000',
        ];
    }
}
```

- [ ] **Step 4: Implement the controller** — `backend/app/Http/Controllers/VariantReanalysisController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\AcknowledgeKbAlertRequest;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\KbChangeAlert;
use App\Services\Genomics\Reanalysis\VariantCanonicalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariantReanalysisController extends Controller
{
    public function __construct(private VariantCanonicalizer $canonicalizer) {}

    public function canonicalize(int $variant): JsonResponse
    {
        $model = GenomicVariant::findOrFail($variant);

        return ApiResponse::success($this->canonicalizer->canonicalize($model));
    }

    public function patientAlerts(Request $request, int $patient): JsonResponse
    {
        $query = KbChangeAlert::where('patient_id', $patient)->with('task:id,title,status');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return ApiResponse::success($query->orderByDesc('created_at')->get());
    }

    public function worklist(Request $request): JsonResponse
    {
        $query = KbChangeAlert::query()->with('variant:id,gene,patient_id')->orderByDesc('created_at');
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity'));
        }

        return ApiResponse::paginated($query->paginate($request->integer('per_page', 25)));
    }

    public function acknowledge(AcknowledgeKbAlertRequest $request, int $alert): JsonResponse
    {
        $model = KbChangeAlert::findOrFail($alert);
        $model->update([
            'status' => $request->validated()['status'],
            'resolution_note' => $request->validated()['resolution_note'] ?? null,
            'acknowledged_by' => $request->user()->id,
            'acknowledged_at' => now(),
        ]);

        return ApiResponse::success($model);
    }
}
```

- [ ] **Step 5: Add routes** — in `backend/routes/api.php`, inside the `auth:sanctum` group (near the genomics / ACMG routes):

```php
    // ── Variant reanalysis (KB-change alerts) ───────────────────────────
    Route::post('/genomic-variants/{variant}/canonicalize', [\App\Http\Controllers\VariantReanalysisController::class, 'canonicalize']);
    Route::get('/patients/{patient}/kb-alerts', [\App\Http\Controllers\VariantReanalysisController::class, 'patientAlerts']);
    Route::get('/kb-alerts', [\App\Http\Controllers\VariantReanalysisController::class, 'worklist']);
    Route::post('/kb-alerts/{alert}/acknowledge', [\App\Http\Controllers\VariantReanalysisController::class, 'acknowledge']);
```

- [ ] **Step 6: Run — expect PASS (5)**, then Pint + commit (surgical on `routes/api.php`):

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Http/Controllers/VariantReanalysisController.php backend/app/Http/Requests/AcknowledgeKbAlertRequest.php backend/tests/Feature/Api/VariantReanalysisTest.php
git add -p backend/routes/api.php   # only your 4 reanalysis routes
git commit -m "feat(reanalysis): add canonicalize + KB-alert API" -- backend/app/Http/Controllers/VariantReanalysisController.php backend/app/Http/Requests/AcknowledgeKbAlertRequest.php backend/tests/Feature/Api/VariantReanalysisTest.php backend/routes/api.php
```

---

### Task 10: Frontend data layer (types + api + hooks)

**Files:**
- Create: `frontend/src/features/reanalysis/types/index.ts`
- Create: `frontend/src/features/reanalysis/api/reanalysisApi.ts`
- Create: `frontend/src/features/reanalysis/hooks/useReanalysis.ts`
- Test: `frontend/src/features/reanalysis/hooks/__tests__/useReanalysis.test.ts`

- [ ] **Step 1: Create types** — `frontend/src/features/reanalysis/types/index.ts`:

```ts
export type KbAlertSeverity = "high" | "medium";
export type KbAlertStatus = "new" | "acknowledged" | "dismissed";

export interface KbChangeAlert {
  id: number;
  genomic_variant_id: number;
  patient_id: number;
  source: string;
  clinvar_variation_id: string | null;
  from_bucket: string;
  to_bucket: string;
  from_stars: number;
  to_stars: number;
  severity: KbAlertSeverity;
  evidence: {
    clinvar_significance?: string;
    review_status?: string;
    gene?: string;
    variation_url?: string;
  } | null;
  status: KbAlertStatus;
  task_id: number | null;
  acknowledged_by: number | null;
  acknowledged_at: string | null;
  resolution_note: string | null;
  created_at: string;
  variant?: { id: number; gene: string; patient_id: number };
}

export const BUCKET_LABEL: Record<string, string> = {
  benign: "Benign",
  likely_benign: "Likely Benign",
  vus: "VUS",
  conflicting: "Conflicting",
  likely_pathogenic: "Likely Pathogenic",
  pathogenic: "Pathogenic",
  unknown: "Unknown",
};
```

- [ ] **Step 2: Create the API module** — `frontend/src/features/reanalysis/api/reanalysisApi.ts`:

```ts
import apiClient from "@/lib/api-client";
import type { KbAlertStatus, KbChangeAlert } from "../types";

export async function getPatientKbAlerts(patientId: number, status?: KbAlertStatus): Promise<KbChangeAlert[]> {
  const { data } = await apiClient.get(`/patients/${patientId}/kb-alerts`, { params: status ? { status } : {} });
  return data.data ?? data;
}

export async function acknowledgeKbAlert(
  alertId: number,
  payload: { status: "acknowledged" | "dismissed"; resolution_note?: string },
): Promise<KbChangeAlert> {
  const { data } = await apiClient.post(`/kb-alerts/${alertId}/acknowledge`, payload);
  return data.data ?? data;
}
```

- [ ] **Step 3: Create hooks** — `frontend/src/features/reanalysis/hooks/useReanalysis.ts`:

```ts
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { acknowledgeKbAlert, getPatientKbAlerts } from "../api/reanalysisApi";
import type { KbAlertStatus } from "../types";

const KEY = "reanalysis";

export function usePatientKbAlerts(patientId: number, status?: KbAlertStatus) {
  return useQuery({
    queryKey: [KEY, "patient", patientId, status],
    queryFn: () => getPatientKbAlerts(patientId, status),
    enabled: Number.isFinite(patientId) && patientId > 0,
  });
}

export function useAcknowledgeKbAlert(patientId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { alertId: number; status: "acknowledged" | "dismissed"; resolution_note?: string }) =>
      acknowledgeKbAlert(payload.alertId, { status: payload.status, resolution_note: payload.resolution_note }),
    onSuccess: () => qc.invalidateQueries({ queryKey: [KEY, "patient", patientId] }),
  });
}
```

- [ ] **Step 4: Write the hook test** — `frontend/src/features/reanalysis/hooks/__tests__/useReanalysis.test.ts`:

```ts
import { describe, it, expect, afterEach } from "vitest";
import { waitFor } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderHookWithProviders, resetStores } from "@/test/utils";
import { usePatientKbAlerts } from "../useReanalysis";

afterEach(() => resetStores());

describe("usePatientKbAlerts", () => {
  it("fetches KB-change alerts for a patient", async () => {
    server.use(
      http.get("/api/patients/5/kb-alerts", () =>
        HttpResponse.json({ success: true, data: [{ id: 1, patient_id: 5, severity: "high", from_bucket: "vus", to_bucket: "pathogenic", status: "new" }] }),
      ),
    );
    const { result } = renderHookWithProviders(() => usePatientKbAlerts(5));
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data![0].severity).toBe("high");
  });
});
```

- [ ] **Step 5: Run + tsc + commit:**

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/reanalysis/hooks/__tests__/useReanalysis.test.ts && npx tsc --noEmit"
git add frontend/src/features/reanalysis/types/index.ts frontend/src/features/reanalysis/api/reanalysisApi.ts frontend/src/features/reanalysis/hooks/useReanalysis.ts frontend/src/features/reanalysis/hooks/__tests__/useReanalysis.test.ts
git commit -m "feat(reanalysis): add frontend data layer" -- frontend/src/features/reanalysis/types/index.ts frontend/src/features/reanalysis/api/reanalysisApi.ts frontend/src/features/reanalysis/hooks/useReanalysis.ts frontend/src/features/reanalysis/hooks/__tests__/useReanalysis.test.ts
```

(Frontend guardrails: run in the `node` container; **do NOT** `npm install` or modify package.json; deps present; commit via explicit literal pathspec; named exports; no `any`.)

---

### Task 11: ReanalysisAlertsPanel component

**Files:**
- Create: `frontend/src/features/reanalysis/components/KbAlertSeverityBadge.tsx`
- Create: `frontend/src/features/reanalysis/components/ReanalysisAlertsPanel.tsx`
- Test: `frontend/src/features/reanalysis/components/__tests__/ReanalysisAlertsPanel.test.tsx`

- [ ] **Step 1: Write the failing test**

```tsx
import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { ReanalysisAlertsPanel } from "../ReanalysisAlertsPanel";

afterEach(() => resetStores());

describe("ReanalysisAlertsPanel", () => {
  it("lists alerts with the transition and lets a clinician dismiss one", async () => {
    server.use(
      http.get("/api/patients/5/kb-alerts", () =>
        HttpResponse.json({ success: true, data: [
          { id: 9, patient_id: 5, genomic_variant_id: 3, source: "clinvar", clinvar_variation_id: "55555",
            from_bucket: "vus", to_bucket: "pathogenic", from_stars: 1, to_stars: 3, severity: "high",
            evidence: { gene: "BRCA1", variation_url: "https://x/" }, status: "new", task_id: 1,
            acknowledged_by: null, acknowledged_at: null, resolution_note: null, created_at: "2026-06-15T00:00:00Z" },
        ] }),
      ),
      http.post("/api/kb-alerts/9/acknowledge", () =>
        HttpResponse.json({ success: true, data: { id: 9, status: "dismissed" } }),
      ),
    );

    renderWithProviders(<ReanalysisAlertsPanel patientId={5} />);

    await waitFor(() => expect(screen.getByText(/VUS/)).toBeInTheDocument());
    expect(screen.getByText(/Pathogenic/)).toBeInTheDocument();
    expect(screen.getByText(/BRCA1/)).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: /dismiss/i }));
    await waitFor(() => expect(screen.queryByRole("button", { name: /dismiss/i })).toBeNull());
  });

  it("shows an empty state when there are no alerts", async () => {
    server.use(http.get("/api/patients/5/kb-alerts", () => HttpResponse.json({ success: true, data: [] })));
    renderWithProviders(<ReanalysisAlertsPanel patientId={5} />);
    await waitFor(() => expect(screen.getByText(/no reanalysis alerts/i)).toBeInTheDocument());
  });
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement `KbAlertSeverityBadge`** — `frontend/src/features/reanalysis/components/KbAlertSeverityBadge.tsx`:

```tsx
import type { KbAlertSeverity } from "../types";

export function KbAlertSeverityBadge({ severity }: { severity: KbAlertSeverity }) {
  const color = severity === "high" ? "var(--primary)" : "var(--accent)";
  return (
    <span className="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase" style={{ color, border: `1px solid ${color}` }}>
      {severity}
    </span>
  );
}
```

- [ ] **Step 4: Implement `ReanalysisAlertsPanel`** — `frontend/src/features/reanalysis/components/ReanalysisAlertsPanel.tsx`:

```tsx
import { KbAlertSeverityBadge } from "./KbAlertSeverityBadge";
import { usePatientKbAlerts, useAcknowledgeKbAlert } from "../hooks/useReanalysis";
import { BUCKET_LABEL } from "../types";

export function ReanalysisAlertsPanel({ patientId }: { patientId: number }) {
  const { data: alerts, isLoading } = usePatientKbAlerts(patientId);
  const ack = useAcknowledgeKbAlert(patientId);

  if (isLoading) return <p className="text-sm text-[var(--text-muted)]">Loading reanalysis alerts…</p>;

  const open = (alerts ?? []).filter((a) => a.status === "new");
  if (open.length === 0) {
    return <p className="text-sm text-[var(--text-muted)]">No reanalysis alerts.</p>;
  }

  return (
    <section className="space-y-2">
      {open.map((a) => (
        <div key={a.id} className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-3">
          <div className="flex items-center gap-2">
            <KbAlertSeverityBadge severity={a.severity} />
            <span className="text-sm text-[var(--text-primary)]">
              {a.evidence?.gene ?? "Variant"}: {BUCKET_LABEL[a.from_bucket] ?? a.from_bucket} → <span className="text-[var(--accent)]">{BUCKET_LABEL[a.to_bucket] ?? a.to_bucket}</span>
            </span>
            {a.clinvar_variation_id && a.evidence?.variation_url && (
              <a href={a.evidence.variation_url} target="_blank" rel="noreferrer" className="text-xs text-[var(--teal)] hover:underline">
                ClinVar {a.clinvar_variation_id}
              </a>
            )}
          </div>
          <p className="mt-1 text-xs text-[var(--text-muted)]">
            ClinVar reclassified ({a.from_stars}→{a.to_stars}★) since last review. {a.evidence?.review_status ?? ""}
          </p>
          <div className="mt-2 flex justify-end gap-2">
            <button type="button" disabled={ack.isPending}
              onClick={() => ack.mutate({ alertId: a.id, status: "acknowledged" })}
              className="rounded-md border border-[var(--surface-elevated)] px-2 py-0.5 text-xs text-[var(--text-secondary)] disabled:opacity-50">
              Acknowledge
            </button>
            <button type="button" disabled={ack.isPending}
              onClick={() => ack.mutate({ alertId: a.id, status: "dismissed", resolution_note: "Dismissed" })}
              className="rounded-md border border-[var(--surface-elevated)] px-2 py-0.5 text-xs text-[var(--text-secondary)] disabled:opacity-50">
              Dismiss
            </button>
          </div>
        </div>
      ))}
    </section>
  );
}
```

- [ ] **Step 5: Run — expect PASS (2)**, then tsc + commit:

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/reanalysis/components/__tests__/ReanalysisAlertsPanel.test.tsx && npx tsc --noEmit"
git add frontend/src/features/reanalysis/components/KbAlertSeverityBadge.tsx frontend/src/features/reanalysis/components/ReanalysisAlertsPanel.tsx frontend/src/features/reanalysis/components/__tests__/ReanalysisAlertsPanel.test.tsx
git commit -m "feat(reanalysis): add KB-change alerts panel" -- frontend/src/features/reanalysis/components/KbAlertSeverityBadge.tsx frontend/src/features/reanalysis/components/ReanalysisAlertsPanel.tsx frontend/src/features/reanalysis/components/__tests__/ReanalysisAlertsPanel.test.tsx
```

---

### Task 12: Embed the panel + default MSW handler + full verification

**Files:**
- Modify: `frontend/src/features/rare-disease/pages/OdysseyDetailPage.tsx` (Plan 2) — add a reanalysis-alerts section keyed on the odyssey's patient
- Modify: `frontend/src/test/mocks/handlers.ts`

- [ ] **Step 1: Embed the panel.** Read `frontend/src/features/rare-disease/pages/OdysseyDetailPage.tsx`. It loads an odyssey via `useOdyssey(odysseyId)` and renders `odyssey.patient_id`. Add the import and a section after the phenotype panel:

```tsx
import { ReanalysisAlertsPanel } from "@/features/reanalysis/components/ReanalysisAlertsPanel";
```
```tsx
<section className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
  <h3 className="mb-2 text-sm font-semibold text-[var(--text-primary)]">Reanalysis alerts</h3>
  <ReanalysisAlertsPanel patientId={odyssey.patient_id} />
</section>
```
(Use the real field for the patient id — `odyssey.patient_id` per Plan 2's `DiagnosticOdyssey` type. Keep the edit minimal/additive; `git add -p` if the file has unrelated concurrent changes.)

- [ ] **Step 2: Add a default MSW handler** — append to the `handlers` array in `frontend/src/test/mocks/handlers.ts`:

```ts
  http.get("/api/patients/:id/kb-alerts", () => HttpResponse.json({ success: true, data: [] })),
```

- [ ] **Step 3: Full verification.**

```bash
docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter 'Reanalysis|ClassificationBucket|VariantCanonicalizer|KbAlert|ReanalyzeVariants|ClinGenAlleleRegistry|FactorySmoke'"
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint --test"
docker compose exec -T node sh -c "cd /app && npx tsc --noEmit"
docker compose exec -T node sh -c "cd /app && npx vite build"
docker compose exec -T node sh -c "cd /app && npx vitest run"
```
Expected: backend reanalysis suite green; Pint clean; tsc clean; vite build OK; full vitest green. (Pre-existing CaseDiscussion/Event failures unrelated; if a backend combined-run failure appears from `clinical`-schema seed leakage, ensure unit tests used the test-only gene `TESTGENEX` — they do.)

- [ ] **Step 4: Commit:**

```bash
git add frontend/src/features/rare-disease/pages/OdysseyDetailPage.tsx frontend/src/test/mocks/handlers.ts
git commit -m "feat(reanalysis): surface KB-change alerts on the odyssey page + MSW handler" -- frontend/src/features/rare-disease/pages/OdysseyDetailPage.tsx frontend/src/test/mocks/handlers.ts
```

---

## Self-Review

**1. Spec coverage:**
- Canonical identity (CAID + ClinVar VariationID) → Tasks 3–6 (table, model, registry client, canonicalizer; coordinate-match fallback). ✓
- Reanalysis loop (delta detection, bucket/star rules, dedup, MDT task, baseline advance) → Tasks 1, 2, 7, 8. ✓
- KB-change alerts (model, API list/worklist/acknowledge, frontend panel + embed) → Tasks 3, 9, 10–12. ✓
- Non-device CDS (alert + evidence + human acknowledge; no auto-classification change) → Task 7 (raises a task, never mutates a classification) + Task 9 (human acknowledge) + Task 11 (UI). ✓
- *Deferred (explicit, with seams):* GA4GH VRS computed id (`vrs_id` column present; vrs-python/SeqRepo/UTA provisioning is a follow-on); ClinGen Gene-Disease Validity (`source` column supports it); ClinVar TSV ingestion upgrade for `DateLastEvaluated` (uses Aurora-side `baselined_at` instead); per-variant batch CAR registration (single resolve now).

**2. Placeholder scan:** Every code step has complete code; every test step has assertions + run commands. The `created_by` system-actor note (Task 7) gives the exact code (`\App\Models\User::query()->value('id')`) and the matching test adjustment — not a vague "handle it."

**3. Type/name consistency:** `ClassificationBucket::normalize/rank/isActionable/stars` consumed by `ReanalysisTransition::severity` and `ReanalysisService`; buckets (`vus`/`pathogenic`/…) consistent across PHP + the frontend `BUCKET_LABEL`. `VariantCanonicalId` fields (`clinvar_variation_id`, `baseline_significance`, `baseline_review_status`, `baselined_at`) consistent across migration/model/factory/canonicalizer/service. `delta_hash` composed identically in `ReanalysisService` and unique-indexed in the migration. Routes (`/genomic-variants/{variant}/canonicalize`, `/patients/{patient}/kb-alerts`, `/kb-alerts`, `/kb-alerts/{alert}/acknowledge`) match the frontend api paths. `GenomicVariant::canonicalId()` relation added in Task 6 and used in its test. `ref_allele`↔`reference_allele` bridge handled in `VariantCanonicalizer::matchClinVar`. ✓

**4. Risk notes:** shared files (`routes/api.php`, `routes/console.php`, `config/services.php`, `.env.example`, `handlers.ts`, `OdysseyDetailPage.tsx`) — every touching task says stage the hunk surgically (`git add -p`) and commit via explicit literal pathspec. Unit tests use the test-only gene `TESTGENEX` to avoid the known `clinical`-schema `DatabaseTruncation` leak. `vite build` run in Task 12 (stricter than tsc).

---

## Execution Handoff

Plan complete and saved to `docs/plans/2026-06-15-variant-reanalysis-loop-plan.md`. Two execution options:

1. **Subagent-Driven (recommended)** — fresh subagent per task, reviews at the logic-risk points (bucket/transition rules, the registry client, the reanalysis service, the controller), with the same explicit-pathspec / test-only-gene discipline used in Plan 3.
2. **Inline Execution** — with checkpoints.

Which approach? (Run in this checkout — Docker is bind-mounted here; mind the concurrent session on shared files.)
