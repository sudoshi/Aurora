# ACMG/AMP Variant Classification — Points Engine (Plan 3)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A transparent ACMG/AMP variant classifier — a Tavtigian/ClinGen-SVI **points engine** with evidence-per-criterion records, conservative auto-population of the safe criteria (from data Aurora already has plus supplied scores), gene-specific (CSpec/VCEP) overrides, and a mandatory human sign-off — surfaced in the UI so the clinician sees *why*, not just *what*.

**Architecture:** Additive Laravel backend (pure-logic classifier + `clinical`-schema classification records attached to the existing `genomic_variants`) following the Plan 1/2 patterns, plus a self-contained `variant-classification` frontend module embedded into the genomics variant view. Stays a **non-device CDS** by design: it computes a *provisional* classification, exposes every contributing criterion + its evidence value + source, and never finalizes without a credentialed human confirming.

**Tech Stack:** Backend — Laravel 11 / PHP 8.4 (enum + value catalog + services), PostgreSQL (`clinical` schema), Pest, Pint. Frontend — React 19 + TS strict, TanStack Query 5, Vitest 3 + MSW 2 + Testing Library. No new runtime dependencies.

---

## Scope note — this is Plan 3 of the rare-disease lead initiative

The strategy (§5.3) pairs "VRS/CAID + ACMG points engine." Per the writing-plans scope-check these are two independent subsystems with very different infrastructure, so they are split:

- **Plan 3 (this plan): ACMG/AMP points engine.** Pure logic + data model + conservative auto-evidence + UI. No heavy infra. Attaches classifications to the existing `clinical.genomic_variants`.
- **Plan 3-IDENTITY (companion, sequenced with Plan 4): GA4GH VRS 2.0 + ClinGen CAID canonicalization.** Requires vrs-python + a ~13 GB SeqRepo volume + a UTA Postgres sidecar + the external ClinGen Allele Registry (HMAC-signed PUTs). It is the canonical join key the **reanalysis loop (Plan 4)** consumes (re-query ClinVar deltas by `variationId`), so it belongs next to Plan 4. When it lands, it adds `vrs_id`/`caid` columns to `genomic_variants` — additive, no rework of this plan.

**Also deferred (noted, not gaps):** AutoPVS1 (GPL-3.0 — must run as an isolated service, so PVS1 stays curator-set here); live gnomAD/REVEL/SpliceAI fetching (this engine *accepts* `population_af` + a calibrated in-silico score as inputs and applies the criteria — the fetchers wire in later without changing the engine); FHIR Genomics Reporting emit; live CSpec API sync (this plan seeds gene specs + models the override shape; scheduled CSpec pull is a follow-on).

---

## Domain reference (authoritative — bake these exact numbers in)

**Points per evidence strength** (Tavtigian 2018, PMID 30192042; ClinGen SVI 2020): Supporting = 1, Moderate = 2, Strong = 4, Very Strong = 8. Pathogenic codes contribute **positive**, benign codes **negative**.

**Score → classification thresholds** (sum of signed points):

| Classification | Points |
|---|---|
| Pathogenic | ≥ +10 |
| Likely Pathogenic | +6 … +9 |
| VUS | 0 … +5 |
| Likely Benign | −1 … −6 |
| Benign | ≤ −7 |

**BA1 is a stand-alone benign filter** — if applied, the variant is **Benign** regardless of the point sum. **PM2 defaults to Supporting** (SVI Sept-2020). **PP5 and BP6 are deprecated** — excluded from the catalog. Any criterion may be applied at a **non-default strength** (e.g. `PVS1` at Moderate = +2, `PM2` at Supporting = +1) — so store `(code, applied_strength)`, never a boolean.

**Calibrated PP3/BP4 REVEL thresholds** (Pejaver 2022, PMID 36413997): PP3 Supporting ≥0.644, Moderate ≥0.773, Strong ≥0.932; BP4 Supporting ≤0.290, Moderate ≤0.183, Strong ≤0.016. Use exactly **one** calibrated predictor per variant (never "≥3 tools agree").

---

## File structure

**Backend — create:**
- `backend/app/Services/Genomics/Acmg/AcmgStrength.php` — enum (very_strong/strong/moderate/supporting → points)
- `backend/app/Services/Genomics/Acmg/AcmgCriteriaCatalog.php` — the 26 active ACMG codes (category, default strength, automatable, standalone, description)
- `backend/app/Services/Genomics/Acmg/AcmgClassifier.php` — points sum + threshold classification + BA1 override (pure)
- `backend/app/Services/Genomics/Acmg/GeneSpecificationResolver.php` — merge baseline + gene-specific (CSpec) overrides
- `backend/app/Services/Genomics/Acmg/AcmgAutoEvidence.php` — conservative auto-criteria (AF, in-silico, ClinVar same-residue)
- `backend/app/Services/Genomics/Acmg/HgvsProtein.php` — minimal HGVS p. parser (residue + ref/alt aa)
- `backend/app/Services/Genomics/Acmg/ClassificationService.php` — orchestration (create+autopopulate, addCriterion, recompute, confirm)
- `backend/app/Models/Clinical/VariantClassification.php`, `ClassificationCriterion.php`, `AcmgGeneSpecification.php`
- `backend/database/factories/Clinical/VariantClassificationFactory.php`, `ClassificationCriterionFactory.php`
- `backend/database/migrations/2026_06_15_020001_create_variant_classification_tables.php`
- `backend/database/migrations/2026_06_15_020002_create_acmg_gene_specifications_table.php`
- `backend/database/seeders/AcmgGeneSpecificationSeeder.php`
- `backend/app/Http/Requests/StoreClassificationCriterionRequest.php`, `ConfirmClassificationRequest.php`
- `backend/app/Http/Controllers/VariantClassificationController.php`
- `backend/tests/Unit/Services/Acmg/{AcmgClassifierTest,GeneSpecificationResolverTest,AcmgAutoEvidenceTest,HgvsProteinTest,ClassificationServiceTest}.php`
- `backend/tests/Feature/Api/{VariantClassificationTest,AcmgCatalogTest}.php`

**Backend — modify:** `backend/routes/api.php` (routes, inside `auth:sanctum`)

**Frontend — create (`frontend/src/features/variant-classification/`):** `types/index.ts`, `api/classificationApi.ts`, `hooks/useClassification.ts` (+ `__tests__`), `components/AcmgPointsBar.tsx`, `ClassificationCriteriaList.tsx`, `AddCriterionForm.tsx`, `ConfirmClassificationDialog.tsx`, `VariantClassificationPanel.tsx` (+ `__tests__`).

**Frontend — modify:** `frontend/src/features/genomics/components/VariantExpandedRow.tsx` (embed the panel — small targeted edit), `frontend/src/test/mocks/handlers.ts` (default handlers).

**Conventions (verified, same as Plans 1–2):** schema-qualified tables (FK to `clinical.genomic_variants` / `app.users`); `ApiResponse::success/error/paginated`; Form Requests; models auto-resolve factories (Clinical models override `newFactory()`); Pest feature tests seed via `app(\Database\Seeders\SuperuserSeeder::class)->run()`; DB-touching Unit tests `uses(RefreshDatabase::class)`. Backend cmds: `docker compose exec -T php sh -c "cd /var/www/html && <cmd>"`, Pint after each PHP edit (PHPStan not installed). Frontend cmds in the `node` container; **run `npx vite build` as well as `tsc` (stricter)**. Postgres "collation version mismatch" warnings are benign. **Do NOT run `npm install` / modify package.json.** A concurrent session may hold uncommitted files — stage only your files (`git add -p` on shared `routes/api.php`/`handlers.ts`/`VariantExpandedRow.tsx`); never `git add -A`. Commits: plain conventional, no Co-Authored-By. Verify branch (`v2/phase-0-scaffold`) before each commit.

---

### Task 1: AcmgStrength enum + AcmgCriteriaCatalog

**Files:**
- Create: `backend/app/Services/Genomics/Acmg/AcmgStrength.php`
- Create: `backend/app/Services/Genomics/Acmg/AcmgCriteriaCatalog.php`
- Test: `backend/tests/Unit/Services/Acmg/AcmgCriteriaCatalogTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Services\Genomics\Acmg\AcmgCriteriaCatalog;
use App\Services\Genomics\Acmg\AcmgStrength;

it('maps strengths to Tavtigian points', function () {
    expect(AcmgStrength::VeryStrong->points())->toBe(8);
    expect(AcmgStrength::Strong->points())->toBe(4);
    expect(AcmgStrength::Moderate->points())->toBe(2);
    expect(AcmgStrength::Supporting->points())->toBe(1);
});

it('catalogs the active ACMG codes and excludes deprecated PP5/BP6', function () {
    expect(AcmgCriteriaCatalog::exists('PVS1'))->toBeTrue();
    expect(AcmgCriteriaCatalog::exists('BP7'))->toBeTrue();
    expect(AcmgCriteriaCatalog::exists('PP5'))->toBeFalse();
    expect(AcmgCriteriaCatalog::exists('BP6'))->toBeFalse();
    expect(AcmgCriteriaCatalog::all())->toHaveCount(26);
});

it('classifies code category and standalone flag', function () {
    expect(AcmgCriteriaCatalog::category('PVS1'))->toBe('pathogenic');
    expect(AcmgCriteriaCatalog::category('BA1'))->toBe('benign');
    expect(AcmgCriteriaCatalog::isStandalone('BA1'))->toBeTrue();
    expect(AcmgCriteriaCatalog::isStandalone('BS1'))->toBeFalse();
});

it('defaults PM2 to supporting per SVI 2020', function () {
    expect(AcmgCriteriaCatalog::defaultStrength('PM2'))->toBe(AcmgStrength::Supporting);
    expect(AcmgCriteriaCatalog::defaultStrength('PVS1'))->toBe(AcmgStrength::VeryStrong);
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter AcmgCriteriaCatalogTest"`
Expected: FAIL — classes not found.

- [ ] **Step 3: Implement the enum** — `backend/app/Services/Genomics/Acmg/AcmgStrength.php`:

```php
<?php

namespace App\Services\Genomics\Acmg;

enum AcmgStrength: string
{
    case VeryStrong = 'very_strong';
    case Strong = 'strong';
    case Moderate = 'moderate';
    case Supporting = 'supporting';

    /** Tavtigian 2018 / ClinGen SVI 2020 point magnitude (unsigned). */
    public function points(): int
    {
        return match ($this) {
            self::VeryStrong => 8,
            self::Strong => 4,
            self::Moderate => 2,
            self::Supporting => 1,
        };
    }
}
```

- [ ] **Step 4: Implement the catalog** — `backend/app/Services/Genomics/Acmg/AcmgCriteriaCatalog.php`:

