<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StoreDiscussionRequest;
use App\Http\Requests\UploadAttachmentsRequest;
use App\Services\CaseDiscussionService;
use Illuminate\Http\JsonResponse;

class CaseDiscussionController extends Controller
{
    public function __construct(
        private readonly CaseDiscussionService $caseDiscussionService,
    ) {}

    /**
     * Get discussions for a case.
     */
    public function index(int $caseId): JsonResponse
    {
        $discussions = $this->caseDiscussionService->listForCase($caseId);

        return ApiResponse::success($discussions);
    }

    /**
     * Store a new discussion message.
     */
    public function store(StoreDiscussionRequest $request, int $caseId): JsonResponse
    {
        $discussion = $this->caseDiscussionService->create(
            $caseId,
            $request->validated(),
            $request->user(),
        );

        return ApiResponse::success($discussion, 'Discussion created successfully.', 201);
    }

    /**
     * Upload attachments for a case.
     */
    public function uploadAttachments(UploadAttachmentsRequest $request, int $caseId): JsonResponse
    {
        $attachments = $this->caseDiscussionService->uploadAttachments(
            $caseId,
            $request->file('files'),
            $request->user(),
        );

        return ApiResponse::success($attachments, 'Attachments uploaded successfully.', 201);
    }
}
