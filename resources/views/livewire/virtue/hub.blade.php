<div class="eg-virtue-page">

    {{-- Flash status --}}
    @if (session('virtue_status'))
        <div class="container pt-3">
            <div class="eg-virtue-toast" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                {{ session('virtue_status') }}
            </div>
        </div>
    @endif

    {{-- Hero --}}
    <section class="eg-virtue-hero">
        <div class="container">
            <div class="eg-virtue-hero-card eg-glass">
                <div class="eg-virtue-hero-icon" aria-hidden="true">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <div>
                    <h1 class="eg-display h3 mb-1">Virtue Forge</h1>
                    <p class="eg-text-muted mb-0">Forge your character, one habit at a time.</p>
                </div>
                <a href="{{ route('virtue.habits', ['locale' => $locale]) }}" class="eg-btn eg-btn--virtue ms-auto">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    New Mission
                </a>
            </div>
        </div>
    </section>

    <section class="container pb-5">

        {{-- Active missions --}}
        @if ($activeRoutines->isNotEmpty())
            <div class="eg-virtue-section-header">
                <h2 class="h5 mb-0">Active Missions</h2>
                <span class="eg-virtue-count">{{ $activeRoutines->count() }}</span>
            </div>
            <div class="eg-virtue-grid mb-4">
                @foreach ($activeRoutines as $routine)
                    <a href="{{ route('virtue.routine', ['locale' => $locale, 'routineId' => $routine->id]) }}"
                       class="eg-virtue-card eg-glass">

                        <div class="eg-virtue-card__head">
                            <div class="eg-virtue-card__icon">
                                {{ $routine->habit?->category->icon() ?? '✨' }}
                            </div>
                            <div>
                                <div class="eg-virtue-card__name">{{ $routine->habit?->name }}</div>
                                <div class="eg-virtue-card__category eg-text-muted small">
                                    {{ $routine->habit?->category->label() }}
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right eg-text-muted ms-auto" aria-hidden="true"></i>
                        </div>

                        <div class="eg-virtue-progress-bar mt-3">
                            <div class="eg-virtue-progress-fill"
                                 style="width: {{ number_format($routine->progressPercent(), 0) }}%"></div>
                        </div>

                        <div class="eg-virtue-card__stats mt-2">
                            <span>
                                <i class="fa-solid fa-fire text-warning" aria-hidden="true"></i>
                                {{ $routine->current_streak }} day streak
                            </span>
                            <span>
                                <i class="fa-solid fa-circle-check text-success" aria-hidden="true"></i>
                                {{ $routine->total_successes }} wins
                            </span>
                            <span class="ms-auto eg-virtue-pct">{{ number_format($routine->progressPercent(), 0) }}%</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Completed missions --}}
        @if ($completedRoutines->isNotEmpty())
            <div class="eg-virtue-section-header">
                <h2 class="h5 mb-0">Victories</h2>
                <span class="eg-virtue-count">{{ $completedRoutines->count() }}</span>
            </div>
            <div class="eg-virtue-grid mb-4">
                @foreach ($completedRoutines as $routine)
                    <a href="{{ route('virtue.routine', ['locale' => $locale, 'routineId' => $routine->id]) }}"
                       class="eg-virtue-card eg-virtue-card--complete eg-glass">
                        <div class="eg-virtue-card__head">
                            <div class="eg-virtue-card__icon">
                                {{ $routine->habit?->category->icon() ?? '✨' }}
                            </div>
                            <div>
                                <div class="eg-virtue-card__name">{{ $routine->habit?->name }}</div>
                                <div class="eg-virtue-card__category eg-text-muted small">
                                    {{ $routine->goal_target }}
                                    {{ $routine->goal_type->value === 'days_count' ? 'days' : 'wins' }} completed
                                </div>
                            </div>
                            <span class="eg-virtue-badge eg-virtue-badge--success ms-auto">
                                <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Complete
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Empty state --}}
        @if ($activeRoutines->isEmpty() && $completedRoutines->isEmpty())
            <div class="eg-virtue-empty eg-glass">
                <div class="eg-virtue-empty__icon">🧠</div>
                <h3 class="h5 mt-3 mb-2">Your forge is empty</h3>
                <p class="eg-text-muted mb-4">
                    Pick a bad habit to work on. The AI will explain its roots and give you a personalised plan.
                </p>
                <a href="{{ route('virtue.habits', ['locale' => $locale]) }}" class="eg-btn eg-btn--virtue">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Start Your First Mission
                </a>
            </div>
        @endif

    </section>

</div>
