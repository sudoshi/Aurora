<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommonsChannelSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('app.users')->where('email', 'admin@acumenus.net')->value('id');

        $channels = [
            [
                'name' => 'general',
                'slug' => 'general',
                'description' => 'General discussion for the Aurora team',
                'type' => 'topic',
                'visibility' => 'public',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'tumor-board',
                'slug' => 'tumor-board',
                'description' => 'Tumor board case discussions and scheduling',
                'type' => 'topic',
                'visibility' => 'public',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'case-review',
                'slug' => 'case-review',
                'description' => 'Complex case reviews and multidisciplinary consultations',
                'type' => 'topic',
                'visibility' => 'public',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'surgical-planning',
                'slug' => 'surgical-planning',
                'description' => 'Pre-operative planning and surgical case discussions',
                'type' => 'topic',
                'visibility' => 'public',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'rare-diseases',
                'slug' => 'rare-diseases',
                'description' => 'Rare disease diagnostic odyssey discussions',
                'type' => 'topic',
                'visibility' => 'public',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'clinical-research',
                'slug' => 'clinical-research',
                'description' => 'Clinical trial updates and research discussions',
                'type' => 'topic',
                'visibility' => 'public',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'ask-abby',
                'slug' => 'ask-abby',
                'description' => 'AI-assisted clinical questions powered by Abby',
                'type' => 'topic',
                'visibility' => 'public',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'announcements',
                'slug' => 'announcements',
                'description' => 'System announcements and important updates',
                'type' => 'announcement',
                'visibility' => 'public',
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($channels as $channel) {
            $exists = DB::table('commons_channels')
                ->where('slug', $channel['slug'])
                ->exists();

            if (!$exists) {
                $channelId = DB::table('commons_channels')->insertGetId($channel);

                // Auto-join admin to all channels
                if ($adminId) {
                    DB::table('commons_channel_members')->insert([
                        'channel_id' => $channelId,
                        'user_id' => $adminId,
                        'role' => 'owner',
                        'joined_at' => $now,
                    ]);
                }
            }
        }
    }
}
