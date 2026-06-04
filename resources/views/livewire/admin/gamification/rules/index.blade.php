<div>
    @include('partials.admin.page-head', [
        'title' => __('admin.gamification.rules_title'),
        'subtitle' => __('admin.gamification.rules_subtitle'),
        'backRoute' => null,
    ])

    @include('partials.admin.gamification-nav', ['activeGamificationNav' => 'rules'])

    <div class="eg-admin-toolbar mb-4">
        <input type="search" wire:model.live.debounce.300ms="search" class="form-control eg-admin-input" placeholder="{{ __('admin.search') }}">
        <select wire:model.live="eventFilter" class="form-select eg-admin-input">
            <option value="">{{ __('admin.gamification.all_events') }}</option>
            @foreach ($events as $event)
                <option value="{{ $event }}">{{ $event }}</option>
            @endforeach
        </select>
        <select wire:model.live="typeFilter" class="form-select eg-admin-input">
            <option value="">{{ __('admin.gamification.all_types') }}</option>
            @foreach ($ruleTypes as $type)
                <option value="{{ $type->value }}">{{ $type->value }}</option>
            @endforeach
        </select>
        <a href="{{ route('admin.gamification.rules.create') }}" class="eg-admin-btn eg-admin-btn--primary">
            {{ __('admin.gamification.new_rule') }}
        </a>
    </div>

    <div class="eg-admin-card">
        <div class="table-responsive">
            <table class="table eg-admin-table mb-0">
                <thead>
                    <tr>
                        <th>{{ __('admin.gamification.col_key') }}</th>
                        <th>{{ __('admin.gamification.col_name') }}</th>
                        <th>{{ __('admin.gamification.col_event') }}</th>
                        <th>{{ __('admin.gamification.col_type') }}</th>
                        <th>{{ __('admin.gamification.col_effects') }}</th>
                        <th>{{ __('admin.gamification.col_priority') }}</th>
                        <th>{{ __('admin.gamification.col_active') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rules as $rule)
                        <tr wire:key="rule-{{ $rule->id }}">
                            <td><code>{{ $rule->key }}</code></td>
                            <td>{{ $rule->name }}</td>
                            <td><span class="small">{{ $rule->event }}</span></td>
                            <td>
                                <span @class(['text-success' => $rule->rule_type->value === 'reward', 'text-danger' => $rule->rule_type->value === 'penalty'])>
                                    {{ $rule->rule_type->value }}
                                </span>
                            </td>
                            <td class="small">{{ \Modules\GamificationEngine\Support\GamificationEffectSummary::fromEffects($rule->effects) }}</td>
                            <td>{{ $rule->priority }}</td>
                            <td>
                                <button type="button" class="eg-admin-btn eg-admin-btn--sm" wire:click="toggleActive({{ $rule->id }})">
                                    {{ $rule->is_active ? __('admin.active') : __('admin.inactive') }}
                                </button>
                            </td>
                            <td>
                                <a href="{{ route('admin.gamification.rules.edit', $rule) }}" class="eg-admin-btn eg-admin-btn--sm">
                                    {{ __('admin.edit') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center eg-text-muted py-4">{{ __('admin.gamification.no_rules') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $rules->links() }}</div>
</div>
