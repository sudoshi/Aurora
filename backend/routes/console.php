<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('genomics:refresh-evidence')->weekly()->sundays()->at('02:00');
Schedule::command('genomics:reanalyze-variants')->monthlyOn(8, '03:00');
Schedule::command('genomics:reanalyze-gene-validity')->monthlyOn(9, '03:00');
