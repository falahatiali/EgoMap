<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\AetherEngine\Models\AetherGeneratedProgram;
use Modules\MissionEngine\Support\MissionLocalizedText;

class UserAetherProgramHistoryService
{
    public function hasAppliedTarget(User $user, string $target): bool
    {
        return AetherGeneratedProgram::query()
            ->where('user_id', $user->id)
            ->where('applied_target', $target)
            ->exists();
    }

    public function latestForTarget(User $user, string $target): ?AetherGeneratedProgram
    {
        return AetherGeneratedProgram::query()
            ->withProgramGraph()
            ->where('user_id', $user->id)
            ->where('applied_target', $target)
            ->with('profile')
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, array{
     *     program: AetherGeneratedProgram,
     *     uuid: string,
     *     version: int,
     *     status: string,
     *     applied_target: ?string,
     *     target_label: string,
     *     title: string,
     *     summary: string,
     *     created_at_label: string,
     *     detail_url: string,
     *     mission_title: ?string,
     * }>
     */
    public function recordsForUser(User $user, ?string $locale = null): Collection
    {
        $locale = $locale ?? app()->getLocale();

        return AetherGeneratedProgram::query()
            ->withProgramGraph()
            ->where('user_id', $user->id)
            ->with(['profile', 'missionEnrollment'])
            ->latest('id')
            ->get()
            ->map(fn (AetherGeneratedProgram $program): array => $this->mapRecord($program, $locale));
    }

    public function summaryForProgram(AetherGeneratedProgram $program, string $locale): string
    {
        if (($program->applied_target ?? '') === 'meal') {
            return __('missions.ai_meal_summary_macros', [
                'calories' => $program->metabolic_target_calories ?? 0,
                'protein' => $program->metabolic_protein_grams ?? 0,
            ]);
        }

        return __('profile.program_summary_workout', [
            'days' => $program->workoutDays->count(),
            'split' => $program->split?->value ?? '—',
        ]);
    }

    /**
     * @return array{
     *     program: AetherGeneratedProgram,
     *     uuid: string,
     *     version: int,
     *     status: string,
     *     applied_target: ?string,
     *     target_label: string,
     *     title: string,
     *     summary: string,
     *     created_at_label: string,
     *     detail_url: string,
     *     mission_title: ?string,
     * }
     */
    private function mapRecord(AetherGeneratedProgram $program, string $locale): array
    {
        $target = $program->applied_target;
        $targetLabel = match ($target) {
            'workout' => __('missions.ai_workout'),
            'meal' => __('missions.ai_meal'),
            default => __('profile.program_target_unknown'),
        };

        $missionTitle = null;

        if ($program->missionEnrollment !== null) {
            $snapshot = is_array($program->missionEnrollment->template_snapshot)
                ? $program->missionEnrollment->template_snapshot
                : [];
            $missionTitle = $program->missionEnrollment->title
                ?: MissionLocalizedText::forLocale($snapshot['title'] ?? '', $locale);
        }

        return [
            'program' => $program,
            'uuid' => $program->uuid,
            'version' => $program->version,
            'status' => $program->status->value,
            'applied_target' => $target,
            'target_label' => $targetLabel,
            'title' => __('profile.program_card_title', [
                'target' => $targetLabel,
                'version' => $program->version,
            ]),
            'summary' => $this->summaryForProgram($program, $locale),
            'created_at_label' => $program->created_at?->locale($locale)->translatedFormat('j F Y') ?? '',
            'detail_url' => route('profile.program.show', ['uuid' => $program->uuid]),
            'mission_title' => $missionTitle !== '' ? $missionTitle : null,
        ];
    }
}
