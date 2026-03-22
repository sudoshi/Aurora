<?php

namespace Database\Seeders;

use App\Models\Clinical\ClinicalPatient;
use Database\Seeders\DemoPatients\OncologyPatient1_LungEGFR;
use Database\Seeders\DemoPatients\OncologyPatient2_CRC_BRAF;
use Database\Seeders\DemoPatients\OncologyPatient3_TNBC_BRCA1;
use Database\Seeders\DemoPatients\PreSurgicalPatient1_CABG;
use Database\Seeders\DemoPatients\PreSurgicalPatient2_HIPEC;
use Database\Seeders\DemoPatients\PreSurgicalPatient3_VHL_HHT;
use Database\Seeders\DemoPatients\RareDiseasePatient1_hATTR;
use Database\Seeders\DemoPatients\RareDiseasePatient2_TSC;
use Database\Seeders\DemoPatients\RareDiseasePatient3_CAPS;
use Database\Seeders\DemoPatients\UndiagnosedPatient1_ECD;
use Database\Seeders\DemoPatients\UndiagnosedPatient2_VEXAS;
use Database\Seeders\DemoPatients\UndiagnosedPatient3_APS1;
use Illuminate\Database\Seeder;

class ClinicalDemoSeeder extends Seeder
{
    /**
     * The ordered list of demo patient seeders to run.
     *
     * @var array<int, class-string>
     */
    private const PATIENT_SEEDERS = [
        RareDiseasePatient1_hATTR::class,
        RareDiseasePatient2_TSC::class,
        RareDiseasePatient3_CAPS::class,
        PreSurgicalPatient1_CABG::class,
        PreSurgicalPatient2_HIPEC::class,
        PreSurgicalPatient3_VHL_HHT::class,
        OncologyPatient1_LungEGFR::class,
        OncologyPatient2_CRC_BRAF::class,
        OncologyPatient3_TNBC_BRCA1::class,
        UndiagnosedPatient1_ECD::class,
        UndiagnosedPatient2_VEXAS::class,
        UndiagnosedPatient3_APS1::class,
    ];

    /**
     * Seed the clinical demo patients.
     *
     * Idempotent: deletes all existing DEMO-* patients before re-seeding.
     * Cascade deletes in the schema handle all child records automatically.
     */
    public function run(): void
    {
        $this->command->info('ClinicalDemoSeeder: Cleaning existing demo patients...');

        $deleted = ClinicalPatient::where('mrn', 'LIKE', 'DEMO-%')->delete();

        $this->command->info("ClinicalDemoSeeder: Removed {$deleted} existing demo patient(s).");

        $total = count(self::PATIENT_SEEDERS);

        foreach (self::PATIENT_SEEDERS as $index => $seederClass) {
            $number = $index + 1;
            $shortName = class_basename($seederClass);

            $this->command->info("ClinicalDemoSeeder: [{$number}/{$total}] Seeding {$shortName}...");

            $seeder = new $seederClass();
            $seeder->seed();
        }

        $finalCount = ClinicalPatient::where('mrn', 'LIKE', 'DEMO-%')->count();

        $this->command->info("ClinicalDemoSeeder: Complete. {$finalCount} demo patient(s) seeded.");
    }
}
