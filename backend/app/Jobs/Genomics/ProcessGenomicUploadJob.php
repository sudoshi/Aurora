<?php

namespace App\Jobs\Genomics;

use App\Models\Clinical\GenomicUpload;
use App\Services\Genomics\GenomicUploadIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessGenomicUploadJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public readonly int $uploadId) {}

    public function handle(GenomicUploadIngestionService $service): void
    {
        $upload = GenomicUpload::find($this->uploadId);

        if (! $upload) {
            return;
        }

        $service->processUpload($upload);
    }
}
