<?php

namespace Modules\GamificationEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\GamificationEngine\Enums\GamificationPunishmentDifficulty;
use Modules\GamificationEngine\Enums\GamificationPunishmentType;
use Modules\GamificationEngine\Models\GamificationPunishment;

class GamificationPunishmentsSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'slug' => 'burpees_10',
                'title' => '10 burpees',
                'description' => 'Full range of motion. Rest 30s between sets if needed.',
                'type' => GamificationPunishmentType::Physical,
                'difficulty' => GamificationPunishmentDifficulty::Medium,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 5,
                'min_slip_severity' => 1,
                'sort_order' => 10,
            ],
            [
                'slug' => 'pushups_30',
                'title' => '30 push-ups',
                'description' => 'Knee push-ups count. Stop if you feel pain.',
                'type' => GamificationPunishmentType::Physical,
                'difficulty' => GamificationPunishmentDifficulty::Medium,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 6,
                'min_slip_severity' => 2,
                'sort_order' => 20,
            ],
            [
                'slug' => 'squats_40',
                'title' => '40 squats',
                'description' => 'Controlled tempo. Breathe through each rep.',
                'type' => GamificationPunishmentType::Physical,
                'difficulty' => GamificationPunishmentDifficulty::Hard,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 8,
                'min_slip_severity' => 2,
                'sort_order' => 30,
            ],
            [
                'slug' => 'run_in_place_2min',
                'title' => '2 minutes running in place',
                'description' => 'Lift knees. Stay in one spot.',
                'type' => GamificationPunishmentType::Physical,
                'difficulty' => GamificationPunishmentDifficulty::Easy,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 2,
                'min_slip_severity' => 1,
                'sort_order' => 40,
            ],
            [
                'slug' => 'plank_1min',
                'title' => '1 minute plank',
                'description' => 'Hold a straight line. Drop to knees if needed.',
                'type' => GamificationPunishmentType::Physical,
                'difficulty' => GamificationPunishmentDifficulty::Easy,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 2,
                'min_slip_severity' => 1,
                'sort_order' => 50,
            ],
            [
                'slug' => 'walk_15min',
                'title' => '15-minute brisk walk',
                'description' => 'Outside if you can. No phone scrolling.',
                'type' => GamificationPunishmentType::Physical,
                'difficulty' => GamificationPunishmentDifficulty::Easy,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 15,
                'min_slip_severity' => 1,
                'sort_order' => 60,
            ],
            [
                'slug' => 'jump_rope_10min',
                'title' => '10 minutes jump rope (or jumping jacks)',
                'description' => 'Skip rope if you do not have one — same effort.',
                'type' => GamificationPunishmentType::Physical,
                'difficulty' => GamificationPunishmentDifficulty::Medium,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 10,
                'min_slip_severity' => 2,
                'sort_order' => 70,
            ],
            [
                'slug' => 'letter_future_self',
                'title' => 'Write a 500-word letter to your future self',
                'description' => 'Honest, kind, forward-looking. Delete it after if you want — the act matters.',
                'type' => GamificationPunishmentType::Writing,
                'difficulty' => GamificationPunishmentDifficulty::Medium,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 20,
                'min_slip_severity' => 1,
                'sort_order' => 80,
            ],
            [
                'slug' => 'box_breathing_5min',
                'title' => '5 minutes box breathing',
                'description' => 'Inhale 4s · hold 4s · exhale 4s · hold 4s. Repeat.',
                'type' => GamificationPunishmentType::Mental,
                'difficulty' => GamificationPunishmentDifficulty::Easy,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 5,
                'min_slip_severity' => 1,
                'sort_order' => 90,
            ],
            [
                'slug' => 'three_wins',
                'title' => 'Write 3 things you did well today',
                'description' => 'No comparison to her. Only you.',
                'type' => GamificationPunishmentType::Writing,
                'difficulty' => GamificationPunishmentDifficulty::Easy,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 5,
                'min_slip_severity' => 1,
                'sort_order' => 100,
            ],
            [
                'slug' => 'friend_call_10min',
                'title' => 'Call a friend for 10 minutes',
                'description' => 'Talk about anything except her. No proof needed — honor system.',
                'type' => GamificationPunishmentType::Mental,
                'difficulty' => GamificationPunishmentDifficulty::Medium,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 10,
                'min_slip_severity' => 2,
                'sort_order' => 110,
            ],
            [
                'slug' => 'cold_water_face',
                'title' => 'Cold water on face + 3 deep breaths',
                'description' => 'Reset your nervous system before you doom-scroll.',
                'type' => GamificationPunishmentType::Time,
                'difficulty' => GamificationPunishmentDifficulty::Easy,
                'points' => 0,
                'coins' => 0,
                'estimated_minutes' => 2,
                'min_slip_severity' => 3,
                'sort_order' => 120,
            ],
        ];

        foreach ($items as $item) {
            GamificationPunishment::query()->updateOrCreate(
                ['slug' => $item['slug']],
                array_merge($item, ['is_active' => true]),
            );
        }
    }
}
