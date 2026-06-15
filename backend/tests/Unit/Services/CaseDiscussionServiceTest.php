<?php

use App\Models\CaseDiscussion;
use App\Models\Clinical\ClinicalPatient;
use App\Models\ClinicalCase;
use App\Models\User;
use App\Services\CaseDiscussionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new CaseDiscussionService;
    $this->user = User::factory()->create();
    $this->patient = ClinicalPatient::factory()->create();
    $this->case = ClinicalCase::factory()->create([
        'patient_id' => $this->patient->id,
        'created_by' => $this->user->id,
    ]);
});

describe('CaseDiscussionService::listForCase', function () {
    it('returns discussions for a given case with relationships loaded', function () {
        CaseDiscussion::create([
            'case_id' => $this->case->id,
            'user_id' => $this->user->id,
            'content' => 'Patient shows improvement after treatment cycle 3.',
        ]);

        $otherCase = ClinicalCase::factory()->create();
        CaseDiscussion::create([
            'case_id' => $otherCase->id,
            'user_id' => $this->user->id,
            'content' => 'Discussion for another case.',
        ]);

        $result = $this->service->listForCase($this->case->id);

        expect($result)->toHaveCount(1)
            ->and($result->first()->content)->toBe('Patient shows improvement after treatment cycle 3.')
            ->and($result->first()->relationLoaded('user'))->toBeTrue()
            ->and($result->first()->relationLoaded('attachments'))->toBeTrue();
    });
});

describe('CaseDiscussionService::create', function () {
    it('creates a discussion for a case', function () {
        $result = $this->service->create($this->case->id, [
            'message' => 'Patient shows improvement after treatment cycle 3.',
        ], $this->user);

        expect($result->content)->toBe('Patient shows improvement after treatment cycle 3.')
            ->and($result->case_id)->toBe($this->case->id)
            ->and($result->user_id)->toBe($this->user->id)
            ->and($result->relationLoaded('user'))->toBeTrue()
            ->and($result->relationLoaded('attachments'))->toBeTrue();

        $this->assertDatabaseHas('app.case_discussions', [
            'id' => $result->id,
            'content' => 'Patient shows improvement after treatment cycle 3.',
        ]);
    });

    it('sets parent_id when provided', function () {
        $parent = CaseDiscussion::create([
            'case_id' => $this->case->id,
            'user_id' => $this->user->id,
            'content' => 'Parent thread',
        ]);

        $result = $this->service->create($this->case->id, [
            'message' => 'Reply to thread',
            'parent_id' => $parent->id,
        ], $this->user);

        expect($result->parent_id)->toBe($parent->id);
    });
});

describe('CaseDiscussionService::uploadAttachments', function () {
    it('stores files and returns attachment records linked to a discussion', function () {
        fakeIsolatedLocalDisk('case-discussion-attachments');

        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');

        $result = $this->service->uploadAttachments($this->case->id, [$file], $this->user);

        expect($result)->toHaveCount(1)
            ->and($result[0]->discussion_id)->not->toBeNull()
            ->and($result[0]->filename)->toBe('report.pdf');

        Storage::disk('local')->assertExists($result[0]->filepath);
        $this->assertDatabaseHas('app.case_discussions', [
            'id' => $result[0]->discussion_id,
            'case_id' => $this->case->id,
            'user_id' => $this->user->id,
            'content' => 'Uploaded attachments',
        ]);
        $this->assertDatabaseHas('app.discussion_attachments', [
            'discussion_id' => $result[0]->discussion_id,
            'filename' => 'report.pdf',
        ]);
    });
});
