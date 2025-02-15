<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\ClinicalGuideline;
use App\Models\DrugInteraction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ClinicalDecisionSupportService
{
    protected $guidelineRepository;
    protected $drugInteractionService;

    public function __construct(
        GuidelineRepository $guidelineRepository,
        DrugInteractionService $drugInteractionService
    ) {
        $this->guidelineRepository = $guidelineRepository;
        $this->drugInteractionService = $drugInteractionService;
    }

    public function analyzePatientData(Patient $patient)
    {
        $alerts = collect();
        $recommendations = collect();

        // Analyze vital signs
        $alerts = $alerts->merge($this->analyzeVitalSigns($patient));

        // Analyze lab results
        $alerts = $alerts->merge($this->analyzeLabResults($patient));

        // Check medication interactions
        $alerts = $alerts->merge($this->checkMedicationInteractions($patient));

        // Get relevant guidelines
        $guidelines = $this->getRelevantGuidelines($patient);
        
        // Generate recommendations based on guidelines
        $recommendations = $this->generateRecommendations($patient, $guidelines);

        return [
            'alerts' => $alerts,
            'recommendations' => $recommendations,
            'guidelines' => $guidelines
        ];
    }

    protected function analyzeVitalSigns(Patient $patient)
    {
        $alerts = collect();
        $latestVitals = $patient->vitals()->latest()->first();

        if ($latestVitals) {
            // Blood pressure analysis
            if ($latestVitals->systolic > 180 || $latestVitals->diastolic > 120) {
                $alerts->push([
                    'severity' => 'high',
                    'category' => 'vital_signs',
                    'title' => 'Severe Hypertension',
                    'description' => 'Blood pressure critically elevated.',
                    'data' => [
                        'systolic' => $latestVitals->systolic,
                        'diastolic' => $latestVitals->diastolic
                    ]
                ]);
            }

            // Heart rate analysis
            if ($latestVitals->heart_rate > 120) {
                $alerts->push([
                    'severity' => 'medium',
                    'category' => 'vital_signs',
                    'title' => 'Tachycardia',
                    'description' => 'Heart rate elevated.',
                    'data' => [
                        'heart_rate' => $latestVitals->heart_rate
                    ]
                ]);
            }
        }

        return $alerts;
    }

    protected function analyzeLabResults(Patient $patient)
    {
        $alerts = collect();
        $recentLabs = $patient->labResults()
            ->with('labTest')
            ->where('collected_at', '>=', now()->subDays(7))
            ->get();

        foreach ($recentLabs as $lab) {
            if ($this->isLabValueCritical($lab)) {
                $alerts->push([
                    'severity' => 'high',
                    'category' => 'lab_results',
                    'title' => "Critical {$lab->labTest->name}",
                    'description' => "Value: {$lab->value} {$lab->unit}",
                    'data' => [
                        'test_name' => $lab->labTest->name,
                        'value' => $lab->value,
                        'unit' => $lab->unit,
                        'reference_range' => [
                            'low' => $lab->labTest->reference_range_low,
                            'high' => $lab->labTest->reference_range_high
                        ]
                    ]
                ]);
            }
        }

        return $alerts;
    }

    protected function checkMedicationInteractions(Patient $patient)
    {
        $alerts = collect();
        $activeMedications = $patient->medications()
            ->where('status', 'active')
            ->get();

        // Check each medication pair for interactions
        $medicationPairs = $activeMedications->flatMap(function ($med1) use ($activeMedications) {
            return $activeMedications->map(function ($med2) use ($med1) {
                return [$med1, $med2];
            })->filter(function ($pair) {
                return $pair[0]->id < $pair[1]->id;
            });
        });

        foreach ($medicationPairs as $pair) {
            $interaction = $this->drugInteractionService->checkInteraction(
                $pair[0]->rxnorm_code,
                $pair[1]->rxnorm_code
            );

            if ($interaction && $interaction->severity >= 'moderate') {
                $alerts->push([
                    'severity' => $interaction->severity === 'severe' ? 'high' : 'medium',
                    'category' => 'medication_interaction',
                    'title' => 'Medication Interaction',
                    'description' => $interaction->description,
                    'data' => [
                        'medications' => [
                            $pair[0]->name,
                            $pair[1]->name
                        ],
                        'mechanism' => $interaction->mechanism,
                        'recommendation' => $interaction->recommendation
                    ]
                ]);
            }
        }

        return $alerts;
    }

    protected function getRelevantGuidelines(Patient $patient)
    {
        $diagnoses = $patient->diagnoses()->pluck('icd10_code');
        
        // Fetch relevant guidelines from cache or database
        $guidelines = Cache::remember(
            "guidelines:patient:{$patient->id}",
            now()->addHours(24),
            function () use ($diagnoses) {
                return $this->guidelineRepository->findByDiagnoses($diagnoses);
            }
        );

        // Filter guidelines based on patient characteristics
        return $guidelines->filter(function ($guideline) use ($patient) {
            return $this->isGuidelineApplicable($guideline, $patient);
        });
    }

    protected function isGuidelineApplicable(ClinicalGuideline $guideline, Patient $patient)
    {
        foreach ($guideline->criteria as $criterion) {
            switch ($criterion['type']) {
                case 'age':
                    if (!$this->meetsCriterionAge($criterion, $patient)) {
                        return false;
                    }
                    break;
                case 'gender':
                    if ($criterion['value'] !== $patient->gender) {
                        return false;
                    }
                    break;
                case 'lab_result':
                    if (!$this->meetsCriterionLabResult($criterion, $patient)) {
                        return false;
                    }
                    break;
                case 'condition':
                    if (!$this->meetsCriterionCondition($criterion, $patient)) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    protected function generateRecommendations(Patient $patient, Collection $guidelines)
    {
        $recommendations = collect();

        foreach ($guidelines as $guideline) {
            $patientFactors = $this->extractPatientFactors($patient, $guideline->required_factors);
            
            foreach ($guideline->recommendations as $recommendation) {
                if ($this->isRecommendationApplicable($recommendation, $patientFactors)) {
                    $recommendations->push([
                        'title' => $recommendation->title,
                        'description' => $recommendation->description,
                        'evidence_level' => $recommendation->evidence_level,
                        'source' => [
                            'name' => $guideline->source_name,
                            'url' => $guideline->source_url,
                            'last_updated' => $guideline->last_updated
                        ],
                        'factors' => $patientFactors,
                        'strength' => $recommendation->strength,
                        'actions' => $this->generateActionItems($recommendation, $patient)
                    ]);
                }
            }
        }

        return $recommendations->sortByDesc('evidence_level');
    }

    protected function extractPatientFactors(Patient $patient, array $requiredFactors)
    {
        $factors = [];

        foreach ($requiredFactors as $factor) {
            switch ($factor['type']) {
                case 'demographic':
                    $factors[$factor['name']] = $this->extractDemographicFactor($patient, $factor);
                    break;
                case 'lab_result':
                    $factors[$factor['name']] = $this->extractLabFactor($patient, $factor);
                    break;
                case 'vital_sign':
                    $factors[$factor['name']] = $this->extractVitalSignFactor($patient, $factor);
                    break;
                case 'medication':
                    $factors[$factor['name']] = $this->extractMedicationFactor($patient, $factor);
                    break;
            }
        }

        return $factors;
    }

    protected function generateActionItems($recommendation, Patient $patient)
    {
        $actions = collect();

        foreach ($recommendation->actions as $action) {
            switch ($action['type']) {
                case 'order_lab':
                    $actions->push([
                        'type' => 'order_lab',
                        'details' => [
                            'test_name' => $action['test_name'],
                            'urgency' => $action['urgency'],
                            'reason' => $action['reason']
                        ]
                    ]);
                    break;
                case 'medication_change':
                    $actions->push([
                        'type' => 'medication_change',
                        'details' => [
                            'medication' => $action['medication'],
                            'action' => $action['action'], // start, stop, modify
                            'reason' => $action['reason'],
                            'dosing' => $action['dosing'] ?? null
                        ]
                    ]);
                    break;
                case 'referral':
                    $actions->push([
                        'type' => 'referral',
                        'details' => [
                            'specialty' => $action['specialty'],
                            'urgency' => $action['urgency'],
                            'reason' => $action['reason']
                        ]
                    ]);
                    break;
                case 'monitoring':
                    $actions->push([
                        'type' => 'monitoring',
                        'details' => [
                            'parameter' => $action['parameter'],
                            'frequency' => $action['frequency'],
                            'duration' => $action['duration'],
                            'threshold' => $action['threshold'] ?? null
                        ]
                    ]);
                    break;
            }
        }

        return $actions;
    }

    protected function predictPatientOutcomes(Patient $patient)
    {
        // Initialize the prediction service
        $predictionService = app(ClinicalPredictionService::class);

        return [
            'mortality_risk' => $predictionService->predictMortalityRisk($patient),
            'readmission_risk' => $predictionService->predictReadmissionRisk($patient),
            'complication_risks' => $predictionService->predictComplicationRisks($patient),
            'length_of_stay' => $predictionService->predictLengthOfStay($patient)
        ];
    }

    protected function isLabValueCritical($lab)
    {
        $criticalRanges = config('clinical.critical_lab_ranges');
        
        if (isset($criticalRanges[$lab->labTest->code])) {
            $range = $criticalRanges[$lab->labTest->code];
            return $lab->value < $range['low'] || $lab->value > $range['high'];
        }

        return false;
    }
}