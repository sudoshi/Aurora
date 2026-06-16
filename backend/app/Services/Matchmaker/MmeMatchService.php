<?php

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
            ->filter(fn ($f) => ($f['observed'] ?? 'yes') !== 'no')
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
