<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PredictionModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Events\HighRiskPatientIdentified;

class ClinicalPredictionService
{
    protected $modelRegistry;
    protected $featureExtractor;

    public function __construct(
        ModelRegistry $modelRegistry,
        FeatureExtractor $featureExtractor
    ) {
        $this->modelRegistry = $modelRegistry;
        $this->featureExtractor = $featureExtractor;
    }

    public function predictMortalityRisk(Patient $patient)
    {
        try {
            $model = $this->modelRegistry->getModel('mortality_prediction');
            $features = $this->featureExtractor->extractMortalityFeatures($patient);
            
            $prediction = $model->predict($features);
            
            if ($prediction['risk_score'] > 0.7) {
                event(new HighRiskPatientIdentified($patient, 'mortality', $prediction));
            }

            return [
                'risk_score' => $prediction['risk_score'],
                'confidence' => $prediction['confidence'],
                'contributing_factors' => $prediction['contributing_factors'],
                'recommended_actions' => $this->generateRiskActions($prediction)
            ];
        } catch (\Exception $e) {
            Log::error('Error predicting mortality risk', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function predictReadmissionRisk(Patient $patient)
    {
        try {
            $model = $this->modelRegistry->getModel('readmission_prediction');
            $features = $this->featureExtractor->extractReadmissionFeatures($patient);
            
            $prediction = $model->predict($features);
            
            if ($prediction['risk_score'] > 0.6) {
                event(new HighRiskPatientIdentified($patient, 'readmission', $prediction));
            }

            return [
                'risk_score' => $prediction['risk_score'],
                'timeframe' => '30_days',
                'confidence' => $prediction['confidence'],
                'contributing_factors' => $prediction['contributing_factors'],
                'recommended_actions' => $this->generateRiskActions($prediction)
            ];
        } catch (\Exception $e) {
            Log::error('Error predicting readmission risk', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function predictComplicationRisks(Patient $patient)
    {
        try {
            $complications = config('clinical.monitored_complications');
            $predictions = [];

            foreach ($complications as $complication) {
                $model = $this->modelRegistry->getModel("{$complication}_prediction");
                $features = $this->featureExtractor->extractComplicationFeatures($patient, $complication);
                
                $prediction = $model->predict($features);
                
                if ($prediction['risk_score'] > 0.5) {
                    event(new HighRiskPatientIdentified($patient, $complication, $prediction));
                }

                $predictions[$complication] = [
                    'risk_score' => $prediction['risk_score'],
                    'confidence' => $prediction['confidence'],
                    'contributing_factors' => $prediction['contributing_factors'],
                    'recommended_actions' => $this->generateRiskActions($prediction)
                ];
            }

            return $predictions;
        } catch (\Exception $e) {
            Log::error('Error predicting complications', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function predictLengthOfStay(Patient $patient)
    {
        try {
            $model = $this->modelRegistry->getModel('length_of_stay_prediction');
            $features = $this->featureExtractor->extractLengthOfStayFeatures($patient);
            
            $prediction = $model->predict($features);

            return [
                'predicted_days' => $prediction['days'],
                'confidence_interval' => [
                    'lower' => $prediction['ci_lower'],
                    'upper' => $prediction['ci_upper']
                ],
                'contributing_factors' => $prediction['contributing_factors'],
                'resource_implications' => $this->calculateResourceImplications($prediction)
            ];
        } catch (\Exception $e) {
            Log::error('Error predicting length of stay', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected function generateRiskActions($prediction)
    {
        $actions = [];

        // Generate actions based on risk factors
        foreach ($prediction['contributing_factors'] as $factor) {
            switch ($factor['type']) {
                case 'vital_sign':
                    $actions[] = [
                        'type' => 'monitoring',
                        'description' => "Increase monitoring frequency of {$factor['name']}",
                        'details' => [
                            'parameter' => $factor['name'],
                            'frequency' => 'every_4_hours',
                            'duration' => '72_hours'
                        ]
                    ];
                    break;
                case 'lab_result':
                    $actions[] = [
                        'type' => 'lab_order',
                        'description' => "Repeat {$factor['name']} test",
                        'details' => [
                            'test' => $factor['name'],
                            'urgency' => 'routine',
                            'frequency' => 'daily'
                        ]
                    ];
                    break;
                case 'medication':
                    $actions[] = [
                        'type' => 'medication_review',
                        'description' => "Review medication: {$factor['name']}",
                        'details' => [
                            'medication' => $factor['name'],
                            'focus' => 'dosing_and_interactions'
                        ]
                    ];
                    break;
            }
        }

        return $actions;
    }

    protected function calculateResourceImplications($prediction)
    {
        return [
            'bed_days' => $prediction['days'],
            'nursing_hours' => $prediction['days'] * 24 * 0.25, // Assuming 25% nurse time
            'estimated_cost' => $prediction['days'] * config('clinical.daily_cost'),
            'resource_constraints' => $this->checkResourceConstraints($prediction)
        ];
    }

    protected function checkResourceConstraints($prediction)
    {
        // Check current resource availability
        $constraints = [];
        
        // Bed availability
        $bedAvailability = Cache::get('bed_availability');
        if ($bedAvailability < $prediction['days']) {
            $constraints[] = [
                'type' => 'bed_availability',
                'severity' => 'high',
                'description' => 'Limited bed availability for predicted length of stay'
            ];
        }

        // Staffing levels
        $staffingLevels = Cache::get('staffing_levels');
        if ($staffingLevels < $prediction['required_staff']) {
            $constraints[] = [
                'type' => 'staffing',
                'severity' => 'medium',
                'description' => 'Potential staffing constraints for required care level'
            ];
        }

        return $constraints;
    }
}