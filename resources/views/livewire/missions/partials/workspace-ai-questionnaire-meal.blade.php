{{-- Step 1: Schedule & injuries --}}
<div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 1])>
    <p class="eg-aether-step-title">{{ __('missions.ai_wizard_step1_title') }}</p>
    <p class="eg-aether-step-help">{{ __('missions.ai_wizard_step1_help') }}</p>

    <div class="eg-aether-field">
        <p class="eg-aether-field__label">{{ __('missions.ai_field_days') }}</p>
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
    </div>

    <div class="eg-aether-field">
        <p class="eg-aether-field__label">{{ __('missions.ai_field_session') }}</p>
        <div class="eg-aether-option-grid">
            @foreach ($aiFormOptions['session_durations'] as $option)
                <button type="button" @class(['eg-aether-option', 'is-selected' => $aiSessionDuration === $option['value']]) wire:click="$set('aiSessionDuration', '{{ $option['value'] }}')">
                    <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="eg-aether-field">
        <p class="eg-aether-field__label">{{ __('missions.ai_field_injuries') }}</p>
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
    </div>
</div>

{{-- Step 2: Goals & equipment --}}
<div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 2])>
    <p class="eg-aether-step-title">{{ __('missions.ai_wizard_step2_title') }}</p>
    <p class="eg-aether-step-help">{{ __('missions.ai_wizard_step2_help') }}</p>

    <div class="eg-aether-field">
        <p class="eg-aether-field__label">{{ __('missions.ai_field_goal') }}</p>
        <div class="eg-aether-option-grid eg-aether-option-grid--wide">
            @foreach ($aiFormOptions['goals'] as $option)
                @php $icon = $aiFormOptions['goal_icons'][$option['value']] ?? 'fa-bullseye'; @endphp
                <button type="button" @class(['eg-aether-option', 'is-selected' => $aiPrimaryGoal === $option['value']]) wire:click="$set('aiPrimaryGoal', '{{ $option['value'] }}')">
                    <span class="eg-aether-option__icon"><i class="fa-solid {{ $icon }}"></i></span>
                    <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="eg-aether-field">
        <p class="eg-aether-field__label">{{ __('missions.ai_field_equipment') }}</p>
        <div class="eg-aether-option-grid">
            @foreach ($aiFormOptions['equipment'] as $option)
                <button type="button" @class(['eg-aether-option', 'is-selected' => $aiEquipment === $option['value']]) wire:click="$set('aiEquipment', '{{ $option['value'] }}')">
                    <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="eg-aether-field">
        <p class="eg-aether-field__label">{{ __('missions.ai_field_diet') }}</p>
        <div class="eg-aether-option-grid">
            @foreach ($aiFormOptions['dietary'] as $option)
                <button type="button" @class(['eg-aether-option', 'is-selected' => $aiDietaryPattern === $option['value']]) wire:click="$set('aiDietaryPattern', '{{ $option['value'] }}')">
                    <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>

{{-- Step 3: Training style & motivation --}}
<div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 3])>
    <p class="eg-aether-step-title">{{ __('missions.ai_wizard_step3_title') }}</p>
    <p class="eg-aether-step-help">{{ __('missions.ai_wizard_step3_help') }}</p>

    <div class="eg-aether-field">
        <p class="eg-aether-field__label">{{ __('missions.ai_field_training_style') }}</p>
        <div class="eg-aether-option-grid">
            @foreach ($aiFormOptions['training_styles'] as $option)
                @php $icon = $aiFormOptions['training_style_icons'][$option['value']] ?? 'fa-dumbbell'; @endphp
                <button type="button" @class(['eg-aether-option', 'is-selected' => $aiTrainingStyle === $option['value']]) wire:click="$set('aiTrainingStyle', '{{ $option['value'] }}')">
                    <span class="eg-aether-option__icon"><i class="fa-solid {{ $icon }}"></i></span>
                    <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="eg-aether-field">
        <p class="eg-aether-field__label">{{ __('missions.ai_field_motivation') }}</p>
        <div class="eg-aether-option-grid">
            @foreach ($aiFormOptions['motivation'] as $option)
                <button type="button" @class(['eg-aether-option', 'is-selected' => $aiMotivationStyle === $option['value']]) wire:click="$set('aiMotivationStyle', '{{ $option['value'] }}')">
                    <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>
</div>

{{-- Step 4: Review --}}
<div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 4])>
    <p class="eg-aether-step-title">{{ __('missions.ai_wizard_step4_title') }}</p>
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
</div>
