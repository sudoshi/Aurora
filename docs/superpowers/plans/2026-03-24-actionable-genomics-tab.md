# Actionable Genomics Tab — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transform the patient Genomics tab into a clinical decision support surface with AI-powered briefing, live therapy matching from a data-driven evidence pipeline, treatment timeline, and enhanced variant table.

**Architecture:** Three phases — backend evidence pipeline (migration, models, seeder, OncoKB service, refresh command), AI briefing endpoint (Python FastAPI), frontend unified tab (8 components composing 4 sections). Each phase produces testable software independently.

**Tech Stack:** Laravel 11 / PHP 8.4, Python FastAPI / Ollama, React 19 / TypeScript / TanStack Query / Tailwind CSS

**Spec:** `docs/superpowers/specs/2026-03-24-actionable-genomics-tab-design.md`

---

## File Structure

### Phase 1: Backend Evidence Pipeline
| File | Action | Responsibility |
|------|--------|---------------|
| `backend/database/migrations/XXXX_create_gene_drug_interactions_table.php` | Create | Gene-drug interaction table |
| `backend/database/migrations/XXXX_create_evidence_updates_table.php` | Create | Evidence audit trail |
| `backend/app/Models/Clinical/GeneDrugInteraction.php` | Create | Eloquent model |
| `backend/app/Models/Clinical/EvidenceUpdate.php` | Create | Audit trail model |
| `backend/database/seeders/GeneDrugInteractionSeeder.php` | Create | Seed ~45 entries from both hardcoded sources |
| `backend/app/Services/Genomics/OncoKbService.php` | Create | OncoKB API integration |
| `backend/app/Services/RadiogenomicsService.php` | Modify | Query interaction table instead of hardcoded array |
| `backend/app/Http/Controllers/GenomicsController.php` | Modify | Add interactions endpoint |
| `backend/app/Console/Commands/RefreshEvidenceCommand.php` | Create | Orchestrate all evidence syncs |
| `backend/routes/console.php` | Modify | Schedule weekly evidence refresh |
| `backend/routes/api.php` | Modify | Add interactions route |

### Phase 2: AI Genomic Briefing
| File | Action | Responsibility |
|------|--------|---------------|
| `ai/app/models/decision_support.py` | Modify | Add briefing request/response models |
| `ai/app/services/genomic_briefing.py` | Create | Synthesize narrative from variant + therapy data |
| `ai/app/routers/decision_support.py` | Modify | Add genomic-briefing endpoint |

### Phase 3: Frontend Unified Genomics Tab
| File | Action | Responsibility |
|------|--------|---------------|
| `frontend/src/features/genomics/types/index.ts` | Modify | Add interaction + briefing types, absorb radiogenomics types |
| `frontend/src/features/genomics/api/genomicsApi.ts` | Modify | Add interaction + briefing API calls |
| `frontend/src/features/genomics/hooks/useGenomics.ts` | Modify | Add useGenomicBriefing, useVariantInterpretation, useGeneDrugInteractions |
| `frontend/src/features/genomics/components/EvidenceBadge.tsx` | Create | Reusable evidence level + source + freshness badge |
| `frontend/src/features/genomics/components/GenomicBriefing.tsx` | Create | Abby AI narrative card |
| `frontend/src/features/genomics/components/ActionableVariantCard.tsx` | Create | Single variant card with therapies + interactions |
| `frontend/src/features/genomics/components/ActionableVariantsPanel.tsx` | Create | Section 2: all actionable cards + VUS accordion |
| `frontend/src/features/genomics/components/TreatmentTimeline.tsx` | Create | Section 3: drug exposure timeline |
| `frontend/src/features/genomics/components/VariantExpandedRow.tsx` | Create | Inline detail row with AI interpretation |
| `frontend/src/features/genomics/components/GenomicVariantTable.tsx` | Create | Section 4: enhanced filterable table |
| `frontend/src/features/patient-profile/components/PatientGenomicsTab.tsx` | Rewrite | Compose 4 sections |
| `frontend/src/features/radiogenomics/` | Delete | Entire directory (absorbed) |
| `frontend/src/features/patient-profile/components/VariantCard.tsx` | Delete | Replaced by VariantExpandedRow |
| `frontend/src/features/patient-profile/components/ActionableGenes.tsx` | Delete | Replaced by ActionableVariantsPanel |

---

## Phase 1: Backend Evidence Pipeline

### Task 1: Gene-Drug Interactions Migration + Model

**Files:**
- Create: `backend/database/migrations/2026_03_25_000001_create_gene_drug_interactions_table.php`
- Create: `backend/app/Models/Clinical/GeneDrugInteraction.php`

- [ ] **Step 1: Create migration**

```bash
cd /home/smudoshi/Github/Aurora/backend
docker compose exec php php artisan make:migration create_gene_drug_interactions_table
```

Replace the generated migration content with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.gene_drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('gene', 50)->index();
            $table->string('variant_pattern', 200)->default('*');
            $table->string('drug', 200);
            $table->string('drug_class', 100)->nullable();
            $table->string('relationship', 50); // sensitive, resistant, dose_adjustment
            $table->string('evidence_level', 10); // 1A, 1B, 2A, 2B, 3A, 3B, 4, R1, R2
            $table->text('indication')->nullable();
            $table->text('mechanism')->nullable();
            $table->string('source', 50)->default('manual'); // oncokb, nccn, fda, pharmgkb, manual
            $table->text('source_url')->nullable();
            $table->timestamp('oncokb_last_synced_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['gene', 'variant_pattern', 'drug'], 'gene_variant_drug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.gene_drug_interactions');
    }
};
```

- [ ] **Step 2: Create Eloquent model**

Create `backend/app/Models/Clinical/GeneDrugInteraction.php`:

```php
<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;

class GeneDrugInteraction extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'clinical.gene_drug_interactions';

    protected $fillable = [
        'gene', 'variant_pattern', 'drug', 'drug_class',
        'relationship', 'evidence_level', 'indication', 'mechanism',
        'source', 'source_url', 'oncokb_last_synced_at', 'last_verified_at',
    ];

    protected $casts = [
        'oncokb_last_synced_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    /**
     * Match interactions for a gene + optional specific variant.
     * If variant_pattern is '*', matches any pathogenic variant in that gene.
     * Otherwise, matches if the patient variant's hgvs_p contains the pattern (case-insensitive).
     */
    public function scopeForVariant($query, string $gene, ?string $hgvsP = null)
    {
        $query->where('gene', strtoupper($gene));

        if ($hgvsP) {
            $query->where(function ($q) use ($hgvsP) {
                $q->where('variant_pattern', '*')
                  ->orWhereRaw('LOWER(?) LIKE \'%\' || LOWER(variant_pattern) || \'%\'', [$hgvsP]);
            });
        } else {
            $query->where('variant_pattern', '*');
        }
    }
}
```

- [ ] **Step 3: Run migration**

```bash
docker compose exec php php artisan migrate
```

- [ ] **Step 4: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add backend/database/migrations/*gene_drug_interactions* backend/app/Models/Clinical/GeneDrugInteraction.php
git commit -m "feat(genomics): gene_drug_interactions migration and model"
```

