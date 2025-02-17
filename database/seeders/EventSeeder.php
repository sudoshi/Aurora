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
            'id' => 46,
            'title' => 'Abdominal Cases MDC',
            'time' => '10:00 AM',
            'duration' => 90,
            'location' => 'Conference Room 2B',
            'category' => 'oncology',
            'description' => 'Multidisciplinary review of complex abdominal oncology cases',
            'team' => json_encode([
                [
                    'name' => 'Dr. Lisa Anderson',
                    'role' => 'Medical Oncology',
                    'available' => true
                ],
                [
                    'name' => 'Dr. David Kim',
                    'role' => 'Radiation Oncology',
                    'available' => true
                ],
                [
                    'name' => 'Dr. Rachel Green',
                    'role' => 'Pathology',
                    'available' => true
                ]
            ]),
            'related_items' => json_encode([
                [
                    'type' => 'document',
                    'title' => 'Recent Imaging',
                    'description' => 'Latest CT and PET scan results'
                ],
                [
                    'type' => 'document',
                    'title' => 'Treatment Protocols',
                    'description' => 'Current treatment plans and response assessments'
                ]
            ])
        ]);

        $event46 = Event::find(46);
        $event46->teamMembers()->sync([
            1 => ['role' => 'Medical Oncology'],
            2 => ['role' => 'Radiation Oncology'],
            3 => ['role' => 'Pathology']
        ]);
        $event46->patients()->sync([5, 6]);


        $event = Event::create([
            'title' => 'Patient Consultation',

            'time' => '2:30 PM',
            'duration' => 60,
            'location' => 'Room 204',
            'category' => 'multidisciplinary',
            'description' => 'Initial consultation for post-surgical recovery plan',
            'team' => json_encode([
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
            ]),
            'related_items' => json_encode([
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
            ])
        ]);

        $event->teamMembers()->attach([1], ['role' => 'doctor']);
        $event->patients()->attach([1]);

    }
}