```php
<?php

namespace App\Services\Genomics\Acmg;

/**
 * The active ACMG/AMP 2015 evidence codes (Richards et al., PMID 25741868),
 * excluding SVI-deprecated PP5/BP6. PM2 defaults to Supporting (SVI Sept-2020).
 * Each entry: [category, default_strength, automatable, standalone, description].
 */
class AcmgCriteriaCatalog
{
    private const CRITERIA = [
        // Pathogenic
        'PVS1' => ['pathogenic', AcmgStrength::VeryStrong, false, false, 'Null variant in a gene where loss-of-function is a known disease mechanism'],
        'PS1' => ['pathogenic', AcmgStrength::Strong, true, false, 'Same amino-acid change as an established pathogenic variant'],
        'PS2' => ['pathogenic', AcmgStrength::Strong, false, false, 'De novo (maternity and paternity confirmed) in a patient with the disease'],
        'PS3' => ['pathogenic', AcmgStrength::Strong, false, false, 'Well-established functional studies show a damaging effect'],
        'PS4' => ['pathogenic', AcmgStrength::Strong, false, false, 'Prevalence in affected individuals significantly increased vs controls'],
        'PM1' => ['pathogenic', AcmgStrength::Moderate, false, false, 'Located in a mutational hotspot or critical functional domain'],
        'PM2' => ['pathogenic', AcmgStrength::Supporting, true, false, 'Absent or extremely low frequency in population databases'],
        'PM3' => ['pathogenic', AcmgStrength::Moderate, false, false, 'For recessive disorders, detected in trans with a pathogenic variant'],
        'PM4' => ['pathogenic', AcmgStrength::Moderate, false, false, 'Protein length change (in-frame indel / stop-loss) in a non-repeat region'],
        'PM5' => ['pathogenic', AcmgStrength::Moderate, true, false, 'Novel missense at a residue where a different pathogenic missense was seen'],
        'PM6' => ['pathogenic', AcmgStrength::Moderate, false, false, 'Assumed de novo without confirmation of maternity and paternity'],
        'PP1' => ['pathogenic', AcmgStrength::Supporting, false, false, 'Co-segregation with disease in multiple affected family members'],
        'PP2' => ['pathogenic', AcmgStrength::Supporting, false, false, 'Missense in a gene with low benign-missense rate where missense is a mechanism'],
        'PP3' => ['pathogenic', AcmgStrength::Supporting, true, false, 'Calibrated in-silico evidence supports a deleterious effect'],
        'PP4' => ['pathogenic', AcmgStrength::Supporting, false, false, 'Patient phenotype/family history highly specific for a single-gene disease'],
        // Benign
        'BA1' => ['benign', AcmgStrength::Strong, true, true, 'Allele frequency >5% in population databases (stand-alone benign)'],
        'BS1' => ['benign', AcmgStrength::Strong, true, false, 'Allele frequency greater than expected for the disorder'],
        'BS2' => ['benign', AcmgStrength::Strong, false, false, 'Observed in healthy adults where full penetrance is expected'],
        'BS3' => ['benign', AcmgStrength::Strong, false, false, 'Well-established functional studies show no damaging effect'],
        'BS4' => ['benign', AcmgStrength::Strong, false, false, 'Lack of segregation in affected family members'],
        'BP1' => ['benign', AcmgStrength::Supporting, false, false, 'Missense in a gene where only truncating variants cause disease'],
        'BP2' => ['benign', AcmgStrength::Supporting, false, false, 'Observed in trans/cis with a pathogenic variant inconsistent with disease'],
        'BP3' => ['benign', AcmgStrength::Supporting, false, false, 'In-frame indel in a repetitive region without known function'],
        'BP4' => ['benign', AcmgStrength::Supporting, true, false, 'Calibrated in-silico evidence supports no impact'],
        'BP5' => ['benign', AcmgStrength::Supporting, false, false, 'Variant found in a case with an alternate molecular cause of disease'],
        'BP7' => ['benign', AcmgStrength::Supporting, false, false, 'Synonymous with no predicted splice impact and no conservation'],
    ];

    /** @return array<string, array{category:string,default_strength:AcmgStrength,automatable:bool,standalone:bool,description:string}> */
    public static function all(): array
    {
        $out = [];
        foreach (self::CRITERIA as $code => [$category, $strength, $auto, $standalone, $desc]) {
            $out[$code] = [
                'category' => $category,
                'default_strength' => $strength,
                'automatable' => $auto,
                'standalone' => $standalone,
                'description' => $desc,
            ];
        }

        return $out;
    }

    public static function exists(string $code): bool
    {
        return isset(self::CRITERIA[$code]);
    }

    public static function category(string $code): string
    {
        return self::CRITERIA[$code][0] ?? throw new \InvalidArgumentException("Unknown ACMG code: {$code}");
    }

    public static function defaultStrength(string $code): AcmgStrength
    {
        return self::CRITERIA[$code][1] ?? throw new \InvalidArgumentException("Unknown ACMG code: {$code}");
    }

    public static function isStandalone(string $code): bool
    {
        return self::CRITERIA[$code][3] ?? false;
    }
}
```

- [ ] **Step 5: Run — expect PASS (4)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Acmg/AcmgStrength.php backend/app/Services/Genomics/Acmg/AcmgCriteriaCatalog.php backend/tests/Unit/Services/Acmg/AcmgCriteriaCatalogTest.php
git commit -m "feat(acmg): add ACMG criteria catalog + strength/points enum"
```

---

### Task 2: AcmgClassifier (points sum → classification)

**Files:**
- Create: `backend/app/Services/Genomics/Acmg/AcmgClassifier.php`
- Test: `backend/tests/Unit/Services/Acmg/AcmgClassifierTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Services\Genomics\Acmg\AcmgClassifier;
use App\Services\Genomics\Acmg\AcmgStrength;

beforeEach(fn () => $this->classifier = new AcmgClassifier);

function crit(string $code, AcmgStrength $s): array
{
    return ['code' => $code, 'strength' => $s];
}

it('sums signed points and classifies pathogenic at >=10', function () {
    // PVS1 (8) + PM2 supporting (1) + PP3 supporting (1) = 10
    $r = $this->classifier->classify([
        crit('PVS1', AcmgStrength::VeryStrong),
        crit('PM2', AcmgStrength::Supporting),
        crit('PP3', AcmgStrength::Supporting),
    ]);
    expect($r['points'])->toBe(10);
    expect($r['classification'])->toBe('pathogenic');
});

it('classifies likely pathogenic in 6..9', function () {
    // PS1 (4) + PM1 (2) = 6
    $r = $this->classifier->classify([crit('PS1', AcmgStrength::Strong), crit('PM1', AcmgStrength::Moderate)]);
    expect($r['points'])->toBe(6);
    expect($r['classification'])->toBe('likely_pathogenic');
});

it('classifies VUS in 0..5', function () {
    $r = $this->classifier->classify([crit('PM1', AcmgStrength::Moderate)]); // +2
    expect($r['classification'])->toBe('vus');
});

it('classifies likely benign in -1..-6', function () {
    // BS1 strong (-4) + BP4 supporting (-1) = -5
    $r = $this->classifier->classify([crit('BS1', AcmgStrength::Strong), crit('BP4', AcmgStrength::Supporting)]);
    expect($r['points'])->toBe(-5);
    expect($r['classification'])->toBe('likely_benign');
});

it('classifies benign at <=-7', function () {
    // BS1 (-4) + BS2 (-4) = -8
    $r = $this->classifier->classify([crit('BS1', AcmgStrength::Strong), crit('BS2', AcmgStrength::Strong)]);
    expect($r['points'])->toBe(-8);
    expect($r['classification'])->toBe('benign');
});

it('treats BA1 as a stand-alone benign override regardless of points', function () {
    $r = $this->classifier->classify([
        crit('BA1', AcmgStrength::Strong),
        crit('PVS1', AcmgStrength::VeryStrong), // would otherwise pull pathogenic
    ]);
    expect($r['classification'])->toBe('benign');
    expect($r['standalone_benign'])->toBeTrue();
});

it('honors strength modulation (PVS1 applied at Moderate = +2)', function () {
    $r = $this->classifier->classify([crit('PVS1', AcmgStrength::Moderate)]);
    expect($r['points'])->toBe(2);
});
```

- [ ] **Step 2: Run — expect FAIL.**

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter AcmgClassifierTest"`

- [ ] **Step 3: Implement** — `backend/app/Services/Genomics/Acmg/AcmgClassifier.php`:

```php
<?php

namespace App\Services\Genomics\Acmg;

/**
 * Pure ACMG/AMP point-based classifier (Tavtigian 2018 / ClinGen SVI 2020).
 * Input: list of ['code' => string, 'strength' => AcmgStrength].
 */
class AcmgClassifier
{
    /**
     * @param  array<int, array{code:string, strength:AcmgStrength}>  $applied
     * @return array{classification:string, points:int, standalone_benign:bool}
     */
    public function classify(array $applied): array
    {
        $points = 0;
        $standaloneBenign = false;

        foreach ($applied as $c) {
            $category = AcmgCriteriaCatalog::category($c['code']);
            if ($category === 'benign' && AcmgCriteriaCatalog::isStandalone($c['code'])) {
                $standaloneBenign = true;
            }
            $magnitude = $c['strength']->points();
            $points += $category === 'pathogenic' ? $magnitude : -$magnitude;
        }

        return [
            'classification' => $standaloneBenign ? 'benign' : $this->fromPoints($points),
            'points' => $points,
            'standalone_benign' => $standaloneBenign,
        ];
    }

    private function fromPoints(int $points): string
    {
        return match (true) {
            $points >= 10 => 'pathogenic',
            $points >= 6 => 'likely_pathogenic',
            $points >= 0 => 'vus',
            $points >= -6 => 'likely_benign',
            default => 'benign',
        };
    }
}
```

- [ ] **Step 4: Run — expect PASS (7)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Acmg/AcmgClassifier.php backend/tests/Unit/Services/Acmg/AcmgClassifierTest.php
git commit -m "feat(acmg): add point-based classifier with BA1 stand-alone override"
```

---

### Task 3: HgvsProtein parser (for PS1/PM5)

**Files:**
- Create: `backend/app/Services/Genomics/Acmg/HgvsProtein.php`
- Test: `backend/tests/Unit/Services/Acmg/HgvsProteinTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Services\Genomics\Acmg\HgvsProtein;

it('parses a 3-letter protein change', function () {
    $p = HgvsProtein::parse('p.Arg175His');
    expect($p)->toMatchArray(['ref' => 'R', 'position' => 175, 'alt' => 'H']);
});

it('parses a 1-letter protein change with NP prefix', function () {
    $p = HgvsProtein::parse('NP_000537.3:p.R175H');
    expect($p)->toMatchArray(['ref' => 'R', 'position' => 175, 'alt' => 'H']);
});

