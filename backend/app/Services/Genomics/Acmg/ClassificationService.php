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
