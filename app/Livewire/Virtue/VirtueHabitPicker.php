<?php

namespace App\Livewire\Virtue;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\VirtueEngine\Models\VirtueHabit;
use Modules\VirtueEngine\Services\VirtueHabitService;

#[Layout('layouts.app')]
class VirtueHabitPicker extends Component
{
    public string $locale = 'en';

    public ?int $selectedHabitId = null;

    public string $goalType = 'days_count';

    public int $goalTarget = 21;

    public string $personalNote = '';

    public string $customDescription = '';

    public bool $isAnalyzing = false;

    public ?array $analyzedHabit = null;

    public string $activeTab = 'suggested';

    public bool $isStarting = false;

    public function mount(): void
    {
        $this->locale = session('locale', 'en');
    }

    public function selectHabit(int $id): void
    {
        $this->selectedHabitId = $id;
        $this->analyzedHabit = null;
    }

    public function analyzeCustomHabit(): void
    {
        $this->validate(['customDescription' => ['required', 'string', 'min:5', 'max:500']]);

        $this->isAnalyzing = true;

        try {
            $habit = app(VirtueHabitService::class)->analyzeAndStoreCustomHabit($this->customDescription);
            $this->analyzedHabit = [
                'id' => $habit->id,
                'name' => $habit->name,
                'category_label' => $habit->category->label(),
                'ai_root_cause' => $habit->ai_root_cause,
                'ai_steps' => $habit->ai_steps ?? [],
                'ai_affirmation' => $habit->ai_affirmation,
            ];
            $this->selectedHabitId = $habit->id;
        } catch (\Throwable) {
            $this->addError('customDescription', 'AI analysis failed. Please try again.');
        } finally {
            $this->isAnalyzing = false;
        }
    }

    public function startRoutine(): void
    {
        $this->validate(['selectedHabitId' => ['required', 'integer', 'exists:virtue_habits,id']]);

        $this->isStarting = true;

        try {
            app(VirtueHabitService::class)->startRoutine(Auth::user(), [
                'virtue_habit_id' => $this->selectedHabitId,
                'personal_note' => $this->personalNote ?: null,
                'goal_type' => $this->goalType,
                'goal_target' => $this->goalTarget,
            ]);

            session()->flash('virtue_status', 'Mission started! Let\'s forge your character. 🔥');

            $this->redirectRoute('virtue.hub', ['locale' => $this->locale]);
        } catch (\Throwable) {
            $this->addError('selectedHabitId', 'Could not start the routine. Please try again.');
            $this->isStarting = false;
        }
    }

    public function render(): View
    {
        $habits = VirtueHabit::query()
            ->where('is_predefined', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $grouped = $habits->groupBy(fn ($h) => $h->category->label());

        return view('livewire.virtue.habit-picker', [
            'groupedHabits' => $grouped,
        ]);
    }
}
