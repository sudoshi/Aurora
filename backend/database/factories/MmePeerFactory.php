<?php

namespace Database\Factories;

use App\Models\MmePeer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MmePeerFactory extends Factory
{
    protected $model = MmePeer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'base_url' => $this->faker->url(),
            'auth_token' => Str::random(40),
            'direction' => 'both',
            'active' => true,
            'contact_email' => $this->faker->safeEmail(),
        ];
    }
}
