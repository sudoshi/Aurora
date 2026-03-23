<?php

namespace Database\Seeders;

use App\Models\Clinical\ClinicalPatient;
use Database\Seeders\TciaPatients\TciaPatient1_PancreaticPDA;
use Database\Seeders\TciaPatients\TciaPatient2_ProstatePSMA;
use Database\Seeders\TciaPatients\TciaPatient3_LungNSCLC;
use Database\Seeders\TciaPatients\TciaPatient4_LiverHCC;
use Database\Seeders\TciaPatients\TciaPatient5_KidneyRCC;
use Database\Seeders\TciaPatients\TciaPatient6_BreastBRCA;
use Database\Seeders\TciaPatients\TciaPatient7_LungAdenoKRAS;
use Database\Seeders\TciaPatients\TciaPatient8_KidneyCCRCC;
use Illuminate\Database\Seeder;

class TciaDemoSeeder extends Seeder
{
    /**
     * Synthetic oncology patients linked to real TCIA imaging collections
     * and GDC genomics data. Each patient represents a distinct cancer type
     * with clinically accurate conditions, treatments, labs, and variants.
     *
     * @var array<int, class-string>
     */
    private const PATIENT_SEEDERS = [
        TciaPatient1_PancreaticPDA::class,
        TciaPatient2_ProstatePSMA::class,
        TciaPatient3_LungNSCLC::class,
        TciaPatient4_LiverHCC::class,
        TciaPatient5_KidneyRCC::class,
        TciaPatient6_BreastBRCA::class,
        TciaPatient7_LungAdenoKRAS::class,
        TciaPatient8_KidneyCCRCC::class,
    ];

    /**
     * Seed the TCIA-linked demo patients.
     *
     * Idempotent: deletes all existing TCIA-* patients before re-seeding.
     * Cascade deletes in the schema handle all child records automatically.
     */
    public function run(): void
    {
        $this->command->info('TciaDemoSeeder: Cleaning existing TCIA patients...');

        $deleted = ClinicalPatient::where('mrn', 'LIKE', 'TCIA-%')->delete();

        $this->command->info("TciaDemoSeeder: Removed {$deleted} existing TCIA patient(s).");

        $total = count(self::PATIENT_SEEDERS);

        foreach (self::PATIENT_SEEDERS as $index => $seederClass) {
            $number = $index + 1;
            $shortName = class_basename($seederClass);

            $this->command->info("TciaDemoSeeder: [{$number}/{$total}] Seeding {$shortName}...");

            $seeder = new $seederClass;
            $seeder->seed();
        }

        $finalCount = ClinicalPatient::where('mrn', 'LIKE', 'TCIA-%')->count();

        $this->command->info("TciaDemoSeeder: Complete. {$finalCount} TCIA patient(s) seeded.");
    }
}
