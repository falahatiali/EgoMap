<?php

namespace App\Livewire\Admin\Gamification\Perks;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\GamificationEngine\Enums\GamificationPerkType;
use Modules\GamificationEngine\Models\GamificationPerk;

class Edit extends Component
{
    use WithAdminPage;

    public ?GamificationPerk $perk = null;

    public string $slug = '';

    public string $name = '';

    public string $description = '';

    public string $type = 'consumable';

    public ?int $durationDays = null;

    public bool $isActive = true;

    public function mount(?GamificationPerk $perk = null): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);

        $this->perk = $perk;

        if ($perk !== null) {
            $this->slug = $perk->slug;
            $this->name = $perk->name;
            $this->description = (string) ($perk->description ?? '');
            $this->type = $perk->type->value;
            $this->durationDays = $perk->duration_days;
            $this->isActive = $perk->is_active;
        }
    }

    public function save(): void
    {
        $this->validate([
            'slug' => ['required', 'string', 'max:80', Rule::unique('gamification_perks', 'slug')->ignore($this->perk?->id)],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(GamificationPerkType::values())],
            'durationDays' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $payload = [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
            'type' => $this->type,
            'duration_days' => $this->durationDays,
            'is_active' => $this->isActive,
        ];

        if ($this->perk === null) {
            $this->perk = GamificationPerk::query()->create($payload);
        } else {
            $this->perk->update($payload);
        }

        $this->adminFlash(__('admin.gamification.perk_saved'));
        $this->redirect(route('admin.gamification.perks.edit', $this->perk), navigate: true);
    }

    public function delete(): void
    {
        if ($this->perk === null) {
            return;
        }

        $this->perk->delete();
        $this->redirect(route('admin.gamification.perks.index'), navigate: true);
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.gamification.perks.edit', [
            'perkTypes' => GamificationPerkType::cases(),
            'activeGamificationNav' => 'perks',
        ], 'gamification');
    }
}
