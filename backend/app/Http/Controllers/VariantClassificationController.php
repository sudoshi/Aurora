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
        $model = VariantClassification::with(['criteria', 'variant:id,gene'])->findOrFail($classification);

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
