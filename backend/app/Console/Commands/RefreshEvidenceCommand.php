<?php

namespace App\Console\Commands;

use App\Models\Clinical\ClinVarSyncLog;
use App\Services\Genomics\ClinVarAnnotationService;
use App\Services\Genomics\ClinVarSyncService;
use App\Services\Genomics\OncoKbService;
use Illuminate\Console\Command;

class RefreshEvidenceCommand extends Command
{
    protected $signature = 'genomics:refresh-evidence {--force : Skip cadence checks}';
    protected $description = 'Refresh all genomics evidence sources (ClinVar + OncoKB)';

    public function handle(
        ClinVarSyncService $clinvar,
        ClinVarAnnotationService $annotator,
        OncoKbService $oncokb,
    ): int {
        $this->info('Starting evidence refresh...');

        // 1. ClinVar sync (weekly cadence)
        $lastSync = ClinVarSyncLog::where('status', 'completed')
            ->latest('finished_at')
            ->first();

        $daysSinceSync = $lastSync
            ? now()->diffInDays($lastSync->finished_at)
            : 999;

        if ($daysSinceSync >= 7 || $this->option('force')) {
            $this->info('Syncing ClinVar variants...');
            try {
                $result = $clinvar->sync('GRCh38', true); // PAPU only for speed
                $this->info("ClinVar: {$result['inserted']} inserted, {$result['updated']} updated");
            } catch (\Exception $e) {
                $this->error("ClinVar sync failed: {$e->getMessage()}");
            }
        } else {
            $this->info("ClinVar sync skipped — last synced {$daysSinceSync} days ago");
        }

        // 2. OncoKB sync
        $this->info('Syncing OncoKB annotations...');
        $oncoResult = $oncokb->syncInteractions();
        if (isset($oncoResult['skipped'])) {
            $this->warn("OncoKB skipped: {$oncoResult['skipped']}");
        } else {
            $this->info("OncoKB: {$oncoResult['synced']} genes synced, {$oncoResult['errors']} errors");
        }

        // 3. Re-annotate patient variants with updated ClinVar data
        $this->info('Re-annotating patient variants with updated ClinVar data...');
        try {
            $annotationResult = $annotator->annotateAll();
            $this->info("ClinVar annotation: {$annotationResult['annotated']} updated, {$annotationResult['skipped']} skipped");
        } catch (\Exception $e) {
            $this->error("ClinVar annotation failed: {$e->getMessage()}");
        }

        $this->info('Evidence refresh complete.');
        return Command::SUCCESS;
    }
}
