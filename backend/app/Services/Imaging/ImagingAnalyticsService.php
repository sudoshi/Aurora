<?php

namespace App\Services\Imaging;

use App\Models\Clinical\ImagingFeature;
use App\Models\Clinical\ImagingStudy;
use Illuminate\Support\Facades\DB;

/**
 * Population-level imaging analytics aggregations.
 */
class ImagingAnalyticsService
{
    public function population(?string $modality): array
    {
        $query = ImagingStudy::query();
        if ($modality) {
            $query->where('modality', $modality);
        }

        $totalStudies = $query->count();
        $totalPatients = (clone $query)->distinct()->count('patient_id');

        $byModality = ImagingStudy::select(
            'modality',
            DB::raw('count(*) as n'),
            DB::raw('count(distinct patient_id) as unique_persons')
        )
            ->whereNotNull('modality')
            ->when($modality, fn ($q) => $q->where('modality', $modality))
            ->groupBy('modality')
            ->orderByDesc('n')
            ->get()
            ->map(fn (ImagingStudy $row) => [
                'modality' => $row->modality,
                'n' => (int) $row->n,
                'unique_persons' => (int) $row->unique_persons,
            ])
            ->values();

        $byBodyPart = ImagingStudy::select('body_part', DB::raw('count(*) as n'))
            ->whereNotNull('body_part')
            ->when($modality, fn ($q) => $q->where('modality', $modality))
            ->groupBy('body_part')
            ->orderByDesc('n')
            ->get()
            ->map(fn (ImagingStudy $row) => [
                'body_part_examined' => $row->body_part,
                'n' => (int) $row->n,
            ])
            ->values();

        return [
            'total_studies' => $totalStudies,
            'total_patients' => $totalPatients,
            'by_modality' => $byModality,
            'by_body_part' => $byBodyPart,
            'top_features' => ImagingFeature::select('feature_name', 'feature_type', DB::raw('count(*) as n'))
                ->when($modality, function ($q) use ($modality) {
                    $studyIds = ImagingStudy::where('modality', $modality)->pluck('id');

                    return $q->whereIn('imaging_study_id', $studyIds);
                })
                ->groupBy('feature_name', 'feature_type')
                ->orderByDesc('n')
                ->limit(10)
                ->get()
                ->map(fn (ImagingFeature $row) => [
                    'feature_name' => $row->feature_name,
                    'feature_type' => $row->feature_type,
                    'n' => (int) $row->n,
                ])
                ->values(),
            'modality_distribution' => $byModality->mapWithKeys(fn (array $row) => [$row['modality'] => $row['n']]),
            'body_part_distribution' => $byBodyPart->mapWithKeys(fn (array $row) => [$row['body_part_examined'] => $row['n']]),
            'temporal_distribution' => [],
        ];
    }
}
