# Phase 9: Feature Completion - Research

**Researched:** 2026-03-25
**Domain:** Laravel backend — OncoKB API integration, file upload persistence, CRUD persistence
**Confidence:** HIGH

## Summary

Phase 9 replaces three groups of stub endpoints with real business logic and database persistence. The three requirements are: (1) OncoKB response parsing in `OncoKbService` to create/update `GeneDrugInteraction` records from OncoKB treatment annotations, (2) file upload endpoints that actually store files and persist `GenomicUpload` metadata, and (3) criteria CRUD endpoints that persist `GenomicCriteria` records.

All three are well-scoped backend tasks with existing routes, validation, test infrastructure, and model patterns already in place. The main complexity is in FEAT-01 (OncoKB parsing) which requires understanding the OncoKB API response schema and mapping evidence levels. FEAT-02 and FEAT-03 are straightforward Laravel model + controller CRUD with file storage.

**Primary recommendation:** Create two new migrations (GenomicUpload, GenomicCriteria tables in clinical schema), two new Eloquent models, implement the three feature groups in the existing controller/service files, and update existing tests from stub assertions to real persistence assertions.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| FEAT-01 | OncoKB response parsing in OncoKbService (parse treatment annotations, map evidence levels, upsert GeneDrugInteraction records) | OncoKB API schema documented; evidence level mapping defined; existing service has HTTP call infrastructure; `GeneDrugInteraction` model has unique constraint for upsert |
| FEAT-02 | GenomicsController upload endpoints (listUploads, storeUpload, showUpload with file handling) | Laravel Storage facade available; `local` disk configured; need new `GenomicUpload` model + migration; existing routes and validation in place |
| FEAT-03 | GenomicsController criteria endpoints (listCriteria, storeCriterion, updateCriterion, destroyCriterion with persistence) | Need new `GenomicCriteria` model + migration; existing routes, validation rules, and stub response shapes define the contract; straightforward Eloquent CRUD |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel | 11 | Web framework | Already in use; provides Storage, Eloquent, Http facades |
| Eloquent ORM | (Laravel 11) | Database models/queries | All existing models use Eloquent; `GeneDrugInteraction` pattern to follow |
| Laravel Storage | (Laravel 11) | File storage abstraction | `local` disk configured in `config/filesystems.php`; standard for file uploads |
| Laravel Http facade | (Laravel 11) | External API calls | Already used in `OncoKbService` for OncoKB API calls |
| Pest | 3.x | Test framework | All existing backend tests use Pest; test files exist for both GenomicsController and OncoKbService |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `ApiResponse` helper | Custom | Consistent JSON responses | All controller responses must use `ApiResponse::success/error/paginated` |
| `GeneDrugInteractionFactory` | Custom | Test data generation | Existing factory with evidence levels, genes, drugs -- use for OncoKB parsing tests |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Local disk storage | S3 disk | S3 config exists but no credentials; local is fine for this milestone |
| `updateOrCreate` | Raw SQL upsert | Eloquent `updateOrCreate` is sufficient given the unique constraint on `gene+variant_pattern+drug` |

## Architecture Patterns

### Recommended Project Structure
```
backend/
├── app/
│   ├── Models/Clinical/
│   │   ├── GeneDrugInteraction.php       # EXISTS - target for OncoKB upserts
│   │   ├── GenomicUpload.php             # NEW - upload metadata model
│   │   └── GenomicCriteria.php           # NEW - criteria model
│   ├── Services/Genomics/
│   │   └── OncoKbService.php             # EXISTS - add parsing logic
│   └── Http/Controllers/
│       └── GenomicsController.php        # EXISTS - replace stubs with real logic
├── database/
│   ├── migrations/
│   │   ├── xxxx_create_genomic_uploads_table.php    # NEW
│   │   └── xxxx_create_genomic_criteria_table.php   # NEW
│   └── factories/Clinical/
│       ├── GenomicUploadFactory.php      # NEW
│       └── GenomicCriteriaFactory.php    # NEW
└── tests/
    ├── Feature/Api/GenomicsControllerTest.php  # EXISTS - update stub tests
    └── Unit/Services/OncoKbServiceTest.php     # EXISTS - add parsing tests
```

