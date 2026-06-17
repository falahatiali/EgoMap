<?php

namespace App\Services\Mood;

use App\Enums\IdeaGoalCadence;
use App\Enums\IdeaStatus;
use App\Models\MoodEntry;
use App\Models\User;
use App\Models\UserIdea;
use App\Support\LocaleConfig;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class IdeaService
{
    /**
     * @return array{
     *     raw: list<array<string, mixed>>,
     *     mature: list<array<string, mixed>>,
     *     harvested: list<array<string, mixed>>
     * }
     */
    public function gardenForUser(User $user): array
    {
        $ideas = UserIdea::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        return [
            'raw' => $ideas->where('status', IdeaStatus::Raw)->values()->map(fn (UserIdea $idea) => $this->presentIdea($idea))->all(),
            'mature' => $ideas->where('status', IdeaStatus::Mature)->values()->map(fn (UserIdea $idea) => $this->presentIdea($idea))->all(),
            'harvested' => $ideas->where('status', IdeaStatus::Harvested)->values()->map(fn (UserIdea $idea) => $this->presentIdea($idea))->all(),
        ];
    }

    /**
     * @param  array{seed_text: string, source?: string, mood_entry_id?: int|null}  $data
     * @return array<string, mixed>
     */
    public function createSeed(User $user, array $data): array
    {
        $moodEntryId = $data['mood_entry_id'] ?? null;

        if ($moodEntryId !== null) {
            $owned = MoodEntry::query()
                ->where('user_id', $user->id)
                ->whereKey($moodEntryId)
                ->exists();

            if (! $owned) {
                throw ValidationException::withMessages([
                    'mood_entry_id' => __('validation.exists', ['attribute' => 'mood_entry_id']),
                ]);
            }
        }

        $idea = UserIdea::query()->create([
            'user_id' => $user->id,
            'mood_entry_id' => $moodEntryId,
            'seed_text' => trim($data['seed_text']),
            'source' => $data['source'] ?? 'manual',
            'status' => IdeaStatus::Raw,
        ]);

        return $this->presentIdea($idea);
    }

    /**
     * @param  array<string, mixed>|null  $details
     * @return array<string, mixed>
     */
    public function matureIdea(User $user, UserIdea $idea, ?array $details = null): array
    {
        $this->assertOwner($user, $idea);

        if ($idea->status !== IdeaStatus::Raw) {
            throw ValidationException::withMessages([
                'status' => 'Only raw seeds can be matured.',
            ]);
        }

        $idea->update([
            'status' => IdeaStatus::Mature,
            'matured_details' => $details ?? [
                'goal_question' => 'What would success look like in one sentence?',
                'first_step' => 'What is the first five-minute step?',
                'why_it_matters' => 'Why does this idea matter to your rebuild?',
            ],
        ]);

        return $this->presentIdea($idea->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function harvestIdea(User $user, UserIdea $idea, IdeaGoalCadence $cadence): array
    {
        $this->assertOwner($user, $idea);

        if ($idea->status !== IdeaStatus::Mature) {
            throw ValidationException::withMessages([
                'status' => 'Only mature ideas can be harvested into goals.',
            ]);
        }

        $idea->update([
            'status' => IdeaStatus::Harvested,
            'goal_cadence' => $cadence,
            'progress' => 0,
            'harvested_at' => now(),
        ]);

        return $this->presentIdea($idea->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function updateProgress(User $user, UserIdea $idea, int $progress): array
    {
        $this->assertOwner($user, $idea);

        if ($idea->status !== IdeaStatus::Harvested) {
            throw ValidationException::withMessages([
                'progress' => 'Only harvested goals can be tracked.',
            ]);
        }

        $idea->update([
            'progress' => max(0, min(100, $progress)),
        ]);

        return $this->presentIdea($idea->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function presentIdea(UserIdea $idea): array
    {
        $locale = LocaleConfig::resolve(app()->getLocale());

        return [
            'id' => $idea->id,
            'seed_text' => $idea->seed_text,
            'source' => $idea->source,
            'status' => $idea->status->value,
            'status_label' => __('mood.idea_status.'.$idea->status->value, locale: $locale),
            'matured_details' => $idea->matured_details,
            'goal_cadence' => $idea->goal_cadence?->value,
            'goal_cadence_label' => $idea->goal_cadence !== null
                ? __('mood.goal_cadence.'.$idea->goal_cadence->value, locale: $locale)
                : null,
            'progress' => $idea->progress,
            'mood_entry_id' => $idea->mood_entry_id,
            'harvested_at' => $idea->harvested_at?->toIso8601String(),
            'created_at_human' => $idea->created_at?->diffForHumans(),
        ];
    }

    private function assertOwner(User $user, UserIdea $idea): void
    {
        if ($idea->user_id !== $user->id) {
            abort(403);
        }
    }

    /**
     * @return Collection<int, UserIdea>
     */
    public function ideasNeedingMaturationPrompt(User $user): Collection
    {
        return UserIdea::query()
            ->where('user_id', $user->id)
            ->where('status', IdeaStatus::Raw)
            ->where('created_at', '<=', now()->subHours(48))
            ->get();
    }
}