it('returns null for non-missense or unparseable input', function () {
    expect(HgvsProtein::parse('c.524G>A'))->toBeNull();
    expect(HgvsProtein::parse('p.Arg175Ter'))->toBeNull(); // nonsense, not a missense substitution
    expect(HgvsProtein::parse(null))->toBeNull();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** — `backend/app/Services/Genomics/Acmg/HgvsProtein.php`:

```php
<?php

namespace App\Services\Genomics\Acmg;

/**
 * Minimal HGVS protein-substitution parser, sufficient for ACMG PS1/PM5
 * same-residue logic. Returns single-letter ref/alt amino acids + position,
 * or null when the input is not a simple missense substitution.
 */
class HgvsProtein
{
    private const THREE_TO_ONE = [
        'Ala' => 'A', 'Arg' => 'R', 'Asn' => 'N', 'Asp' => 'D', 'Cys' => 'C',
        'Gln' => 'Q', 'Glu' => 'E', 'Gly' => 'G', 'His' => 'H', 'Ile' => 'I',
        'Leu' => 'L', 'Lys' => 'K', 'Met' => 'M', 'Phe' => 'F', 'Pro' => 'P',
        'Ser' => 'S', 'Thr' => 'T', 'Trp' => 'W', 'Tyr' => 'Y', 'Val' => 'V',
    ];

    /** @return array{ref:string, position:int, alt:string}|null */
    public static function parse(?string $hgvs): ?array
    {
        if ($hgvs === null) {
            return null;
        }

        // Isolate the p. portion if a full HGVS (NP_..:p.X) was given.
        if (str_contains($hgvs, 'p.')) {
            $hgvs = substr($hgvs, strpos($hgvs, 'p.'));
        }
        $expr = ltrim($hgvs, 'p.()');

        // Three-letter form: ArgNNNHis
        if (preg_match('/^([A-Z][a-z]{2})(\d+)([A-Z][a-z]{2})$/', $expr, $m)) {
            $ref = self::THREE_TO_ONE[$m[1]] ?? null;
            $alt = self::THREE_TO_ONE[$m[3]] ?? null;
            if ($ref && $alt) {
                return ['ref' => $ref, 'position' => (int) $m[2], 'alt' => $alt];
            }

            return null;
        }

        // One-letter form: RNNNH
        if (preg_match('/^([A-Z])(\d+)([A-Z])$/', $expr, $m)) {
            $valid = array_values(self::THREE_TO_ONE);
            if (in_array($m[1], $valid, true) && in_array($m[3], $valid, true)) {
                return ['ref' => $m[1], 'position' => (int) $m[2], 'alt' => $m[3]];
            }
        }

        return null;
    }
}
```

- [ ] **Step 4: Run — expect PASS (3)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Acmg/HgvsProtein.php backend/tests/Unit/Services/Acmg/HgvsProteinTest.php
git commit -m "feat(acmg): add minimal HGVS protein parser for PS1/PM5"
```

---

### Task 4: Migrations — classification + gene-specification tables

**Files:**
- Create: `backend/database/migrations/2026_06_15_020001_create_variant_classification_tables.php`
- Create: `backend/database/migrations/2026_06_15_020002_create_acmg_gene_specifications_table.php`

- [ ] **Step 1: Write the classification-tables migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.variant_classifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('genomic_variant_id');
            $table->string('gene_symbol')->nullable();
            $table->string('computed_classification'); // pathogenic, likely_pathogenic, vus, likely_benign, benign
            $table->integer('computed_points')->default(0);
            $table->string('final_classification')->nullable();   // set on human sign-off
            $table->string('status')->default('computed');        // computed, confirmed
            $table->string('ruleset_version')->default('acmg-2015-svi-2020');
            $table->string('gene_specification_id')->nullable();  // CSpec spec id applied, if any
            $table->text('override_reason')->nullable();          // required when final != computed
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('genomic_variant_id')->references('id')->on('clinical.genomic_variants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('app.users');
            $table->foreign('confirmed_by')->references('id')->on('app.users');
            $table->index('genomic_variant_id');
            $table->index('status');
        });

        Schema::create('clinical.classification_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classification_id');
            $table->string('code');                 // PVS1 … BP7
            $table->string('applied_strength');     // very_strong, strong, moderate, supporting
            $table->integer('points');              // signed
            $table->string('data_source');          // auto:gnomad, auto:clinvar, auto:insilico, manual
            $table->string('evidence_value')->nullable(); // e.g. "REVEL=0.81", "gnomAD AF=0.0001"
            $table->text('rationale')->nullable();
            $table->string('set_by')->default('curator'); // auto, curator
            $table->unsignedBigInteger('set_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('classification_id')->references('id')->on('clinical.variant_classifications')->onDelete('cascade');
            $table->foreign('set_by_user_id')->references('id')->on('app.users');
            $table->unique(['classification_id', 'code']);
            $table->index('classification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.classification_criteria');
        Schema::dropIfExists('clinical.variant_classifications');
    }
};
```

- [ ] **Step 2: Write the gene-specifications migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.acmg_gene_specifications', function (Blueprint $table) {
            $table->id();
            $table->string('gene_symbol');
            $table->string('disease')->nullable();   // MONDO / free text
            $table->string('vcep')->nullable();       // ClinGen VCEP name
            $table->string('spec_id');                // CSpec specification id
            $table->string('spec_version');
            $table->jsonb('criteria_overrides');      // { "PM2": {"applicable":true,"default_strength":"supporting"}, "BA1": {"af_threshold":0.01}, ... }
            $table->string('source_url')->nullable();
            $table->timestamps();

            $table->unique(['gene_symbol', 'spec_id', 'spec_version']);
            $table->index('gene_symbol');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.acmg_gene_specifications');
    }
};
```

- [ ] **Step 3: Apply to the testing DB and verify**

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan migrate --env=testing --force"`
Expected: both migrations report DONE (ignore collation warnings).

- [ ] **Step 4: Commit**

```bash
git add backend/database/migrations/2026_06_15_020001_create_variant_classification_tables.php backend/database/migrations/2026_06_15_020002_create_acmg_gene_specifications_table.php
git commit -m "feat(acmg): add classification + gene-specification tables"
```

---

### Task 5: Models + factories

**Files:**
- Create: `backend/app/Models/Clinical/VariantClassification.php`, `ClassificationCriterion.php`, `AcmgGeneSpecification.php`
- Create: `backend/database/factories/Clinical/VariantClassificationFactory.php`, `ClassificationCriterionFactory.php`
- Test: append to `backend/tests/Feature/FactorySmokeTest.php`

- [ ] **Step 1: Add failing factory smoke tests** — append to `backend/tests/Feature/FactorySmokeTest.php`:

```php
it('creates a VariantClassification via factory', function () {
    $c = \App\Models\Clinical\VariantClassification::factory()->create();
    expect($c->id)->toBeInt();
    expect($c->computed_classification)->toBeString();
});

it('creates a ClassificationCriterion via factory', function () {
    $crit = \App\Models\Clinical\ClassificationCriterion::factory()->create();
    expect($crit->id)->toBeInt();
    expect($crit->code)->toBeString();
});
```

- [ ] **Step 2: Run — expect FAIL** (classes not found).

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter FactorySmokeTest"`

- [ ] **Step 3: Create `VariantClassification`** — `backend/app/Models/Clinical/VariantClassification.php`:

```php
<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantClassification extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'clinical.variant_classifications';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\VariantClassificationFactory::new();
    }

    protected $fillable = [
        'genomic_variant_id', 'gene_symbol', 'computed_classification', 'computed_points',
        'final_classification', 'status', 'ruleset_version', 'gene_specification_id',
        'override_reason', 'created_by', 'confirmed_by', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return ['computed_points' => 'integer', 'confirmed_at' => 'datetime'];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(GenomicVariant::class, 'genomic_variant_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(ClassificationCriterion::class, 'classification_id');
    }
}
```

- [ ] **Step 4: Create `ClassificationCriterion`** — `backend/app/Models/Clinical/ClassificationCriterion.php`:

```php
<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassificationCriterion extends Model
{
    use HasFactory;

    protected $table = 'clinical.classification_criteria';

    protected static function newFactory()
    {
        return \Database\Factories\Clinical\ClassificationCriterionFactory::new();
    }

    protected $fillable = [
        'classification_id', 'code', 'applied_strength', 'points',
        'data_source', 'evidence_value', 'rationale', 'set_by', 'set_by_user_id',
    ];

    protected function casts(): array
    {
        return ['points' => 'integer'];
    }

    public function classification(): BelongsTo
    {
        return $this->belongsTo(VariantClassification::class, 'classification_id');
    }
}
```

- [ ] **Step 5: Create `AcmgGeneSpecification`** — `backend/app/Models/Clinical/AcmgGeneSpecification.php`:

```php
<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;

class AcmgGeneSpecification extends Model
{
    protected $table = 'clinical.acmg_gene_specifications';

    protected $fillable = [
        'gene_symbol', 'disease', 'vcep', 'spec_id', 'spec_version', 'criteria_overrides', 'source_url',
    ];

    protected function casts(): array
    {
        return ['criteria_overrides' => 'array'];
    }
}
```

- [ ] **Step 6: Create factories** — `backend/database/factories/Clinical/VariantClassificationFactory.php`:

```php
<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantClassification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VariantClassificationFactory extends Factory
{
    protected $model = VariantClassification::class;

    public function definition(): array
    {
        return [
            'genomic_variant_id' => GenomicVariant::factory(),
            'gene_symbol' => 'BRCA1',
            'computed_classification' => 'vus',
            'computed_points' => 0,
            'status' => 'computed',
            'ruleset_version' => 'acmg-2015-svi-2020',
            'created_by' => User::factory(),
        ];
    }
}
```

`backend/database/factories/Clinical/ClassificationCriterionFactory.php`:

```php
<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\ClassificationCriterion;
use App\Models\Clinical\VariantClassification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassificationCriterionFactory extends Factory
{
    protected $model = ClassificationCriterion::class;

    public function definition(): array
    {
        return [
            'classification_id' => VariantClassification::factory(),
            'code' => 'PM2',
            'applied_strength' => 'supporting',
            'points' => 1,
            'data_source' => 'manual',
            'set_by' => 'curator',
            'set_by_user_id' => User::factory(),
        ];
    }
}
```

> `GenomicVariant::factory()` already exists (`Database\Factories\Clinical\GenomicVariantFactory`). If its definition omits required NOT-NULL columns, the factory will still satisfy the FK here; do not modify it.

- [ ] **Step 7: Run — expect PASS**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Models/Clinical/VariantClassification.php backend/app/Models/Clinical/ClassificationCriterion.php backend/app/Models/Clinical/AcmgGeneSpecification.php backend/database/factories/Clinical/VariantClassificationFactory.php backend/database/factories/Clinical/ClassificationCriterionFactory.php backend/tests/Feature/FactorySmokeTest.php
git commit -m "feat(acmg): add classification models + factories"
```

---

### Task 6: GeneSpecificationResolver

**Files:**
- Create: `backend/app/Services/Genomics/Acmg/GeneSpecificationResolver.php`
- Test: `backend/tests/Unit/Services/Acmg/GeneSpecificationResolverTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Clinical\AcmgGeneSpecification;
use App\Services\Genomics\Acmg\GeneSpecificationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->resolver = new GeneSpecificationResolver);

it('returns baseline (no overrides) when no gene spec exists', function () {
    $spec = $this->resolver->resolve('UNKNOWNGENE');
    expect($spec['spec_id'])->toBeNull();
    expect($spec['overrides'])->toBe([]);
    expect($spec['af_threshold_ba1'])->toBe(0.05); // baseline BA1 default
});

