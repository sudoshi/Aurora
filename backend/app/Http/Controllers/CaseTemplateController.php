<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\CaseTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseTemplateController extends Controller
{
    /**
     * GET /case-templates
     * List all templates, optionally filtered by specialty or case_type.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CaseTemplate::query();

        if ($request->has('specialty')) {
            $query->where('specialty', $request->input('specialty'));
        }

        if ($request->has('case_type')) {
            $query->where('case_type', $request->input('case_type'));
        }

        $templates = $query->orderBy('specialty')->orderBy('name')->get();

        return ApiResponse::success($templates, 'Case templates retrieved');
    }

    /**
     * GET /case-templates/{slug}
     */
    public function show(string $slug): JsonResponse
    {
        $template = CaseTemplate::where('slug', $slug)->first();

        if (! $template) {
            return ApiResponse::error('Template not found', 404);
        }

        return ApiResponse::success($template, 'Case template retrieved');
    }
}