### Pattern 1: OncoKB Response Parsing + Upsert
**What:** Parse the `treatments` array from OncoKB `IndicatorQueryResp`, extract drug/level/indication, and upsert `GeneDrugInteraction` records.
**When to use:** During `syncInteractions()` after a successful API response.
**Example:**
```php
// OncoKB API returns IndicatorQueryResp with treatments array
// Each treatment has: drugs[], level (LEVEL_1, LEVEL_2A, etc.), description, levelAssociatedCancerType
// Map to GeneDrugInteraction fields:
//   gene          <- from the gene being queried
//   drug          <- treatment.drugs[0].name (or comma-joined for combos)
//   evidence_level <- map LEVEL_1 -> '1', LEVEL_2A -> '2A', etc.
//   relationship  <- 'sensitive' for LEVEL_1-4, 'resistant' for LEVEL_R1/R2
//   source        <- 'oncokb'
//   indication    <- treatment.description or levelAssociatedCancerType.name

GeneDrugInteraction::updateOrCreate(
    ['gene' => $gene, 'variant_pattern' => $variantPattern, 'drug' => $drugName],
    [
        'evidence_level' => $mappedLevel,
        'relationship' => $relationship,
        'indication' => $indication,
        'source' => 'oncokb',
        'source_url' => "https://www.oncokb.org/gene/{$gene}",
        'oncokb_last_synced_at' => now(),
        'last_verified_at' => now(),
    ]
);
```

### Pattern 2: File Upload with Storage Facade
**What:** Accept uploaded file, store via Storage facade, create database record.
**When to use:** `storeUpload` endpoint.
**Example:**
```php
$file = $request->file('file');
$path = $file->store('genomic-uploads', 'local');

$upload = GenomicUpload::create([
    'original_filename' => $file->getClientOriginalName(),
    'stored_path' => $path,
    'file_format' => $request->input('file_format'),
    'genome_build' => $request->input('genome_build', 'GRCh38'),
    'sample_id' => $request->input('sample_id'),
    'status' => 'uploaded',
    'uploaded_by' => auth()->id(),
    'file_size' => $file->getSize(),
]);

return ApiResponse::success($upload, 'Upload created', 201);
```

### Pattern 3: Standard Eloquent CRUD (Criteria)
**What:** Basic model CRUD with validation already defined in controller stubs.
**When to use:** All four criteria endpoints.
**Example:**
```php
// Store
$criterion = GenomicCriteria::create([
    ...$request->validated(),
    'created_by' => auth()->id(),
]);
return ApiResponse::success($criterion, 'Criterion created', 201);

// Update
$criterion = GenomicCriteria::findOrFail($id);
$criterion->update($request->validated());
return ApiResponse::success($criterion, 'Criterion updated');

// Destroy
$criterion = GenomicCriteria::findOrFail($id);
$criterion->delete();
return ApiResponse::success(null, 'Criterion deleted');
```

### Anti-Patterns to Avoid
- **Returning stub data after DB write:** Stubs currently return hardcoded arrays. After implementing persistence, always return the actual Eloquent model/collection, not synthetic data.
- **Not using `findOrFail` for show/update/destroy:** The current stubs accept any ID without checking existence. Real implementations must return 404 for non-existent records.
- **Parsing OncoKB in the controller:** Keep parsing logic in `OncoKbService`, not in the controller. The controller should not know about OncoKB response structure.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| File storage paths | Custom path generation | `$file->store('genomic-uploads', 'local')` | Laravel handles unique filenames, directory creation |
| Upsert logic | Manual SELECT + INSERT/UPDATE | `Model::updateOrCreate()` | Handles race conditions, uses DB unique constraint |
| Pagination | Manual offset/limit math | `Model::paginate($perPage)` + `ApiResponse::paginated()` | Already established pattern in `listVariants` |
| File validation | Manual MIME checking | Laravel validation rules (`file`, `mimes:vcf,csv,tsv,txt`) | Handles edge cases, security |

## Common Pitfalls

### Pitfall 1: OncoKB Evidence Level Mapping
**What goes wrong:** OncoKB returns `LEVEL_1`, `LEVEL_2A`, etc. but `GeneDrugInteraction.evidence_level` stores `1`, `2A`, etc.
**Why it happens:** Different naming conventions between OncoKB API and internal model.
**How to avoid:** Create a static mapping array in `OncoKbService`:
```php
private const LEVEL_MAP = [
    'LEVEL_1' => '1', 'LEVEL_2' => '2A', 'LEVEL_3A' => '3A',
    'LEVEL_3B' => '3B', 'LEVEL_4' => '4', 'LEVEL_R1' => 'R1', 'LEVEL_R2' => 'R2',
];
```
**Warning signs:** Tests fail with `evidence_level` not matching expected values.