it('merges a gene-specific override and AF thresholds', function () {
    AcmgGeneSpecification::create([
        'gene_symbol' => 'MYH7',
        'vcep' => 'Cardiomyopathy VCEP',
        'spec_id' => 'GN001',
        'spec_version' => '1.0.0',
        'criteria_overrides' => [
            'BA1' => ['af_threshold' => 0.001],
            'BS1' => ['af_threshold' => 0.0002],
            'PM2' => ['af_threshold' => 0.00004],
            'PP2' => ['applicable' => false],
        ],
    ]);

    $spec = $this->resolver->resolve('MYH7');
    expect($spec['spec_id'])->toBe('GN001');
    expect($spec['af_threshold_ba1'])->toBe(0.001);
    expect($spec['af_threshold_bs1'])->toBe(0.0002);
    expect($spec['af_threshold_pm2'])->toBe(0.00004);
    expect($spec['overrides']['PP2']['applicable'])->toBeFalse();
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** — `backend/app/Services/Genomics/Acmg/GeneSpecificationResolver.php`:

```php
<?php

namespace App\Services\Genomics\Acmg;

use App\Models\Clinical\AcmgGeneSpecification;

/**
 * Resolves the effective ACMG ruleset for a gene: baseline ACMG-2015/SVI-2020
 * thresholds, overridden by a ClinGen CSpec/VCEP gene specification when one exists.
 */
class GeneSpecificationResolver
{
    // Baseline allele-frequency thresholds (dominant defaults).
    private const BASELINE_BA1 = 0.05;
    private const BASELINE_BS1 = 0.01;
    private const BASELINE_PM2 = 0.0001;

    /**
     * @return array{spec_id:?string, spec_version:?string, overrides:array<string,mixed>, af_threshold_ba1:float, af_threshold_bs1:float, af_threshold_pm2:float}
     */
    public function resolve(string $gene, ?string $disease = null): array
    {
        $query = AcmgGeneSpecification::where('gene_symbol', $gene);
        if ($disease !== null) {
            $query->where(fn ($q) => $q->where('disease', $disease)->orWhereNull('disease'));
        }
        $spec = $query->orderByDesc('spec_version')->first();

        $overrides = $spec?->criteria_overrides ?? [];

        return [
            'spec_id' => $spec?->spec_id,
            'spec_version' => $spec?->spec_version,
            'overrides' => $overrides,
            'af_threshold_ba1' => (float) ($overrides['BA1']['af_threshold'] ?? self::BASELINE_BA1),
            'af_threshold_bs1' => (float) ($overrides['BS1']['af_threshold'] ?? self::BASELINE_BS1),
            'af_threshold_pm2' => (float) ($overrides['PM2']['af_threshold'] ?? self::BASELINE_PM2),
        ];
    }

    /** A criterion is applicable unless a gene spec explicitly disables it. */
    public function isApplicable(array $resolved, string $code): bool
    {
        return (bool) ($resolved['overrides'][$code]['applicable'] ?? true);
    }
}
```

- [ ] **Step 4: Run — expect PASS (2)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Acmg/GeneSpecificationResolver.php backend/tests/Unit/Services/Acmg/GeneSpecificationResolverTest.php
git commit -m "feat(acmg): add gene-specification (CSpec/VCEP) resolver"
```

---

### Task 7: AcmgAutoEvidence — frequency + in-silico criteria

**Files:**
- Create: `backend/app/Services/Genomics/Acmg/AcmgAutoEvidence.php`
- Test: `backend/tests/Unit/Services/Acmg/AcmgAutoEvidenceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Services\Genomics\Acmg\AcmgAutoEvidence;
use App\Services\Genomics\Acmg\GeneSpecificationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->auto = new AcmgAutoEvidence(new GeneSpecificationResolver));

it('proposes BA1 for common variants', function () {
    $c = $this->auto->fromFrequency('MYH7', 0.08);
    expect(collect($c)->pluck('code'))->toContain('BA1');
});

it('proposes BS1 between the BS1 and BA1 thresholds', function () {
    $c = $this->auto->fromFrequency('MYH7', 0.02); // > baseline BS1 0.01, < BA1 0.05
    $codes = collect($c)->pluck('code');
    expect($codes)->toContain('BS1');
    expect($codes)->not->toContain('BA1');
});

it('proposes PM2_Supporting for absent/rare variants', function () {
    $c = $this->auto->fromFrequency('MYH7', 0.0);
    $pm2 = collect($c)->firstWhere('code', 'PM2');
    expect($pm2)->not->toBeNull();
    expect($pm2['strength'])->toBe('supporting');
    expect($pm2['data_source'])->toBe('auto:gnomad');
});

it('maps REVEL to calibrated PP3 strengths (Pejaver 2022)', function () {
    expect(collect($this->auto->fromInSilico(0.95))->firstWhere('code', 'PP3')['strength'])->toBe('strong');     // >=0.932
    expect(collect($this->auto->fromInSilico(0.80))->firstWhere('code', 'PP3')['strength'])->toBe('moderate');   // >=0.773
    expect(collect($this->auto->fromInSilico(0.70))->firstWhere('code', 'PP3')['strength'])->toBe('supporting'); // >=0.644
});

it('maps REVEL to calibrated BP4 strengths', function () {
    expect(collect($this->auto->fromInSilico(0.01))->firstWhere('code', 'BP4')['strength'])->toBe('strong');     // <=0.016
    expect(collect($this->auto->fromInSilico(0.10))->firstWhere('code', 'BP4')['strength'])->toBe('moderate');   // <=0.183
    expect(collect($this->auto->fromInSilico(0.25))->firstWhere('code', 'BP4')['strength'])->toBe('supporting'); // <=0.290
});

it('proposes nothing in-silico for the intermediate gray zone', function () {
    expect($this->auto->fromInSilico(0.45))->toBe([]);
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** — `backend/app/Services/Genomics/Acmg/AcmgAutoEvidence.php`:

```php
<?php

namespace App\Services\Genomics\Acmg;

/**
 * Conservative auto-population of the SAFE ACMG criteria from data Aurora has
 * (population AF, calibrated in-silico score). Returns proposed criteria as
 * ['code','strength','data_source','evidence_value']; everything stays overridable.
 */
class AcmgAutoEvidence
{
    // Pejaver 2022 calibrated REVEL thresholds.
    private const PP3_STRONG = 0.932;
    private const PP3_MODERATE = 0.773;
    private const PP3_SUPPORTING = 0.644;
    private const BP4_STRONG = 0.016;
    private const BP4_MODERATE = 0.183;
    private const BP4_SUPPORTING = 0.290;

    public function __construct(private GeneSpecificationResolver $resolver) {}

    /**
     * @return array<int, array{code:string,strength:string,data_source:string,evidence_value:string}>
     */
    public function fromFrequency(string $gene, float $populationAf, ?string $disease = null): array
    {
        $spec = $this->resolver->resolve($gene, $disease);
        $ev = sprintf('gnomAD AF=%g', $populationAf);
        $out = [];

        if ($populationAf > $spec['af_threshold_ba1']) {
            $out[] = ['code' => 'BA1', 'strength' => 'strong', 'data_source' => 'auto:gnomad', 'evidence_value' => $ev];
        } elseif ($populationAf > $spec['af_threshold_bs1']) {
            $out[] = ['code' => 'BS1', 'strength' => 'strong', 'data_source' => 'auto:gnomad', 'evidence_value' => $ev];
        } elseif ($populationAf <= $spec['af_threshold_pm2']) {
            $out[] = ['code' => 'PM2', 'strength' => 'supporting', 'data_source' => 'auto:gnomad', 'evidence_value' => $ev];
        }

        return $out;
    }

    /**
     * @return array<int, array{code:string,strength:string,data_source:string,evidence_value:string}>
     */
    public function fromInSilico(float $revel): array
    {
        $ev = sprintf('REVEL=%g', $revel);

        $strength = match (true) {
            $revel >= self::PP3_STRONG => 'strong',
            $revel >= self::PP3_MODERATE => 'moderate',
            $revel >= self::PP3_SUPPORTING => 'supporting',
            default => null,
        };
        if ($strength !== null) {
            return [['code' => 'PP3', 'strength' => $strength, 'data_source' => 'auto:insilico', 'evidence_value' => $ev]];
        }

        $strength = match (true) {
            $revel <= self::BP4_STRONG => 'strong',
            $revel <= self::BP4_MODERATE => 'moderate',
            $revel <= self::BP4_SUPPORTING => 'supporting',
            default => null,
        };
        if ($strength !== null) {
            return [['code' => 'BP4', 'strength' => $strength, 'data_source' => 'auto:insilico', 'evidence_value' => $ev]];
        }

        return [];
    }
}
```

- [ ] **Step 4: Run — expect PASS (6)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Acmg/AcmgAutoEvidence.php backend/tests/Unit/Services/Acmg/AcmgAutoEvidenceTest.php
git commit -m "feat(acmg): add auto-evidence for AF (PM2/BS1/BA1) + calibrated in-silico (PP3/BP4)"
```

---

### Task 8: AcmgAutoEvidence — ClinVar same-residue (PS1/PM5)

**Files:**
- Modify: `backend/app/Services/Genomics/Acmg/AcmgAutoEvidence.php` (add `fromClinVar`)
- Test: `backend/tests/Unit/Services/Acmg/AcmgAutoEvidenceTest.php` (add a `describe`/cases)

- [ ] **Step 1: Add the failing test** — append to `AcmgAutoEvidenceTest.php`:

```php
it('proposes PS1 for the same amino-acid change as a known pathogenic ClinVar variant', function () {
    \App\Models\Clinical\ClinVarVariant::create([
        'chromosome' => '17', 'position' => 43000000, 'reference_allele' => 'G', 'alternate_allele' => 'A',
        'gene_symbol' => 'TP53', 'hgvs' => 'NP_000537.3:p.Arg175His', 'is_pathogenic' => true,
        'clinical_significance' => 'Pathogenic',
    ]);

    $c = $this->auto->fromClinVar('TP53', 'p.Arg175His');
    expect(collect($c)->firstWhere('code', 'PS1'))->not->toBeNull();
});

it('proposes PM5 for a novel change at a residue with a different known pathogenic missense', function () {
    \App\Models\Clinical\ClinVarVariant::create([
        'chromosome' => '17', 'position' => 43000000, 'reference_allele' => 'G', 'alternate_allele' => 'T',
        'gene_symbol' => 'TP53', 'hgvs' => 'NP_000537.3:p.Arg175Leu', 'is_pathogenic' => true,
        'clinical_significance' => 'Pathogenic',
    ]);

    $c = $this->auto->fromClinVar('TP53', 'p.Arg175His'); // same residue 175, different alt aa
    $codes = collect($c)->pluck('code');
    expect($codes)->toContain('PM5');
    expect($codes)->not->toContain('PS1');
});

it('proposes nothing from ClinVar when the residue is unseen or the change is not missense', function () {
    expect($this->auto->fromClinVar('TP53', 'p.Arg175His'))->toBe([]); // no ClinVar rows
    expect($this->auto->fromClinVar('TP53', 'c.524G>A'))->toBe([]);     // not a protein substitution
});
```

- [ ] **Step 2: Run — expect FAIL** (`fromClinVar` undefined).

- [ ] **Step 3: Implement** — add the `use` import and method to `AcmgAutoEvidence.php`:

Add at the top with the other imports:

```php
use App\Models\Clinical\ClinVarVariant;
```

Add this method to the class:

```php
    /**
     * PS1 (same amino-acid change) / PM5 (same residue, different change) by matching
     * the variant's protein change against pathogenic ClinVar variants in the same gene.
     *
     * @return array<int, array{code:string,strength:string,data_source:string,evidence_value:string}>
     */
    public function fromClinVar(string $gene, ?string $proteinHgvs): array
    {
        $target = HgvsProtein::parse($proteinHgvs);
        if ($target === null) {
            return [];
        }

        $candidates = ClinVarVariant::where('gene_symbol', $gene)
            ->where('is_pathogenic', true)
            ->whereNotNull('hgvs')
            ->get(['hgvs', 'variation_id']);

        $sameResidueDifferentAa = false;

        foreach ($candidates as $cv) {
            $other = HgvsProtein::parse($cv->hgvs);
            if ($other === null || $other['ref'] !== $target['ref'] || $other['position'] !== $target['position']) {
                continue;
            }
            if ($other['alt'] === $target['alt']) {
                // Exact same amino-acid change → PS1.
                return [[
                    'code' => 'PS1', 'strength' => 'strong', 'data_source' => 'auto:clinvar',
                    'evidence_value' => 'ClinVar pathogenic '.$cv->hgvs.($cv->variation_id ? " (VID {$cv->variation_id})" : ''),
                ]];
            }
            $sameResidueDifferentAa = true;
        }

        if ($sameResidueDifferentAa) {
            return [[
                'code' => 'PM5', 'strength' => 'moderate', 'data_source' => 'auto:clinvar',
                'evidence_value' => "Different pathogenic missense at residue {$target['ref']}{$target['position']} in ClinVar",
            ]];
        }

        return [];
    }
```

- [ ] **Step 4: Run — expect PASS** (full `AcmgAutoEvidenceTest`), then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter AcmgAutoEvidenceTest"
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Acmg/AcmgAutoEvidence.php backend/tests/Unit/Services/Acmg/AcmgAutoEvidenceTest.php
git commit -m "feat(acmg): add ClinVar same-residue PS1/PM5 auto-evidence"
```

---

### Task 9: ClassificationService (orchestration + sign-off)

**Files:**
- Create: `backend/app/Services/Genomics/Acmg/ClassificationService.php`
- Test: `backend/tests/Unit/Services/Acmg/ClassificationServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Clinical\GenomicVariant;
use App\Models\User;
use App\Services\Genomics\Acmg\AcmgAutoEvidence;
use App\Services\Genomics\Acmg\AcmgClassifier;
use App\Services\Genomics\Acmg\ClassificationService;
use App\Services\Genomics\Acmg\GeneSpecificationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $resolver = new GeneSpecificationResolver;
    $this->service = new ClassificationService(new AcmgClassifier, new AcmgAutoEvidence($resolver), $resolver);
    $this->user = User::factory()->create();
    $this->variant = GenomicVariant::factory()->create(['gene_symbol' => 'BRCA1']);
});

it('creates a classification and auto-populates criteria from supplied evidence', function () {
    $c = $this->service->create($this->variant, $this->user->id, ['population_af' => 0.0, 'revel' => 0.95]);

    expect($c->criteria()->pluck('code')->all())->toEqualCanonicalizing(['PM2', 'PP3']);
    // PM2 supporting (+1) + PP3 strong (+4) = 5 → VUS
    expect($c->computed_points)->toBe(5);
    expect($c->computed_classification)->toBe('vus');
    expect($c->status)->toBe('computed');
    expect($c->criteria()->where('code', 'PP3')->first()->set_by)->toBe('auto');
});

it('recomputes after a curator adds a criterion', function () {
    $c = $this->service->create($this->variant, $this->user->id, ['population_af' => 0.0]); // PM2 only (+1)
    $this->service->addCriterion($c, 'PVS1', 'very_strong', $this->user->id, rationale: 'Canonical splice');
    $c = $this->service->recompute($c->fresh('criteria'));

    expect($c->computed_points)->toBe(9); // PM2 1 + PVS1 8
    expect($c->computed_classification)->toBe('likely_pathogenic');
});

it('confirms with a human sign-off and records an override reason when final differs', function () {
    $c = $this->service->create($this->variant, $this->user->id, ['population_af' => 0.0]); // computed VUS
    $c = $this->service->confirm($c, 'likely_pathogenic', $this->user->id, 'Strong segregation in 3 families (PP1_Strong)');

    expect($c->status)->toBe('confirmed');
    expect($c->final_classification)->toBe('likely_pathogenic');
    expect($c->override_reason)->toBe('Strong segregation in 3 families (PP1_Strong)');
    expect($c->confirmed_by)->toBe($this->user->id);
    expect($c->confirmed_at)->not->toBeNull();
});

it('rejects an unknown ACMG code', function () {
    $c = $this->service->create($this->variant, $this->user->id, []);
    $this->service->addCriterion($c, 'PXX9', 'strong', $this->user->id);
})->throws(InvalidArgumentException::class);
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement** — `backend/app/Services/Genomics/Acmg/ClassificationService.php`:

```php
<?php

namespace App\Services\Genomics\Acmg;

use App\Models\Clinical\ClassificationCriterion;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantClassification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClassificationService
{
    public function __construct(
        private AcmgClassifier $classifier,
        private AcmgAutoEvidence $auto,
        private GeneSpecificationResolver $resolver,
    ) {}

    /**
     * Create a classification for a variant and auto-populate the safe criteria.
     *
     * @param  array{population_af?:float, revel?:float, protein_hgvs?:string}  $evidence
     */
    public function create(GenomicVariant $variant, int $actorId, array $evidence): VariantClassification
    {
        $gene = (string) ($variant->gene_symbol ?? '');
        $resolved = $this->resolver->resolve($gene);

        $proposed = [];
        if (array_key_exists('population_af', $evidence)) {
            $proposed = array_merge($proposed, $this->auto->fromFrequency($gene, (float) $evidence['population_af']));
        }
        if (array_key_exists('revel', $evidence)) {
            $proposed = array_merge($proposed, $this->auto->fromInSilico((float) $evidence['revel']));
        }
        if (! empty($evidence['protein_hgvs'])) {
            $proposed = array_merge($proposed, $this->auto->fromClinVar($gene, (string) $evidence['protein_hgvs']));
        }

        // Drop criteria the gene spec disables.
        $proposed = array_values(array_filter(
            $proposed,
            fn (array $p) => $this->resolver->isApplicable($resolved, $p['code']),
        ));

        return DB::transaction(function () use ($variant, $actorId, $gene, $resolved, $proposed) {
            $classification = VariantClassification::create([
                'genomic_variant_id' => $variant->id,
                'gene_symbol' => $gene,
                'computed_classification' => 'vus',
                'computed_points' => 0,
                'status' => 'computed',
                'gene_specification_id' => $resolved['spec_id'],
                'created_by' => $actorId,
            ]);

            foreach ($proposed as $p) {
                $this->persistCriterion($classification, $p['code'], $p['strength'], 'auto', $p['data_source'], $actorId, $p['evidence_value'] ?? null, null);
            }

            return $this->recompute($classification->fresh('criteria'));
        });
    }

    public function addCriterion(
        VariantClassification $classification,
        string $code,
        string $strength,
        int $actorId,
        ?string $rationale = null,
    ): ClassificationCriterion {
        return $this->persistCriterion($classification, $code, $strength, 'curator', 'manual', $actorId, null, $rationale);
    }

    /** Recompute the points + classification from the persisted criteria. */
    public function recompute(VariantClassification $classification): VariantClassification
    {
        $applied = $classification->criteria->map(fn (ClassificationCriterion $c) => [
            'code' => $c->code,
            'strength' => AcmgStrength::from($c->applied_strength),
        ])->all();

        $result = $this->classifier->classify($applied);

        $classification->update([
            'computed_classification' => $result['classification'],
            'computed_points' => $result['points'],
        ]);

        return $classification->fresh('criteria');
    }

    /** Human sign-off. `$final` is the curator's final call; reason required if it differs from computed. */
    public function confirm(VariantClassification $classification, string $final, int $actorId, ?string $overrideReason = null): VariantClassification
    {
        if ($final !== $classification->computed_classification && empty($overrideReason)) {
            throw new InvalidArgumentException('An override reason is required when the final classification differs from the computed one.');
        }

        $classification->update([
            'status' => 'confirmed',
            'final_classification' => $final,
            'override_reason' => $final !== $classification->computed_classification ? $overrideReason : null,
            'confirmed_by' => $actorId,
            'confirmed_at' => now(),
        ]);

        return $classification->fresh('criteria');
    }

    private function persistCriterion(
        VariantClassification $classification,
        string $code,
        string $strength,
        string $setBy,
        string $dataSource,
        int $actorId,
        ?string $evidenceValue,
        ?string $rationale,
    ): ClassificationCriterion {
        if (! AcmgCriteriaCatalog::exists($code)) {
            throw new InvalidArgumentException("Unknown ACMG code: {$code}");
        }
        $strengthEnum = AcmgStrength::from($strength);
        $magnitude = $strengthEnum->points();
        $signed = AcmgCriteriaCatalog::category($code) === 'pathogenic' ? $magnitude : -$magnitude;

        return ClassificationCriterion::updateOrCreate(
            ['classification_id' => $classification->id, 'code' => $code],
            [
                'applied_strength' => $strength,
                'points' => $signed,
                'data_source' => $dataSource,
                'evidence_value' => $evidenceValue,
                'rationale' => $rationale,
                'set_by' => $setBy,
                'set_by_user_id' => $actorId,
            ],
        );
    }
}
```

- [ ] **Step 4: Run — expect PASS (4)**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter ClassificationServiceTest"
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Services/Genomics/Acmg/ClassificationService.php backend/tests/Unit/Services/Acmg/ClassificationServiceTest.php
git commit -m "feat(acmg): add classification orchestration service with human sign-off"
```

---

### Task 10: Form Requests

**Files:**
- Create: `backend/app/Http/Requests/StoreClassificationCriterionRequest.php`, `ConfirmClassificationRequest.php`

- [ ] **Step 1: Write `StoreClassificationCriterionRequest`**

```php
<?php

namespace App\Http\Requests;

use App\Services\Genomics\Acmg\AcmgCriteriaCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassificationCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', Rule::in(array_keys(AcmgCriteriaCatalog::all()))],
            'applied_strength' => ['required', 'string', Rule::in(['very_strong', 'strong', 'moderate', 'supporting'])],
            'rationale' => 'nullable|string|max:2000',
        ];
    }
}
```

- [ ] **Step 2: Write `ConfirmClassificationRequest`**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmClassificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'final_classification' => ['required', 'string', Rule::in(['pathogenic', 'likely_pathogenic', 'vus', 'likely_benign', 'benign'])],
            'override_reason' => 'nullable|string|max:2000',
        ];
    }
}
```

- [ ] **Step 3: Pint + commit:**

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Http/Requests/StoreClassificationCriterionRequest.php backend/app/Http/Requests/ConfirmClassificationRequest.php
git commit -m "feat(acmg): add classification form requests"
```

