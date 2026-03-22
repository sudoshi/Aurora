<?php

namespace App\Services;

use App\Models\CaseDiscussion;
use App\Models\ClinicalCase;
use App\Models\DiscussionAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CaseDiscussionService
{
    /**
     * List all discussions for a given clinical case.
     */
    public function listForCase(int $caseId): Collection
    {
        $case = ClinicalCase::findOrFail($caseId);

        return $case->discussions()->with(['user', 'attachments'])->get();
    }

    /**
     * Create a new discussion message for a clinical case.
     *
     * @param  array{message: string, parent_id?: int|null}  $data
     */
    public function create(int $caseId, array $data, User $user): CaseDiscussion
    {
        $case = ClinicalCase::findOrFail($caseId);

        $discussion = new CaseDiscussion;
        $discussion->content = $data['message'];
        $discussion->user_id = $user->id;
        $discussion->case_id = $case->id;

        if (isset($data['parent_id'])) {
            $discussion->parent_id = $data['parent_id'];
        }

        $discussion->save();

        return $discussion->load(['user', 'attachments']);
    }

    /**
     * Upload attachments for a clinical case discussion.
     *
     * @param  array<\Illuminate\Http\UploadedFile>  $files
     * @return array<DiscussionAttachment>
     */
    public function uploadAttachments(int $caseId, array $files, User $user): array
    {
        // Verify the case exists
        ClinicalCase::findOrFail($caseId);

        $attachments = [];

        foreach ($files as $file) {
            $path = $file->store('attachments');

            $attachment = new DiscussionAttachment;
            $attachment->filename = $file->getClientOriginalName();
            $attachment->filepath = $path;
            $attachment->mime_type = $file->getMimeType();
            $attachment->size = $file->getSize();
            $attachment->uploaded_by = $user->id;
            $attachment->save();

            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
