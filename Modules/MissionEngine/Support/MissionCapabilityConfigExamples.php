<?php

namespace Modules\MissionEngine\Support;

use Modules\MissionEngine\Enums\MissionCapabilityKey;

final class MissionCapabilityConfigExamples
{
    public static function jsonFor(MissionCapabilityKey $key): ?string
    {
        $config = match ($key) {
            MissionCapabilityKey::Task => [
                'features' => [
                    'ai_workout_plan' => [
                        'requires_pro' => true,
                        'label' => ['en' => 'AI workout plan', 'fa' => 'AI workout plan'],
                    ],
                ],
            ],
            MissionCapabilityKey::Nutrition => [
                'features' => [
                    'ai_meal_plan' => [
                        'requires_pro' => true,
                        'label' => ['en' => 'AI meal plan', 'fa' => 'AI meal plan'],
                    ],
                ],
            ],
            MissionCapabilityKey::Registration => [
                'checklist' => [
                    ['key' => 'visit_gym', 'label' => ['en' => 'Visit gym & ask about membership', 'fa' => '']],
                    ['key' => 'sign_contract', 'label' => ['en' => 'Sign membership contract', 'fa' => '']],
                    ['key' => 'first_session', 'label' => ['en' => 'Book first session', 'fa' => '']],
                ],
            ],
            MissionCapabilityKey::Measurement => [
                'metrics' => [
                    ['key' => 'weight', 'unit' => 'kg', 'label' => ['en' => 'Body weight', 'fa' => '']],
                    ['key' => 'body_fat', 'unit' => '%', 'label' => ['en' => 'Body fat', 'fa' => '']],
                ],
            ],
            default => [],
        };

        if ($config === []) {
            return "{\n}\n";
        }

        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }

    public static function hintFor(MissionCapabilityKey $key): string
    {
        return match ($key) {
            MissionCapabilityKey::Task => 'features → Pro-gated AI workout plan',
            MissionCapabilityKey::Nutrition => 'features → Pro-gated AI meal plan',
            MissionCapabilityKey::Registration => 'checklist → gym signup steps',
            MissionCapabilityKey::Measurement => 'metrics → weight, body fat, etc.',
            MissionCapabilityKey::Schedule => 'Usually no config — use Fields for gym days.',
            MissionCapabilityKey::Supplement => 'Usually no config — users log intake in workspace.',
            MissionCapabilityKey::Equipment => 'Usually no config — equipment list lives in Fields.',
            MissionCapabilityKey::Mindset => 'Optional prompts or journal keys (custom).',
            default => 'Leave empty {} unless you need custom keys.',
        };
    }
}