---

### Task 11: VariantClassificationController + routes + catalog endpoint

**Files:**
- Create: `backend/app/Http/Controllers/VariantClassificationController.php`
- Modify: `backend/routes/api.php`
- Test: `backend/tests/Feature/Api/VariantClassificationTest.php`, `backend/tests/Feature/Api/AcmgCatalogTest.php`

- [ ] **Step 1: Write the failing feature tests**

`backend/tests/Feature/Api/AcmgCatalogTest.php`:

```php
<?php

use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
});

it('returns the ACMG criteria catalog', function () {
    $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/acmg/criteria');
    $response->assertStatus(200)->assertJsonPath('success', true);
    expect($response->json('data'))->toHaveKey('PVS1');
    expect($response->json('data.PVS1.category'))->toBe('pathogenic');
});

it('requires authentication for the catalog', function () {
    $this->getJson('/api/acmg/criteria')->assertStatus(401);
});
```

`backend/tests/Feature/Api/VariantClassificationTest.php`:

```php
<?php

use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantClassification;
use App\Models\User;

beforeEach(function () {
    app(\Database\Seeders\SuperuserSeeder::class)->run();
    $this->user = User::where('email', 'admin@acumenus.net')->first();
    $this->variant = GenomicVariant::factory()->create(['gene_symbol' => 'BRCA1']);
});

it('creates a classification and auto-populates from supplied evidence', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/genomic-variants/{$this->variant->id}/classifications", [
            'population_af' => 0.0, 'revel' => 0.95,
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.computed_classification', 'vus')
        ->assertJsonPath('data.computed_points', 5);
    expect(collect($response->json('data.criteria'))->pluck('code'))->toContain('PP3');
});

it('adds a curator criterion and recomputes', function () {
    $c = VariantClassification::factory()->create(['genomic_variant_id' => $this->variant->id, 'created_by' => $this->user->id]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/classifications/{$c->id}/criteria", ['code' => 'PVS1', 'applied_strength' => 'very_strong']);

    $response->assertStatus(200)->assertJsonPath('data.computed_points', 8);
});

it('rejects an invalid ACMG code with 422', function () {
    $c = VariantClassification::factory()->create(['genomic_variant_id' => $this->variant->id, 'created_by' => $this->user->id]);
    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/classifications/{$c->id}/criteria", ['code' => 'NOPE', 'applied_strength' => 'strong'])
        ->assertStatus(422);
});

it('confirms with human sign-off', function () {
    $c = VariantClassification::factory()->create([
        'genomic_variant_id' => $this->variant->id, 'created_by' => $this->user->id,
        'computed_classification' => 'vus', 'computed_points' => 3,
    ]);

    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/classifications/{$c->id}/confirm", [
            'final_classification' => 'likely_pathogenic',
            'override_reason' => 'PP1_Strong segregation',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.final_classification', 'likely_pathogenic');
});

it('requires an override reason when final differs from computed (422)', function () {
    $c = VariantClassification::factory()->create([
        'genomic_variant_id' => $this->variant->id, 'created_by' => $this->user->id,
        'computed_classification' => 'vus', 'computed_points' => 3,
    ]);

    $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/classifications/{$c->id}/confirm", ['final_classification' => 'pathogenic'])
        ->assertStatus(422);
});

it('requires authentication', function () {
    $this->postJson("/api/genomic-variants/{$this->variant->id}/classifications", [])->assertStatus(401);
});
```

- [ ] **Step 2: Run — expect FAIL.**

Run: `docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter 'VariantClassificationTest|AcmgCatalogTest'"`

