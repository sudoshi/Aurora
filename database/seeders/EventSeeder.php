<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::create([
            'title' => 'Patient Consultation',
            'time' => '2:30 PM',
            'duration' => 60,
            'location' => 'Room 204',
            'category' => 'multidisciplinary',
            'description' => 'Initial consultation for post-surgical recovery plan',
            'team' => [
                [
                    'name' => 'Dr. Sarah Johnson',
                    'role' => 'Primary Care Physician',
                    'available' => true
                ],
                [
                    'name' => 'Dr. Michael Chen',
                    'role' => 'Specialist',
                    'available' => true
                ],
                [
                    'name' => 'Emma Wilson',
                    'role' => 'Nurse Practitioner',
                    'available' => false
                ]
            ],
            'patients' => [
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'age' => 45,
                    'condition' => 'Post-surgical recovery',
                    'status' => 'Stable'
                ]
            ],
            'related_items' => [
                [
                    'type' => 'document',
                    'title' => 'Surgery Report',
                    'description' => 'Detailed report from the surgical procedure'
                ],
                [
                    'type' => 'note',
                    'title' => 'Recovery Notes',
                    'description' => 'Daily progress notes from nursing staff'
                ]
            ]
        ]);
    }
}
