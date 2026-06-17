<?php

namespace App\Livewire\Mood;

use App\Enums\IdeaGoalCadence;
use App\Enums\MoodEmotion;
use App\Models\UserIdea;
use App\Services\Mood\IdeaService;
use App\Services\Mood\MoodService;
use App\Support\MoodEmotionCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\CommunityEngine\Services\CommunityPostService;

#[Layout('layouts.app')]
class MoodHub extends Component
{
    public string $locale = 'en';

    public ?string $selectedEmotion = null;

    public int $intensity = 6;

    /** @var array<string, mixed>|null */
    public ?array $todayEntry = null;

    public string $manualSeed = '';

    public ?int $harvestIdeaId = null;

    public string $harvestCadence = 'monthly';

    public bool $reshaping = false;

    /** @var array<int, int> */
    public array $progressDrafts = [];

    public function mount(MoodService $moodService): void
    {
        $this->locale = session('locale', 'en');
        $this->todayEntry = $moodService->dashboardForUser(Auth::user())['today'];
    }

    public function selectEmotion(string $emotion): void
    {
        if (! in_array($emotion, MoodEmotion::values(), true)) {
            return;
        }

        $this->selectedEmotion = $emotion;
    }

    public function logMood(MoodService $moodService): void
    {
        $this->validate([
            'selectedEmotion' => ['required', 'string', Rule::in(MoodEmotion::values())],
            'intensity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $this->todayEntry = $moodService->logMood(
            Auth::user(),
            MoodEmotion::from($this->selectedEmotion),
            $this->intensity,
            $this->locale,
        );

        session()->flash('mood_status', __('mood.save_mood'));
        $this->reshaping = false;
    }

    public function startReshape(): void
    {
        $this->reshaping = true;
        $this->selectedEmotion = null;
        $this->intensity = 6;
    }

    public function saveIdeaFromMood(IdeaService $ideaService): void
    {
        if ($this->todayEntry === null) {
            return;
        }

        $seed = data_get($this->todayEntry, 'ai_response.idea_seed');

        if (! is_string($seed) || trim($seed) === '') {
            return;
        }

        $ideaService->createSeed(Auth::user(), [
            'seed_text' => $seed,
            'source' => 'ai_suggestion',
            'mood_entry_id' => $this->todayEntry['id'] ?? null,
        ]);

        session()->flash('mood_status', __('mood.save_to_ideas'));
    }

    public function addManualSeed(IdeaService $ideaService): void
    {
        $validated = $this->validate([
            'manualSeed' => ['required', 'string', 'max:500'],
        ]);

        $ideaService->createSeed(Auth::user(), [
            'seed_text' => trim($validated['manualSeed']),
            'source' => 'manual',
        ]);

        $this->manualSeed = '';
        session()->flash('mood_status', __('mood.save_to_ideas'));
    }

    public function matureIdea(int $ideaId, IdeaService $ideaService): void
    {
        $idea = UserIdea::query()->where('user_id', Auth::id())->findOrFail($ideaId);
        $ideaService->matureIdea(Auth::user(), $idea);
        session()->flash('mood_status', __('mood.mature_idea'));
    }

    public function openHarvest(int $ideaId): void
    {
        $this->harvestIdeaId = $ideaId;
        $this->harvestCadence = IdeaGoalCadence::Monthly->value;
    }

    public function closeHarvest(): void
    {
        $this->harvestIdeaId = null;
    }

    public function confirmHarvest(IdeaService $ideaService): void
    {
        if ($this->harvestIdeaId === null) {
            return;
        }

        $this->validate([
            'harvestCadence' => ['required', 'string', Rule::in(array_column(IdeaGoalCadence::cases(), 'value'))],
        ]);

        $idea = UserIdea::query()->where('user_id', Auth::id())->findOrFail($this->harvestIdeaId);
        $ideaService->harvestIdea(Auth::user(), $idea, IdeaGoalCadence::from($this->harvestCadence));

        $this->harvestIdeaId = null;
        session()->flash('mood_status', __('mood.harvest_idea'));
    }

    public function updateProgress(int $ideaId, IdeaService $ideaService): void
    {
        $progress = $this->progressDrafts[$ideaId] ?? null;

        if ($progress === null) {
            return;
        }

        $idea = UserIdea::query()->where('user_id', Auth::id())->findOrFail($ideaId);
        $ideaService->updateProgress(Auth::user(), $idea, (int) $progress);

        session()->flash('mood_status', __('mood.check_in'));
    }

    public function render(MoodService $moodService, IdeaService $ideaService): View
    {
        $dashboard = $moodService->dashboardForUser(Auth::user());
        $this->todayEntry = $dashboard['today'];

        $garden = $ideaService->gardenForUser(Auth::user());

        foreach ($garden['harvested'] as $idea) {
            $id = (int) $idea['id'];
            $this->progressDrafts[$id] ??= (int) $idea['progress'];
        }

        $communityPreview = app(CommunityPostService::class)->feed(
            sort: 'latest',
            viewerId: Auth::id(),
            perPage: 1,
        )->first();

        return view('livewire.mood.mood-hub', [
            'emotions' => MoodEmotionCatalog::options($this->locale),
            'heatmap' => $dashboard['heatmap'],
            'garden' => $garden,
            'communityPreview' => $communityPreview,
            'cadenceOptions' => collect(IdeaGoalCadence::cases())->mapWithKeys(fn (IdeaGoalCadence $cadence): array => [
                $cadence->value => __('mood.goal_cadence.'.$cadence->value, locale: $this->locale),
            ])->all(),
        ]);
    }
}
