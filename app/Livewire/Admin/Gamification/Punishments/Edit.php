<?php

namespace App\Livewire\Admin\Gamification\Punishments;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\GamificationEngine\Enums\GamificationPunishmentDifficulty;
use Modules\GamificationEngine\Enums\GamificationPunishmentType;
use Modules\GamificationEngine\Models\GamificationPunishment;

class Edit extends Component
{
    use WithAdminPage;

    public ?GamificationPunishment $punishment = null;

    public string $slug = '';

    public string $title = '';

    public string $description = '';

    public string $type = 'physical';

    public string $difficulty = 'easy';

    public int $points = 0;

    public int $coins = 0;

    public int $estimatedMinutes = 5;

    public int $minSlipSeverity = 1;

    public int $sortOrder = 100;

    public bool $isActive = true;

    public function mount(?GamificationPunishment $punishment = null): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);

        $this->punishment = $punishment;

        if ($punishment !== null) {
            $this->slug = $punishment->slug;
            $this->title = $punishment->title;
            $this->description = (string) ($punishment->description ?? '');
            $this->type = $punishment->type->value;
            $this->difficulty = $punishment->difficulty->value;
            $this->points = $punishment->points;
            $this->coins = $punishment->coins;
            $this->estimatedMinutes = $punishment->estimated_minutes;
            $this->minSlipSeverity = $punishment->min_slip_severity;
            $this->sortOrder = $punishment->sort_order;
            $this->isActive = $punishment->is_active;
        }
    }

    public function save(): void
    {
        $this->validate([
            'slug' => ['required', 'string', 'max:80', Rule::unique('gamification_punishments', 'slug')->ignore($this->punishment?->id)],
            'title' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(GamificationPunishmentType::values())],
            'difficulty' => ['required', Rule::in(GamificationPunishmentDifficulty::values())],
            'estimatedMinutes' => ['required', 'integer', 'min:1', 'max:180'],
            'minSlipSeverity' => ['required', 'integer', 'min:1', 'max:3'],
        ]);

        $payload = [
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description !== '' ? $this->description : null,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'points' => $this->points,
            'coins' => $this->coins,
            'estimated_minutes' => $this->estimatedMinutes,
            'min_slip_severity' => $this->minSlipSeverity,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];

        if ($this->punishment === null) {
            $this->punishment = GamificationPunishment::query()->create($payload);
        } else {
            $this->punishment->update($payload);
        }

        $this->adminFlash(__('admin.gamification.punishment_saved'));
        $this->redirect(route('admin.gamification.punishments.edit', $this->punishment), navigate: true);
    }

    public function delete(): void
    {
        if ($this->punishment === null) {
            return;
        }

        $this->punishment->delete();
        $this->redirect(route('admin.gamification.punishments.index'), navigate: true);
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.gamification.punishments.edit', [
            'activeGamificationNav' => 'punishments',
            'types' => GamificationPunishmentType::cases(),
            'difficulties' => GamificationPunishmentDifficulty::cases(),
        ], 'gamification');
    }
}