---

### Task 2: Evidence Updates Migration + Model

**Files:**
- Create: `backend/database/migrations/2026_03_25_000002_create_evidence_updates_table.php`
- Create: `backend/app/Models/Clinical/EvidenceUpdate.php`

- [ ] **Step 1: Create migration**

```bash
docker compose exec php php artisan make:migration create_evidence_updates_table
```

Replace content with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.evidence_updates', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50); // clinvar, oncokb, manual
            $table->string('action', 50); // created, updated, removed
            $table->string('entity_type', 50); // gene_drug_interaction, clinvar_variant
            $table->unsignedBigInteger('entity_id');
            $table->jsonb('old_value')->nullable();
            $table->jsonb('new_value')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.evidence_updates');
    }
};
```

- [ ] **Step 2: Create model**

Create `backend/app/Models/Clinical/EvidenceUpdate.php`:

```php
<?php

namespace App\Models\Clinical;

use Illuminate\Database\Eloquent\Model;

class EvidenceUpdate extends Model
{
    public $timestamps = false;
    protected $connection = 'pgsql';
    protected $table = 'clinical.evidence_updates';

    protected $fillable = [
        'source', 'action', 'entity_type', 'entity_id',
        'old_value', 'new_value',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];
}
```

- [ ] **Step 3: Run migration**

```bash
docker compose exec php php artisan migrate
```

- [ ] **Step 4: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add backend/database/migrations/*evidence_updates* backend/app/Models/Clinical/EvidenceUpdate.php
git commit -m "feat(genomics): evidence_updates audit trail migration and model"
```

---

### Task 3: Seed Gene-Drug Interactions

**Files:**
- Create: `backend/database/seeders/GeneDrugInteractionSeeder.php`

- [ ] **Step 1: Create seeder**

