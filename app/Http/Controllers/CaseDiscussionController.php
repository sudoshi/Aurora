<?php

namespace App\Http\Controllers;

use App\Models\CaseDiscussion;
use App\Models\DiscussionAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\ClinicalCase;


class CaseDiscussionController extends Controller
{
    /**
     * Get discussions for a case
     */
    public function index(ClinicalCase $case)
    {
        return response()->json($case->discussions);
    }

    /**
     * Store a new discussion message
     */
    public function store(Request $request, ClinicalCase $case)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $discussion = new CaseDiscussion();
        $discussion->content = $request->input('content');
        $discussion->user_id = Auth::id();
        $discussion->case_id = $case->id;
        $discussion->save();

        // Handle attachments (simplified for now)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments'); // Store the file
                $attachment = new DiscussionAttachment();
                $attachment->discussion_id = $discussion->id;
                $attachment->filename = $file->getClientOriginalName();
                $attachment->filepath = $path;
                $attachment->mime_type = $file->getMimeType();
                $attachment->size = $file->getSize();
                $attachment->save();
            }
        }


        return response()->json([
            'status' => 'success',
            'message' => $discussion
        ], 201);
    }


    /**
     * Upload attachments for a discussion
     */
    public function uploadAttachments(Request $request, $caseId)
    {
        // This function is now handled within the store method.
        return response()->json(['message' => 'Attachments should be uploaded with the discussion message.'], 400);

    }
}
