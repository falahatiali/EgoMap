<?php

namespace Modules\VirtueEngine\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\VirtueEngine\Enums\VirtueGoalType;
use Modules\VirtueEngine\Enums\VirtueHabitCategory;
use Modules\VirtueEngine\Enums\VirtueRoutineStatus;
use Modules\VirtueEngine\Models\VirtueHabit;
use Modules\VirtueEngine\Models\VirtueRoutine;

class VirtueHabitService
{
    public function __construct(private readonly VirtueAIService $ai) {}

    /**
     * @return Collection<int, VirtueHabit>
     */
    public function listPredefinedHabits(): Collection
    {
        return VirtueHabit::query()
            ->where('is_predefined', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Analyze a custom habit description via AI and persist it as a non-predefined habit.
     */
    public function analyzeAndStoreCustomHabit(string $description): VirtueHabit
    {
        $aiResult = $this->ai->analyzeHabit($description);

        $category = $this->resolveCategory($aiResult['category'] ?? null);

        return VirtueHabit::query()->create([
            'slug' => 'custom-'.Str::slug($description, '-').'-'.time(),
            'name' => Str::title(Str::limit($description, 60)),
            'category' => $category,
            'description' => $description,
            'ai_root_cause' => $aiResult['root_cause'] ?? null,
            'ai_steps' => $aiResult['steps'] ?? null,
            'ai_affirmation' => $aiResult['affirmation'] ?? null,
            'is_predefined' => false,
            'is_active' => true,
            'sort_order' => 999,
        ]);
    }

    /**
     * Start a new habit routine for a user.
     *
     * @param  array{virtue_habit_id: int, personal_note?: string|null, goal_type?: string, goal_target?: int}  $data
     */
    public function startRoutine(User $user, array $data): VirtueRoutine
    {
        $goalType = VirtueGoalType::tryFrom($data['goal_type'] ?? '') ?? VirtueGoalType::DaysCount;
        $goalTarget = (int) ($data['goal_target'] ?? config("virtue.goal_types.{$goalType->value}.default_target", 21));

        return VirtueRoutine::query()->create([
            'user_id' => $user->id,
            'virtue_habit_id' => $data['virtue_habit_id'],
            'personal_note' => $data['personal_note'] ?? null,
            'goal_type' => $goalType,
            'goal_target' => $goalTarget,
            'status' => VirtueRoutineStatus::Active,
        ]);
    }

    /**
     * @return Collection<int, VirtueRoutine>
     */
    public function userRoutines(User $user, ?string $status = null): Collection
    {
        return VirtueRoutine::query()
            ->with('habit')
            ->where('user_id', $user->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->get();
    }

    public function findRoutineForUser(int $routineId, User $user): VirtueRoutine
    {
        return VirtueRoutine::query()
            ->with('habit')
            ->where('user_id', $user->id)
            ->findOrFail($routineId);
    }

    private function resolveCategory(?string $aiCategory): VirtueHabitCategory
    {
        if ($aiCategory === null) {
            return VirtueHabitCategory::Custom;
        }

        return VirtueHabitCategory::tryFrom(strtolower($aiCategory)) ?? VirtueHabitCategory::Custom;
    }
}
