<?php

namespace Database\Factories;

use App\Enums\NoContactStatus;
use App\Models\NoContactProtocol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoContactProtocol>
 */
class NoContactProtocolFactory extends Factory
{
    protected $model = NoContactProtocol::class;

    public function definition(): array
    {
        $startedAt = now()->subDays(5);
        $durationDays = 90;

        return [
            'user_id' => User::factory(),
            'guest_token' => null,
            'duration_days' => $durationDays,
            'status' => NoContactStatus::Active,
            'streak_started_at' => $startedAt,
            'target_ends_at' => $startedAt->copy()->addDays($durationDays),
            'slip_count' => 0,
        ];
    }

    public function forGuest(string $token): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'guest_token' => $token,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (): array {
            $startedAt = now()->subDays(100);

            return [
                'status' => NoContactStatus::Completed,
                'streak_started_at' => $startedAt,
                'target_ends_at' => $startedAt->copy()->addDays(90),
                'completed_at' => now(),
            ];
        });
    }
}
