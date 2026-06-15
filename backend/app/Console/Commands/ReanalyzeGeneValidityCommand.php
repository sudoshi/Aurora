<?php

namespace App\Console\Commands;

use App\Services\Genomics\Reanalysis\GeneValidityReanalysisService;
use Illuminate\Console\Command;

class ReanalyzeGeneValidityCommand extends Command
{
    protected $signature = 'genomics:reanalyze-gene-validity';

    protected $description = 'Reanalyze patient genes against current ClinGen Gene-Disease Validity classifications and raise KB-change alerts';

    public function handle(GeneValidityReanalysisService $service): int
    {
        $this->info('Running gene-disease validity reanalysis against ClinGen…');
        $alerts = $service->run();
        $this->info("Gene-validity reanalysis complete: {$alerts} new KB-change alert(s) raised.");

        return self::SUCCESS;
    }
}
