<?php

namespace App\Http\Controllers\Mme;

use App\Http\Controllers\Controller;
use App\Http\Requests\MmeMatchRequest;
use App\Services\Matchmaker\MmeMatchService;
use Illuminate\Http\JsonResponse;

class MatchController extends Controller
{
    public function __construct(private MmeMatchService $service) {}

    public function match(MmeMatchRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->resultsEnvelope($request->validated()),
            200,
            ['Content-Type' => 'application/vnd.ga4gh.matchmaker.v1.0+json'],
        );
    }
}
