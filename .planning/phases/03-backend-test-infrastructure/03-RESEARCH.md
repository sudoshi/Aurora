# Phase 3: Backend Test Infrastructure - Research

**Researched:** 2026-03-25
**Domain:** Pest PHP testing with multi-schema PostgreSQL, Laravel model factories
**Confidence:** HIGH

## Summary

Phase 3 establishes the Pest test suite to run against Aurora's multi-schema PostgreSQL database (app, clinical, public) and creates factories for the five required models. The primary challenge is that Laravel's default `RefreshDatabase` trait re-runs all migrations on every test, which is slow with 27+ migrations across three schemas including pgvector extension creation. `DatabaseTruncation` is the correct approach: it runs migrations once, then truncates tables between tests.

The second challenge is that the Clinical namespace models (`GeneDrugInteraction`, `GenomicVariant`) lack the `HasFactory` trait and have no factories. These models live in `App\Models\Clinical` and use schema-qualified table names or rely on `search_path` resolution. Factories must be created with explicit `$model` bindings, and the `HasFactory` trait must be added to those models.

A third issue discovered during research: the `Patient` model references `dev.patients` (a legacy schema that has no migration), while `ClinicalPatient` references just `patients` (resolved via `search_path` to `clinical.patients`). The `Patient` model is the one required by INFRA-02, and it already has a factory, but that factory will fail in a fresh test database because the `dev` schema does not exist. This must be addressed.

**Primary recommendation:** Use `DatabaseTruncation` (not `RefreshDatabase`) in Pest.php, create a `.env.testing` file pointing to a dedicated test database, add `HasFactory` to Clinical models, and create factories with realistic defaults matching migration column constraints.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| INFRA-01 | Configure Pest with multi-schema PostgreSQL support (DatabaseTruncation or custom) | DatabaseTruncation trait in Pest.php, `.env.testing` with test DB, schema creation handled by migration 000001, pgvector extension needed in test DB |
| INFRA-02 | Create Laravel model factories for User, Patient, ClinicalCase, GeneDrugInteraction, GenomicVariant | UserFactory exists (good defaults), PatientFactory exists (needs `dev` schema fix or Patient model table fix), ClinicalCaseFactory exists (needs schema alignment), GeneDrugInteraction and GenomicVariant factories must be created from scratch with HasFactory trait added to models |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Pest | 3.8 | Test framework | Already installed. Fluent syntax, first-class Laravel 11 support. |
| PHPUnit | 11.0.1 | Underlying engine | Required by Pest 3.x. No direct interaction needed. |
| Mockery | 1.6 | Mocking | Already installed. Standard Laravel mocking library. |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Illuminate\Foundation\Testing\DatabaseTruncation | built-in | Test DB reset | Feature tests -- truncates tables between tests instead of re-migrating |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| DatabaseTruncation | RefreshDatabase | RefreshDatabase re-runs all migrations per test. With 27+ migrations, pgvector, and 3 schemas, this is unacceptably slow (5-10s per test). DatabaseTruncation runs migrations once, truncates in ~50ms. |
| DatabaseTruncation | LazilyRefreshDatabase | LazilyRefreshDatabase only migrates once per suite but still wraps each test in a transaction -- multi-schema PostgreSQL with cross-schema foreign keys can cause issues with transaction rollback. DatabaseTruncation is safer. |

## Architecture Patterns

### Test Database Strategy

The test database must be a separate PostgreSQL database (not the development one). This avoids destroying development data when tests truncate tables.

**Required `.env.testing`:**
```env
APP_ENV=testing
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=aurora_test
DB_USERNAME=smudoshi
DB_PASSWORD=acumenus
```

**Create the test database:**
```sql
CREATE DATABASE aurora_test OWNER smudoshi;
\c aurora_test
CREATE EXTENSION IF NOT EXISTS vector;
```

### Pest.php Configuration Pattern

```php
pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\DatabaseTruncation::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit');
```

**Key:** `DatabaseTruncation` replaces `RefreshDatabase` for Feature tests. Unit tests should NOT touch the database.

### DatabaseTruncation with Multi-Schema Tables

