<div class="eg-aether-wizard-step" wire:key="aether-wizard-step-{{ $aiWizardStepKey }}">
    <p class="eg-aether-wizard-question">{{ __('missions.ai_wq_'.$aiWizardStepKey.'_title') }}</p>
    @if (__('missions.ai_wq_'.$aiWizardStepKey.'_help') !== 'missions.ai_wq_'.$aiWizardStepKey.'_help')
        <p class="eg-aether-step-help">{{ __('missions.ai_wq_'.$aiWizardStepKey.'_help') }}</p>
    @endif

    @switch($aiWizardStepKey)
        @case('gender')
            <div class="eg-aether-choice-grid eg-aether-choice-grid--compact">
                @foreach ($aiFormOptions['genders'] as $option)
                    <button
                        type="button"
                        @class(['eg-aether-choice-card', 'is-selected' => $aiGender === $option['value']])
                        wire:click="$set('aiGender', '{{ $option['value'] }}')"
                    >
                        <span class="eg-aether-choice-card__label">{{ $option['label'] }}</span>
                        <span class="eg-aether-choice-card__radio" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
            @error('aiGender')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('age')
            <div class="eg-aether-choice-grid">
                @foreach ($aiFormOptions['age_ranges'] as $option)
                    <button
                        type="button"
                        @class(['eg-aether-choice-card', 'is-selected' => $aiAgeRange === $option['value']])
                        wire:click="selectAiAgeRange('{{ $option['value'] }}')"
                    >
                        <span class="eg-aether-choice-card__label">{{ $option['label'] }}</span>
                        <span class="eg-aether-choice-card__radio" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
            @error('aiAgeRange')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('height')
            <div class="eg-aether-metric-picker" x-data="{ value: @entangle('aiHeightCm').live }">
                <div class="eg-aether-metric-picker__display">
                    <span class="eg-aether-metric-picker__value" x-text="value"></span>
                    <span class="eg-aether-metric-picker__unit">{{ __('missions.ai_metric_cm') }}</span>
                </div>
                <input type="range" min="140" max="220" step="1" class="eg-aether-range eg-aether-range--hero" x-model="value">
                <div class="eg-aether-metric-picker__ticks">
                    @foreach ([150, 165, 175, 185, 200] as $tick)
                        <button type="button" class="eg-aether-metric-tick" wire:click="$set('aiHeightCm', {{ $tick }})">{{ eg_num($tick) }}</button>
                    @endforeach
                </div>
            </div>
            @error('aiHeightCm')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('weight')
            <div class="eg-aether-metric-picker" x-data="{ value: @entangle('aiWeightKg').live }">
                <div class="eg-aether-metric-picker__display">
                    <span class="eg-aether-metric-picker__value" x-text="Number(value).toFixed(1)"></span>
                    <span class="eg-aether-metric-picker__unit">{{ __('missions.ai_metric_kg') }}</span>
                </div>
                <input type="range" min="40" max="160" step="0.5" class="eg-aether-range eg-aether-range--hero" x-model="value">
                <div class="eg-aether-metric-picker__ticks">
                    @foreach ([55, 70, 85, 100, 115] as $tick)
                        <button type="button" class="eg-aether-metric-tick" wire:click="$set('aiWeightKg', {{ $tick }})">{{ eg_num($tick) }}</button>
                    @endforeach
                </div>
            </div>
            @error('aiWeightKg')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('current_body')
            <div class="eg-aether-body-grid">
                @foreach ($aiFormOptions['body_builds'] as $option)
                    <button
                        type="button"
                        @class(['eg-aether-body-card', 'is-selected' => $aiCurrentBodyBuild === $option['value']])
                        wire:click="$set('aiCurrentBodyBuild', '{{ $option['value'] }}')"
                    >
                        <x-aether.body-silhouette
                            :variant="$option['value']"
                            :gender="$aiGender"
                            :selected="$aiCurrentBodyBuild === $option['value']"
                            glow="emerald"
                        />
                        <span class="eg-aether-body-card__label">{{ $option['label'] }}</span>
                    </button>
                @endforeach
            </div>
            @error('aiCurrentBodyBuild')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('target_body')
            <div class="eg-aether-body-grid">
                @foreach ($aiFormOptions['body_goals'] as $option)
                    <button
                        type="button"
                        @class(['eg-aether-body-card', 'is-selected' => $aiTargetBodyGoal === $option['value']])
                        wire:click="$set('aiTargetBodyGoal', '{{ $option['value'] }}')"
                    >
                        <x-aether.body-silhouette
                            :variant="$option['value']"
                            :gender="$aiGender"
                            :selected="$aiTargetBodyGoal === $option['value']"
                            glow="indigo"
                        />
                        <span class="eg-aether-body-card__label">{{ $option['label'] }}</span>
                    </button>
                @endforeach
            </div>
            @error('aiTargetBodyGoal')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('goal')
            <div class="eg-aether-choice-grid">
                @foreach ($aiFormOptions['goals'] as $option)
                    @php $icon = $aiFormOptions['goal_icons'][$option['value']] ?? 'fa-bullseye'; @endphp
                    <button
                        type="button"
                        @class(['eg-aether-choice-card eg-aether-choice-card--icon', 'is-selected' => $aiPrimaryGoal === $option['value']])
                        wire:click="$set('aiPrimaryGoal', '{{ $option['value'] }}')"
                    >
                        <span class="eg-aether-choice-card__icon"><i class="fa-solid {{ $icon }}"></i></span>
                        <span class="eg-aether-choice-card__label">{{ $option['label'] }}</span>
                        <span class="eg-aether-choice-card__radio" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
            @error('aiPrimaryGoal')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('gym_confidence')
            <div class="eg-aether-choice-grid">
                @foreach ($aiFormOptions['gym_confidence'] as $option)
                    <button
                        type="button"
                        @class(['eg-aether-choice-card', 'is-selected' => $aiGymConfidence === $option['value']])
                        wire:click="$set('aiGymConfidence', '{{ $option['value'] }}')"
                    >
                        <span class="eg-aether-choice-card__label">{{ $option['label'] }}</span>
                        <span class="eg-aether-choice-card__radio" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
            @error('aiGymConfidence')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('days')
            <div class="eg-aether-segment-row">
                @foreach ($aiFormOptions['day_options'] as $option)
                    <button
                        type="button"
                        @class(['eg-aether-segment', 'is-selected' => $aiTrainingDaysPerWeek === $option['value']])
                        wire:click="selectAiTrainingDays({{ $option['value'] }})"
                    >
                        {{ $option['label'] }}
                    </button>
                @endforeach
            </div>
            @error('aiTrainingDaysPerWeek')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('session')
            <div class="eg-aether-choice-grid">
                @foreach ($aiFormOptions['session_durations'] as $option)
                    <button
                        type="button"
                        @class(['eg-aether-choice-card', 'is-selected' => $aiSessionDuration === $option['value']])
                        wire:click="$set('aiSessionDuration', '{{ $option['value'] }}')"
                    >
                        <span class="eg-aether-choice-card__label">{{ $option['label'] }}</span>
                        <span class="eg-aether-choice-card__radio" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
            @error('aiSessionDuration')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('equipment')
            <div class="eg-aether-choice-grid">
                @foreach ($aiFormOptions['equipment'] as $option)
                    <button
                        type="button"
                        @class(['eg-aether-choice-card', 'is-selected' => $aiEquipment === $option['value']])
                        wire:click="$set('aiEquipment', '{{ $option['value'] }}')"
                    >
                        <span class="eg-aether-choice-card__label">{{ $option['label'] }}</span>
                        <span class="eg-aether-choice-card__radio" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
            @error('aiEquipment')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('injuries')
            <div class="eg-aether-option-grid eg-aether-option-grid--injuries">
                @foreach ($aiFormOptions['injuries'] as $option)
                    @php
                        $isSelected = $option['value'] === 'none'
                            ? $aiInjuryTags === []
                            : in_array($option['value'], $aiInjuryTags, true);
                    @endphp
                    <button type="button" @class(['eg-aether-option', 'is-selected' => $isSelected]) wire:click="toggleAiInjury('{{ $option['value'] }}')">
                        <span class="eg-aether-option__icon"><i class="fa-solid {{ $option['icon'] }}"></i></span>
                        <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                    </button>
                @endforeach
            </div>
            @break

        @case('style')
            <div class="eg-aether-option-grid">
                @foreach ($aiFormOptions['training_styles'] as $option)
                    @php $icon = $aiFormOptions['training_style_icons'][$option['value']] ?? 'fa-dumbbell'; @endphp
                    <button type="button" @class(['eg-aether-option', 'is-selected' => $aiTrainingStyle === $option['value']]) wire:click="$set('aiTrainingStyle', '{{ $option['value'] }}')">
                        <span class="eg-aether-option__icon"><i class="fa-solid {{ $icon }}"></i></span>
                        <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                    </button>
                @endforeach
            </div>
            @error('aiTrainingStyle')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('motivation')
            <div class="eg-aether-choice-grid">
                @foreach ($aiFormOptions['motivation'] as $option)
                    <button
                        type="button"
                        @class(['eg-aether-choice-card', 'is-selected' => $aiMotivationStyle === $option['value']])
                        wire:click="$set('aiMotivationStyle', '{{ $option['value'] }}')"
                    >
                        <span class="eg-aether-choice-card__label">{{ $option['label'] }}</span>
                        <span class="eg-aether-choice-card__radio" aria-hidden="true"></span>
                    </button>
                @endforeach
            </div>
            @error('aiMotivationStyle')<p class="eg-aether-field__error">{{ $message }}</p>@enderror
            @break

        @case('review')
            <p class="eg-aether-step-help">{{ __('missions.ai_wizard_step4_help') }}</p>
            <div class="eg-aether-review-grid">
                @foreach ($aiWizardReview as $item)
                    <div class="eg-aether-review-card">
                        <span class="eg-aether-review-card__label">{{ $item['label'] }}</span>
                        <strong class="eg-aether-review-card__value">{{ $item['value'] }}</strong>
                    </div>
                @endforeach
            </div>
            <p class="eg-aether-modal__footnote">
                <i class="fa-solid fa-shield-heart" aria-hidden="true"></i>
                {{ __('missions.ai_wizard_smart_defaults') }}
            </p>
            @break
    @endswitch
</div>
