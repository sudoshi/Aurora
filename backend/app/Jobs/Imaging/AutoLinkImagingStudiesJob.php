<?php

namespace App\Jobs\Imaging;

use App\Models\Clinical\ImagingIngestionRun;
use App\Services\Imaging\ImagingIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutoLinkImagingStudiesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $runId) {}

    public function handle(ImagingIngestionService $ingestionService): void
    {
        $run = ImagingIngestionRun::findOrFail($this->runId);

        $ingestionService->processAutoLink($run);
    }
}