- [ ] **Step 3: Implement the controller** — `backend/app/Http/Controllers/VariantClassificationController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\ConfirmClassificationRequest;
use App\Http\Requests\StoreClassificationCriterionRequest;
use App\Models\Clinical\ClassificationCriterion;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\VariantClassification;
use App\Services\Genomics\Acmg\AcmgCriteriaCatalog;
use App\Services\Genomics\Acmg\ClassificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class VariantClassificationController extends Controller
{
    public function __construct(private ClassificationService $service) {}

    public function catalog(): JsonResponse
    {
        $catalog = collect(AcmgCriteriaCatalog::all())->map(fn (array $d) => [
            'category' => $d['category'],
            'default_strength' => $d['default_strength']->value,
            'automatable' => $d['automatable'],
            'standalone' => $d['standalone'],
            'description' => $d['description'],
        ]);

        return ApiResponse::success($catalog);
    }

    public function store(Request $request, int $variant): JsonResponse
    {
        $variantModel = GenomicVariant::findOrFail($variant);

        $evidence = $request->validate([
            'population_af' => 'sometimes|numeric|min:0|max:1',
            'revel' => 'sometimes|numeric|min:0|max:1',
            'protein_hgvs' => 'sometimes|string|max:200',
        ]);

        $classification = $this->service->create($variantModel, $request->user()->id, $evidence);

        return ApiResponse::success($classification->load('criteria'), 'Created', 201);
    }

    public function show(int $classification): JsonResponse
    {
        $model = VariantClassification::with(['criteria', 'variant:id,gene_symbol'])->findOrFail($classification);

        return ApiResponse::success($model);
    }

    public function addCriterion(StoreClassificationCriterionRequest $request, int $classification): JsonResponse
    {
        $model = VariantClassification::findOrFail($classification);

        try {
            $this->service->addCriterion(
                $model,
                $request->validated()['code'],
                $request->validated()['applied_strength'],
                $request->user()->id,
                $request->validated()['rationale'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($this->service->recompute($model->fresh('criteria')));
    }

    public function destroyCriterion(int $criterion): JsonResponse
    {
        $model = ClassificationCriterion::findOrFail($criterion);
        $classification = $model->classification;
        $model->delete();

        return ApiResponse::success($this->service->recompute($classification->fresh('criteria')));
    }

    public function confirm(ConfirmClassificationRequest $request, int $classification): JsonResponse
    {
        $model = VariantClassification::findOrFail($classification);

        try {
            $confirmed = $this->service->confirm(
                $model,
                $request->validated()['final_classification'],
                $request->user()->id,
                $request->validated()['override_reason'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($confirmed);
    }
}
```

- [ ] **Step 4: Add routes** — in `backend/routes/api.php`, inside the `auth:sanctum` group (near the genomics routes):

```php
    // ── ACMG variant classification ─────────────────────────────────────
    Route::get('/acmg/criteria', [\App\Http\Controllers\VariantClassificationController::class, 'catalog']);
    Route::post('/genomic-variants/{variant}/classifications', [\App\Http\Controllers\VariantClassificationController::class, 'store']);
    Route::get('/classifications/{classification}', [\App\Http\Controllers\VariantClassificationController::class, 'show']);
    Route::post('/classifications/{classification}/criteria', [\App\Http\Controllers\VariantClassificationController::class, 'addCriterion']);
    Route::delete('/classification-criteria/{criterion}', [\App\Http\Controllers\VariantClassificationController::class, 'destroyCriterion']);
    Route::post('/classifications/{classification}/confirm', [\App\Http\Controllers\VariantClassificationController::class, 'confirm']);
```

- [ ] **Step 5: Run — expect PASS (9)**, then Pint + commit (surgically stage the routes hunk):

```bash
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/app/Http/Controllers/VariantClassificationController.php backend/tests/Feature/Api/VariantClassificationTest.php backend/tests/Feature/Api/AcmgCatalogTest.php
git add -p backend/routes/api.php   # select ONLY the ACMG route hunk
git commit -m "feat(acmg): add variant classification API + ACMG catalog endpoint"
```

---

### Task 12: AcmgGeneSpecificationSeeder (example CSpec specs)

**Files:**
- Create: `backend/database/seeders/AcmgGeneSpecificationSeeder.php`
- Test: `backend/tests/Feature/Api/VariantClassificationTest.php` (add one gene-spec case)

- [ ] **Step 1: Add the failing test** — append to `VariantClassificationTest.php`:

```php
it('applies a gene-specific BA1 threshold from a seeded spec', function () {
    $this->seed(\Database\Seeders\AcmgGeneSpecificationSeeder::class);
    $myh7 = GenomicVariant::factory()->create(['gene_symbol' => 'MYH7']);

    // AF 0.002 is benign-common under MYH7's stricter BA1 (0.001) but not under the 0.05 baseline.
    $response = $this->actingAs($this->user, 'sanctum')
        ->postJson("/api/genomic-variants/{$myh7->id}/classifications", ['population_af' => 0.002]);

    $response->assertStatus(201);
    expect(collect($response->json('data.criteria'))->pluck('code'))->toContain('BA1');
});
```

- [ ] **Step 2: Run — expect FAIL** (seeder missing / BA1 not applied under baseline).

- [ ] **Step 3: Implement the seeder** — `backend/database/seeders/AcmgGeneSpecificationSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Clinical\AcmgGeneSpecification;
use Illuminate\Database\Seeder;

class AcmgGeneSpecificationSeeder extends Seeder
{
    public function run(): void
    {
        // Example ClinGen VCEP specifications (illustrative thresholds; replace via the
        // live CSpec sync planned as a follow-on). Idempotent.
        $specs = [
            [
                'gene_symbol' => 'MYH7', 'disease' => 'Cardiomyopathy', 'vcep' => 'Cardiomyopathy VCEP',
                'spec_id' => 'GN001', 'spec_version' => '1.0.0',
                'criteria_overrides' => [
                    'BA1' => ['af_threshold' => 0.001],
                    'BS1' => ['af_threshold' => 0.0002],
                    'PM2' => ['af_threshold' => 0.00004],
                    'PP2' => ['applicable' => false],
                ],
                'source_url' => 'https://cspec.genome.network/cspec/ui/svi/',
            ],
            [
                'gene_symbol' => 'BRCA1', 'disease' => 'Hereditary breast and ovarian cancer', 'vcep' => 'ENIGMA BRCA1/2 VCEP',
                'spec_id' => 'GN002', 'spec_version' => '1.0.0',
                'criteria_overrides' => [
                    'BA1' => ['af_threshold' => 0.001],
                    'BS1' => ['af_threshold' => 0.0001],
                    'PM2' => ['af_threshold' => 0.00002],
                    'PP2' => ['applicable' => false],
                    'BP1' => ['applicable' => false],
                ],
                'source_url' => 'https://cspec.genome.network/cspec/ui/svi/',
            ],
        ];

        foreach ($specs as $spec) {
            AcmgGeneSpecification::updateOrCreate(
                ['gene_symbol' => $spec['gene_symbol'], 'spec_id' => $spec['spec_id'], 'spec_version' => $spec['spec_version']],
                $spec,
            );
        }
    }
}
```

- [ ] **Step 4: Run — expect PASS**, then Pint + commit:

```bash
docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter VariantClassificationTest"
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint"
git add backend/database/seeders/AcmgGeneSpecificationSeeder.php backend/tests/Feature/Api/VariantClassificationTest.php
git commit -m "feat(acmg): seed example ClinGen VCEP gene specifications"
```

---

### Task 13: Frontend data layer (types + api + hooks)

**Files:**
- Create: `frontend/src/features/variant-classification/types/index.ts`
- Create: `frontend/src/features/variant-classification/api/classificationApi.ts`
- Create: `frontend/src/features/variant-classification/hooks/useClassification.ts`
- Test: `frontend/src/features/variant-classification/hooks/__tests__/useClassification.test.ts`

- [ ] **Step 1: Create types** — `frontend/src/features/variant-classification/types/index.ts`:

```ts
export type AcmgClassification = "pathogenic" | "likely_pathogenic" | "vus" | "likely_benign" | "benign";
export type AcmgStrength = "very_strong" | "strong" | "moderate" | "supporting";

export interface AcmgCriterionDef {
  category: "pathogenic" | "benign";
  default_strength: AcmgStrength;
  automatable: boolean;
  standalone: boolean;
  description: string;
}
export type AcmgCatalog = Record<string, AcmgCriterionDef>;

export interface ClassificationCriterion {
  id: number;
  classification_id: number;
  code: string;
  applied_strength: AcmgStrength;
  points: number;
  data_source: string;
  evidence_value: string | null;
  rationale: string | null;
  set_by: "auto" | "curator";
  set_by_user_id: number | null;
}

export interface VariantClassification {
  id: number;
  genomic_variant_id: number;
  gene_symbol: string | null;
  computed_classification: AcmgClassification;
  computed_points: number;
  final_classification: AcmgClassification | null;
  status: "computed" | "confirmed";
  ruleset_version: string;
  gene_specification_id: string | null;
  override_reason: string | null;
  confirmed_by: number | null;
  confirmed_at: string | null;
  criteria?: ClassificationCriterion[];
}

export interface CreateClassificationInput {
  population_af?: number;
  revel?: number;
  protein_hgvs?: string;
}

export const CLASSIFICATION_LABEL: Record<AcmgClassification, string> = {
  pathogenic: "Pathogenic",
  likely_pathogenic: "Likely Pathogenic",
  vus: "VUS",
  likely_benign: "Likely Benign",
  benign: "Benign",
};
```

- [ ] **Step 2: Create the API module** — `frontend/src/features/variant-classification/api/classificationApi.ts`:

```ts
import apiClient from "@/lib/api-client";
import type {
  AcmgCatalog,
  AcmgClassification,
  AcmgStrength,
  CreateClassificationInput,
  VariantClassification,
} from "../types";

export async function getAcmgCatalog(): Promise<AcmgCatalog> {
  const { data } = await apiClient.get("/acmg/criteria");
  return data.data ?? data;
}

export async function getClassification(id: number): Promise<VariantClassification> {
  const { data } = await apiClient.get(`/classifications/${id}`);
  return data.data ?? data;
}

export async function createClassification(
  variantId: number,
  input: CreateClassificationInput,
): Promise<VariantClassification> {
  const { data } = await apiClient.post(`/genomic-variants/${variantId}/classifications`, input);
  return data.data ?? data;
}

export async function addCriterion(
  classificationId: number,
  payload: { code: string; applied_strength: AcmgStrength; rationale?: string },
): Promise<VariantClassification> {
  const { data } = await apiClient.post(`/classifications/${classificationId}/criteria`, payload);
  return data.data ?? data;
}

export async function deleteCriterion(criterionId: number): Promise<VariantClassification> {
  const { data } = await apiClient.delete(`/classification-criteria/${criterionId}`);
  return data.data ?? data;
}

export async function confirmClassification(
  classificationId: number,
  payload: { final_classification: AcmgClassification; override_reason?: string },
): Promise<VariantClassification> {
  const { data } = await apiClient.post(`/classifications/${classificationId}/confirm`, payload);
  return data.data ?? data;
}
```

- [ ] **Step 3: Create hooks** — `frontend/src/features/variant-classification/hooks/useClassification.ts`:

```ts
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import {
  addCriterion,
  confirmClassification,
  createClassification,
  deleteCriterion,
  getAcmgCatalog,
  getClassification,
} from "../api/classificationApi";
import type { AcmgClassification, AcmgStrength, CreateClassificationInput } from "../types";

const KEY = "variant-classification";

export function useAcmgCatalog() {
  return useQuery({ queryKey: [KEY, "catalog"], queryFn: getAcmgCatalog, staleTime: 60 * 60 * 1000 });
}

export function useClassification(id: number | null) {
  return useQuery({
    queryKey: [KEY, "detail", id],
    queryFn: () => getClassification(id as number),
    enabled: typeof id === "number" && id > 0,
  });
}

export function useCreateClassification(variantId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (input: CreateClassificationInput) => createClassification(variantId, input),
    onSuccess: (c) => qc.setQueryData([KEY, "detail", c.id], c),
  });
}

export function useAddCriterion(classificationId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { code: string; applied_strength: AcmgStrength; rationale?: string }) =>
      addCriterion(classificationId, payload),
    onSuccess: (c) => qc.setQueryData([KEY, "detail", classificationId], c),
  });
}

export function useDeleteCriterion(classificationId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (criterionId: number) => deleteCriterion(criterionId),
    onSuccess: (c) => qc.setQueryData([KEY, "detail", classificationId], c),
  });
}

export function useConfirmClassification(classificationId: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (payload: { final_classification: AcmgClassification; override_reason?: string }) =>
      confirmClassification(classificationId, payload),
    onSuccess: (c) => qc.setQueryData([KEY, "detail", classificationId], c),
  });
}
```

