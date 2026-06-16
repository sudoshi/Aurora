<?php

namespace Database\Factories;

use App\Models\DiagnosticOdyssey;
use App\Models\MmeMatch;
use App\Models\MmePeer;
use Illuminate\Database\Eloquent\Factories\Factory;

class MmeMatchFactory extends Factory
{
    protected $model = MmeMatch::class;

    public function definition(): array
    {
        return [
            'odyssey_id' => DiagnosticOdyssey::factory(),
            'direction' => 'outbound',
            'peer_id' => MmePeer::factory(),
            'score' => 0.8,
            'matched_label' => 'External case 1',
            'matched_contact_name' => 'Dr X',
            'matched_contact_href' => 'mailto:x@example.org',
            'matched_profile' => ['id' => 'ext-1', 'contact' => ['name' => 'Dr X', 'href' => 'mailto:x@example.org']],
            'status' => 'new',
        ];
    }
}
