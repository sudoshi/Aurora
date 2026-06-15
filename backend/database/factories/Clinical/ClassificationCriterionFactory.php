<?php

namespace Database\Factories\Clinical;

use App\Models\Clinical\ClassificationCriterion;
use App\Models\Clinical\VariantClassification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassificationCriterionFactory extends Factory
{
    protected $model = ClassificationCriterion::class;

    public function definition(): array
    {
        return [
            'classification_id' => VariantClassification::factory(),
            'code' => 'PM2',
            'applied_strength' => 'supporting',
            'points' => 1,
            'data_source' => 'manual',
            'set_by' => 'curator',
            'set_by_user_id' => User::factory(),
        ];
    }
}
