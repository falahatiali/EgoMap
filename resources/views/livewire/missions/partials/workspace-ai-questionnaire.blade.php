@if ($showAiQuestionnaire)
    <div class="eg-mission-ai-modal eg-aether-modal" role="dialog" aria-modal="true" aria-labelledby="ai-questionnaire-title">
        <div class="eg-mission-ai-modal__backdrop" wire:click="closeAiQuestionnaire"></div>
        <div class="eg-mission-ai-modal__panel eg-glass">
            <div class="eg-aether-modal__glow" aria-hidden="true"></div>

            <header class="eg-mission-ai-modal__head">
                <div>
                    <p class="eg-mission-ai-modal__kicker">{{ __('missions.ai_wizard_kicker') }}</p>
                    <h2 id="ai-questionnaire-title" class="h4 mb-1">
                        @if ($aiWizardStep === 1)
                            {{ __('missions.ai_wizard_enter_gym') }}
                        @elseif ($aiQuestionnaireTarget === 'meal')
                            {{ __('missions.ai_wizard_title_meal') }}
                        @else
                            {{ __('missions.ai_wizard_title_workout') }}
                        @endif
                    </h2>
                    <p class="small eg-text-muted mb-0">
                        {{ __('missions.ai_wizard_step', ['current' => eg_num($aiWizardStep), 'total' => eg_num($aiWizardSteps)]) }}
                        · {{ __('missions.ai_wizard_step'.$aiWizardStep.'_title') }}
                    </p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-light" wire:click="closeAiQuestionnaire" aria-label="{{ __('missions.ai_wizard_close') }}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </header>

            <div class="eg-aether-modal__progress eg-aether-modal__progress--{{ $aiWizardSteps }}" aria-hidden="true">
                @for ($step = 1; $step <= $aiWizardSteps; $step++)
                    <span @class(['is-active' => $aiWizardStep >= $step])></span>
                @endfor
            </div>

            @error('aiWizard')
                <div class="alert alert-danger py-2 small">{{ $message }}</div>
            @enderror

            <div class="eg-mission-ai-modal__body">
                {{-- Step 1: Schedule & injuries (zero typing) --}}
                <div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 1])>
                    <p class="eg-aether-step-title">{{ __('missions.ai_wizard_step1_title') }}</p>
                    <p class="small eg-text-muted mb-3">{{ __('missions.ai_wizard_step1_help') }}</p>

                    <p class="small fw-semibold mb-2">{{ __('missions.ai_field_days') }}</p>
                    <div class="eg-aether-chip-row mb-4">
                        @foreach ($aiFormOptions['day_options'] as $option)
                            <button type="button" class="eg-aether-chip @if ($aiTrainingDaysPerWeek === $option['value']) is-selected @endif" wire:click="selectAiTrainingDays({{ $option['value'] }})">
                                {{ $option['label'] }}
                            </button>
                        @endforeach
                    </div>
                    @error('aiTrainingDaysPerWeek')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                    <p class="small fw-semibold mb-2">{{ __('missions.ai_field_session') }}</p>
                    <div class="eg-aether-option-grid mb-4">
                        @foreach ($aiFormOptions['session_durations'] as $option)
                            <button type="button" @class(['eg-aether-option', 'is-selected' => $aiSessionDuration === $option['value']]) wire:click="$set('aiSessionDuration', '{{ $option['value'] }}')">
                                <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <p class="small fw-semibold mb-2">{{ __('missions.ai_field_injuries') }}</p>
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

                {{-- Step 2: Goals & equipment --}}
                <div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 2])>
                    <p class="eg-aether-step-title">{{ __('missions.ai_wizard_step2_title') }}</p>
                    <p class="small eg-text-muted mb-3">{{ __('missions.ai_wizard_step2_help') }}</p>

                    <p class="small fw-semibold mb-2">{{ __('missions.ai_field_goal') }}</p>
                    <div class="eg-aether-option-grid eg-aether-option-grid--wide mb-4">
                        @foreach ($aiFormOptions['goals'] as $option)
                            @php $icon = $aiFormOptions['goal_icons'][$option['value']] ?? 'fa-bullseye'; @endphp
                            <button type="button" @class(['eg-aether-option', 'is-selected' => $aiPrimaryGoal === $option['value']]) wire:click="$set('aiPrimaryGoal', '{{ $option['value'] }}')">
                                <span class="eg-aether-option__icon"><i class="fa-solid {{ $icon }}"></i></span>
                                <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <p class="small fw-semibold mb-2">{{ __('missions.ai_field_equipment') }}</p>
                    <div class="eg-aether-option-grid mb-4">
                        @foreach ($aiFormOptions['equipment'] as $option)
                            <button type="button" @class(['eg-aether-option', 'is-selected' => $aiEquipment === $option['value']]) wire:click="$set('aiEquipment', '{{ $option['value'] }}')">
                                <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <p class="small fw-semibold mb-2">{{ __('missions.ai_field_diet') }}</p>
                    <div class="eg-aether-option-grid">
                        @foreach ($aiFormOptions['dietary'] as $option)
                            <button type="button" @class(['eg-aether-option', 'is-selected' => $aiDietaryPattern === $option['value']]) wire:click="$set('aiDietaryPattern', '{{ $option['value'] }}')">
                                <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Step 3: Training style & motivation --}}
                <div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 3])>
                    <p class="eg-aether-step-title">{{ __('missions.ai_wizard_step3_title') }}</p>
                    <p class="small eg-text-muted mb-3">{{ __('missions.ai_wizard_step3_help') }}</p>

                    <p class="small fw-semibold mb-2">{{ __('missions.ai_field_training_style') }}</p>
                    <div class="eg-aether-option-grid mb-4">
                        @foreach ($aiFormOptions['training_styles'] as $option)
                            @php $icon = $aiFormOptions['training_style_icons'][$option['value']] ?? 'fa-dumbbell'; @endphp
                            <button type="button" @class(['eg-aether-option', 'is-selected' => $aiTrainingStyle === $option['value']]) wire:click="$set('aiTrainingStyle', '{{ $option['value'] }}')">
                                <span class="eg-aether-option__icon"><i class="fa-solid {{ $icon }}"></i></span>
                                <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <p class="small fw-semibold mb-2">{{ __('missions.ai_field_motivation') }}</p>
                    <div class="eg-aether-option-grid">
                        @foreach ($aiFormOptions['motivation'] as $option)
                            <button type="button" @class(['eg-aether-option', 'is-selected' => $aiMotivationStyle === $option['value']]) wire:click="$set('aiMotivationStyle', '{{ $option['value'] }}')">
                                <span class="eg-aether-option__label">{{ $option['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Step 4: Review --}}
                <div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 4])>
                    <p class="eg-aether-step-title">{{ __('missions.ai_wizard_step4_title') }}</p>
                    <p class="small eg-text-muted mb-3">{{ __('missions.ai_wizard_step4_help') }}</p>

                    <div class="eg-aether-review-grid">
                        @foreach ($aiWizardReview as $item)
                            <div class="eg-aether-review-card">
                                <span class="small eg-text-muted">{{ $item['label'] }}</span>
                                <strong>{{ $item['value'] }}</strong>
                            </div>
                        @endforeach
                    </div>

                    <p class="small eg-text-muted mt-3 mb-0">
                        <i class="fa-solid fa-shield-heart me-1"></i>{{ __('missions.ai_wizard_smart_defaults') }}
                    </p>
                </div>
            </div>

            <footer class="eg-mission-ai-modal__foot">
                @if ($aiWizardStep > 1)
                    <button type="button" class="btn btn-outline-light" wire:click="aiWizardBack" @disabled($aiIsGenerating)>
                        {{ __('missions.ai_wizard_back') }}
                    </button>
                @else
                    <span></span>
                @endif

                @if ($aiWizardStep < $aiWizardSteps)
                    <button type="button" class="btn btn-primary" wire:click="aiWizardNext">
                        {{ __('missions.ai_wizard_next') }}
                    </button>
                @else
                    <button type="button" class="btn btn-primary eg-aether-generate-btn" wire:click="submitAiQuestionnaire" wire:loading.attr="disabled" wire:target="submitAiQuestionnaire">
                        <span wire:loading.remove wire:target="submitAiQuestionnaire"><i class="fa-solid fa-dumbbell me-1"></i>{{ __('missions.ai_wizard_generate') }}</span>
                        <span wire:loading wire:target="submitAiQuestionnaire">{{ __('missions.ai_generating') }}</span>
                    </button>
                @endif
            </footer>
        </div>
    </div>
@endif