### Pitfall 2: OncoKB API Endpoint Selection
**What goes wrong:** The current code calls `/api/v1/genes/{gene}/variants` which returns variant info, NOT treatment annotations.
**Why it happens:** Annotation endpoints are different from gene/variant lookup endpoints.
**How to avoid:** Use the annotation endpoints: `/api/v1/annotate/mutations/byProteinChange` or `/api/v1/annotate/mutations/byHGVSg` to get treatment data. Alternatively, keep querying `/genes/{gene}/variants` to discover variants, then annotate each variant to get treatments. The simplest approach for this phase: query treatments per gene using existing variants in the DB and the annotation endpoint.
**Warning signs:** Response JSON has no `treatments` array.

### Pitfall 3: Unique Constraint on GeneDrugInteraction
**What goes wrong:** `updateOrCreate` throws duplicate key violation if the lookup keys don't exactly match the DB unique index.
**Why it happens:** The table has `UNIQUE(gene, variant_pattern, drug)`. If drug names differ slightly (casing, spacing), duplicates are created or upserts fail.
**How to avoid:** Normalize drug names before upsert: `strtolower(trim($drugName))` or use consistent casing. Also normalize `gene` to uppercase (already done in existing code).
**Warning signs:** Integrity constraint violation errors in logs.

### Pitfall 4: File Upload in Tests
**What goes wrong:** Tests fail because `UploadedFile::fake()` doesn't create a real file on disk.
**Why it happens:** Need to use `Storage::fake('local')` in tests to avoid writing to real filesystem.
**How to avoid:** Use `Storage::fake('local')` + `UploadedFile::fake()->create('test.vcf', 100)` pattern.
**Warning signs:** Tests leave files on disk, or fail with storage permission errors.

### Pitfall 5: GenomicUpload Model Missing
**What goes wrong:** Trying to use `GenomicUpload::create()` before the model and migration exist.
**Why it happens:** No `GenomicUpload` or `GenomicCriteria` model exists yet -- they must be created.
**How to avoid:** Create migrations and models FIRST, then implement controller logic.
**Warning signs:** Class not found errors.

## Code Examples

### OncoKB Evidence Level Mapping
```php
// In OncoKbService.php
private const LEVEL_MAP = [
    'LEVEL_1'  => '1',
    'LEVEL_2'  => '2A',
    'LEVEL_2A' => '2A',  // alias
    'LEVEL_2B' => '2B',
    'LEVEL_3A' => '3A',
    'LEVEL_3B' => '3B',
    'LEVEL_4'  => '4',
    'LEVEL_R1' => 'R1',
    'LEVEL_R2' => 'R2',
];

private const RESISTANCE_LEVELS = ['R1', 'R2'];

private function mapEvidenceLevel(string $oncoKbLevel): ?string
{
    return self::LEVEL_MAP[$oncoKbLevel] ?? null;
}

private function mapRelationship(string $mappedLevel): string
{
    return in_array($mappedLevel, self::RESISTANCE_LEVELS) ? 'resistant' : 'sensitive';
}
```

### GenomicUpload Migration Schema
```php
Schema::create('clinical.genomic_uploads', function (Blueprint $table) {
    $table->id();
    $table->string('original_filename', 500);
    $table->string('stored_path', 1000);
    $table->string('file_format', 50);          // vcf, csv, tsv, maf
    $table->string('genome_build', 20)->default('GRCh38');
    $table->string('sample_id', 200)->nullable();
    $table->string('status', 50)->default('uploaded'); // uploaded, processing, imported, error
    $table->unsignedInteger('total_variants')->default(0);
    $table->unsignedInteger('mapped_variants')->default(0);
    $table->unsignedInteger('unmapped_variants')->default(0);
    $table->unsignedBigInteger('file_size')->default(0);
    $table->foreignId('uploaded_by')->nullable()->constrained('app.users');
    $table->timestamps();
});
```

### GenomicCriteria Migration Schema
```php
Schema::create('clinical.genomic_criteria', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255);
    $table->string('criteria_type', 50);         // variant, gene, pathway, cohort
    $table->jsonb('criteria_definition');         // flexible filter definition
    $table->text('description')->nullable();
    $table->boolean('is_shared')->default(false);
    $table->foreignId('created_by')->nullable()->constrained('app.users');
    $table->timestamps();
});
```

