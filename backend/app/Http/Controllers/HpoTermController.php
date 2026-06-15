<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Services\RareDisease\HpoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HpoTermController extends Controller
{
    public function __construct(private HpoService $hpo) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:100',
            'limit' => 'sometimes|integer|min:1|max:25',
        ]);

        return ApiResponse::success(
            $this->hpo->search($validated['q'], $validated['limit'] ?? 10)
        );
    }
}
