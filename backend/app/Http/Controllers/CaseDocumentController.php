<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\CaseDocument;
use App\Models\ClinicalCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CaseDocumentController extends Controller
{
    /**
     * GET /api/cases/{case}/documents
     * List documents for a case.
     */
    public function index(int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $documents = CaseDocument::where('case_id', $case)
            ->with('uploader')
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($documents, 'Documents retrieved');
    }

    /**
     * POST /api/cases/{case}/documents
     * Upload a document to a case.
     */
    public function store(Request $request, int $case): JsonResponse
    {
        $clinicalCase = ClinicalCase::find($case);

        if (! $clinicalCase) {
            return ApiResponse::error('Case not found', 404);
        }

        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
            'document_type' => 'required|string|in:pathology_report,radiology,genomic,clinical_note,external,other',
            'description' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $path = $file->store('case-documents/'.$case, 'local');

        $document = CaseDocument::create([
            'case_id' => $case,
            'uploaded_by' => $request->user()->id,
            'filename' => $file->getClientOriginalName(),
            'filepath' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'document_type' => $request->input('document_type'),
            'description' => $request->input('description'),
        ]);

        return ApiResponse::success(
            $document->load('uploader'),
            'Document uploaded',
            201,
        );
    }

    /**
     * DELETE /api/documents/{document}
     * Delete a document.
     */
    public function destroy(int $document): JsonResponse
    {
        $doc = CaseDocument::find($document);

        if (! $doc) {
            return ApiResponse::error('Document not found', 404);
        }

        // Delete the file from storage
        if (Storage::disk('local')->exists($doc->filepath)) {
            Storage::disk('local')->delete($doc->filepath);
        }

        $doc->delete();

        return ApiResponse::success(null, 'Document deleted');
    }
}
