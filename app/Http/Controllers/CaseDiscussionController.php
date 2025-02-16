<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CaseDiscussionController extends Controller
{
    /**
     * Get discussions for a case
     */
    public function index($caseId)
    {
        // TODO: Fetch discussions from database
        // For now, return sample data
        return response()->json([
            [
                'id' => 1,
                'content' => 'Initial assessment completed.',
                'user' => [
                    'id' => 1,
                    'name' => 'Dr. Smith',
                    'role' => 'Primary Care'
                ],
                'created_at' => now()->subHours(2),
                'attachments' => []
            ],
            [
                'id' => 2,
                'content' => 'Lab results reviewed. Everything looks normal.',
                'user' => [
                    'id' => 2,
                    'name' => 'Dr. Johnson',
                    'role' => 'Specialist'
                ],
                'created_at' => now()->subHour(),
                'attachments' => [
                    [
                        'id' => 1,
                        'name' => 'lab-results.pdf',
                        'type' => 'application/pdf',
                        'size' => 1024 * 500 // 500KB
                    ]
                ]
            ]
        ]);
    }

    /**
     * Store a new discussion message
     */
    public function store(Request $request, $caseId)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'attachments' => 'array'
        ]);

        // TODO: Save message to database
        return response()->json([
            'status' => 'success',
            'message' => [
                'id' => rand(100, 999),
                'content' => $request->content,
                'user' => [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                    'role' => 'Healthcare Provider'
                ],
                'created_at' => now(),
                'attachments' => $request->attachments ?? []
            ]
        ]);
    }

    /**
     * Upload attachments for a discussion
     */
    public function uploadAttachments(Request $request, $caseId)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:10240' // 10MB max per file
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            // TODO: Implement proper file storage
            // For now, just return file info
            $uploadedFiles[] = [
                'id' => rand(100, 999),
                'name' => $file->getClientOriginalName(),
                'type' => $file->getMimeType(),
                'size' => $file->getSize()
            ];
        }

        return response()->json($uploadedFiles);
    }
}