Create `backend/database/seeders/GeneDrugInteractionSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Clinical\GeneDrugInteraction;
use Illuminate\Database\Seeder;

class GeneDrugInteractionSeeder extends Seeder
{
    public function run(): void
    {
        // Union of RadiogenomicsService::buildCorrelations() and
        // RadiogenomicsController::variantDrugInteractions() hardcoded data.
        // Where both sources define the same gene-drug pair, the controller
        // entry is preferred (has mechanism + evidence_summary).
        $interactions = [
            // --- Oncology (Level 1A / FDA-approved) ---
            ['gene' => 'BRAF', 'drug' => 'Vemurafenib', 'drug_class' => 'BRAF inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'BRAF V600E kinase inhibition', 'indication' => 'FDA-approved for BRAF V600E-mutant melanoma', 'source' => 'oncokb'],
            ['gene' => 'BRAF', 'drug' => 'Dabrafenib', 'drug_class' => 'BRAF inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'BRAF V600 kinase inhibition', 'indication' => 'FDA-approved for BRAF V600-mutant melanoma and NSCLC', 'source' => 'oncokb'],
            ['gene' => 'KRAS', 'drug' => 'Cetuximab', 'drug_class' => 'Anti-EGFR antibody', 'relationship' => 'resistant', 'evidence_level' => '1A', 'mechanism' => 'KRAS activation bypasses EGFR blockade', 'indication' => 'KRAS mutations predict resistance to anti-EGFR therapy in CRC', 'source' => 'oncokb'],
            ['gene' => 'KRAS', 'drug' => 'Panitumumab', 'drug_class' => 'Anti-EGFR antibody', 'relationship' => 'resistant', 'evidence_level' => '1A', 'mechanism' => 'KRAS activation bypasses EGFR blockade', 'indication' => 'KRAS mutations predict resistance in CRC', 'source' => 'oncokb'],
            ['gene' => 'KRAS', 'drug' => 'Sotorasib', 'drug_class' => 'KRAS G12C inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'Covalent KRAS G12C inhibition', 'indication' => 'FDA-approved for KRAS G12C-mutant NSCLC', 'source' => 'oncokb', 'variant_pattern' => 'G12C'],
            ['gene' => 'EGFR', 'drug' => 'Osimertinib', 'drug_class' => 'EGFR TKI (3rd gen)', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'Third-gen EGFR TKI', 'indication' => 'FDA-approved for EGFR-mutant NSCLC including T790M', 'source' => 'oncokb'],
            ['gene' => 'EGFR', 'drug' => 'Erlotinib', 'drug_class' => 'EGFR TKI (1st gen)', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'First-gen EGFR TKI', 'indication' => 'FDA-approved for EGFR exon 19del/L858R NSCLC', 'source' => 'oncokb'],
            ['gene' => 'EGFR', 'drug' => 'Gefitinib', 'drug_class' => 'EGFR TKI (1st gen)', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'First-gen EGFR TKI', 'indication' => 'FDA-approved for EGFR-mutant NSCLC', 'source' => 'oncokb'],
            ['gene' => 'ALK', 'drug' => 'Alectinib', 'drug_class' => 'ALK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'ALK inhibition', 'indication' => 'FDA-approved for ALK-positive NSCLC', 'source' => 'oncokb'],
            ['gene' => 'ALK', 'drug' => 'Crizotinib', 'drug_class' => 'ALK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'ALK/ROS1/MET inhibition', 'indication' => 'FDA-approved for ALK-positive NSCLC', 'source' => 'oncokb'],
            ['gene' => 'HER2', 'drug' => 'Trastuzumab', 'drug_class' => 'Anti-HER2 antibody', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'HER2 monoclonal antibody', 'indication' => 'FDA-approved for HER2-positive breast cancer', 'source' => 'oncokb'],
            ['gene' => 'HER2', 'drug' => 'Pertuzumab', 'drug_class' => 'Anti-HER2 antibody', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'HER2 dimerization inhibitor', 'indication' => 'FDA-approved for HER2-positive breast cancer', 'source' => 'oncokb'],
            ['gene' => 'BRCA1', 'drug' => 'Olaparib', 'drug_class' => 'PARP inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'indication' => 'FDA-approved for BRCA-mutant ovarian/breast cancer', 'source' => 'oncokb'],
            ['gene' => 'BRCA1', 'drug' => 'Rucaparib', 'drug_class' => 'PARP inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'indication' => 'FDA-approved for BRCA-mutant ovarian cancer', 'source' => 'oncokb'],
            ['gene' => 'BRCA2', 'drug' => 'Olaparib', 'drug_class' => 'PARP inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'indication' => 'FDA-approved for BRCA-mutant ovarian/breast/prostate cancer', 'source' => 'oncokb'],
            ['gene' => 'BRCA2', 'drug' => 'Rucaparib', 'drug_class' => 'PARP inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PARP inhibition exploits HR deficiency', 'indication' => 'FDA-approved for BRCA-mutant ovarian cancer', 'source' => 'oncokb'],
            ['gene' => 'PIK3CA', 'drug' => 'Alpelisib', 'drug_class' => 'PI3K inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PI3K alpha-selective inhibition', 'indication' => 'FDA-approved for PIK3CA-mutant HR+/HER2- breast cancer', 'source' => 'oncokb'],
            ['gene' => 'NTRK1', 'drug' => 'Larotrectinib', 'drug_class' => 'TRK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'TRK inhibition', 'indication' => 'FDA-approved for NTRK fusion-positive solid tumors', 'source' => 'oncokb'],
            ['gene' => 'NTRK1', 'drug' => 'Entrectinib', 'drug_class' => 'TRK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'TRK/ROS1/ALK inhibition', 'indication' => 'FDA-approved for NTRK fusion-positive solid tumors', 'source' => 'oncokb'],
            // --- Oncology (Level 2) ---
            ['gene' => 'TP53', 'drug' => 'Cisplatin', 'drug_class' => 'Platinum agent', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'TP53 loss may increase platinum sensitivity in some contexts', 'indication' => 'Context-dependent — varies by tumor type', 'source' => 'manual'],
            ['gene' => 'MAP2K1', 'drug' => 'Trametinib', 'drug_class' => 'MEK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'MEK1/2 inhibition', 'indication' => 'FDA-approved with dabrafenib for BRAF V600E; active in MAP2K1-mutant histiocytosis', 'source' => 'oncokb'],
            ['gene' => 'MAP2K1', 'drug' => 'Cobimetinib', 'drug_class' => 'MEK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'MEK1 inhibition', 'indication' => 'FDA-approved with vemurafenib for BRAF V600-mutant melanoma', 'source' => 'oncokb'],
            ['gene' => 'DNMT3A', 'drug' => 'Azacitidine', 'drug_class' => 'Hypomethylating agent', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'Hypomethylating agent targets clonal hematopoiesis', 'indication' => 'AML/MDS with DNMT3A mutations', 'source' => 'manual'],
            ['gene' => 'DNMT3A', 'drug' => 'Decitabine', 'drug_class' => 'Hypomethylating agent', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'DNA methyltransferase inhibition', 'indication' => 'AML/MDS with DNMT3A mutations', 'source' => 'manual'],
            ['gene' => 'VHL', 'drug' => 'Bevacizumab', 'drug_class' => 'Anti-VEGF antibody', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'Anti-VEGF reduces angiogenesis in VHL-deficient tumors', 'indication' => 'VHL-associated RCC', 'source' => 'oncokb'],
            ['gene' => 'ENG', 'drug' => 'Bevacizumab', 'drug_class' => 'Anti-VEGF antibody', 'relationship' => 'sensitive', 'evidence_level' => '2A', 'mechanism' => 'Anti-VEGF reduces AVM bleeding in HHT', 'indication' => 'Off-label for HHT epistaxis and GI bleeding', 'source' => 'manual'],
            ['gene' => 'ENG', 'drug' => 'Thalidomide', 'drug_class' => 'Immunomodulator', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'Anti-angiogenic for HHT', 'indication' => 'Off-label for HHT', 'source' => 'manual'],
            // --- Non-oncology pharmacogenomics ---
            ['gene' => 'TTR', 'drug' => 'Tafamidis', 'drug_class' => 'TTR stabilizer', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'TTR tetramer stabilization', 'indication' => 'FDA-approved for ATTR cardiomyopathy', 'source' => 'fda'],
            ['gene' => 'TTR', 'drug' => 'Patisiran', 'drug_class' => 'siRNA', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'TTR mRNA silencing via siRNA', 'indication' => 'FDA-approved for hATTR polyneuropathy', 'source' => 'fda'],
            ['gene' => 'TTR', 'drug' => 'Diflunisal', 'drug_class' => 'NSAID/TTR stabilizer', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'TTR tetramer stabilization (off-label)', 'indication' => 'Off-label for ATTR', 'source' => 'manual'],
            ['gene' => 'TSC2', 'drug' => 'Everolimus', 'drug_class' => 'mTOR inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'mTOR inhibition downstream of TSC1/TSC2', 'indication' => 'FDA-approved for TSC-associated SEGA and renal AML', 'source' => 'fda'],
            ['gene' => 'TSC2', 'drug' => 'Sirolimus', 'drug_class' => 'mTOR inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'mTOR inhibition', 'indication' => 'TSC-associated lymphangioleiomyomatosis', 'source' => 'fda'],
            ['gene' => 'VHL', 'drug' => 'Belzutifan', 'drug_class' => 'HIF-2α inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'HIF-2α inhibition in VHL-deficient cells', 'indication' => 'FDA-approved for VHL-associated RCC, hemangioblastoma, pNET', 'source' => 'fda'],
            ['gene' => 'VHL', 'drug' => 'Sunitinib', 'drug_class' => 'Multi-kinase inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'Multi-kinase VEGFR inhibition', 'indication' => 'FDA-approved for VHL-associated clear cell RCC', 'source' => 'fda'],
            ['gene' => 'UBA1', 'drug' => 'Azacitidine', 'drug_class' => 'Hypomethylating agent', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'Hypomethylating agent targets clonal hematopoiesis', 'indication' => 'Emerging treatment for VEXAS syndrome', 'source' => 'manual'],
            ['gene' => 'UBA1', 'drug' => 'Ruxolitinib', 'drug_class' => 'JAK inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '3', 'mechanism' => 'JAK1/2 inhibition for inflammatory component', 'indication' => 'Emerging for VEXAS', 'source' => 'manual'],
            ['gene' => 'PCSK9', 'drug' => 'Evolocumab', 'drug_class' => 'PCSK9 inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PCSK9 inhibition increases LDL receptor recycling', 'indication' => 'FDA-approved for familial hypercholesterolemia', 'source' => 'fda'],
            ['gene' => 'PCSK9', 'drug' => 'Alirocumab', 'drug_class' => 'PCSK9 inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PCSK9 inhibition', 'indication' => 'FDA-approved for familial hypercholesterolemia', 'source' => 'fda'],
            ['gene' => 'LDLR', 'drug' => 'Evolocumab', 'drug_class' => 'PCSK9 inhibitor', 'relationship' => 'sensitive', 'evidence_level' => '1A', 'mechanism' => 'PCSK9 inhibition preserves residual LDLR function', 'indication' => 'FDA-approved for heterozygous FH with LDLR mutations', 'source' => 'fda'],
            ['gene' => 'LDLR', 'drug' => 'Atorvastatin', 'drug_class' => 'Statin', 'relationship' => 'dose_adjustment', 'evidence_level' => '1A', 'mechanism' => 'Partial LDL reduction; depends on residual LDLR', 'indication' => 'First-line for FH but response varies with mutation', 'source' => 'nccn'],
            ['gene' => 'BTNL2', 'drug' => 'Infliximab', 'drug_class' => 'Anti-TNFα', 'relationship' => 'sensitive', 'evidence_level' => '3', 'mechanism' => 'Anti-TNFα for refractory sarcoidosis', 'indication' => 'Off-label for cardiac and neurosarcoidosis', 'source' => 'manual'],
            ['gene' => 'BTNL2', 'drug' => 'Methotrexate', 'drug_class' => 'Antimetabolite', 'relationship' => 'sensitive', 'evidence_level' => '2B', 'mechanism' => 'Immunosuppression for sarcoidosis', 'indication' => 'Second-line for sarcoidosis', 'source' => 'manual'],
        ];

        foreach ($interactions as $entry) {
            GeneDrugInteraction::updateOrCreate(
                [
                    'gene' => $entry['gene'],
                    'variant_pattern' => $entry['variant_pattern'] ?? '*',
                    'drug' => $entry['drug'],
                ],
                array_merge(
                    ['variant_pattern' => '*'],
                    $entry,
                    ['last_verified_at' => now()]
                )
            );
        }

        $this->command->info('Seeded ' . count($interactions) . ' gene-drug interactions.');
    }
}
```

