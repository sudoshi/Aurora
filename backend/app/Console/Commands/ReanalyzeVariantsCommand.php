<?php

namespace App\Console\Commands;

use App\Services\Genomics\Reanalysis\ReanalysisService;
use Illuminate\Console\Command;

class ReanalyzeVariantsCommand extends Command
{
    protected $signature = 'genomics:reanalyze-variants';

    protected $description = 'Reanalyze canonicalized patient variants against current ClinVar classifications and raise KB-change alerts';

    public function handle(ReanalysisService $service): int
    {
        $this->info('Running variant reanalysis against ClinVar…');
        $alerts = $service->run();
        $this->info("Reanalysis complete: {$alerts} new KB-change alert(s) raised.");

        return self::SUCCESS;
    }
}