### Updating Existing Tests (stub -> real persistence)
```php
// BEFORE (current stub test):
it('listCriteria returns empty array', function () {
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/genomics/criteria');
    $response->assertJsonPath('data', []);
});

// AFTER (real persistence test):
it('listCriteria returns persisted criteria', function () {
    GenomicCriteria::factory()->count(3)->create();
    $response = $this->actingAs($this->user, 'sanctum')
        ->getJson('/api/genomics/criteria');
    $response->assertStatus(200)
        ->assertJsonPath('success', true);
    expect(count($response->json('data')))->toBe(3);
});
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Stub endpoints returning hardcoded JSON | Real persistence with Eloquent models | Phase 9 (now) | Endpoints become functional |
| Manual GeneDrugInteraction seeding | OncoKB API sync with upsert | Phase 9 (now) | Fresh therapy data from authoritative source |
| No file upload storage | Laravel Storage with metadata tracking | Phase 9 (now) | Genomic files can be uploaded and managed |

## Open Questions

1. **OncoKB API endpoint for treatment data per gene**
   - What we know: Current code calls `/api/v1/genes/{gene}/variants` which returns variant info. Treatment annotations come from annotation endpoints (`/annotate/mutations/*`).
   - What's unclear: Whether to keep the per-gene-variants approach and annotate each, or switch to a different endpoint strategy.
   - Recommendation: Keep the existing `/genes/{gene}/variants` call to discover variants, then for each variant with treatments, parse the treatment data. If the response already includes treatment hints (oncogenic status), use that. For v1, a simpler approach: use the existing API call and parse whatever treatment-relevant data the response contains. The TODO comment says "parse OncoKB response" which implies the response already has usable data.

2. **File format validation scope**
   - What we know: The stub validates `file` as required and `file_format` as string. Real implementation should validate actual file types.
   - What's unclear: Whether to validate file contents (e.g., VCF header parsing) or just metadata.
   - Recommendation: For this phase, validate file extension/MIME only. Content parsing is a separate concern (the existing `importToOmop` and `matchPersons` stubs suggest a multi-step pipeline).

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest 3.x on PHPUnit |
| Config file | `backend/phpunit.xml` |
| Quick run command | `cd backend && php artisan test --filter=GenomicsController` |
| Full suite command | `cd backend && php artisan test` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| FEAT-01 | OncoKB parses treatments, maps levels, upserts GeneDrugInteraction | unit | `cd backend && php artisan test --filter=OncoKbServiceTest -x` | Exists -- needs new parsing tests |
| FEAT-02 | POST /genomics/uploads stores file + record; GET lists/shows | feature | `cd backend && php artisan test --filter=GenomicsControllerTest -x` | Exists -- needs update from stub assertions |
| FEAT-03 | Criteria CRUD persists and retrieves GenomicCriteria records | feature | `cd backend && php artisan test --filter=GenomicsControllerTest -x` | Exists -- needs update from stub assertions |

### Sampling Rate
- **Per task commit:** `cd backend && php artisan test --filter=GenomicsController --filter=OncoKbService`
- **Per wave merge:** `cd backend && php artisan test`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `backend/database/migrations/xxxx_create_genomic_uploads_table.php` -- new migration for FEAT-02
- [ ] `backend/database/migrations/xxxx_create_genomic_criteria_table.php` -- new migration for FEAT-03
- [ ] `backend/app/Models/Clinical/GenomicUpload.php` -- new model for FEAT-02
- [ ] `backend/app/Models/Clinical/GenomicCriteria.php` -- new model for FEAT-03
- [ ] `backend/database/factories/Clinical/GenomicUploadFactory.php` -- factory for testing FEAT-02
- [ ] `backend/database/factories/Clinical/GenomicCriteriaFactory.php` -- factory for testing FEAT-03

## Sources

### Primary (HIGH confidence)
- Codebase inspection: `OncoKbService.php`, `GenomicsController.php`, `GeneDrugInteraction.php`, existing tests, migrations, config files
- OncoKB API documentation: [API Info](https://api.oncokb.org/oncokb-website/api) -- treatment response structure, evidence levels
- OncoKB Swagger spec: [Swagger](https://www.oncokb.org/swagger-ui/index.html) -- `IndicatorQueryTreatment` schema with drugs[], level, description fields

### Secondary (MEDIUM confidence)
- [OncoKB Annotator GitHub](https://github.com/oncokb/oncokb-annotator) -- reference implementation for parsing OncoKB responses
- [OncoKB FAQs - Technical](https://faq.oncokb.org/technical) -- API usage patterns

### Tertiary (LOW confidence)
- None

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH -- all libraries already in use, patterns established
- Architecture: HIGH -- follows existing codebase patterns exactly (models, controllers, tests)
- Pitfalls: HIGH -- identified from actual codebase inspection (unique constraints, evidence level mapping, missing models)
- OncoKB API parsing: MEDIUM -- API response structure verified via docs but exact field availability for `/genes/{gene}/variants` endpoint needs runtime verification

**Research date:** 2026-03-25
**Valid until:** 2026-04-25 (stable -- internal codebase patterns, OncoKB API is versioned)