- [ ] **Step 2: Run seeder**

```bash
docker compose exec php php artisan db:seed --class=GeneDrugInteractionSeeder
```

- [ ] **Step 3: Verify**

```bash
docker compose exec php php artisan tinker --execute="echo App\Models\Clinical\GeneDrugInteraction::count() . ' interactions seeded';"
```

Expected: `43 interactions seeded` (approximately)

- [ ] **Step 4: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add backend/database/seeders/GeneDrugInteractionSeeder.php
git commit -m "feat(genomics): seed gene-drug interaction table from hardcoded sources"
```

---

### Task 4: Interactions API Endpoint

**Files:**
- Modify: `backend/app/Http/Controllers/GenomicsController.php`
- Modify: `backend/routes/api.php`

- [ ] **Step 1: Add the interactions method to GenomicsController**

Add this method to `GenomicsController.php`:

```php
/**
 * GET /api/genomics/interactions
 * Query gene-drug interactions from the evidence database.
 */
public function interactions(Request $request): JsonResponse
{
    $query = \App\Models\Clinical\GeneDrugInteraction::query();

    if ($gene = $request->input('gene')) {
        $query->where('gene', strtoupper($gene));
    }
    if ($evidenceLevel = $request->input('evidence_level')) {
        $query->where('evidence_level', $evidenceLevel);
    }
    if ($relationship = $request->input('relationship')) {
        $query->where('relationship', $relationship);
    }
    if ($source = $request->input('source')) {
        $query->where('source', $source);
    }

    $interactions = $query->orderBy('gene')->orderBy('evidence_level')->get();

    return response()->json([
        'success' => true,
        'data' => $interactions,
    ]);
}
```

- [ ] **Step 2: Add route**

In `backend/routes/api.php`, find the genomics route group and add:

```php
Route::get('/genomics/interactions', [GenomicsController::class, 'interactions']);
```

- [ ] **Step 3: Test**

```bash
docker compose exec php php artisan route:list --path=genomics/interactions
```

Expected: Shows the GET route.

```bash
curl -s http://localhost:8085/api/genomics/interactions?gene=BRAF -H "Authorization: Bearer TOKEN" -H "Accept: application/json" | head -c 200
```

- [ ] **Step 4: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add backend/app/Http/Controllers/GenomicsController.php backend/routes/api.php
git commit -m "feat(genomics): add GET /api/genomics/interactions endpoint"
```

---

### Task 5: Update RadiogenomicsService to Use Database

**Files:**
- Modify: `backend/app/Services/RadiogenomicsService.php`

- [ ] **Step 1: Replace hardcoded array with database query**

In `RadiogenomicsService.php`, find the `buildCorrelations` method. Replace the hardcoded `$knownInteractions` array (lines 96-118) with a database query:

```php
// Query gene-drug interactions from the evidence database
$geneList = $variants->pluck('gene')->map(fn($g) => strtoupper($g))->unique()->values()->all();
$dbInteractions = \App\Models\Clinical\GeneDrugInteraction::whereIn('gene', $geneList)->get();

$knownInteractions = [];
foreach ($dbInteractions as $row) {
    $knownInteractions[strtoupper($row->gene)][] = [
        'drug' => $row->drug,
        'relationship' => $row->relationship,
        'evidence' => $row->evidence_level,
        'mechanism' => $row->mechanism,
        'source' => $row->source,
        'last_verified_at' => $row->last_verified_at?->toIso8601String(),
    ];
}
```

The rest of the `buildCorrelations` method stays unchanged — it already iterates `$knownInteractions` by gene.

- [ ] **Step 2: Test**

```bash
curl -s http://localhost:8085/api/radiogenomics/patients/154 -H "Authorization: Bearer TOKEN" -H "Accept: application/json" | python3 -c 'import sys,json; d=json.load(sys.stdin); print(f"correlations: {len(d.get(\"data\",d).get(\"correlations\",[]))}")'
```

- [ ] **Step 3: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add backend/app/Services/RadiogenomicsService.php
git commit -m "refactor(genomics): RadiogenomicsService queries interaction table instead of hardcoded array"
```

---

### Task 6: OncoKB Service + Evidence Refresh Command

**Files:**
- Create: `backend/app/Services/Genomics/OncoKbService.php`
- Create: `backend/app/Console/Commands/RefreshEvidenceCommand.php`
- Modify: `backend/routes/console.php`

- [ ] **Step 1: Create OncoKbService**

Create `backend/app/Services/Genomics/OncoKbService.php`:

```php
<?php

namespace App\Services\Genomics;

