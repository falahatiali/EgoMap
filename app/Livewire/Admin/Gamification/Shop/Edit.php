<?php

namespace App\Livewire\Admin\Gamification\Shop;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\GamificationEngine\Enums\GamificationShopEffectType;
use Modules\GamificationEngine\Models\GamificationShopItem;

class Edit extends Component
{
    use WithAdminPage;

    public ?GamificationShopItem $item = null;

    public string $slug = '';

    public string $name = '';

    public string $description = '';

    public string $icon = 'fa-bag-shopping';

    public int $costCoins = 0;

    public string $effectType = 'streak_freeze';

    public string $effectsJson = '{}';

    public int $sortOrder = 100;

    public bool $isActive = true;

    public function mount(?GamificationShopItem $item = null): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);

        $this->item = $item;

        if ($item !== null) {
            $this->slug = $item->slug;
            $this->name = $item->name;
            $this->description = (string) ($item->description ?? '');
            $this->icon = $item->icon;
            $this->costCoins = $item->cost_coins;
            $this->effectType = $item->effect_type->value;
            $this->effectsJson = json_encode($item->effects ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
            $this->sortOrder = $item->sort_order;
            $this->isActive = $item->is_active;
        }
    }

    public function save(): void
    {
        $this->validate([
            'slug' => ['required', 'string', 'max:80', Rule::unique('gamification_shop_items', 'slug')->ignore($this->item?->id)],
            'name' => ['required', 'string', 'max:120'],
            'costCoins' => ['required', 'integer', 'min:0'],
            'effectType' => ['required', Rule::in(GamificationShopEffectType::values())],
            'effectsJson' => ['required', 'string'],
            'sortOrder' => ['required', 'integer', 'min:1'],
        ]);

        $effects = json_decode($this->effectsJson, true);
        if (! is_array($effects)) {
            $this->addError('effectsJson', __('admin.gamification.invalid_json'));

            return;
        }

        $payload = [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
            'icon' => $this->icon,
            'cost_coins' => $this->costCoins,
            'effect_type' => $this->effectType,
            'effects' => $effects,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];

        if ($this->item === null) {
            $this->item = GamificationShopItem::query()->create($payload);
        } else {
            $this->item->update($payload);
        }

        $this->adminFlash(__('admin.gamification.shop_item_saved'));
        $this->redirect(route('admin.gamification.shop.edit', $this->item), navigate: true);
    }

    public function delete(): void
    {
        if ($this->item === null) {
            return;
        }

        $this->item->delete();
        $this->redirect(route('admin.gamification.shop.index'), navigate: true);
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.gamification.shop.edit', [
            'effectTypes' => GamificationShopEffectType::cases(),
            'activeGamificationNav' => 'shop',
        ], 'gamification');
    }
}
