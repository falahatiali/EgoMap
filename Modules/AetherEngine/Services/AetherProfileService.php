<?php

namespace Modules\AetherEngine\Services;

use App\Models\User;
use Modules\AetherEngine\Models\AetherUserProfile;

class AetherProfileService
{
    public function __construct(private InjuryTagResolver $injuryTagResolver) {}

    /**
     * @param  array<string, mixed>  $answers
     */
    public function upsertForUser(User $user, array $answers, bool $markComplete = true): AetherUserProfile
    {
        $injuryTags = $this->injuryTagResolver->resolve(
            $answers['injuries_limitations'] ?? null,
            $answers['injury_tags'] ?? null,
        );

        $attributes = collect($answers)
            ->except(['injury_tags'])
            ->merge([
                'user_id' => $user->id,
                'injury_tags' => $injuryTags,
                'questionnaire_completed_at' => $markComplete ? now() : null,
            ])
            ->all();

        return AetherUserProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            $attributes,
        );
    }

    public function forUser(User $user): ?AetherUserProfile
    {
        return AetherUserProfile::query()->where('user_id', $user->id)->first();
    }
}
