@if ($showAiQuestionnaire)
    <div class="eg-mission-ai-modal" role="dialog" aria-modal="true" aria-labelledby="ai-questionnaire-title">
        <div class="eg-mission-ai-modal__backdrop" wire:click="closeAiQuestionnaire"></div>
        <div class="eg-mission-ai-modal__panel eg-glass">
            <header class="eg-mission-ai-modal__head">
                <div>
                    <p class="eg-mission-ai-modal__kicker">{{ __('missions.ai_wizard_kicker') }}</p>
                    <h2 id="ai-questionnaire-title" class="h5 mb-1">
                        {{ $aiQuestionnaireTarget === 'meal' ? __('missions.ai_wizard_title_meal') : __('missions.ai_wizard_title_workout') }}
                    </h2>
                    <p class="small eg-text-muted mb-0">
                        {{ __('missions.ai_wizard_step', ['current' => eg_num($aiWizardStep), 'total' => eg_num($aiWizardSteps)]) }}
                    </p>
                </div>
                <button type="button" class="btn btn-sm btn-outline-light" wire:click="closeAiQuestionnaire" aria-label="{{ __('missions.ai_wizard_close') }}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </header>

            <div class="eg-mission-ai-modal__progress" aria-hidden="true">
                @for ($step = 1; $step <= $aiWizardSteps; $step++)
                    <span @class(['is-active' => $aiWizardStep >= $step])></span>
                @endfor
            </div>

            @error('aiWizard')
                <div class="alert alert-danger py-2 small">{{ $message }}</div>
            @enderror

            <div class="eg-mission-ai-modal__body">
                <div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 1])>
                    <p class="small eg-text-muted">{{ __('missions.ai_wizard_step1_help') }}</p>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('missions.ai_field_age') }}</label>
                            <input type="number" class="form-control" wire:model.live="aiAge" min="16" max="80">
                            @error('aiAge')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('missions.ai_field_gender') }}</label>
                            <select class="form-select" wire:model.live="aiGender">
                                @foreach ($aiFormOptions['genders'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiGender')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('missions.ai_field_body_fat') }}</label>
                            <input type="number" step="0.1" class="form-control" wire:model.live="aiBodyFatPercent" placeholder="{{ __('missions.optional') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_height') }}</label>
                            <input type="number" class="form-control" wire:model.live="aiHeightCm" min="130" max="230">
                            @error('aiHeightCm')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_weight') }}</label>
                            <input type="number" step="0.1" class="form-control" wire:model.live="aiWeightKg" min="35" max="250">
                            @error('aiWeightKg')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 2])>
                    <p class="small eg-text-muted">{{ __('missions.ai_wizard_step2_help') }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_goal') }}</label>
                            <select class="form-select" wire:model.live="aiPrimaryGoal">
                                @foreach ($aiFormOptions['goals'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiPrimaryGoal')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_experience') }}</label>
                            <select class="form-select" wire:model.live="aiTrainingExperience">
                                @foreach ($aiFormOptions['experience'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiTrainingExperience')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('missions.ai_field_days') }}</label>
                            <input type="number" class="form-control" wire:model.live="aiTrainingDaysPerWeek" min="1" max="7">
                            @error('aiTrainingDaysPerWeek')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('missions.ai_field_session') }}</label>
                            <select class="form-select" wire:model.live="aiSessionDuration">
                                @foreach ($aiFormOptions['session_durations'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiSessionDuration')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('missions.ai_field_time') }}</label>
                            <select class="form-select" wire:model.live="aiPreferredWorkoutTime">
                                @foreach ($aiFormOptions['workout_times'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiPreferredWorkoutTime')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('missions.ai_field_equipment') }}</label>
                            <select class="form-select" wire:model.live="aiEquipment">
                                @foreach ($aiFormOptions['equipment'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiEquipment')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('missions.ai_field_injuries') }}</label>
                            <textarea class="form-control" rows="2" wire:model.live="aiInjuriesLimitations" placeholder="{{ __('missions.ai_field_injuries_placeholder') }}"></textarea>
                        </div>
                    </div>
                </div>

                <div @class(['eg-mission-ai-step', 'd-none' => $aiWizardStep !== 3])>
                    <p class="small eg-text-muted">{{ __('missions.ai_wizard_step3_help') }}</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_diet') }}</label>
                            <select class="form-select" wire:model.live="aiDietaryPattern">
                                @foreach ($aiFormOptions['dietary'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiDietaryPattern')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_cooking') }}</label>
                            <select class="form-select" wire:model.live="aiCookingAbility">
                                @foreach ($aiFormOptions['cooking'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiCookingAbility')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_tone') }}</label>
                            <select class="form-select" wire:model.live="aiCoachingTone">
                                @foreach ($aiFormOptions['tones'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiCoachingTone')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_motivation') }}</label>
                            <select class="form-select" wire:model.live="aiMotivationStyle">
                                @foreach ($aiFormOptions['motivation'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                            @error('aiMotivationStyle')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('missions.ai_field_allergies') }}</label>
                            <input type="text" class="form-control" wire:model.live="aiAllergiesText" placeholder="{{ __('missions.ai_field_allergies_placeholder') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_favorites') }}</label>
                            <input type="text" class="form-control" wire:model.live="aiFavoriteExercisesText">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('missions.ai_field_dislikes') }}</label>
                            <input type="text" class="form-control" wire:model.live="aiDislikedExercisesText">
                        </div>
                    </div>
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
                    <button type="button" class="btn btn-primary" wire:click="submitAiQuestionnaire" wire:loading.attr="disabled" wire:target="submitAiQuestionnaire">
                        <span wire:loading.remove wire:target="submitAiQuestionnaire">{{ __('missions.ai_wizard_generate') }}</span>
                        <span wire:loading wire:target="submitAiQuestionnaire">{{ __('missions.ai_generating') }}</span>
                    </button>
                @endif
            </footer>
        </div>
    </div>
@endif
