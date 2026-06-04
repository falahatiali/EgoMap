<?php

namespace App\Livewire\Missions;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\MissionEngine\Enums\MissionEnrollmentStatus;
use Modules\MissionEngine\Models\MissionEnrollment;
use Modules\MissionEngine\Models\MissionTemplate;
use Modules\MissionEngine\Services\MissionEnrollmentService;

#[Layout('layouts.app')]
class Show extends Component
{
    public MissionTemplate $template;

    public function mount(MissionTemplate $template): void
    {
        abort_unless($template->isPublished(), 404);

        $this->template = $template->load(['category', 'capabilities.capabilityType', 'phases', 'fields']);
    }

    public function startMission(MissionEnrollmentService $enrollmentService): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 401);

        $existing = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('template_id', $this->template->id)
            ->where('status', MissionEnrollmentStatus::Active)
            ->first();

        if ($existing !== null) {
            $this->redirect(route('missions.workspace', ['enrollment' => $existing->uuid]), navigate: true);

            return;
        }

        $enrollment = $enrollmentService->enroll($user, $this->template);

        $this->redirect(route('missions.workspace', ['enrollment' => $enrollment->uuid]), navigate: true);
    }

    public function openWorkspace(): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 401);

        $enrollment = MissionEnrollment::query()
            ->where('user_id', $user->id)
            ->where('template_id', $this->template->id)
            ->latest('updated_at')
            ->firstOrFail();

        $this->redirect(route('missions.workspace', ['enrollment' => $enrollment->uuid]), navigate: true);
    }

    public function render(): View
    {
        $locale = app()->getLocale();
        $user = auth()->user();

        $activeEnrollment = null;

        if ($user !== null) {
            $activeEnrollment = MissionEnrollment::query()
                ->where('user_id', $user->id)
                ->where('template_id', $this->template->id)
                ->latest('updated_at')
                ->first();
        }

        $enabledCapabilities = $this->template->capabilities
            ->where('is_enabled', true)
            ->sortBy('sort_order')
            ->values();

        return view('livewire.missions.show', [
            'locale' => $locale,
            'activeEnrollment' => $activeEnrollment,
            'enabledCapabilities' => $enabledCapabilities,
        ]);
    }
}