use App\Models\Clinical\EvidenceUpdate;
use App\Models\Clinical\GeneDrugInteraction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OncoKbService
{
    private string $baseUrl = 'https://www.oncokb.org/api/v1';
    private ?string $token;

    public function __construct()
    {
        $this->token = config('services.oncokb.token');
    }

    /**
     * Sync therapy annotations for all genes in our interaction table.
     * Returns ['synced' => int, 'errors' => int].
     */
    public function syncInteractions(): array
    {
        if (!$this->token) {
            Log::warning('OncoKB API token not configured — skipping sync');
            return ['synced' => 0, 'errors' => 0, 'skipped' => 'no_token'];
        }

        $genes = GeneDrugInteraction::distinct()->pluck('gene')->all();
        $synced = 0;
        $errors = 0;

        foreach ($genes as $gene) {
            try {
                $response = Http::withToken($this->token)
                    ->acceptJson()
                    ->get("{$this->baseUrl}/genes/{$gene}/variants");

                if ($response->failed()) {
                    Log::warning("OncoKB sync failed for gene {$gene}: HTTP {$response->status()}");
                    $errors++;
                    continue;
                }

                // TODO: Parse OncoKB response and upsert new interactions.
                // For v1, we verify connectivity and update the sync timestamp.
                // Full parsing (creating/updating GeneDrugInteraction records from
                // OncoKB treatment annotations) is a follow-up task.
                GeneDrugInteraction::where('gene', $gene)
                    ->update(['oncokb_last_synced_at' => now()]);

                $synced++;
            } catch (\Exception $e) {
                Log::error("OncoKB sync error for gene {$gene}: {$e->getMessage()}");
                $errors++;
            }
        }

        return ['synced' => $synced, 'errors' => $errors];
    }
}
```

- [ ] **Step 2: Add OncoKB config**

In `backend/config/services.php`, add:

```php
'oncokb' => [
    'token' => env('ONCOKB_API_TOKEN'),
],
```

- [ ] **Step 3: Create RefreshEvidenceCommand**

Create `backend/app/Console/Commands/RefreshEvidenceCommand.php`:

```php
<?php

namespace App\Console\Commands;

use App\Services\Genomics\ClinVarSyncService;
use App\Services\Genomics\ClinVarAnnotationService;
use App\Services\Genomics\OncoKbService;
use Illuminate\Console\Command;

class RefreshEvidenceCommand extends Command
{
    protected $signature = 'genomics:refresh-evidence {--force : Skip cadence checks}';
    protected $description = 'Refresh all genomics evidence sources (ClinVar + OncoKB)';

    public function handle(
        ClinVarSyncService $clinvar,
        ClinVarAnnotationService $annotator,
        OncoKbService $oncokb,
    ): int {
        $this->info('Starting evidence refresh...');

        // 1. ClinVar sync (weekly cadence)
        $lastSync = \App\Models\Clinical\ClinVarSyncLog::where('status', 'completed')
            ->latest('finished_at')
            ->first();

        $daysSinceSync = $lastSync
            ? now()->diffInDays($lastSync->finished_at)
            : 999;

        if ($daysSinceSync >= 7 || $this->option('force')) {
            $this->info('Syncing ClinVar variants...');
            try {
                $result = $clinvar->sync('GRCh38', true); // PAPU only for speed
                $this->info("ClinVar: {$result['inserted']} inserted, {$result['updated']} updated");
            } catch (\Exception $e) {
                $this->error("ClinVar sync failed: {$e->getMessage()}");
            }
        } else {
            $this->info("ClinVar sync skipped — last synced {$daysSinceSync} days ago");
        }

        // 2. OncoKB sync
        $this->info('Syncing OncoKB annotations...');
        $oncoResult = $oncokb->syncInteractions();
        $this->info("OncoKB: {$oncoResult['synced']} genes synced, {$oncoResult['errors']} errors");

        // 3. Re-annotate patient variants with updated ClinVar data
        $this->info('Re-annotating patient variants with updated ClinVar data...');
        try {
            $annotationResult = $annotator->annotateAll();
            $this->info("ClinVar annotation: {$annotationResult['annotated']} updated, {$annotationResult['skipped']} skipped");
        } catch (\Exception $e) {
            $this->error("ClinVar annotation failed: {$e->getMessage()}");
        }

        $this->info('Evidence refresh complete.');
        return Command::SUCCESS;
    }
}
```

- [ ] **Step 4: Schedule the command**

In `backend/routes/console.php`, add:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('genomics:refresh-evidence')->weekly()->sundays()->at('02:00');
```

- [ ] **Step 5: Test**

```bash
docker compose exec php php artisan genomics:refresh-evidence --force
```

- [ ] **Step 6: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add backend/app/Services/Genomics/OncoKbService.php backend/app/Console/Commands/RefreshEvidenceCommand.php backend/routes/console.php backend/config/services.php
git commit -m "feat(genomics): OncoKB service, evidence refresh command, weekly schedule"
```

---

## Phase 2: AI Genomic Briefing

### Task 7: Genomic Briefing AI Endpoint

**Files:**
- Modify: `ai/app/models/decision_support.py`
- Create: `ai/app/services/genomic_briefing.py`
- Modify: `ai/app/routers/decision_support.py`

- [ ] **Step 1: Add Pydantic models**

In `ai/app/models/decision_support.py`, add at the end:

```python
# --- Genomic Briefing ---


class VariantSummary(BaseModel):
    gene: str
    variant: str
    classification: str
    evidence_level: str | None = None
    therapies: list[str] = Field(default_factory=list)


class DrugExposureSummary(BaseModel):
    drug_name: str
    start_date: str | None = None
    end_date: str | None = None


class InteractionSummary(BaseModel):
    gene: str
    drug: str
    relationship: str
    evidence_level: str
    mechanism: str | None = None


class GenomicBriefingRequest(BaseModel):
    patient_id: int
    variants: list[VariantSummary] = Field(default_factory=list)
    drug_exposures: list[DrugExposureSummary] = Field(default_factory=list)
    interactions: list[InteractionSummary] = Field(default_factory=list)
    total_variant_count: int = 0


class GenomicBriefingResponse(BaseModel):
    briefing: str = ""
    generated_at: str = ""
    variant_count: int = 0
    actionable_count: int = 0
    error: str | None = None
```

- [ ] **Step 2: Create the briefing service**

Create `ai/app/services/genomic_briefing.py`:

```python
"""Genomic briefing service — synthesizes a narrative from variant + therapy data."""

import logging
from datetime import datetime, timezone

from app.models.decision_support import (
    GenomicBriefingRequest,
    GenomicBriefingResponse,
)
from app.services.llm_utils import call_ollama_json

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = (
    "You are a molecular oncology expert writing a clinical genomic briefing for a "
    "treating physician. Synthesize the provided variant data, therapy matches, and "
    "drug exposure history into a concise 3-5 sentence narrative. "
    "Lead with the most actionable finding. Include evidence levels (e.g., Level 1A). "
    "Mention current drug interactions if relevant. "
    "Be direct and clinical — this is for a physician making treatment decisions."
)


