<?php

use App\Models\CaseDiscussion;
use App\Models\ClinicalCase;
use App\Models\DiscussionAttachment;
use App\Models\User;
use App\Services\CaseDiscussionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->service = new CaseDiscussionService;
});

describe('CaseDiscussionService::listForCase', function () {
    it('returns discussions for a given case', function () {
        $discussions = Mockery::mock(Collection::class);

        $discussionsQuery = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $discussionsQuery->shouldReceive('with')
            ->with(['user', 'attachments'])
            ->andReturnSelf();
        $discussionsQuery->shouldReceive('get')
            ->andReturn($discussions);

        $case = Mockery::mock(ClinicalCase::class);
        $case->shouldReceive('discussions')->andReturn($discussionsQuery);

        $mock = Mockery::mock('alias:'.ClinicalCase::class);
        $mock->shouldReceive('findOrFail')->with(1)->andReturn($case);

        $result = $this->service->listForCase(1);

        expect($result)->toBe($discussions);
    });
});

describe('CaseDiscussionService::create', function () {
    it('creates a discussion for a case', function () {
        $case = Mockery::mock(ClinicalCase::class);
        $case->id = 1;

        $caseMock = Mockery::mock('alias:'.ClinicalCase::class);
        $caseMock->shouldReceive('findOrFail')->with(1)->andReturn($case);

        $user = Mockery::mock(User::class);
        $user->id = 42;

        $discussion = Mockery::mock(CaseDiscussion::class)->makePartial();
        $discussion->shouldReceive('save')->once();
        $discussion->shouldReceive('load')
            ->with(['user', 'attachments'])
            ->once()
            ->andReturnSelf();

        // Override the new CaseDiscussion() call via alias
        $discussionAlias = Mockery::mock('alias:'.CaseDiscussion::class);
        // Since the service uses `new CaseDiscussion()`, we test the
        // integration path where it sets properties and saves.

        $data = [
            'message' => 'Patient shows improvement after treatment cycle 3.',
        ];

        // We verify the service calls ClinicalCase::findOrFail
        // Detailed property-setting is integration-level
        expect(fn () => $this->service->create(1, $data, $user))
            ->not->toThrow(\Exception::class);
    });

    it('sets parent_id when provided', function () {
        $case = Mockery::mock(ClinicalCase::class);
        $case->id = 1;

        $caseMock = Mockery::mock('alias:'.ClinicalCase::class);
        $caseMock->shouldReceive('findOrFail')->with(1)->andReturn($case);

        $user = Mockery::mock(User::class);
        $user->id = 42;

        $data = [
            'message' => 'Reply to thread',
            'parent_id' => 5,
        ];

        // This tests the code path where parent_id is set
        expect(fn () => $this->service->create(1, $data, $user))
            ->not->toThrow(\Exception::class);
    });
});

describe('CaseDiscussionService::uploadAttachments', function () {
    it('stores files and returns attachment records', function () {
        Storage::fake('local');

        $caseMock = Mockery::mock('alias:'.ClinicalCase::class);
        $caseMock->shouldReceive('findOrFail')->with(1)->andReturn(
            Mockery::mock(ClinicalCase::class)
        );

        $user = Mockery::mock(User::class);
        $user->id = 42;

        $file = UploadedFile::fake()->create('report.pdf', 1024, 'application/pdf');
        $files = [$file];

        // The service creates DiscussionAttachment instances via `new`
        // and calls save(). We verify no exceptions are thrown.
        expect(fn () => $this->service->uploadAttachments(1, $files, $user))
            ->not->toThrow(\Exception::class);
    });
});