`DatabaseTruncation` by default only truncates tables in the default schema. For Aurora's multi-schema setup, the `$tablesToTruncate` or `$exceptTables` properties may need configuration. The trait truncates all tables found via `information_schema.tables` for the connection, respecting the `search_path`.

**Important:** Because the pgsql connection has `search_path = app,clinical,public`, truncation will find tables in all three schemas. Tables like `migrations`, `roles`, `permissions` should be excluded from truncation to avoid breaking the test infrastructure.

```php
// In tests/TestCase.php or Pest.php
// Tables to EXCLUDE from truncation (seeded once, never change)
protected $exceptTables = [
    'migrations',
    'app.roles',
    'app.permissions',
    'app.model_has_roles',
    'app.model_has_permissions',
    'app.role_has_permissions',
];
```

### Factory Placement for Clinical Models

Clinical models live in `App\Models\Clinical\` namespace. Laravel auto-discovers factories by convention: `Database\Factories\{ModelClass}Factory`. For models in sub-namespaces, factories must go in matching sub-directories:

```
backend/database/factories/
  UserFactory.php              # exists -- App\Models\User
  PatientFactory.php           # exists -- App\Models\Patient (needs dev schema fix)
  ClinicalCaseFactory.php      # exists -- App\Models\ClinicalCase
  Clinical/
    GeneDrugInteractionFactory.php   # NEW -- App\Models\Clinical\GeneDrugInteraction
    GenomicVariantFactory.php        # NEW -- App\Models\Clinical\GenomicVariant
```

**Namespace:** `Database\Factories\Clinical\`

Each Clinical model must have `HasFactory` trait added:
```php
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneDrugInteraction extends Model
{
    use HasFactory;
    // ...
}
```

### Sample Test Pattern

```php
// tests/Feature/FactorySmokeTest.php
use App\Models\User;
use App\Models\Patient;
use App\Models\ClinicalCase;
use App\Models\Clinical\GeneDrugInteraction;
use App\Models\Clinical\GenomicVariant;
use App\Models\Clinical\ClinicalPatient;