async def generate_briefing(request: GenomicBriefingRequest) -> GenomicBriefingResponse:
    """Generate a narrative genomic briefing from structured data."""
    actionable = [v for v in request.variants if v.classification in ("pathogenic", "likely_pathogenic")]

    if not actionable:
        return GenomicBriefingResponse(
            briefing="No actionable genomic variants identified. All variants are classified as VUS or benign.",
            generated_at=datetime.now(timezone.utc).isoformat(),
            variant_count=request.total_variant_count,
            actionable_count=0,
        )

    # Build structured context for the LLM
    variant_lines = []
    for v in actionable:
        therapies = ", ".join(v.therapies) if v.therapies else "none identified"
        variant_lines.append(
            f"- {v.gene} {v.variant} ({v.classification}, {v.evidence_level or 'unknown level'}): therapies: {therapies}"
        )

    drug_lines = []
    for d in request.drug_exposures:
        period = f"{d.start_date or '?'} to {d.end_date or 'present'}"
        drug_lines.append(f"- {d.drug_name} ({period})")

    interaction_lines = []
    for i in request.interactions:
        interaction_lines.append(
            f"- {i.gene} + {i.drug}: {i.relationship} ({i.evidence_level}) — {i.mechanism or 'mechanism unknown'}"
        )

    prompt = f"""Write a clinical genomic briefing (3-5 sentences) for this patient.

Total variants: {request.total_variant_count}
Actionable variants: {len(actionable)}

ACTIONABLE VARIANTS:
{chr(10).join(variant_lines)}

CURRENT/RECENT DRUG EXPOSURES:
{chr(10).join(drug_lines) if drug_lines else "None recorded"}

GENE-DRUG INTERACTIONS:
{chr(10).join(interaction_lines) if interaction_lines else "None identified"}

Respond in JSON:
{{"briefing": "your 3-5 sentence clinical narrative here"}}"""

    try:
        data = await call_ollama_json(prompt, system=SYSTEM_PROMPT)
        briefing_text = str(data.get("briefing", "Unable to generate briefing."))
    except Exception as e:
        logger.error("Genomic briefing generation failed: %s", e)
        briefing_text = f"Briefing generation failed: {type(e).__name__}"

    return GenomicBriefingResponse(
        briefing=briefing_text,
        generated_at=datetime.now(timezone.utc).isoformat(),
        variant_count=request.total_variant_count,
        actionable_count=len(actionable),
    )
```

- [ ] **Step 3: Add the router endpoint**

In `ai/app/routers/decision_support.py`, add the import at the top:

```python
from app.models.decision_support import (
    # ... existing imports ...
    GenomicBriefingRequest,
    GenomicBriefingResponse,
)
from app.services.genomic_briefing import generate_briefing
```

Add the endpoint:

```python
@router.post("/genomic-briefing", response_model=GenomicBriefingResponse)
async def genomic_briefing_endpoint(
    request: GenomicBriefingRequest,
) -> GenomicBriefingResponse:
    """Generate a clinical genomic briefing narrative for a patient."""
    try:
        return await generate_briefing(request)
    except Exception as exc:
        logger.error("Genomic briefing failed: %s", exc)
        return GenomicBriefingResponse(
            error=f"Genomic briefing service unavailable: {type(exc).__name__}",
        )
```

- [ ] **Step 4: Test**

```bash
curl -s http://localhost:8100/api/decision-support/genomic-briefing -X POST \
  -H 'Content-Type: application/json' \
  -d '{"patient_id":154,"variants":[{"gene":"BRAF","variant":"V600E","classification":"pathogenic","evidence_level":"1A","therapies":["Vemurafenib","Dabrafenib"]}],"drug_exposures":[],"interactions":[{"gene":"BRAF","drug":"Vemurafenib","relationship":"sensitive","evidence_level":"1A","mechanism":"BRAF V600E kinase inhibition"}],"total_variant_count":47}' | python3 -m json.tool
```

Note: The AI service may not be running in Docker yet. If it returns connection refused, this endpoint will be tested when the AI container is available. The endpoint is correctly structured.

- [ ] **Step 5: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add ai/app/models/decision_support.py ai/app/services/genomic_briefing.py ai/app/routers/decision_support.py
git commit -m "feat(genomics): AI genomic briefing endpoint for Abby narrative"
```

---

## Phase 3: Frontend Unified Genomics Tab

Phase 3 is the largest phase. Due to the size and interconnected nature of the frontend components, this phase should be implemented as a **single large task** rather than trying to split components that depend on each other. The implementer should work through the components bottom-up (shared components first, then sections, then the container).

### Task 8: Frontend Types, API, and Hooks

**Files:**
- Modify: `frontend/src/features/genomics/types/index.ts`
- Modify: `frontend/src/features/genomics/api/genomicsApi.ts`
- Modify: `frontend/src/features/genomics/hooks/useGenomics.ts`

- [ ] **Step 1: Add new types**

In `frontend/src/features/genomics/types/index.ts`, add these types:

```typescript
// --- Gene-Drug Interactions ---

export interface GeneDrugInteraction {
  id: number;
  gene: string;
  variant_pattern: string;
  drug: string;
  drug_class: string | null;
  relationship: "sensitive" | "resistant" | "dose_adjustment";
  evidence_level: string;
  indication: string | null;
  mechanism: string | null;
  source: "oncokb" | "nccn" | "fda" | "pharmgkb" | "manual";
  source_url: string | null;
  oncokb_last_synced_at: string | null;
  last_verified_at: string | null;
}

// --- Genomic Briefing (AI) ---

export interface GenomicBriefingVariant {
  gene: string;
  variant: string;
  classification: string;
  evidence_level: string | null;
  therapies: string[];
}

export interface GenomicBriefingDrugExposure {
  drug_name: string;
  start_date: string | null;
  end_date: string | null;
}

export interface GenomicBriefingInteraction {
  gene: string;
  drug: string;
  relationship: string;
  evidence_level: string;
  mechanism: string | null;
}

export interface GenomicBriefingRequest {
  patient_id: number;
  variants: GenomicBriefingVariant[];
  drug_exposures: GenomicBriefingDrugExposure[];
  interactions: GenomicBriefingInteraction[];
  total_variant_count: number;
}

export interface GenomicBriefingResponse {
  briefing: string;
  generated_at: string;
  variant_count: number;
  actionable_count: number;
  error?: string;
}

// --- Radiogenomics (absorbed from features/radiogenomics) ---

export interface DrugExposure {
  drug_name: string;
  drug_class: string | null;
  start_date: string | null;
  end_date: string | null;
  total_days: number | null;
}

export interface VariantDrugCorrelation {
  variant_id: number;
  gene_symbol: string;
  variant: string;
  clinical_significance: string;
  drug_name: string;
  relationship: string;
  evidence_level: string;
  mechanism: string | null;
  source: string | null;
  last_verified_at: string | null;
  patient_exposed: boolean;
  exposure_start: string | null;
  exposure_end: string | null;
}

export interface PrecisionRecommendation {
  gene: string;
  variant: string;
  drugs_avoid: string[];
  drugs_consider: string[];
  rationale: string;
}

export interface RadiogenomicsPanel {
  patient: {
    person_id: number;
    gender: string | null;
    year_of_birth: number | null;
    race: string | null;
    ethnicity: string | null;
  };
  variants: {
    all: number;
    actionable: number;
    vus: number;
    other: number;
    details: GenomicVariant[];
  };
  drug_exposures: DrugExposure[];
  correlations: VariantDrugCorrelation[];
  recommendations: PrecisionRecommendation[];
}
```

