<div class="eg-virtue-page" x-data="{ showSuccess: @entangle('showSuccessModal'), showSlip: @entangle('showSlipModal') }">

    <section class="container pt-3">
        @include('partials.page-nav-actions', [
            'links' => [
                ['href' => route('virtue.hub', ['locale' => $locale]), 'label' => 'Back to Virtue Forge', 'icon' => 'fa-brain'],
            ],
        ])
    </section>

    <section class="container pb-5">

        {{-- Header card --}}
        <div class="eg-virtue-detail-header eg-glass mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="eg-virtue-card__icon eg-virtue-card__icon--lg">
                    {{ $routine->habit?->category->icon() ?? '✨' }}
                </div>
                <div>
                    <h1 class="eg-display h4 mb-1">{{ $routine->habit?->name }}</h1>
                    <span class="eg-virtue-badge">{{ $routine->habit?->category->label() }}</span>
                </div>
                @if ($routine->status->value === 'completed')
                    <span class="eg-virtue-badge eg-virtue-badge--success ms-auto">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Complete
                    </span>
                @endif
            </div>

            {{-- Stats row --}}
            <div class="eg-virtue-stats-row">
                <div class="eg-virtue-stat">
                    <div class="eg-virtue-stat__value">{{ $routine->current_streak }}</div>
                    <div class="eg-virtue-stat__label">
                        <i class="fa-solid fa-fire text-warning" aria-hidden="true"></i> Streak
                    </div>
                </div>
                <div class="eg-virtue-stat">
                    <div class="eg-virtue-stat__value">{{ $routine->best_streak }}</div>
                    <div class="eg-virtue-stat__label">Best</div>
                </div>
                <div class="eg-virtue-stat eg-virtue-stat--progress">
                    <div class="eg-virtue-circle-progress">
                        <svg viewBox="0 0 36 36" class="circular-chart">
                            <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <path class="circle"
                                  stroke-dasharray="{{ number_format($routine->progressPercent(), 0) }}, 100"
                                  d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                            <text x="18" y="20.35" class="circle-pct">{{ number_format($routine->progressPercent(), 0) }}%</text>
                        </svg>
                    </div>
                    <div class="eg-virtue-stat__label">Progress</div>
                </div>
                <div class="eg-virtue-stat">
                    <div class="eg-virtue-stat__value">{{ $routine->total_successes }}</div>
                    <div class="eg-virtue-stat__label">
                        <i class="fa-solid fa-circle-check text-success" aria-hidden="true"></i> Wins
                    </div>
                </div>
                <div class="eg-virtue-stat">
                    <div class="eg-virtue-stat__value">{{ $routine->goal_target }}</div>
                    <div class="eg-virtue-stat__label">
                        Goal {{ $routine->goal_type->value === 'days_count' ? 'days' : 'wins' }}
                    </div>
                </div>
            </div>

            {{-- Affirmation --}}
            @if ($routine->habit?->ai_affirmation)
                <div class="eg-virtue-affirmation mt-3">
                    <i class="fa-solid fa-quote-left" aria-hidden="true"></i>
                    {{ $routine->habit->ai_affirmation }}
                </div>
            @endif
        </div>

        {{-- Action buttons (active only) --}}
        @if ($routine->status->value === 'active')
            <div class="eg-virtue-actions mb-4">
                <button type="button"
                        class="eg-btn eg-btn--success flex-fill"
                        wire:click="openSuccessModal">
                    <i class="fa-solid fa-trophy" aria-hidden="true"></i>
                    I Won Today
                </button>
                <button type="button"
                        class="eg-btn eg-btn--danger"
                        wire:click="openSlipModal">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                    I Slipped
                </button>
            </div>
        @endif

        {{-- AI coaching plan --}}
        @if ($routine->habit?->ai_steps)
            <div class="eg-glass p-4 mb-4">
                <h2 class="h6 mb-3">
                    <i class="fa-solid fa-brain eg-virtue-icon-color me-1" aria-hidden="true"></i>
                    Your Daily Practice
                </h2>
                @if ($routine->habit->ai_root_cause)
                    <p class="eg-text-muted small mb-3">{{ $routine->habit->ai_root_cause }}</p>
                @endif
                @foreach ($routine->habit->ai_steps as $step)
                    <div class="eg-virtue-step mb-3">
                        <div class="eg-virtue-step__num">{{ $step['order'] }}</div>
                        <div>
                            <div class="fw-semibold">{{ $step['action'] }}</div>
                            <div class="small eg-text-muted">{{ $step['daily_practice'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Recent wins --}}
        @if ($routine->successLogs->isNotEmpty())
            <h2 class="h6 mb-3">Recent Wins</h2>
            <div class="eg-virtue-log-list mb-4">
                @foreach ($routine->successLogs as $log)
                    <div class="eg-virtue-log-item eg-virtue-log-item--win">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <span class="text-success fw-semibold small">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                +{{ $log->points_earned }} pts
                            </span>
                            <span class="eg-text-muted small">{{ $log->logged_at->format('d/m') }}</span>
                        </div>
                        @if ($log->situation)
                            <p class="mb-1 small">{{ $log->situation }}</p>
                        @endif
                        @if ($log->ai_encouragement)
                            <p class="mb-0 small fst-italic eg-virtue-icon-color">{{ $log->ai_encouragement }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Completion banner --}}
        @if ($routine->status->value === 'completed')
            <div class="eg-virtue-completion-banner eg-glass">
                <div class="eg-virtue-completion-banner__emoji">🎉</div>
                <h2 class="h4 mt-2 mb-2">Mission Complete!</h2>
                <p class="eg-text-muted mb-3">
                    You went {{ $routine->goal_target }} {{ $routine->goal_type->value === 'days_count' ? 'days' : 'wins' }} strong.
                    That's a new you.
                </p>
                <div class="eg-virtue-completion-rewards">
                    🏅 +200 points &nbsp;•&nbsp; Virtue Master badge
                </div>
            </div>
        @endif

    </section>

    {{-- Success modal --}}
    <div class="eg-virtue-modal-backdrop" x-show="showSuccess" x-cloak x-transition>
        <div class="eg-virtue-modal eg-glass" @click.stop>

            @if ($aiEncouragement)
                <div class="eg-virtue-encouragement">
                    <div class="mb-2" style="font-size: 2rem;">✨</div>
                    <p class="fw-semibold mb-0">{{ $aiEncouragement }}</p>
                </div>
                <button type="button" class="eg-btn eg-btn--virtue w-100 mt-3" wire:click="closeSuccessModal">
                    Close
                </button>
            @else
                <h3 class="h5 mb-1">🏆 You Won Today!</h3>
                <p class="eg-text-muted small mb-3">+5 points incoming</p>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">What happened? <span class="text-muted">(optional)</span></label>
                    <textarea wire:model="situation"
                              class="form-control eg-virtue-textarea"
                              rows="3"
                              placeholder="e.g. I was upset with a colleague but spoke directly without sarcasm…"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">How did you feel?</label>
                    <div class="eg-virtue-emotion-pills">
                        @foreach (['😊 Proud', '🙂 Calm', '💪 Strong', '😌 Peaceful', '🤩 Amazing'] as $emotion)
                            <button type="button"
                                    class="eg-virtue-goal-pill {{ $emotionalState === $emotion ? 'is-active' : '' }}"
                                    wire:click="$set('emotionalState', '{{ $emotionalState === $emotion ? '' : $emotion }}')">
                                {{ $emotion }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="button"
                            class="eg-btn eg-btn--success flex-fill"
                            wire:click="logSuccess"
                            @if($isLogging) disabled @endif>
                        <span wire:loading.remove wire:target="logSuccess">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                            Log & Earn +5 pts
                        </span>
                        <span wire:loading wire:target="logSuccess">
                            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                            Logging…
                        </span>
                    </button>
                    <button type="button" class="eg-btn" wire:click="closeModals">Cancel</button>
                </div>
            @endif
        </div>
    </div>

    {{-- Slip modal --}}
    <div class="eg-virtue-modal-backdrop" x-show="showSlip" x-cloak x-transition>
        <div class="eg-virtue-modal eg-glass" @click.stop>

            @if ($slipResult)
                @if (!empty($slipResult['ai_response']))
                    <div class="eg-virtue-slip-result">
                        <p class="mb-3">{{ $slipResult['ai_response']['acknowledgement'] }}</p>

                        <div class="eg-virtue-micro-task mb-3">
                            <div class="fw-semibold mb-1">
                                <i class="fa-solid fa-bolt text-warning" aria-hidden="true"></i>
                                Do this now:
                            </div>
                            {{ $slipResult['ai_response']['micro_task'] }}
                        </div>

                        <p class="fst-italic eg-text-muted small mb-2">{{ $slipResult['ai_response']['motivation_close'] }}</p>
                        <p class="small text-danger mb-0">{{ $slipResult['ai_response']['points_deducted_message'] }}</p>
                    </div>
                @endif

                <button type="button" class="eg-btn eg-btn--virtue w-100 mt-3" wire:click="closeModals">
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    Back to progress
                </button>
            @else
                <h3 class="h5 mb-1">
                    <i class="fa-solid fa-triangle-exclamation text-danger me-1" aria-hidden="true"></i>
                    Report a Slip
                </h3>
                <p class="eg-text-muted small mb-3">Honesty takes courage. +1 pt for reporting.</p>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">What happened? <span class="text-muted">(optional)</span></label>
                    <textarea wire:model="whatHappened"
                              class="form-control eg-virtue-textarea"
                              rows="3"
                              placeholder="e.g. I got frustrated and snapped sarcastically at a friend…"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="button"
                            class="eg-btn eg-btn--danger flex-fill"
                            wire:click="logSlip"
                            @if($isLogging) disabled @endif>
                        <span wire:loading.remove wire:target="logSlip">
                            <i class="fa-solid fa-flag" aria-hidden="true"></i>
                            Report Honestly
                        </span>
                        <span wire:loading wire:target="logSlip">
                            <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                            Reporting…
                        </span>
                    </button>
                    <button type="button" class="eg-btn" wire:click="closeModals">Cancel</button>
                </div>
            @endif

        </div>
    </div>

</div>
