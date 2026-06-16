<?php

namespace App\Http\Controllers\Mme;

use App\Http\Controllers\Controller;
use App\Http\Helpers\ApiResponse;
use App\Models\DiagnosticOdyssey;
use App\Models\MmeMatch;
use App\Services\Matchmaker\MmeOutboundService;
use Illuminate\Http\JsonResponse;

class MmeSearchController extends Controller
{
    public function __construct(private MmeOutboundService $outbound) {}

    public function search(int $odyssey): JsonResponse
    {
        $n = $this->outbound->searchForOdyssey(DiagnosticOdyssey::findOrFail($odyssey));

        return ApiResponse::success(['stored' => $n]);
    }

    public function list(int $odyssey): JsonResponse
    {
        return ApiResponse::success(MmeMatch::where('odyssey_id', $odyssey)->orderByDesc('score')->get());
    }
}