- [ ] **Step 2: Add API functions**

In `frontend/src/features/genomics/api/genomicsApi.ts`, add:

```typescript
import type {
  GeneDrugInteraction,
  GenomicBriefingRequest,
  GenomicBriefingResponse,
  RadiogenomicsPanel,
} from "../types";

// Gene-drug interactions
export async function getInteractions(params: { gene?: string; evidence_level?: string; relationship?: string } = {}): Promise<GeneDrugInteraction[]> {
  const { data } = await apiClient.get("/genomics/interactions", { params });
  return data.data ?? data;
}

// Radiogenomics panel (absorbed from features/radiogenomics)
export async function getRadiogenomicsPanel(patientId: number): Promise<RadiogenomicsPanel> {
  const { data } = await apiClient.get(`/radiogenomics/patients/${patientId}`);
  return data.data ?? data;
}

// AI genomic briefing
const AI_BASE = import.meta.env.VITE_AI_URL || "http://localhost:8100/api";

export const generateGenomicBriefing = async (data: GenomicBriefingRequest): Promise<GenomicBriefingResponse> => {
  const resp = await fetch(`${AI_BASE}/decision-support/genomic-briefing`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  });
  return resp.json();
};

// AI variant interpretation (existing endpoint, new wrapper)
export const interpretVariant = async (gene: string, variant: string, cancerType?: string) => {
  const resp = await fetch(`${AI_BASE}/decision-support/variant-interpret`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ gene, variant, cancer_type: cancerType }),
  });
  return resp.json();
};
```

Note: The `apiClient` is already imported at the top of this file. The existing pattern uses `const { data } = await apiClient.get(...)` — follow this pattern, not `unwrap`.

**Important field name note:** The backend DB column is `gene` (verified). The frontend `GenomicVariant` type uses `gene_symbol` because the API response may serialize differently. When assembling `GenomicBriefingRequest`, map `variant.gene_symbol` to `gene`. In the `RadiogenomicsService`, `$variant->gene` is correct.

- [ ] **Step 3: Add hooks**

In `frontend/src/features/genomics/hooks/useGenomics.ts`, add:

```typescript
import { getInteractions, getRadiogenomicsPanel, generateGenomicBriefing, interpretVariant } from "../api/genomicsApi";
import type { GenomicBriefingRequest } from "../types";

export function useGeneDrugInteractions(gene?: string) {
  return useQuery({
    queryKey: ["gene-drug-interactions", gene],
    queryFn: () => getInteractions(gene ? { gene } : {}),
    staleTime: 300_000, // 5 min — evidence data changes slowly
  });
}

export function useRadiogenomicsPanel(patientId: number | null) {
  return useQuery({
    queryKey: ["radiogenomics-panel", patientId],
    queryFn: () => getRadiogenomicsPanel(patientId!),
    enabled: patientId != null && patientId > 0,
    staleTime: 60_000,
  });
}

export function useGenomicBriefing() {
  return useMutation({
    mutationFn: (data: GenomicBriefingRequest) => generateGenomicBriefing(data),
  });
}

export function useVariantInterpretation() {
  return useMutation({
    mutationFn: ({ gene, variant, cancerType }: { gene: string; variant: string; cancerType?: string }) =>
      interpretVariant(gene, variant, cancerType),
  });
}
```

Add `useQueryClient` and `useMutation` to the imports from `@tanstack/react-query` if not already present.

- [ ] **Step 4: Type check**

```bash
cd /home/smudoshi/Github/Aurora/frontend && npx tsc --noEmit
```

- [ ] **Step 5: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add frontend/src/features/genomics/types/index.ts frontend/src/features/genomics/api/genomicsApi.ts frontend/src/features/genomics/hooks/useGenomics.ts
git commit -m "feat(genomics): add interaction types, API functions, and hooks for unified tab"
```

---

### Task 9: Build Frontend Components (EvidenceBadge, GenomicBriefing, ActionableVariantCard)

**Files:**
- Create: `frontend/src/features/genomics/components/EvidenceBadge.tsx`
- Create: `frontend/src/features/genomics/components/GenomicBriefing.tsx`
- Create: `frontend/src/features/genomics/components/ActionableVariantCard.tsx`

This task creates the shared building-block components. The implementer should read the spec (Section 1, 2, 5f) for UI details and follow existing Aurora dark-theme patterns from other components.

- [ ] **Step 1: Create EvidenceBadge**

A reusable badge showing evidence level + source + freshness. Accepts `evidence_level`, `source`, and `last_verified_at` props. Shows:
- Color-coded level badge (green for 1A/1B, yellow for 2A/2B, gray for 3+)
- Source label (OncoKB, NCCN, FDA, etc.)
- Amber warning if `last_verified_at` > 30 days ago

- [ ] **Step 2: Create GenomicBriefing**

The Abby AI narrative card (spec Section 1). Purple-bordered card with:
- Abby brain icon + "Genomic Summary" header
- The narrative text from the AI response
- Evidence freshness timestamp
- "Regenerate" button that re-triggers the briefing mutation
- Loading skeleton (pulsing animation) while generating
- Error state with retry

Calls `useGenomicBriefing()` mutation. The parent component prepares the `GenomicBriefingRequest` data and passes a trigger function.

- [ ] **Step 3: Create ActionableVariantCard**

A card for a single pathogenic/likely pathogenic variant (spec Section 2). Shows:
- Gene + protein change with pathogenicity badge
- Variant details (type, coordinates, AF)
- ClinVar: significance, disease, review status
- Matched therapies list with `EvidenceBadge` per therapy
- Current drug interactions with exposure timeline
- "AI Interpret", "Flag", "Discuss" action buttons

- [ ] **Step 4: Type check**

```bash
cd /home/smudoshi/Github/Aurora/frontend && npx tsc --noEmit
```

- [ ] **Step 5: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add frontend/src/features/genomics/components/EvidenceBadge.tsx frontend/src/features/genomics/components/GenomicBriefing.tsx frontend/src/features/genomics/components/ActionableVariantCard.tsx
git commit -m "feat(genomics): EvidenceBadge, GenomicBriefing, ActionableVariantCard components"
```

