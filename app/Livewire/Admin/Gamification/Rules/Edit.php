<?php

namespace App\Livewire\Admin\Gamification\Rules;

use App\Enums\Permission;
use App\Livewire\Admin\Concerns\WithAdminPage;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\GamificationEngine\Enums\GamificationEvent;
use Modules\GamificationEngine\Enums\GamificationRuleType;
use Modules\GamificationEngine\Models\GamificationRule;

class Edit extends Component
{
    use WithAdminPage;

    public ?GamificationRule $rule = null;

    public string $key = '';

    public string $name = '';

    public string $description = '';

    public string $event = '';

    public string $ruleType = 'reward';

    public string $conditionsJson = '{}';

    public string $effectsJson = '{}';

    public ?int $maxPerDay = null;

    public int $priority = 100;

    public bool $isActive = true;

    public function mount(?GamificationRule $rule = null): void
    {
        abort_unless(auth()->user()?->can(Permission::AdminGamificationManage->value), 403);

        $this->rule = $rule;

        if ($rule !== null) {
            $this->key = $rule->key;
            $this->name = $rule->name;
            $this->description = (string) ($rule->description ?? '');
            $this->event = $rule->event;
            $this->ruleType = $rule->rule_type->value;
            $this->conditionsJson = json_encode($rule->conditions ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
            $this->effectsJson = json_encode($rule->effects ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}';
            $this->maxPerDay = $rule->max_per_day;
            $this->priority = $rule->priority;
            $this->isActive = $rule->is_active;
        }
    }

    public function save(): void
    {
        $this->validate([
            'key' => ['required', 'string', 'max:80', Rule::unique('gamification_rules', 'key')->ignore($this->rule?->id)],
            'name' => ['required', 'string', 'max:120'],
            'event' => ['required', 'string', Rule::in(GamificationEvent::values())],
            'ruleType' => ['required', Rule::in(array_column(GamificationRuleType::cases(), 'value'))],
            'conditionsJson' => ['required', 'string'],
            'effectsJson' => ['required', 'string'],
            'priority' => ['required', 'integer', 'min:1', 'max:9999'],
            'maxPerDay' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $conditions = json_decode($this->conditionsJson, true);
        $effects = json_decode($this->effectsJson, true);

        if (! is_array($conditions) || ! is_array($effects)) {
            $this->addError('conditionsJson', __('admin.gamification.invalid_json'));

            return;
        }

        $payload = [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description !== '' ? $this->description : null,
            'event' => $this->event,
            'rule_type' => $this->ruleType,
            'conditions' => $conditions === [] ? null : $conditions,
            'effects' => $effects,
            'max_per_day' => $this->maxPerDay,
            'priority' => $this->priority,
            'is_active' => $this->isActive,
        ];

        if ($this->rule === null) {
            $this->rule = GamificationRule::query()->create($payload);
        } else {
            $this->rule->update($payload);
        }

        $this->adminFlash(__('admin.gamification.rule_saved'));
        $this->redirect(route('admin.gamification.rules.edit', $this->rule), navigate: true);
    }

    public function render(): View
    {
        return $this->adminView('livewire.admin.gamification.rules.edit', [
            'events' => GamificationEvent::cases(),
            'ruleTypes' => GamificationRuleType::cases(),
        ], 'gamification');
    }
}