describe('Model Factories', function () {
    it('creates a valid User', function () {
        $user = User::factory()->create();
        expect($user)->toBeInstanceOf(User::class);
        expect($user->id)->toBeGreaterThan(0);
        expect($user->is_active)->toBeTrue();
    });

    it('creates a valid ClinicalCase with relationships', function () {
        $case = ClinicalCase::factory()->create();
        expect($case->creator)->toBeInstanceOf(User::class);
    });

    it('creates a valid GeneDrugInteraction', function () {
        $interaction = GeneDrugInteraction::factory()->create();
        expect($interaction->gene)->toBeString();
        expect($interaction->drug)->toBeString();
    });

    it('creates a valid GenomicVariant', function () {
        $variant = GenomicVariant::factory()->create();
        expect($variant->gene)->toBeString();
        expect($variant->patient)->toBeInstanceOf(ClinicalPatient::class);
    });
});
```

### Anti-Patterns to Avoid
- **Using RefreshDatabase with 27+ migrations:** Each test takes 5-10s. Use DatabaseTruncation.
- **Putting database-hitting tests in Unit/:** Unit tests must be fast and isolated. Database tests belong in Feature/.
- **Creating factories without matching migration columns:** Factories that define columns not in the migration will cause SQL errors. Cross-reference migration column names exactly.
- **Seeding the superuser in every test:** Use `actingAs(User::factory()->create())` instead. Only auth-specific tests should seed the superuser.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Database reset between tests | Custom truncation SQL | `DatabaseTruncation` trait | Laravel handles table discovery, foreign key ordering, and multi-connection support |
| Test data generation | Manual array inserts | Laravel Model Factories | Factories handle relationships, default values, and state variants consistently |
| Test database creation | Manual SQL in test setup | `.env.testing` + `php artisan migrate --env=testing` | Laravel automatically uses `.env.testing` when `APP_ENV=testing` |
| Schema-qualified table testing | Raw SQL assertions | `assertDatabaseHas('app.users', [...])` | Laravel's testing assertions support schema-qualified table names |

## Common Pitfalls

### Pitfall 1: `dev` Schema Does Not Exist in Migrations
**What goes wrong:** The `Patient` model uses `protected $table = 'dev.patients'` and `Event` model uses `protected $table = 'dev.events'`, but NO migration creates a `dev` schema. The only schemas created are `app`, `clinical`, and `public` (in migration 000001).
**Why it happens:** These are legacy V1 models that were not updated during the V2 scaffold.
**How to avoid:** Either: (a) update `Patient` model to use `app.patients` or `clinical.patients` (depending on which it should map to), or (b) create a `dev` schema in migrations. Option (a) is correct -- the `Patient` model is likely a legacy model that should reference `clinical.patients` (same as `ClinicalPatient`), or the factory must be updated to work with `ClinicalPatient` instead.
**Warning signs:** `SQLSTATE[3F000]: Invalid schema name` errors when running PatientFactory.

### Pitfall 2: pgvector Extension Not Available in Test Database
**What goes wrong:** Migration `create_clinical_tables` runs `CREATE EXTENSION IF NOT EXISTS vector`. If pgvector is not installed on the PostgreSQL instance, this fails and all clinical table migrations fail.
**How to avoid:** Ensure `pgvector` is installed on the local PostgreSQL 16 instance. Run `CREATE EXTENSION IF NOT EXISTS vector;` manually on the test database if needed.
**Warning signs:** `ERROR: could not open extension control file ... vector.control` during migration.

### Pitfall 3: DatabaseTruncation Truncates Seeded Permission Tables
**What goes wrong:** Spatie permission tables (`roles`, `permissions`, `model_has_roles`) get truncated between tests. Any test relying on roles fails.
**How to avoid:** Use `$exceptTables` to protect permission tables from truncation, OR re-seed permissions in a `beforeEach` (slower). The `$exceptTables` approach is preferred.
**Warning signs:** `Spatie\Permission\Exceptions\RoleDoesNotExist` errors in tests that use `actingAs` with role-bearing users.

### Pitfall 4: GenomicVariant Table Resolution Depends on search_path
**What goes wrong:** `GenomicVariant` has `protected $table = 'genomic_variants'` (no schema prefix). It relies on the pgsql connection's `search_path = app,clinical,public` to resolve to `clinical.genomic_variants`. If the test database connection has a different search_path, the table is not found.
**How to avoid:** Ensure the test database uses the same `pgsql` connection config (which includes `search_path`). The `.env.testing` should only override `DB_DATABASE`, not `DB_CONNECTION`.
**Warning signs:** `relation "genomic_variants" does not exist` in tests.

### Pitfall 5: ClinicalCase Factory References Patient (dev schema)
**What goes wrong:** The existing `ClinicalCaseFactory` has `'patient_id' => Patient::factory()`. But `ClinicalCase->patient()` returns `BelongsTo(ClinicalPatient::class)`. If Patient creates in `dev.patients` but ClinicalCase expects `clinical.patients`, foreign key constraints fail.
**How to avoid:** Update ClinicalCaseFactory to use `ClinicalPatient` factory (once created) instead of `Patient::factory()`. This requires creating a `ClinicalPatientFactory` as well, or updating the `patient_id` to reference the correct table.

## Code Examples

### GeneDrugInteractionFactory
```php
<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\GeneDrugInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;

class GeneDrugInteractionFactory extends Factory
{
    protected $model = GeneDrugInteraction::class;