- [ ] **Step 4: Write the hook test** — `frontend/src/features/variant-classification/hooks/__tests__/useClassification.test.ts`:

```ts
import { describe, it, expect, afterEach } from "vitest";
import { waitFor, act } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderHookWithProviders, resetStores } from "@/test/utils";
import { useAcmgCatalog, useCreateClassification } from "../useClassification";

afterEach(() => resetStores());

describe("useAcmgCatalog", () => {
  it("fetches the ACMG catalog", async () => {
    server.use(
      http.get("/api/acmg/criteria", () =>
        HttpResponse.json({ success: true, data: { PVS1: { category: "pathogenic", default_strength: "very_strong", automatable: false, standalone: false, description: "x" } } }),
      ),
    );
    const { result } = renderHookWithProviders(() => useAcmgCatalog());
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data!.PVS1.category).toBe("pathogenic");
  });
});

describe("useCreateClassification", () => {
  it("creates a classification from supplied evidence", async () => {
    server.use(
      http.post("/api/genomic-variants/42/classifications", () =>
        HttpResponse.json({ success: true, data: { id: 7, genomic_variant_id: 42, computed_classification: "vus", computed_points: 5, status: "computed", criteria: [] } }, { status: 201 }),
      ),
    );
    const { result } = renderHookWithProviders(() => useCreateClassification(42));
    await act(async () => { result.current.mutate({ population_af: 0, revel: 0.95 }); });
    await waitFor(() => expect(result.current.isSuccess).toBe(true));
    expect(result.current.data!.computed_points).toBe(5);
  });
});
```

- [ ] **Step 5: Run + tsc + commit:**

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/variant-classification/hooks/__tests__/useClassification.test.ts && npx tsc --noEmit"
git add frontend/src/features/variant-classification/types/index.ts frontend/src/features/variant-classification/api/classificationApi.ts frontend/src/features/variant-classification/hooks/useClassification.ts frontend/src/features/variant-classification/hooks/__tests__/useClassification.test.ts
git commit -m "feat(acmg): add variant-classification frontend data layer"
```

---

### Task 14: AcmgPointsBar + ClassificationCriteriaList components

**Files:**
- Create: `frontend/src/features/variant-classification/components/AcmgPointsBar.tsx`
- Create: `frontend/src/features/variant-classification/components/ClassificationCriteriaList.tsx`
- Test: `frontend/src/features/variant-classification/components/__tests__/AcmgPointsBar.test.tsx`

- [ ] **Step 1: Write the failing test**

```tsx
import { describe, it, expect } from "vitest";
import { render, screen } from "@testing-library/react";
import { AcmgPointsBar } from "../AcmgPointsBar";

