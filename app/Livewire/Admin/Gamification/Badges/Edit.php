<?php

namespace App\Livewire\Admin\Gamification\Badges;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\GamificationEngine\Models\GamificationBadge;

class Edit extends Component
{
    use WithAdminPage;

    public ?GamificationBadge $badge = null;

    public string $slug = '';

    public string $name = '';

    public string $description = '';

    public string $icon = 'fa-medal';

    public bool $isActive = true;

    public function mount(?GamificationBadge $badge = null): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);

        $this->badge = $badge;

        if ($badge !== null) {
            $this->slug = $badge->slug;
            $this->name = $badge->name;
            $this->description = (string) ($badge->description ?? '');
            $this->icon = $badge->icon;
            $this->isActive = $badge->is_active;
        }
    }

    public function save(): void
    {
        $this->validate([
            'slug' => ['required', 'string', 'max:80', Rule::unique('gamification_badges', 'slug')->ignore($this->badge?->id)],
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['required', 'string', 'max:40'],
        ]);

        $payload = [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
            'icon' => $this->icon,
            'is_active' => $this->isActive,
        ];

        if ($this->badge === null) {
            $this->badge = GamificationBadge::query()->create($payload);
        } else {
            $this->badge->update($payload);
        }

        $this->adminFlash(__('admin.gamification.badge_saved'));
        $this->redirect(route('admin.gamification.badges.edit', $this->badge), navigate: true);
    }

    public function delete(): void
    {
        if ($this->badge === null) {
            return;
        }

        $this->badge->delete();
        $this->redirect(route('admin.gamification.badges.index'), navigate: true);
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.gamification.badges.edit', [
            'activeGamificationNav' => 'badges',
        ], 'gamification');
    }
}
