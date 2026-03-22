<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MeasurementEnrichmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedMeasurements();
        $this->seedVariants();
        $this->seedDrugEras();
    }

    private function seedMeasurements(): void
    {
        $now = now();

        $measurements = [
            // ──────────────────────────────────────────────────────────────
            // Patient 148 — Marcus Washington — TTR Cardiac Amyloidosis
            // ──────────────────────────────────────────────────────────────

            // Study 1312 (2018-05-10 TTE)
            ['imaging_study_id' => 1312, 'measurement_type' => 'LV_wall_thickness',  'target_lesion' => false, 'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2018-05-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1312, 'measurement_type' => 'LVEF',               'target_lesion' => false, 'value_numeric' => 60,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2018-05-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1312, 'measurement_type' => 'E_to_A_ratio',       'target_lesion' => false, 'value_numeric' => 0.8,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2018-05-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1313 (2020-06-10 TTE)
            ['imaging_study_id' => 1313, 'measurement_type' => 'LV_wall_thickness',  'target_lesion' => false, 'value_numeric' => 14,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2020-06-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1313, 'measurement_type' => 'LVEF',               'target_lesion' => false, 'value_numeric' => 55,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2020-06-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1313, 'measurement_type' => 'E_to_A_ratio',       'target_lesion' => false, 'value_numeric' => 0.6,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2020-06-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1314 (2020-12-15 Cardiac MRI)
            ['imaging_study_id' => 1314, 'measurement_type' => 'native_T1',          'target_lesion' => false, 'value_numeric' => 1150,  'unit' => 'ms',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2020-12-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1314, 'measurement_type' => 'ECV',                'target_lesion' => false, 'value_numeric' => 0.55,  'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2020-12-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1314, 'measurement_type' => 'LV_wall_thickness',  'target_lesion' => false, 'value_numeric' => 14,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2020-12-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1315 (2021-06-20 PYP)
            ['imaging_study_id' => 1315, 'measurement_type' => 'H_CL_ratio',         'target_lesion' => false, 'value_numeric' => 1.8,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-06-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1315, 'measurement_type' => 'uptake_grade',        'target_lesion' => false, 'value_numeric' => 3,     'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-06-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1319 (2022-05-10 TTE)
            ['imaging_study_id' => 1319, 'measurement_type' => 'LV_wall_thickness',  'target_lesion' => false, 'value_numeric' => 15,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-05-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1319, 'measurement_type' => 'LVEF',               'target_lesion' => false, 'value_numeric' => 55,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-05-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1319, 'measurement_type' => 'E_to_A_ratio',       'target_lesion' => false, 'value_numeric' => 0.5,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-05-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1320 (2024-05-10 TTE)
            ['imaging_study_id' => 1320, 'measurement_type' => 'LV_wall_thickness',  'target_lesion' => false, 'value_numeric' => 15,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-05-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1320, 'measurement_type' => 'LVEF',               'target_lesion' => false, 'value_numeric' => 52,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-05-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1320, 'measurement_type' => 'E_to_A_ratio',       'target_lesion' => false, 'value_numeric' => 0.5,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-05-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 149 — Isabella Ramirez — Tuberous Sclerosis (TSC2)
            // ──────────────────────────────────────────────────────────────

            // Study 1322 (2012-01-10 Neonatal echo)
            ['imaging_study_id' => 1322, 'measurement_type' => 'rhabdomyoma_LV',     'target_lesion' => false, 'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2012-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1322, 'measurement_type' => 'rhabdomyoma_LV2',    'target_lesion' => false, 'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2012-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1322, 'measurement_type' => 'rhabdomyoma_RV',     'target_lesion' => false, 'value_numeric' => 6,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2012-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1323 (2012-07-10)
            ['imaging_study_id' => 1323, 'measurement_type' => 'rhabdomyoma_largest', 'target_lesion' => false, 'value_numeric' => 9,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2012-07-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1324 (2013-01-10)
            ['imaging_study_id' => 1324, 'measurement_type' => 'rhabdomyoma_largest', 'target_lesion' => false, 'value_numeric' => 4,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2013-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1325 (2014-01-10)
            ['imaging_study_id' => 1325, 'measurement_type' => 'rhabdomyoma_largest', 'target_lesion' => false, 'value_numeric' => 1,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2014-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1326 (2012-01-10 Brain MRI)
            ['imaging_study_id' => 1326, 'measurement_type' => 'SEN_largest',         'target_lesion' => false, 'value_numeric' => 5,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2012-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1326, 'measurement_type' => 'cortical_tuber_count','target_lesion' => false, 'value_numeric' => 12,   'unit' => 'count', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2012-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1327 (2016-01-10 Brain)
            ['imaging_study_id' => 1327, 'measurement_type' => 'SEN_largest',         'target_lesion' => false, 'value_numeric' => 9,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2016-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1328 (2018-01-10 Brain — SEGA)
            ['imaging_study_id' => 1328, 'measurement_type' => 'SEGA_diameter',       'target_lesion' => true,  'value_numeric' => 13,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2018-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1329 (2019-01-10 Brain — on everolimus)
            ['imaging_study_id' => 1329, 'measurement_type' => 'SEGA_diameter',       'target_lesion' => true,  'value_numeric' => 9,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2019-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1330 (2022-01-10 Brain — stable)
            ['imaging_study_id' => 1330, 'measurement_type' => 'SEGA_diameter',       'target_lesion' => true,  'value_numeric' => 9,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1331 (2026-01-10 Brain — stable)
            ['imaging_study_id' => 1331, 'measurement_type' => 'SEGA_diameter',       'target_lesion' => true,  'value_numeric' => 9,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1332 (2020-01-10 Renal MRI)
            ['imaging_study_id' => 1332, 'measurement_type' => 'AML_right',           'target_lesion' => true,  'value_numeric' => 21,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2020-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1332, 'measurement_type' => 'AML_left_1',          'target_lesion' => true,  'value_numeric' => 15,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2020-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1332, 'measurement_type' => 'AML_left_2',          'target_lesion' => true,  'value_numeric' => 8,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2020-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1333 (2022-01-10 Renal)
            ['imaging_study_id' => 1333, 'measurement_type' => 'AML_right',           'target_lesion' => true,  'value_numeric' => 35,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1333, 'measurement_type' => 'AML_left_1',          'target_lesion' => true,  'value_numeric' => 15,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1334 (2026-01-10 Renal — stable on everolimus)
            ['imaging_study_id' => 1334, 'measurement_type' => 'AML_right',           'target_lesion' => true,  'value_numeric' => 32,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1334, 'measurement_type' => 'AML_left_1',          'target_lesion' => true,  'value_numeric' => 14,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 150 — Ananya Patel — Catastrophic APS
            // ──────────────────────────────────────────────────────────────

            // Study 1336 (2018-10-14 OB US)
            ['imaging_study_id' => 1336, 'measurement_type' => 'fetal_CRL',           'target_lesion' => false, 'value_numeric' => 0,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2018-10-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1338 (2022-04-08 LE duplex)
            ['imaging_study_id' => 1338, 'measurement_type' => 'DVT_diameter',        'target_lesion' => false, 'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-04-08'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1340 (2026-04-01 bilateral LE)
            ['imaging_study_id' => 1340, 'measurement_type' => 'DVT_R_CFV',           'target_lesion' => false, 'value_numeric' => 14,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-04-01'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1340, 'measurement_type' => 'DVT_L_CFV',           'target_lesion' => false, 'value_numeric' => 15,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-04-01'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1341 (2026-04-02 CTPA)
            ['imaging_study_id' => 1341, 'measurement_type' => 'PE_burden_score',     'target_lesion' => false, 'value_numeric' => 12,    'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-04-02'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1342 (2026-04-02 CT abd)
            ['imaging_study_id' => 1342, 'measurement_type' => 'renal_infarct_R',     'target_lesion' => false, 'value_numeric' => 30,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-04-02'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1342, 'measurement_type' => 'renal_infarct_L',     'target_lesion' => false, 'value_numeric' => 25,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-04-02'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1343 (2026-04-02 echo)
            ['imaging_study_id' => 1343, 'measurement_type' => 'TAPSE',               'target_lesion' => false, 'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-04-02'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1343, 'measurement_type' => 'RVSP',                'target_lesion' => false, 'value_numeric' => 55,    'unit' => 'mmHg',  'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-04-02'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1343, 'measurement_type' => 'RV_diameter',          'target_lesion' => false, 'value_numeric' => 45,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-04-02'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1339 (2024-04-15 brain MRI)
            ['imaging_study_id' => 1339, 'measurement_type' => 'WM_lesion_count',     'target_lesion' => false, 'value_numeric' => 3,     'unit' => 'count', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-04-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1339, 'measurement_type' => 'largest_WM_lesion',   'target_lesion' => false, 'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-04-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 151 — Robert Kowalski — Complex Cardiac Surgery
            // ──────────────────────────────────────────────────────────────

            // Study 1348 (2025-09-15 TTE)
            ['imaging_study_id' => 1348, 'measurement_type' => 'LVEF',                'target_lesion' => false, 'value_numeric' => 30,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-09-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1348, 'measurement_type' => 'aortic_valve_area',   'target_lesion' => false, 'value_numeric' => 0.7,   'unit' => 'cm2',   'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-09-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1348, 'measurement_type' => 'mean_gradient',       'target_lesion' => false, 'value_numeric' => 48,    'unit' => 'mmHg',  'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-09-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1348, 'measurement_type' => 'LV_end_diastolic',    'target_lesion' => false, 'value_numeric' => 62,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-09-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1349 (2025-10-15 coronary angio)
            ['imaging_study_id' => 1349, 'measurement_type' => 'LAD_stenosis',        'target_lesion' => false, 'value_numeric' => 90,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-10-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1349, 'measurement_type' => 'RCA_stenosis',        'target_lesion' => false, 'value_numeric' => 80,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-10-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1349, 'measurement_type' => 'LCx_stenosis',        'target_lesion' => false, 'value_numeric' => 70,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-10-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1350 (2025-11-01 CT chest)
            ['imaging_study_id' => 1350, 'measurement_type' => 'ascending_aorta',     'target_lesion' => false, 'value_numeric' => 48,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-11-01'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1350, 'measurement_type' => 'sternal_gap',         'target_lesion' => false, 'value_numeric' => 2,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-11-01'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1351 (2025-11-15 abdominal US)
            ['imaging_study_id' => 1351, 'measurement_type' => 'liver_span',          'target_lesion' => false, 'value_numeric' => 16,    'unit' => 'cm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-11-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1351, 'measurement_type' => 'portal_vein_velocity','target_lesion' => false, 'value_numeric' => 18,    'unit' => 'cm_s',  'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-11-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 152 — Carmen Delgado — Sarcoidosis
            // ──────────────────────────────────────────────────────────────

            // Study 1352 (2026-02-01 CT abd)
            ['imaging_study_id' => 1352, 'measurement_type' => 'spleen_length',       'target_lesion' => false, 'value_numeric' => 15,    'unit' => 'cm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-01'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1352, 'measurement_type' => 'hepatic_granuloma_ct','target_lesion' => false, 'value_numeric' => 4,     'unit' => 'count', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-01'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1353 (2026-02-01 CT chest)
            ['imaging_study_id' => 1353, 'measurement_type' => 'mediastinal_LN_SAX',  'target_lesion' => true,  'value_numeric' => 22,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-01'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1353, 'measurement_type' => 'hilar_LN_SAX',        'target_lesion' => true,  'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-01'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1354 (2026-02-05 PET)
            ['imaging_study_id' => 1354, 'measurement_type' => 'cardiac_SUVmax',      'target_lesion' => false, 'value_numeric' => 8.2,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-05'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1354, 'measurement_type' => 'mediastinal_LN_SUV',  'target_lesion' => false, 'value_numeric' => 6.4,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-05'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1354, 'measurement_type' => 'hepatic_SUVmax',      'target_lesion' => false, 'value_numeric' => 4.1,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-05'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1355 (2026-02-10 TTE)
            ['imaging_study_id' => 1355, 'measurement_type' => 'LVEF',                'target_lesion' => false, 'value_numeric' => 45,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1355, 'measurement_type' => 'septal_thickness',    'target_lesion' => false, 'value_numeric' => 14,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1355, 'measurement_type' => 'pericardial_effusion','target_lesion' => false, 'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 153 — Erik Lindgren — VHL + HHT
            // ──────────────────────────────────────────────────────────────

            // Study 1356 (2026-01-15 Brain MRI)
            ['imaging_study_id' => 1356, 'measurement_type' => 'hblast_cerebellum',   'target_lesion' => true,  'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1356, 'measurement_type' => 'hblast_brainstem',    'target_lesion' => true,  'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1357 (2026-01-16 MRA brain)
            ['imaging_study_id' => 1357, 'measurement_type' => 'AVM_diameter',        'target_lesion' => false, 'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-16'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1357, 'measurement_type' => 'feeding_artery_count','target_lesion' => false, 'value_numeric' => 3,     'unit' => 'count', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-16'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1358 (2026-01-20 CT chest)
            ['imaging_study_id' => 1358, 'measurement_type' => 'pulm_AVM_RLL',        'target_lesion' => false, 'value_numeric' => 15,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1358, 'measurement_type' => 'pulm_AVM_LLL',        'target_lesion' => false, 'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1359 (2026-01-22 bubble echo)
            ['imaging_study_id' => 1359, 'measurement_type' => 'shunt_grade',         'target_lesion' => false, 'value_numeric' => 3,     'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-22'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1361 (2026-02-05 abd MRI)
            ['imaging_study_id' => 1361, 'measurement_type' => 'RCC_right',           'target_lesion' => true,  'value_numeric' => 22,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-05'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1361, 'measurement_type' => 'pancreatic_cyst',     'target_lesion' => false, 'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-05'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1361, 'measurement_type' => 'pheochromocytoma',    'target_lesion' => false, 'value_numeric' => 0,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-05'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1362 (2026-02-05 spine MRI)
            ['imaging_study_id' => 1362, 'measurement_type' => 'hblast_T10',          'target_lesion' => true,  'value_numeric' => 10,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-02-05'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1363 (2025-06-15 prior brain)
            ['imaging_study_id' => 1363, 'measurement_type' => 'hblast_cerebellum',   'target_lesion' => true,  'value_numeric' => 15,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-06-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 154 — James Whitfield — EGFR+ NSCLC
            // ──────────────────────────────────────────────────────────────

            // Study 1365 (2021-04-12 baseline CT)
            ['imaging_study_id' => 1365, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 42,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-04-12'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1365, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 28,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-04-12'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1365, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 15,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-04-12'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1366 (2021-07-15 PR on osimertinib)
            ['imaging_study_id' => 1366, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 28,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-07-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1366, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-07-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1366, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 10,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-07-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1367 (2021-10-20 continued response)
            ['imaging_study_id' => 1367, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 22,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-10-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1367, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 14,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-10-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1367, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-10-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1368 (2022-04-18 near CR)
            ['imaging_study_id' => 1368, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-04-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1368, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-04-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1368, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 6,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-04-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1369 (2022-10-14 stable)
            ['imaging_study_id' => 1369, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-10-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1369, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-10-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1369, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 6,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-10-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1370 (2023-04-12 PD, C797S resistance)
            ['imaging_study_id' => 1370, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 25,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2023-04-12'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1370, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 16,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2023-04-12'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1370, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2023-04-12'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1371 (2023-06-22 amivantamab response)
            ['imaging_study_id' => 1371, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 22,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2023-06-22'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1371, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 14,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2023-06-22'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1371, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 10,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2023-06-22'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1372 (2024-01-18)
            ['imaging_study_id' => 1372, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 20,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-01-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1372, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-01-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1372, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-01-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1373 (2024-07-15 PD again)
            ['imaging_study_id' => 1373, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 28,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-07-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1373, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 20,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-07-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1373, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 16,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-07-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1374 (2025-01-20 chemo response)
            ['imaging_study_id' => 1374, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 24,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-01-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1374, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 16,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-01-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1374, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-01-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1375 (2025-07-18 PD + new brain met)
            ['imaging_study_id' => 1375, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 30,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-07-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1375, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 22,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-07-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1375, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-07-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1375, 'measurement_type' => 'brain_met',           'target_lesion' => true,  'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-07-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1376 (2026-01-15)
            ['imaging_study_id' => 1376, 'measurement_type' => 'primary_RUL',         'target_lesion' => true,  'value_numeric' => 26,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1376, 'measurement_type' => 'mediastinal_LN',      'target_lesion' => true,  'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1376, 'measurement_type' => 'adrenal_met',         'target_lesion' => true,  'value_numeric' => 14,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1376, 'measurement_type' => 'brain_met',           'target_lesion' => true,  'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-01-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 156 — Priya Sharma — TNBC BRCA1+
            // ──────────────────────────────────────────────────────────────

            // Study 1391 (2021-09-25 Breast MRI baseline)
            ['imaging_study_id' => 1391, 'measurement_type' => 'breast_mass',         'target_lesion' => true,  'value_numeric' => 48,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-09-25'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1391, 'measurement_type' => 'axillary_LN',         'target_lesion' => true,  'value_numeric' => 24,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2021-09-25'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1392 (2022-01-14 mid-neoadjuvant PR)
            ['imaging_study_id' => 1392, 'measurement_type' => 'breast_mass',         'target_lesion' => true,  'value_numeric' => 22,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-01-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1392, 'measurement_type' => 'axillary_LN',         'target_lesion' => true,  'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-01-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1393 (2022-03-14 pre-surgical near pCR)
            ['imaging_study_id' => 1393, 'measurement_type' => 'breast_mass',         'target_lesion' => true,  'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-03-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1393, 'measurement_type' => 'axillary_LN',         'target_lesion' => true,  'value_numeric' => 5,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2022-03-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1394 (2024-06-14 metastatic baseline)
            ['imaging_study_id' => 1394, 'measurement_type' => 'liver_met',           'target_lesion' => true,  'value_numeric' => 32,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-06-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1394, 'measurement_type' => 'lung_met',            'target_lesion' => true,  'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-06-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1394, 'measurement_type' => 'bone_met_present',    'target_lesion' => true,  'value_numeric' => 1,     'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-06-14'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1396 (2024-06-18 PET)
            ['imaging_study_id' => 1396, 'measurement_type' => 'liver_SUVmax',        'target_lesion' => false, 'value_numeric' => 12.4,  'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-06-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1396, 'measurement_type' => 'lung_SUVmax',         'target_lesion' => false, 'value_numeric' => 8.2,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-06-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1397 (2024-09-18 olaparib PR)
            ['imaging_study_id' => 1397, 'measurement_type' => 'liver_met',           'target_lesion' => true,  'value_numeric' => 24,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-09-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1397, 'measurement_type' => 'lung_met',            'target_lesion' => true,  'value_numeric' => 14,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-09-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1398 (2025-01-20 continued PR)
            ['imaging_study_id' => 1398, 'measurement_type' => 'liver_met',           'target_lesion' => true,  'value_numeric' => 20,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-01-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1398, 'measurement_type' => 'lung_met',            'target_lesion' => true,  'value_numeric' => 10,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-01-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1399 (2025-06-18 stable)
            ['imaging_study_id' => 1399, 'measurement_type' => 'liver_met',           'target_lesion' => true,  'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-06-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1399, 'measurement_type' => 'lung_met',            'target_lesion' => true,  'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-06-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1400 (2025-12-15 PD, BRCA reversion)
            ['imaging_study_id' => 1400, 'measurement_type' => 'liver_met',           'target_lesion' => true,  'value_numeric' => 28,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-12-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1400, 'measurement_type' => 'lung_met',            'target_lesion' => true,  'value_numeric' => 16,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-12-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1400, 'measurement_type' => 'new_peritoneal',      'target_lesion' => true,  'value_numeric' => 15,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-12-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1401 (2026-03-10 sacituzumab response)
            ['imaging_study_id' => 1401, 'measurement_type' => 'liver_met',           'target_lesion' => true,  'value_numeric' => 22,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-03-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1401, 'measurement_type' => 'lung_met',            'target_lesion' => true,  'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-03-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1401, 'measurement_type' => 'peritoneal',          'target_lesion' => true,  'value_numeric' => 10,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2026-03-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 157 — Marcus Thompson — Erdheim-Chester + BRAF V600E
            // ──────────────────────────────────────────────────────────────

            // Study 1402 (2023-06-20 XR)
            ['imaging_study_id' => 1402, 'measurement_type' => 'cortical_thickness',  'target_lesion' => false, 'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2023-06-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1403 (2023-07-15 bone scan)
            ['imaging_study_id' => 1403, 'measurement_type' => 'femoral_uptake_ratio','target_lesion' => false, 'value_numeric' => 3.2,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2023-07-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1404 (2024-02-20 Brain MRI)
            ['imaging_study_id' => 1404, 'measurement_type' => 'pituitary_stalk',     'target_lesion' => false, 'value_numeric' => 4.2,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-02-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1405 (2024-06-15 CT chest)
            ['imaging_study_id' => 1405, 'measurement_type' => 'septal_thickness',    'target_lesion' => false, 'value_numeric' => 2.5,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-06-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1405, 'measurement_type' => 'pleural_effusion_R',  'target_lesion' => false, 'value_numeric' => 12,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-06-15'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1406 (2024-08-10 CT abd)
            ['imaging_study_id' => 1406, 'measurement_type' => 'perinephric_strand',  'target_lesion' => false, 'value_numeric' => 1,     'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-08-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1406, 'measurement_type' => 'renal_encase_score',  'target_lesion' => false, 'value_numeric' => 3,     'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-08-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1407 (2025-02-10 TTE)
            ['imaging_study_id' => 1407, 'measurement_type' => 'pericardial_effusion','target_lesion' => false, 'value_numeric' => 18,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-02-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1407, 'measurement_type' => 'RA_mass',             'target_lesion' => true,  'value_numeric' => 22,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-02-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1408 (2025-02-20 Cardiac MRI)
            ['imaging_study_id' => 1408, 'measurement_type' => 'pericardial_thick',   'target_lesion' => false, 'value_numeric' => 5,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-02-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1408, 'measurement_type' => 'RA_mass',             'target_lesion' => true,  'value_numeric' => 22,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-02-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1408, 'measurement_type' => 'LGE_present',         'target_lesion' => false, 'value_numeric' => 1,     'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-02-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1409 (2025-04-20 PET)
            ['imaging_study_id' => 1409, 'measurement_type' => 'femoral_SUVmax',      'target_lesion' => false, 'value_numeric' => 4.2,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-04-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1409, 'measurement_type' => 'pericardial_SUVmax',  'target_lesion' => false, 'value_numeric' => 5.8,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-04-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1409, 'measurement_type' => 'RA_mass_SUVmax',      'target_lesion' => false, 'value_numeric' => 6.1,   'unit' => 'ratio', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-04-20'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 158 — Gerald Kowalczyk — VEXAS (UBA1)
            // ──────────────────────────────────────────────────────────────

            // Study 1410 (2024-08-18 LE duplex)
            ['imaging_study_id' => 1410, 'measurement_type' => 'popliteal_DVT_diam',  'target_lesion' => false, 'value_numeric' => 10,    'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-08-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1411 (2024-08-18 CTPA)
            ['imaging_study_id' => 1411, 'measurement_type' => 'PE_present',          'target_lesion' => false, 'value_numeric' => 0,     'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-08-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1411, 'measurement_type' => 'GGO_extent',          'target_lesion' => false, 'value_numeric' => 10,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-08-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1412 (2024-04-18 CT sinus)
            ['imaging_study_id' => 1412, 'measurement_type' => 'septal_cartilage_th', 'target_lesion' => false, 'value_numeric' => 1.5,   'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-04-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1412, 'measurement_type' => 'saddle_deform_score', 'target_lesion' => false, 'value_numeric' => 1,     'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2024-04-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1413 (2025-06-10 CT chest)
            ['imaging_study_id' => 1413, 'measurement_type' => 'GGO_extent',          'target_lesion' => false, 'value_numeric' => 25,    'unit' => '%',     'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-06-10'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ──────────────────────────────────────────────────────────────
            // Patient 159 — Sofia Reyes — APS-1 (AIRE)
            // ──────────────────────────────────────────────────────────────

            // Study 1414 (2025-03-18 knee US)
            ['imaging_study_id' => 1414, 'measurement_type' => 'suprapatellar_eff_R', 'target_lesion' => false, 'value_numeric' => 8,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-03-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1414, 'measurement_type' => 'suprapatellar_eff_L', 'target_lesion' => false, 'value_numeric' => 6,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-03-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1414, 'measurement_type' => 'synovial_thickness',  'target_lesion' => false, 'value_numeric' => 4,     'unit' => 'mm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-03-18'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // Study 1415 (2025-07-12 liver US)
            ['imaging_study_id' => 1415, 'measurement_type' => 'liver_span',          'target_lesion' => false, 'value_numeric' => 14,    'unit' => 'cm',    'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-07-12'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['imaging_study_id' => 1415, 'measurement_type' => 'liver_echo_score',    'target_lesion' => false, 'value_numeric' => 2,     'unit' => 'score', 'measured_by' => 'synthetic_enrichment_v2', 'measured_at' => Carbon::parse('2025-07-12'), 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('imaging_measurements')->insert($measurements);
    }

    private function seedVariants(): void
    {
        $now = now();

        $variants = [
            // ── Patient 148 — benign contrast ──
            ['patient_id' => 148, 'gene' => 'APOE',   'variant' => 'e3/e4',          'variant_type' => 'SNP',           'chromosome' => '19', 'position' => 44908684,  'ref_allele' => 'T',  'alt_allele' => 'C',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.14,  'clinical_significance' => 'benign',     'actionability' => 'none',        'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 149 — benign contrast ──
            ['patient_id' => 149, 'gene' => 'MTOR',   'variant' => 'p.Ser2215Tyr',   'variant_type' => 'missense',      'chromosome' => '1',  'position' => 11184573,  'ref_allele' => 'C',  'alt_allele' => 'A',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.001, 'clinical_significance' => 'benign',     'actionability' => 'none',        'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 150 — Factor V Leiden (pathogenic, clinically relevant for APS) ──
            ['patient_id' => 150, 'gene' => 'F5',     'variant' => 'G1691A',         'variant_type' => 'SNP',           'chromosome' => '1',  'position' => 169519049, 'ref_allele' => 'G',  'alt_allele' => 'A',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.05,  'clinical_significance' => 'pathogenic', 'actionability' => 'therapeutic',  'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 151 — cardiac variants ──
            ['patient_id' => 151, 'gene' => 'PCSK9',  'variant' => 'p.D374Y',        'variant_type' => 'missense',      'chromosome' => '1',  'position' => 55505647,  'ref_allele' => 'G',  'alt_allele' => 'T',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.001, 'clinical_significance' => 'pathogenic', 'actionability' => 'therapeutic',  'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['patient_id' => 151, 'gene' => 'MYBPC3', 'variant' => 'p.Arg502Trp',    'variant_type' => 'missense',      'chromosome' => '11', 'position' => 47352957,  'ref_allele' => 'C',  'alt_allele' => 'T',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.002, 'clinical_significance' => 'VUS',        'actionability' => 'monitor',     'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['patient_id' => 151, 'gene' => 'LDLR',   'variant' => 'c.1775G>A',      'variant_type' => 'missense',      'chromosome' => '19', 'position' => 11224088,  'ref_allele' => 'G',  'alt_allele' => 'A',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.0005,'clinical_significance' => 'pathogenic', 'actionability' => 'therapeutic',  'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 152 — sarcoidosis variants ──
            ['patient_id' => 152, 'gene' => 'ACE',    'variant' => 'I/D',            'variant_type' => 'indel',         'chromosome' => '17', 'position' => 61566031,  'ref_allele' => '-',  'alt_allele' => 'ALU','zygosity' => 'heterozygous', 'allele_frequency' => 0.45,  'clinical_significance' => 'VUS',        'actionability' => 'none',        'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['patient_id' => 152, 'gene' => 'HLA-DRB1','variant' => '*03:01',        'variant_type' => 'HLA_allele',    'chromosome' => '6',  'position' => 32578775,  'ref_allele' => '-',  'alt_allele' => '-',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.12,  'clinical_significance' => 'VUS',        'actionability' => 'none',        'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['patient_id' => 152, 'gene' => 'BTNL2',  'variant' => 'p.Arg262Trp',    'variant_type' => 'missense',      'chromosome' => '6',  'position' => 32363825,  'ref_allele' => 'C',  'alt_allele' => 'T',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.08,  'clinical_significance' => 'pathogenic', 'actionability' => 'diagnostic',   'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 153 — VUS contrast ──
            ['patient_id' => 153, 'gene' => 'PTEN',   'variant' => 'p.Pro246Leu',    'variant_type' => 'missense',      'chromosome' => '10', 'position' => 89720732,  'ref_allele' => 'C',  'alt_allele' => 'T',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.003, 'clinical_significance' => 'VUS',        'actionability' => 'monitor',     'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 154 — benign contrast ──
            ['patient_id' => 154, 'gene' => 'STK11',  'variant' => 'p.Phe354Leu',    'variant_type' => 'missense',      'chromosome' => '19', 'position' => 1220321,   'ref_allele' => 'T',  'alt_allele' => 'C',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.01,  'clinical_significance' => 'benign',     'actionability' => 'none',        'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 156 — VUS contrast ──
            ['patient_id' => 156, 'gene' => 'PALB2',  'variant' => 'c.3113G>A',      'variant_type' => 'missense',      'chromosome' => '16', 'position' => 23614882,  'ref_allele' => 'G',  'alt_allele' => 'A',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.004, 'clinical_significance' => 'VUS',        'actionability' => 'monitor',     'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 157 — benign contrast ──
            ['patient_id' => 157, 'gene' => 'MAP2K1', 'variant' => 'p.Pro124Ser',    'variant_type' => 'missense',      'chromosome' => '15', 'position' => 66729162,  'ref_allele' => 'C',  'alt_allele' => 'T',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.005, 'clinical_significance' => 'benign',     'actionability' => 'none',        'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 158 — DNMT3A (pathogenic, somatic, relevant to VEXAS MDS) ──
            ['patient_id' => 158, 'gene' => 'DNMT3A', 'variant' => 'p.Arg882His',    'variant_type' => 'missense',      'chromosome' => '2',  'position' => 25457242,  'ref_allele' => 'G',  'alt_allele' => 'A',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.35,  'clinical_significance' => 'pathogenic', 'actionability' => 'therapeutic',  'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 159 — VUS autoimmune susceptibility ──
            ['patient_id' => 159, 'gene' => 'CTLA4',  'variant' => 'c.49A>G',        'variant_type' => 'missense',      'chromosome' => '2',  'position' => 204732714, 'ref_allele' => 'A',  'alt_allele' => 'G',  'zygosity' => 'heterozygous', 'allele_frequency' => 0.33,  'clinical_significance' => 'VUS',        'actionability' => 'monitor',     'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('genomic_variants')->insertOrIgnore($variants);
    }

    private function seedDrugEras(): void
    {
        $now = now();

        $drugEras = [
            // ── Patient 151 — Robert Kowalski ──
            ['patient_id' => 151, 'drug_name' => 'Atorvastatin', 'era_start' => '2015-01-01', 'era_end' => null,          'gap_days' => 0, 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['patient_id' => 151, 'drug_name' => 'Furosemide',   'era_start' => '2024-06-01', 'era_end' => null,          'gap_days' => 0, 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['patient_id' => 151, 'drug_name' => 'Warfarin',     'era_start' => '2025-01-15', 'era_end' => null,          'gap_days' => 0, 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['patient_id' => 151, 'drug_name' => 'Dobutamine',   'era_start' => '2025-10-01', 'era_end' => '2025-11-15', 'gap_days' => 0, 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],

            // ── Patient 152 — Carmen Delgado ──
            ['patient_id' => 152, 'drug_name' => 'Prednisone',   'era_start' => '2025-06-01', 'era_end' => null,          'gap_days' => 0, 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['patient_id' => 152, 'drug_name' => 'Methotrexate', 'era_start' => '2025-09-01', 'era_end' => null,          'gap_days' => 0, 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
            ['patient_id' => 152, 'drug_name' => 'Infliximab',   'era_start' => '2026-01-15', 'era_end' => null,          'gap_days' => 0, 'source_id' => 'enrichment_v2', 'source_type' => 'synthetic', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('drug_eras')->insert($drugEras);
    }
}
