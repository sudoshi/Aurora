<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Seeder;

class PatientSeeder extends Seeder
{
    public function run(): void
    {
        Patient::create([
            'id' => 1,
            'name' => 'John Doe',
            'condition' => 'Post-surgical recovery',
            'status' => 'Stable'
        ]);

        Patient::create([
            'id' => 5,
            'name' => 'Steve Jobs',
            'condition' => 'Stage 4 Pancreatic Cancer (Neuroendocrine Tumor) - Metastatic disease with liver involvement. Previous Whipple procedure with subsequent progression.',
            'status' => 'Under Treatment - Current therapy includes targeted molecular therapy and symptom management.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Patient::create([
            'id' => 6,
            'name' => 'Mallikarjun Udoshi',
            'condition' => 'Stage 4 Colon Cancer - Metastatic adenocarcinoma with liver and peritoneal involvement. KRAS mutation positive.',
            'status' => 'Under Treatment - Receiving FOLFOX chemotherapy with good response on recent imaging.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
