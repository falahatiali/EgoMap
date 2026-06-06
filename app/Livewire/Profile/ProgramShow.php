<?php

namespace App\Livewire\Profile;

use App\Services\Profile\UserAetherProgramHistoryService;
use App\Support\LocaleConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\AetherEngine\Models\AetherGeneratedProgram;

#[Layout('layouts.app')]
class ProgramShow extends Component
{
    public AetherGeneratedProgram $program;

    public function mount(string $uuid): void
    {
        $this->program = AetherGeneratedProgram::query()
            ->withProgramGraph()
            ->with(['profile', 'missionEnrollment'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        abort_unless($this->program->user_id === Auth::id(), 403);
    }

    public function render(UserAetherProgramHistoryService $historyService): View
    {
        $locale = LocaleConfig::fromRoute();
        $record = $historyService->recordsForUser(Auth::user(), $locale)
            ->firstWhere('uuid', $this->program->uuid);

        return view('livewire.profile.program-show', [
            'locale' => $locale,
            'record' => $record,
            'metabolic' => $this->program->metabolicSummary(),
            'coachNotes' => $this->program->coachNarrative()->toDisplayMap(),
            'shoppingList' => $this->program->shopping_list_summary,
            'split' => $this->program->split?->value,
        ]);
    }
}