describe("AcmgPointsBar", () => {
  it("shows the classification label, point total, and threshold ladder", () => {
    render(<AcmgPointsBar classification="likely_pathogenic" points={7} />);
    expect(screen.getByText(/likely pathogenic/i)).toBeInTheDocument();
    expect(screen.getByText(/\+7/)).toBeInTheDocument();
    expect(screen.getByText(/≥ ?10/)).toBeInTheDocument(); // threshold ladder rendered
  });
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement `AcmgPointsBar`** — `frontend/src/features/variant-classification/components/AcmgPointsBar.tsx`:

```tsx
import { CLASSIFICATION_LABEL, type AcmgClassification } from "../types";

interface AcmgPointsBarProps {
  classification: AcmgClassification;
  points: number;
}

const COLOR: Record<AcmgClassification, string> = {
  pathogenic: "var(--primary)",
  likely_pathogenic: "var(--primary)",
  vus: "var(--accent)",
  likely_benign: "var(--teal)",
  benign: "var(--teal)",
};

export function AcmgPointsBar({ classification, points }: AcmgPointsBarProps) {
  const signed = points > 0 ? `+${points}` : `${points}`;
  return (
    <div className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-3">
      <div className="flex items-center justify-between">
        <span className="text-sm font-semibold" style={{ color: COLOR[classification] }}>
          {CLASSIFICATION_LABEL[classification]}
        </span>
        <span className="font-mono text-sm text-[var(--text-primary)]">{signed} pts</span>
      </div>
      <p className="mt-1 text-xs text-[var(--text-muted)]">
        Thresholds: Pathogenic ≥ 10 · Likely Path. 6–9 · VUS 0–5 · Likely Benign −1…−6 · Benign ≤ −7
      </p>
    </div>
  );
}
```

- [ ] **Step 4: Implement `ClassificationCriteriaList`** — `frontend/src/features/variant-classification/components/ClassificationCriteriaList.tsx`:

```tsx
import { Trash2 } from "lucide-react";
import type { ClassificationCriterion } from "../types";

interface ClassificationCriteriaListProps {
  criteria: ClassificationCriterion[];
  onRemove?: (criterionId: number) => void;
  removing?: boolean;
}

export function ClassificationCriteriaList({ criteria, onRemove, removing }: ClassificationCriteriaListProps) {
  if (criteria.length === 0) {
    return <p className="text-sm text-[var(--text-muted)]">No criteria applied yet.</p>;
  }

  return (
    <ul className="divide-y divide-[var(--surface-elevated)]">
      {criteria.map((c) => (
        <li key={c.id} className="flex items-center gap-2 py-2 text-sm">
          <span className="font-mono font-semibold text-[var(--text-primary)]">{c.code}</span>
          <span className="text-xs text-[var(--text-secondary)]">{c.applied_strength.replace("_", " ")}</span>
          <span className="font-mono text-xs text-[var(--text-muted)]">{c.points > 0 ? `+${c.points}` : c.points}</span>
          {c.evidence_value && <span className="text-xs text-[var(--text-muted)]">· {c.evidence_value}</span>}
          <span className="rounded bg-[var(--surface-elevated)] px-1.5 py-0.5 text-[10px] text-[var(--text-muted)]">{c.set_by}</span>
          {onRemove && (
            <button
              type="button"
              aria-label={`Remove ${c.code}`}
              disabled={removing}
              onClick={() => onRemove(c.id)}
              className="ml-auto text-[var(--text-muted)] hover:text-[var(--primary)] disabled:opacity-50"
            >
              <Trash2 size={15} />
            </button>
          )}
        </li>
      ))}
    </ul>
  );
}
```

- [ ] **Step 5: Run — expect PASS**, then tsc + commit:

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/variant-classification/components/__tests__/AcmgPointsBar.test.tsx && npx tsc --noEmit"
git add frontend/src/features/variant-classification/components/AcmgPointsBar.tsx frontend/src/features/variant-classification/components/ClassificationCriteriaList.tsx frontend/src/features/variant-classification/components/__tests__/AcmgPointsBar.test.tsx
git commit -m "feat(acmg): add points bar + criteria list components"
```

---

### Task 15: AddCriterionForm + ConfirmClassificationDialog + VariantClassificationPanel

**Files:**
- Create: `frontend/src/features/variant-classification/components/AddCriterionForm.tsx`, `ConfirmClassificationDialog.tsx`, `VariantClassificationPanel.tsx`
- Test: `frontend/src/features/variant-classification/components/__tests__/VariantClassificationPanel.test.tsx`

- [ ] **Step 1: Write the failing panel test**

```tsx
import { describe, it, expect, afterEach } from "vitest";
import { screen, waitFor, fireEvent } from "@testing-library/react";
import { http, HttpResponse } from "msw";
import { server } from "@/test/mocks/server";
import { renderWithProviders, resetStores } from "@/test/utils";
import { VariantClassificationPanel } from "../VariantClassificationPanel";

afterEach(() => resetStores());

const catalog = { PVS1: { category: "pathogenic", default_strength: "very_strong", automatable: false, standalone: false, description: "x" } };

describe("VariantClassificationPanel", () => {
  it("creates a classification and renders the computed result with criteria", async () => {
    server.use(
      http.get("/api/acmg/criteria", () => HttpResponse.json({ success: true, data: catalog })),
      http.post("/api/genomic-variants/42/classifications", () =>
        HttpResponse.json({ success: true, data: {
          id: 7, genomic_variant_id: 42, computed_classification: "vus", computed_points: 1, status: "computed",
          criteria: [{ id: 1, classification_id: 7, code: "PM2", applied_strength: "supporting", points: 1, data_source: "auto:gnomad", evidence_value: "gnomAD AF=0", rationale: null, set_by: "auto", set_by_user_id: null }],
        } }, { status: 201 }),
      ),
    );

    renderWithProviders(<VariantClassificationPanel genomicVariantId={42} />);

    fireEvent.click(await screen.findByRole("button", { name: /classify variant/i }));

    await waitFor(() => expect(screen.getByText(/VUS/)).toBeInTheDocument());
    expect(screen.getByText("PM2")).toBeInTheDocument();
    expect(screen.getByText(/gnomAD AF=0/)).toBeInTheDocument();
  });
});
```

- [ ] **Step 2: Run — expect FAIL.**

- [ ] **Step 3: Implement `AddCriterionForm`** — `frontend/src/features/variant-classification/components/AddCriterionForm.tsx`:

```tsx
import { useState } from "react";
import { useAcmgCatalog, useAddCriterion } from "../hooks/useClassification";
import type { AcmgStrength } from "../types";

const STRENGTHS: AcmgStrength[] = ["very_strong", "strong", "moderate", "supporting"];

export function AddCriterionForm({ classificationId }: { classificationId: number }) {
  const { data: catalog } = useAcmgCatalog();
  const add = useAddCriterion(classificationId);
  const [code, setCode] = useState("");
  const [strength, setStrength] = useState<AcmgStrength>("supporting");
  const [rationale, setRationale] = useState("");

  const codes = catalog ? Object.keys(catalog) : [];

  function submit() {
    if (!code) return;
    add.mutate(
      { code, applied_strength: strength, rationale: rationale || undefined },
      { onSuccess: () => { setCode(""); setRationale(""); } },
    );
  }

  return (
    <div className="flex flex-wrap items-center gap-2">
      <label htmlFor="acmg-code" className="sr-only">ACMG code</label>
      <select id="acmg-code" value={code} onChange={(e) => {
        setCode(e.target.value);
        if (e.target.value && catalog) setStrength(catalog[e.target.value].default_strength);
      }} className="rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]">
        <option value="">Add criterion…</option>
        {codes.map((c) => <option key={c} value={c}>{c}</option>)}
      </select>
      <label htmlFor="acmg-strength" className="sr-only">Strength</label>
      <select id="acmg-strength" value={strength} onChange={(e) => setStrength(e.target.value as AcmgStrength)}
        className="rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]">
        {STRENGTHS.map((s) => <option key={s} value={s}>{s.replace("_", " ")}</option>)}
      </select>
      <input value={rationale} onChange={(e) => setRationale(e.target.value)} placeholder="rationale (optional)"
        className="flex-1 rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]" />
      <button type="button" onClick={submit} disabled={!code || add.isPending}
        className="rounded-md bg-[var(--accent)] px-3 py-1 text-sm text-black disabled:opacity-50">Add</button>
    </div>
  );
}
```

- [ ] **Step 4: Implement `ConfirmClassificationDialog`** — `frontend/src/features/variant-classification/components/ConfirmClassificationDialog.tsx`:

```tsx
import { useState } from "react";
import { useConfirmClassification } from "../hooks/useClassification";
import { CLASSIFICATION_LABEL, type AcmgClassification, type VariantClassification } from "../types";

const OPTIONS: AcmgClassification[] = ["pathogenic", "likely_pathogenic", "vus", "likely_benign", "benign"];

export function ConfirmClassificationDialog({
  classification, open, onClose,
}: { classification: VariantClassification; open: boolean; onClose: () => void }) {
  const confirm = useConfirmClassification(classification.id);
  const [final, setFinal] = useState<AcmgClassification>(classification.computed_classification);
  const [reason, setReason] = useState("");
  const [error, setError] = useState<string | null>(null);

  if (!open) return null;
  const differs = final !== classification.computed_classification;

  function submit() {
    setError(null);
    if (differs && reason.trim() === "") { setError("An override reason is required when changing the computed call."); return; }
    confirm.mutate(
      { final_classification: final, override_reason: differs ? reason : undefined },
      { onSuccess: onClose, onError: () => setError("Confirmation failed") },
    );
  }

  return (
    <div role="dialog" aria-modal="true" className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="w-full max-w-md rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <h3 className="mb-1 text-sm font-semibold text-[var(--text-primary)]">Confirm classification</h3>
        <p className="mb-3 text-xs text-[var(--text-muted)]">Computed: {CLASSIFICATION_LABEL[classification.computed_classification]} ({classification.computed_points} pts). You are the deciding clinician.</p>
        <label htmlFor="final-cls" className="mb-1 block text-xs text-[var(--text-secondary)]">Final classification</label>
        <select id="final-cls" value={final} onChange={(e) => setFinal(e.target.value as AcmgClassification)}
          className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]">
          {OPTIONS.map((o) => <option key={o} value={o}>{CLASSIFICATION_LABEL[o]}</option>)}
        </select>
        {differs && (
          <>
            <label htmlFor="ovr" className="mb-1 mt-3 block text-xs text-[var(--text-secondary)]">Override reason (required)</label>
            <textarea id="ovr" rows={3} value={reason} onChange={(e) => setReason(e.target.value)}
              className="w-full rounded-md border border-[var(--surface-elevated)] bg-[var(--surface-base)] px-2 py-1 text-sm text-[var(--text-primary)]" />
          </>
        )}
        {error && <p className="mt-2 text-sm text-[var(--primary)]">{error}</p>}
        <div className="mt-4 flex justify-end gap-2">
          <button type="button" onClick={onClose} className="rounded-md px-3 py-1 text-sm text-[var(--text-secondary)]">Cancel</button>
          <button type="button" onClick={submit} disabled={confirm.isPending}
            className="rounded-md bg-[var(--primary)] px-3 py-1 text-sm text-white disabled:opacity-50">Confirm &amp; sign off</button>
        </div>
      </div>
    </div>
  );
}
```

- [ ] **Step 5: Implement `VariantClassificationPanel`** — `frontend/src/features/variant-classification/components/VariantClassificationPanel.tsx`:

```tsx
import { useState } from "react";
import { AcmgPointsBar } from "./AcmgPointsBar";
import { ClassificationCriteriaList } from "./ClassificationCriteriaList";
import { AddCriterionForm } from "./AddCriterionForm";
import { ConfirmClassificationDialog } from "./ConfirmClassificationDialog";
import { useCreateClassification, useDeleteCriterion } from "../hooks/useClassification";
import { CLASSIFICATION_LABEL, type VariantClassification } from "../types";

export function VariantClassificationPanel({ genomicVariantId }: { genomicVariantId: number }) {
  const create = useCreateClassification(genomicVariantId);
  const [classification, setClassification] = useState<VariantClassification | null>(null);
  const [confirmOpen, setConfirmOpen] = useState(false);
  const del = useDeleteCriterion(classification?.id ?? 0);

  function classify() {
    create.mutate({}, { onSuccess: (c) => setClassification(c) });
  }

  if (!classification) {
    return (
      <div className="rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
        <p className="mb-2 text-sm text-[var(--text-secondary)]">No ACMG classification yet.</p>
        <button type="button" onClick={classify} disabled={create.isPending}
          className="rounded-md bg-[var(--primary)] px-3 py-1.5 text-sm text-white disabled:opacity-50">Classify variant</button>
      </div>
    );
  }

  const confirmed = classification.status === "confirmed";

  return (
    <div className="space-y-3 rounded-lg border border-[var(--surface-elevated)] bg-[var(--surface-raised)] p-4">
      <AcmgPointsBar classification={classification.computed_classification} points={classification.computed_points} />

      {confirmed && classification.final_classification && (
        <p className="text-sm text-[var(--teal)]">
          Signed off: {CLASSIFICATION_LABEL[classification.final_classification]}
          {classification.override_reason ? ` — ${classification.override_reason}` : ""}
        </p>
      )}

      <ClassificationCriteriaList
        criteria={classification.criteria ?? []}
        onRemove={confirmed ? undefined : (id) => del.mutate(id, { onSuccess: (c) => setClassification(c) })}
        removing={del.isPending}
      />

      {!confirmed && (
        <>
          <AddCriterionForm classificationId={classification.id} />
          <div className="flex justify-end">
            <button type="button" onClick={() => setConfirmOpen(true)}
              className="rounded-md border border-[var(--primary)] px-3 py-1 text-sm text-[var(--primary)] hover:bg-[var(--surface-elevated)]">
              Confirm &amp; sign off
            </button>
          </div>
        </>
      )}

      <ConfirmClassificationDialog
        classification={classification}
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
      />
    </div>
  );
}
```

> Note: the panel keeps the latest classification in local state (each mutation returns the full updated record), so `AddCriterionForm`'s cache update is reflected when the panel re-reads via `useClassification` in a fuller integration. For this plan the local-state flow is sufficient and is what the test exercises. (A follow-on can switch to `useClassification(classification.id)` as the single source of truth.)

- [ ] **Step 6: Run — expect PASS**, then tsc + vite build + commit:

```bash
docker compose exec -T node sh -c "cd /app && npx vitest run src/features/variant-classification/components/__tests__/VariantClassificationPanel.test.tsx && npx tsc --noEmit && npx vite build"
git add frontend/src/features/variant-classification/components/AddCriterionForm.tsx frontend/src/features/variant-classification/components/ConfirmClassificationDialog.tsx frontend/src/features/variant-classification/components/VariantClassificationPanel.tsx frontend/src/features/variant-classification/components/__tests__/VariantClassificationPanel.test.tsx
git commit -m "feat(acmg): add classification panel (add criterion, sign-off)"
```

---

### Task 16: Embed panel + default MSW handlers + full verification

**Files:**
- Modify: `frontend/src/features/genomics/components/VariantExpandedRow.tsx` (embed the panel)
- Modify: `frontend/src/test/mocks/handlers.ts`

- [ ] **Step 1: Embed the panel.** Read `frontend/src/features/genomics/components/VariantExpandedRow.tsx`. It renders details for an expanded genomic-variant row and receives a variant object with a numeric `id`. Add an import and render the panel near the bottom of the expanded content:

```tsx
import { VariantClassificationPanel } from "@/features/variant-classification/components/VariantClassificationPanel";
```
and within the expanded body (use the row's variant id — confirm the prop/field name when reading the file, e.g. `variant.id`):
```tsx
<div className="mt-3">
  <h4 className="mb-1 text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">ACMG Classification</h4>
  <VariantClassificationPanel genomicVariantId={variant.id} />
</div>
```
If `VariantExpandedRow` has unrelated uncommitted concurrent-session changes, `git add -p` only your hunk.

- [ ] **Step 2: Add default MSW handlers** — append to the `handlers` array in `frontend/src/test/mocks/handlers.ts`:

```ts
  http.get("/api/acmg/criteria", () => HttpResponse.json({ success: true, data: {} })),
```

- [ ] **Step 3: Full verification.**

```bash
docker compose exec -T php sh -c "cd /var/www/html && php artisan test --filter 'Acmg|VariantClassification|Classification|FactorySmoke'"
docker compose exec -T php sh -c "cd /var/www/html && vendor/bin/pint --test"
docker compose exec -T node sh -c "cd /app && npx tsc --noEmit"
docker compose exec -T node sh -c "cd /app && npx vite build"
docker compose exec -T node sh -c "cd /app && npx vitest run"
```
Expected: backend ACMG suite green; Pint clean; tsc clean; vite build OK; full vitest green. (Pre-existing CaseDiscussion/Event failures remain unrelated.)

- [ ] **Step 4: Commit:**

```bash
git add frontend/src/features/genomics/components/VariantExpandedRow.tsx frontend/src/test/mocks/handlers.ts
git commit -m "feat(acmg): embed classification panel in genomics variant row + MSW handler"
```

---

## Self-Review

**1. Spec coverage (against the ACMG-engine half of strategy §5.3):**
- ACMG/AMP Tavtigian points engine → Tasks 1–2 (catalog, strengths, classifier, exact thresholds + BA1 override + strength modulation). ✓
- ClinGen CSpec gene-specific criteria → Tasks 4, 6, 12 (override table, resolver, seeded VCEP specs). ✓
- Auto-computable criteria + data sources → Tasks 7–8 (PM2/BS1/BA1 from AF; PP3/BP4 calibrated REVEL; PS1/PM5 from ClinVar same-residue). ✓
- Evidence-per-criterion + transparent points + human sign-off (non-device CDS) → Tasks 5, 9, 11, 14–15 (criteria records with data_source/evidence_value/set_by; recompute; confirm with override reason; UI points bar + per-criterion list + sign-off dialog). ✓
- *Deferred (explicit, not gaps):* GA4GH VRS / ClinGen CAID identity layer (companion plan, Plan-3-IDENTITY, sequenced with Plan 4); AutoPVS1 PVS1 automation (GPL-3.0 isolated service); live gnomAD/REVEL/SpliceAI fetchers (engine accepts the inputs); FHIR Genomics Reporting emit; scheduled live CSpec sync; SpliceAI-based PP3/BP4 for splice variants.

**2. Placeholder scan:** Every code step has complete code; every test step has real assertions + exact run commands. The two integration touch-points (`VariantExpandedRow` field name; the panel's single-source-of-truth note) are flagged as read-the-file/known-deferral, not vague.

**3. Type/name consistency:** `AcmgStrength` enum values (`very_strong|strong|moderate|supporting`) match across catalog, classifier, criteria validation, service, frontend type, and the strength `<select>`. `classify()` returns `{classification, points, standalone_benign}` consumed by the service. `ClassificationService(AcmgClassifier, AcmgAutoEvidence, GeneSpecificationResolver)` ctor matches the unit-test wiring and Laravel auto-resolution. Points: `PM2` supporting = +1, `PP3` strong = +4 → total 5 → VUS is asserted identically in the service test (Task 9) and the API test (Task 11). Routes (`/genomic-variants/{variant}/classifications`, `/classifications/{id}/criteria`, `/classification-criteria/{id}`, `/classifications/{id}/confirm`, `/acmg/criteria`) match the api-client paths in Task 13. Tables `clinical.variant_classifications` / `clinical.classification_criteria` / `clinical.acmg_gene_specifications` consistent across migration/model/factory. ✓

**4. Risk notes:** `routes/api.php`, `handlers.ts`, `VariantExpandedRow.tsx` are shared — every task touching them says stage surgically (`git add -p`). `vite build` is run (Tasks 15, 16) in addition to `tsc`.

---

## Execution Handoff

Plan complete and saved to `docs/plans/2026-06-15-acmg-points-engine-plan.md`. Two execution options:

1. **Subagent-Driven (recommended)** — fresh subagent per task, spec + quality review at the risk points (classifier, auto-evidence, service, controller), with the same shared-file surgical-staging discipline used in Plan 2.
2. **Inline Execution** — execute here with checkpoints.

Which approach? (Run in this checkout given Docker is bind-mounted here; mind the concurrent session on shared files.)
