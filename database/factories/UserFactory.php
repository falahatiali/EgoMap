<?php

namespace Database\Factories;

use App\Enums\BreakupDuration;
use App\Enums\BreakupInitiator;
use App\Enums\PrimaryStruggle;
use App\Enums\RecoveryPhase;
use App\Enums\RelationshipDuration;
use App\Models\NoContactProtocol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function recoveryDiagnose(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship_duration' => RelationshipDuration::OneToThreeYears->value,
            'breakup_duration' => BreakupDuration::Months->value,
            'breakup_initiator' => BreakupInitiator::Them->value,
            'primary_struggle' => PrimaryStruggle::Worthless->value,
            'recovery_phase' => RecoveryPhase::Detox->value,
            'recovery_triage_completed_at' => now(),
        ]);
    }

    public function recoveryDetox(): static
    {
        return $this->state(fn (array $attributes) => [
            'relationship_duration' => RelationshipDuration::OneToThreeYears->value,
            'breakup_duration' => BreakupDuration::Weeks->value,
            'breakup_initiator' => BreakupInitiator::Them->value,
            'primary_struggle' => PrimaryStruggle::Stalking->value,
            'recovery_phase' => RecoveryPhase::Detox->value,
            'recovery_triage_completed_at' => now(),
        ]);
    }

    public function recoveryWithAdvancedUnlocked(): static
    {
        return $this->recoveryDiagnose()->afterCreating(function (User $user): void {
            $startedAt = now()->subHours(25);

            NoContactProtocol::factory()->create([
                'user_id' => $user->id,
                'streak_started_at' => $startedAt,
                'target_ends_at' => $startedAt->copy()->addDays(90),
            ]);
        });
    }
}
