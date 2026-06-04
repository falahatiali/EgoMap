<?php

namespace Database\Factories;

use App\Enums\GhostModeEventType;
use App\Models\GhostModeEvent;
use App\Models\NoContactProtocol;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GhostModeEvent>
 */
class GhostModeEventFactory extends Factory
{
    protected $model = GhostModeEvent::class;

    public function definition(): array
    {
        return [
            'no_contact_protocol_id' => NoContactProtocol::factory(),
            'type' => GhostModeEventType::Slip,
            'trigger' => 'felt_weak',
            'user_text' => null,
            'ai_result' => [
                'recovery_task' => '10 pushups or 5 minutes of deep breathing.',
            ],
        ];
    }

    public function emergency(): static
    {
        return $this->state(fn (): array => [
            'type' => GhostModeEventType::Emergency,
            'trigger' => null,
            'user_text' => null,
            'ai_result' => [
                'message' => 'This is a critical moment. Do not decide now.',
                'exercise' => '4 seconds in, 6 seconds out — ten breaths.',
                'source' => 'fallback',
            ],
        ]);
    }

    public function blackhole(): static
    {
        return $this->state(fn (): array => [
            'type' => GhostModeEventType::Blackhole,
            'trigger' => null,
            'user_text' => fake()->sentence(),
            'ai_result' => [
                'regret_probability' => 72,
                'dominant_emotions' => 'longing, anxiety',
                'source' => 'fallback',
            ],
        ]);
    }
}
