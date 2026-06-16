<?php

namespace App\Http\Controllers\Beacon;

use App\Http\Controllers\Controller;
use App\Services\Beacon\BeaconService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeaconController extends Controller
{
    public function __construct(private BeaconService $beacon) {}

    public function index(): JsonResponse
    {
        return response()->json($this->beacon->info());
    }

    public function info(): JsonResponse
    {
        return response()->json($this->beacon->info());
    }

    public function serviceInfo(): JsonResponse
    {
        return response()->json($this->beacon->serviceInfo());
    }

    public function configuration(): JsonResponse
    {
        return response()->json($this->beacon->configuration());
    }

    public function map(): JsonResponse
    {
        return response()->json($this->beacon->map());
    }

    public function entryTypes(): JsonResponse
    {
        return response()->json($this->beacon->entryTypes());
    }

    public function filteringTerms(): JsonResponse
    {
        return response()->json($this->beacon->filteringTerms());
    }

    public function gVariants(Request $request): JsonResponse
    {
        $requested = (string) $request->query('requestedGranularity', config('services.beacon.default_granularity', 'boolean'));
        // PRIVACY: never expose record/aggregated — clamp to boolean|count only.
        $granularity = in_array($requested, ['boolean', 'count'], true) ? $requested : 'boolean';

        return response()->json($this->beacon->queryGVariants($request->query(), $granularity));
    }
}
