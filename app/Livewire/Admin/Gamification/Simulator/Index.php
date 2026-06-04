<?php

namespace App\Livewire\Admin\Gamification\Simulator;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Services\GamificationEngine;

class Index extends Component
{
    use WithAdminPage;

    public string $event = '';

    public string $metadataJson = '{}';

    /** @var list<array<string, mixed>> */
    public array $results = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);
        $this->event = GamificationEvent::GhostModeSlipReported->value;
    }

    public function runSimulation(GamificationEngine $engine): void
    {
        $this->validate([
            'event' => ['required', Rule::in(GamificationEvent::values())],
            'metadataJson' => ['required', 'string'],
        ]);

        $metadata = json_decode($this->metadataJson, true);
        if (! is_array($metadata)) {
            $this->addError('metadataJson', __('admin.gamification.invalid_json'));

            return;
        }

        $this->results = $engine->simulate($this->event, ['metadata' => $metadata])['matches'] ?? [];
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.gamification.simulator.index', [
            'events' => GamificationEvent::cases(),
            'activeGamificationNav' => 'simulator',
        ], 'gamification');
    }
}
