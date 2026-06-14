<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StorePhenotypeFeatureRequest;
use App\Models\DiagnosticOdyssey;
use App\Models\PhenotypeFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PhenotypeFeatureController extends Controller
{
    public function index(int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::findOrFail($odyssey);

        return ApiResponse::success(
            $model->phenotypeFeatures()->orderBy('hpo_id')->get()
        );
    }

    public function store(StorePhenotypeFeatureRequest $request, int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::findOrFail($odyssey);

        $feature = $model->phenotypeFeatures()->create([
            ...$request->validated(),
            'excluded' => $request->boolean('excluded'),
            'recorded_by' => $request->user()->id,
        ]);

        return ApiResponse::success($feature, 'Created', 201);
    }

    public function destroy(Request $request, int $phenotype): JsonResponse
    {
        $feature = PhenotypeFeature::findOrFail($phenotype);

        if ($feature->recorded_by !== $request->user()->id && ! $request->user()->hasRole('admin')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $feature->delete();

        return ApiResponse::success(null, 'Deleted', 200);
    }
}