    public function definition(): array
    {
        $genes = ['BRAF', 'EGFR', 'KRAS', 'TP53', 'ALK', 'ROS1', 'BRCA1', 'BRCA2', 'PIK3CA', 'HER2'];
        $drugs = ['Vemurafenib', 'Dabrafenib', 'Erlotinib', 'Osimertinib', 'Sotorasib', 'Olaparib', 'Crizotinib'];
        $evidenceLevels = ['1', '2A', '2B', '3A', '3B', '4', 'R1', 'R2'];
        $relationships = ['sensitive', 'resistant', 'diagnostic', 'prognostic'];

        return [
            'gene' => fake()->randomElement($genes),
            'variant_pattern' => '*',
            'drug' => fake()->randomElement($drugs),
            'drug_class' => fake()->optional()->randomElement(['kinase_inhibitor', 'PARP_inhibitor', 'checkpoint_inhibitor']),
            'relationship' => fake()->randomElement($relationships),
            'evidence_level' => fake()->randomElement($evidenceLevels),
            'indication' => fake()->optional()->sentence(),
            'mechanism' => fake()->optional()->sentence(),
            'source' => fake()->randomElement(['oncokb', 'manual', 'clinvar']),
            'source_url' => fake()->optional()->url(),
            'oncokb_last_synced_at' => fake()->optional()->dateTimeBetween('-30 days'),
            'last_verified_at' => fake()->optional()->dateTimeBetween('-90 days'),
        ];
    }
}
```

### GenomicVariantFactory
```php
<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\ClinicalPatient;
use App\Models\Clinical\GenomicVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class GenomicVariantFactory extends Factory
{
    protected $model = GenomicVariant::class;

    public function definition(): array
    {
        $genes = ['BRAF', 'EGFR', 'KRAS', 'TP53', 'ALK', 'BRCA1', 'BRCA2', 'PIK3CA'];
        $variantTypes = ['SNV', 'indel', 'fusion', 'CNV', 'rearrangement'];
        $significance = ['pathogenic', 'likely_pathogenic', 'VUS', 'likely_benign', 'benign'];
        $chromosomes = array_map(fn($i) => (string)$i, range(1, 22));
        $chromosomes[] = 'X';
        $chromosomes[] = 'Y';

        return [
            'patient_id' => ClinicalPatient::factory(),
            'gene' => fake()->randomElement($genes),
            'variant' => fake()->optional()->lexify('????'),
            'variant_type' => fake()->randomElement($variantTypes),
            'chromosome' => fake()->randomElement($chromosomes),
            'position' => fake()->numberBetween(1000000, 250000000),
            'ref_allele' => fake()->randomElement(['A', 'T', 'G', 'C']),
            'alt_allele' => fake()->randomElement(['A', 'T', 'G', 'C']),
            'zygosity' => fake()->randomElement(['heterozygous', 'homozygous']),
            'allele_frequency' => fake()->randomFloat(6, 0.001, 0.999),
            'clinical_significance' => fake()->randomElement($significance),
            'actionability' => fake()->optional()->randomElement(['actionable', 'potentially_actionable', 'unknown']),
        ];
    }
}
```

### ClinicalPatientFactory (dependency for GenomicVariant)
```php
<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\ClinicalPatient;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicalPatientFactory extends Factory
{
    protected $model = ClinicalPatient::class;

    public function definition(): array
    {
        return [
            'mrn' => fake()->unique()->numerify('MRN-######'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date('Y-m-d', '-20 years'),
            'sex' => fake()->randomElement(['male', 'female']),
            'race' => fake()->optional()->randomElement(['white', 'black', 'asian', 'other']),
            'ethnicity' => fake()->optional()->randomElement(['hispanic', 'non-hispanic']),
        ];
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| RefreshDatabase (re-migrate every test) | DatabaseTruncation (migrate once, truncate) | Laravel 10.x+ | 10-50x faster test suites on multi-migration projects |
| PHPUnit directly | Pest 3.x wrapping PHPUnit 11 | 2024 | Cleaner syntax, better Laravel integration, same underlying engine |
| Manual factory definitions | Factory classes with HasFactory trait | Laravel 8+ (2020) | Consistent, relationship-aware test data generation |

## Open Questions

1. **`Patient` model `dev.patients` schema mismatch**
   - What we know: Patient model references `dev.patients` but no `dev` schema exists in migrations. ClinicalPatient references `patients` (resolved via search_path to `clinical.patients`).
   - What's unclear: Is `Patient` model still used in production code? Should it be updated to match V2 schemas, or left as-is for backward compatibility?
   - Recommendation: The `Patient` model appears to be a V1 legacy model. For INFRA-02, create the factory for `Patient` but note that its tests will fail without either creating a `dev` schema or updating the model's `$table`. The safest approach for this phase is to update the `Patient` model table to `clinical.patients` (since that is where patient data lives in V2) or have the factory produce data via `ClinicalPatient` instead. Flag for planner decision.

2. **Spatie permission table schema prefix**
   - What we know: Permission tables are in `app.` schema (migration 000004). Spatie config may need `table_names` overrides to include the schema prefix.
   - What's unclear: Whether Spatie's built-in config handles schema-prefixed table names in the test database correctly.
   - Recommendation: Verify during implementation. If Spatie queries fail, update `config/permission.php` with schema-qualified table names.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest 3.8 + PHPUnit 11.0.1 |
| Config file | `backend/phpunit.xml` |
| Quick run command | `cd backend && ./vendor/bin/pest --filter=FactorySmoke` |
| Full suite command | `cd backend && ./vendor/bin/pest` |

### Phase Requirements to Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| INFRA-01 | Pest runs with DatabaseTruncation against multi-schema DB | smoke | `cd backend && php artisan test --env=testing` | No -- Wave 0 |
| INFRA-02 | Factories produce valid User instances | unit | `cd backend && ./vendor/bin/pest --filter="creates a valid User"` | No -- Wave 0 |
| INFRA-02 | Factories produce valid Patient instances | unit | `cd backend && ./vendor/bin/pest --filter="creates a valid Patient"` | No -- Wave 0 |
| INFRA-02 | Factories produce valid ClinicalCase instances | unit | `cd backend && ./vendor/bin/pest --filter="creates a valid ClinicalCase"` | No -- Wave 0 |
| INFRA-02 | Factories produce valid GeneDrugInteraction instances | unit | `cd backend && ./vendor/bin/pest --filter="creates a valid GeneDrugInteraction"` | No -- Wave 0 |
| INFRA-02 | Factories produce valid GenomicVariant instances | unit | `cd backend && ./vendor/bin/pest --filter="creates a valid GenomicVariant"` | No -- Wave 0 |

### Sampling Rate
- **Per task commit:** `cd backend && ./vendor/bin/pest --filter=FactorySmoke`
- **Per wave merge:** `cd backend && ./vendor/bin/pest`
- **Phase gate:** All factory smoke tests pass before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `backend/.env.testing` -- test database configuration
- [ ] `aurora_test` database created in PostgreSQL with pgvector extension
- [ ] `backend/tests/Feature/FactorySmokeTest.php` -- validates all 5 factories
- [ ] `backend/database/factories/Clinical/GeneDrugInteractionFactory.php` -- new factory
- [ ] `backend/database/factories/Clinical/GenomicVariantFactory.php` -- new factory
- [ ] `backend/database/factories/Clinical/ClinicalPatientFactory.php` -- dependency factory for GenomicVariant
- [ ] `HasFactory` trait added to GeneDrugInteraction, GenomicVariant, ClinicalPatient models
- [ ] `backend/tests/Pest.php` updated to use DatabaseTruncation instead of RefreshDatabase

## Sources

### Primary (HIGH confidence)
- Codebase analysis: `backend/tests/Pest.php`, `backend/phpunit.xml`, all model files, all migration files, all existing factories
- `backend/config/database.php` -- pgsql connection with `search_path = app,clinical,public` and clinical alias
- `.planning/codebase/TESTING.md` -- existing test patterns documentation
- `.planning/research/STACK.md` -- verified Pest 3.8 + PHPUnit 11 stack
- `.planning/research/PITFALLS.md` -- multi-schema truncation pitfalls documented

### Secondary (MEDIUM confidence)
- Laravel 11 DatabaseTruncation trait behavior with multi-schema PostgreSQL -- based on Laravel docs and codebase patterns
- Factory auto-discovery for sub-namespace models -- based on Laravel convention documentation

### Tertiary (LOW confidence)
- Spatie permission table handling with schema-prefixed names in test context -- needs runtime validation

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Pest 3.8 already installed, DatabaseTruncation is built-in Laravel
- Architecture: HIGH - Multi-schema setup well-documented in codebase, factory patterns are standard Laravel
- Pitfalls: HIGH - dev schema mismatch confirmed by code analysis, pgvector dependency verified in migrations

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable -- Pest/Laravel versions unlikely to change)