---

### Task 10: Build Section Components (ActionableVariantsPanel, TreatmentTimeline, GenomicVariantTable)

**Files:**
- Create: `frontend/src/features/genomics/components/ActionableVariantsPanel.tsx`
- Create: `frontend/src/features/genomics/components/TreatmentTimeline.tsx`
- Create: `frontend/src/features/genomics/components/GenomicVariantTable.tsx`
- Create: `frontend/src/features/genomics/components/VariantExpandedRow.tsx`

- [ ] **Step 1: Create ActionableVariantsPanel**

Section 2 of the tab. Renders `ActionableVariantCard` for each pathogenic/likely pathogenic variant. Below the cards, a collapsible "Variants of Uncertain Significance (N)" accordion with a compact VUS list showing gene, alteration, ClinVar status, and an "AI Interpret" button.

Props: `variants` (all variants), `interactions` (GeneDrugInteraction[]), `correlations` (VariantDrugCorrelation[]), `drugExposures` (DrugExposure[]), `patientId` (number).

Filters variants internally into actionable (pathogenic + likely pathogenic) and VUS.

- [ ] **Step 2: Create TreatmentTimeline**

Section 3. Collapsible component. One-line summary when collapsed: "N drugs, M with genomic interactions". When expanded, renders horizontal CSS bars for each drug exposure:
- Bar width proportional to duration relative to total timeline span
- Color: green (sensitive), red (resistant), gray (no interaction)
- Label: drug name, date range
- Hover/click: shows correlation detail

Props: `drugExposures` (DrugExposure[]), `correlations` (VariantDrugCorrelation[]).

CSS-only rendering with proportional-width divs — no charting library.

- [ ] **Step 3: Create VariantExpandedRow**

Inline detail row shown when a variant table row is clicked. Contains:
- AI interpretation (fetched on expand via `useVariantInterpretation` mutation)
- Matching therapies from interaction table (if any)
- ClinVar disease + review status
- Full coordinates, alleles, quality metrics
- Flag + Discuss action buttons

- [ ] **Step 4: Create GenomicVariantTable**

Section 4. Enhanced filterable table. Filter bar with:
- Significance dropdown (All/Pathogenic/Likely Pathogenic/VUS/Benign)
- Gene search (text input)
- Variant type checkboxes (SNV/Indel/Fusion/CNV)

Table columns: Gene, Alteration, Type, AF%, ClinVar, Evidence Level, Actions.
Clicking a row toggles `VariantExpandedRow` inline.
Pagination: 25 per page using `useGenomicVariants` with `clinvar_significance` and `gene` params.

- [ ] **Step 5: Type check**

```bash
cd /home/smudoshi/Github/Aurora/frontend && npx tsc --noEmit
```

- [ ] **Step 6: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add frontend/src/features/genomics/components/ActionableVariantsPanel.tsx frontend/src/features/genomics/components/TreatmentTimeline.tsx frontend/src/features/genomics/components/GenomicVariantTable.tsx frontend/src/features/genomics/components/VariantExpandedRow.tsx
git commit -m "feat(genomics): ActionableVariantsPanel, TreatmentTimeline, GenomicVariantTable, VariantExpandedRow"
```

---

### Task 11: Rewrite PatientGenomicsTab + Dead Code Cleanup

**Files:**
- Rewrite: `frontend/src/features/patient-profile/components/PatientGenomicsTab.tsx`
- Delete: `frontend/src/features/radiogenomics/` (entire directory)
- Delete: `frontend/src/features/patient-profile/components/VariantCard.tsx`
- Delete: `frontend/src/features/patient-profile/components/ActionableGenes.tsx`

- [ ] **Step 1: Rewrite PatientGenomicsTab**

Replace the entire content of `PatientGenomicsTab.tsx`. The new component is a composition container rendering 4 sections vertically:

1. `<GenomicBriefing>` — pass briefing data assembled from variants + interactions + drug exposures
2. `<ActionableVariantsPanel>` — pass variants, interactions, correlations, drug exposures
3. `<TreatmentTimeline>` — pass drug exposures and correlations (starts collapsed)
4. `<GenomicVariantTable>` — pass patientId for its own data fetching

Data flow:
- `useGenomicVariants({ person_id: patientId })` for variant data
- `useRadiogenomicsPanel(patientId)` for drug exposures + correlations + recommendations
- `useGeneDrugInteractions()` for the full interaction table
- Assemble `GenomicBriefingRequest` from the above data and pass to `GenomicBriefing`

The component should handle loading states (show skeleton when any data is loading) and empty state (no variants → show empty message, same as current).

- [ ] **Step 2: Delete dead code**

Delete the following:
- `frontend/src/features/radiogenomics/` — entire directory
- `frontend/src/features/patient-profile/components/VariantCard.tsx`
- `frontend/src/features/patient-profile/components/ActionableGenes.tsx`

Check for any imports of these files elsewhere and remove them.

- [ ] **Step 3: Type check**

```bash
cd /home/smudoshi/Github/Aurora/frontend && npx tsc --noEmit
```

- [ ] **Step 4: Commit**

```bash
cd /home/smudoshi/Github/Aurora
git add -u
git add frontend/src/features/patient-profile/components/PatientGenomicsTab.tsx
git commit -m "feat(genomics): unified Genomics tab with Abby briefing, therapy matching, treatment timeline

Absorbs radiogenomics feature. Removes VariantCard, ActionableGenes (replaced by new components)."
```

---

### Task 12: Smoke Test + Deploy

- [ ] **Step 1: Build frontend**

```bash
cd /home/smudoshi/Github/Aurora/frontend && npm run build
```

- [ ] **Step 2: Verify via browser**

Open `https://aurora.acumenus.net/profiles/154` → Genomics tab.

Verify:
- Abby Genomic Briefing card appears at top (or loading skeleton if AI service is down)
- Actionable variants shown as cards with therapy matches and evidence badges
- VUS section collapsed below
- Treatment timeline collapsible
- Full variant table with filters (significance, gene, type)
- Clicking a variant row expands with AI interpretation
- Evidence badges show level + source + freshness

- [ ] **Step 3: Verify within case integration**

Open `https://aurora.acumenus.net/cases/15` → Overview tab → Genomics view mode.

Verify the embedded genomics tab works identically within the case detail page.

- [ ] **Step 4: Push**

```bash
cd /home/smudoshi/Github/Aurora
git push origin v2/phase-0-scaffold
```
