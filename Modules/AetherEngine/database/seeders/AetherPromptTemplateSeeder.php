<?php

namespace Modules\AetherEngine\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\AetherEngine\Models\AetherPromptTemplate;

class AetherPromptTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'slug' => 'tough-love-coach',
                'name' => 'Tough Love Coach',
                'tone' => 'tough_love',
                'system_prompt' => 'Tone override: tough_love — short, punchy, commanding sentences. No excuses framing.',
                'task_prompt' => 'Generate the full 12-week program JSON from userProfile. Align exercises and macros with deterministicProgram. Output only valid JSON.',
                'is_default' => false,
            ],
            [
                'slug' => 'gentle-coach',
                'name' => 'Gentle Encouragement Coach',
                'tone' => 'gentle',
                'system_prompt' => 'Tone override: gentle — supportive, collaborative language. Acknowledge the rebuild journey.',
                'task_prompt' => 'Generate the full 12-week program JSON from userProfile. Align exercises and macros with deterministicProgram. Output only valid JSON.',
                'is_default' => true,
            ],
            [
                'slug' => 'technical-coach',
                'name' => 'Technical Data-Driven Coach',
                'tone' => 'technical',
                'system_prompt' => 'Tone override: technical — precise exercise science terminology, RPE, volume landmarks, periodization notes.',
                'task_prompt' => 'Generate the full 12-week program JSON from userProfile. Align exercises and macros with deterministicProgram. Output only valid JSON.',
                'is_default' => false,
            ],
        ];

        foreach ($templates as $template) {
            AetherPromptTemplate::query()->updateOrCreate(
                ['slug' => $template['slug']],
                array_merge($template, ['is_active' => true]),
            );
        }
    }
}
