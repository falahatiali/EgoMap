@if ($showAiQuestionnaire)
    <div class="eg-mission-ai-modal eg-aether-modal" role="dialog" aria-modal="true" aria-labelledby="ai-questionnaire-title">
        <div class="eg-mission-ai-modal__backdrop" wire:click="closeAiQuestionnaire"></div>
        <div class="eg-mission-ai-modal__panel">
            <div class="eg-aether-modal__glow" aria-hidden="true"></div>
            <div class="eg-aether-modal__shell">
                <header class="eg-mission-ai-modal__head">
                    <div class="eg-aether-modal__brand">
                        <span class="eg-aether-modal__brand-icon" aria-hidden="true">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </span>
                        <div>
                            <p class="eg-mission-ai-modal__kicker">{{ __('missions.ai_wizard_kicker') }}</p>
                            <h2 id="ai-questionnaire-title" class="eg-aether-modal__title">
                                @if ($aiQuestionnaireTarget === 'meal')
                                    {{ __('missions.ai_wizard_title_meal') }}
                                @elseif ($aiWizardStep === 1)
                                    {{ __('missions.ai_wizard_enter_gym') }}
                                @else
                                    {{ __('missions.ai_wizard_title_workout') }}
                                @endif
                            </h2>
                            <p class="eg-aether-modal__subtitle">
                                {{ __('missions.ai_wizard_step', ['current' => eg_num($aiWizardStep), 'total' => eg_num($aiWizardSteps)]) }}
                                @if ($aiQuestionnaireTarget === 'workout')
                                    · {{ __('missions.ai_wq_'.$aiWizardStepKey.'_kicker') }}
                                @else
                                    · {{ __('missions.ai_wizard_step'.$aiWizardStep.'_title') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <button type="button" class="eg-aether-modal__close" wire:click="closeAiQuestionnaire" aria-label="{{ __('missions.ai_wizard_close') }}">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </header>

                <div class="eg-aether-modal__progress-track" aria-hidden="true">
                    <span class="eg-aether-modal__progress-fill" style="width: {{ $aiWizardProgressPercent }}%"></span>
                </div>

                @error('aiWizard')
                    <div class="eg-aether-modal__alert" role="alert">{{ $message }}</div>
                @enderror

                <div class="eg-mission-ai-modal__body">
                    @if ($aiQuestionnaireTarget === 'workout')
                        @include('livewire.missions.partials.workspace-ai-questionnaire-workout')
                    @else
                        @include('livewire.missions.partials.workspace-ai-questionnaire-meal')
                    @endif
                </div>

                <footer class="eg-mission-ai-modal__foot">
                    @if ($aiWizardStep > 1)
                        <button type="button" class="eg-aether-modal-btn eg-aether-modal-btn--ghost" wire:click="aiWizardBack" @disabled($aiIsGenerating)>
                            {{ __('missions.ai_wizard_back') }}
                        </button>
                    @else
                        <span></span>
                    @endif

                    @if ($aiWizardStep < $aiWizardSteps)
                        <button
                            type="button"
                            class="eg-aether-modal-btn eg-aether-modal-btn--primary"
                            wire:click="aiWizardNext"
                            @disabled(! $aiWizardCanProceed)
                        >
                            {{ __('missions.ai_wizard_next') }}
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    @else
                        <button type="button" class="eg-aether-modal-btn eg-aether-modal-btn--generate" wire:click="submitAiQuestionnaire" wire:loading.attr="disabled" wire:target="submitAiQuestionnaire">
                            <span wire:loading.remove wire:target="submitAiQuestionnaire">
                                <i class="fa-solid fa-dumbbell" aria-hidden="true"></i>
                                {{ __('missions.ai_wizard_generate') }}
                            </span>
                            <span wire:loading wire:target="submitAiQuestionnaire">{{ __('missions.ai_generating') }}</span>
                        </button>
                    @endif
                </footer>
            </div>
        </div>
    </div>
@endif
