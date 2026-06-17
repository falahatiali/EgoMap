<div
    class="mood-page"
    x-data="{ harvestOpen: @entangle('harvestIdeaId') }"
    @if ($todayEntry)
        style="--mood-accent: {{ \App\Support\MoodEmotionCatalog::color(\App\Enums\MoodEmotion::from($todayEntry['emotion'])) }};"
    @endif
>
    <div class="mood-shell">
        @if (session('mood_status'))
            <div class="mood-toast" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3500)"
                x-transition:leave="opacity-0 translate-y-1 transition-all duration-300">
                {{ session('mood_status') }}
            </div>
        @endif

        <header class="mood-header">
            <div>
                <h1 class="mood-header__title">{{ __('mood.compass_title') }}</h1>
                <p class="mood-header__subtitle">{{ __('mood.compass_subtitle') }}</p>
            </div>
        </header>

        <div class="mood-grid">
            {{-- Column 1: Feeling Compass --}}
            <section class="mood-panel mood-panel--compass">
                <h2 class="mood-panel__title">{{ __('mood.compass_title') }}</h2>

                @if ($todayEntry && ! $reshaping)
                    @php
                        $emotion = \App\Enums\MoodEmotion::from($todayEntry['emotion']);
                        $accent = \App\Support\MoodEmotionCatalog::color($emotion);
                        $ai = $todayEntry['ai_response'] ?? [];
                    @endphp

                    <div class="mood-wisdom" style="--mood-accent: {{ $accent }};">
                        <div class="mood-wisdom__badge">
                            {{ \App\Support\MoodEmotionCatalog::emoji($emotion) }}
                            {{ $todayEntry['emotion_label'] ?? $todayEntry['emotion'] }}
                            · {{ $todayEntry['intensity'] }}/10
                        </div>

                        <div class="mood-wisdom__block">
                            <span class="mood-wisdom__label">{{ __('mood.compass_subtitle') }}</span>
                            <p class="mood-wisdom__text">{{ $ai['empathy'] ?? '' }}</p>
                        </div>

                        <div class="mood-wisdom__block">
                            <span class="mood-wisdom__label">Try this</span>
                            <p class="mood-wisdom__text">{{ $ai['challenge'] ?? '' }}</p>
                        </div>

                        <div class="mood-wisdom__block">
                            <span class="mood-wisdom__label">Reframe</span>
                            <p class="mood-wisdom__text mood-wisdom__text--muted">{{ $ai['reframe'] ?? '' }}</p>
                        </div>

                        @if (! empty($ai['idea_seed']))
                            <div class="mood-seed-preview">
                                <span class="mood-seed-preview__label">Idea seed</span>
                                <p>{{ $ai['idea_seed'] }}</p>
                            </div>
                        @endif

                        <div class="mood-wisdom__actions">
                            <button type="button" wire:click="saveIdeaFromMood" class="mood-btn mood-btn--primary">
                                {{ __('mood.save_to_ideas') }}
                            </button>
                            <button type="button" wire:click="startReshape" class="mood-btn mood-btn--ghost">
                                {{ __('mood.compass_title') }}
                            </button>
                        </div>
                    </div>
                @else
                    <div class="mood-emotion-grid">
                        @foreach ($emotions as $emotion)
                            <button
                                type="button"
                                wire:click="selectEmotion('{{ $emotion['value'] }}')"
                                class="mood-emotion {{ $selectedEmotion === $emotion['value'] ? 'mood-emotion--active' : '' }}"
                                style="--mood-emotion-color: {{ $emotion['color'] }};"
                            >
                                <span class="mood-emotion__emoji">{{ $emotion['emoji'] }}</span>
                                <span class="mood-emotion__label">{{ $emotion['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    @if ($selectedEmotion)
                        @php
                            $selected = collect($emotions)->firstWhere('value', $selectedEmotion);
                            $accent = $selected['color'] ?? '#34D399';
                        @endphp

                        <div class="mood-intensity" style="--mood-accent: {{ $accent }};">
                            <div class="mood-intensity__head">
                                <span>{{ __('mood.intensity_label') }}</span>
                                <strong>{{ $intensity }}/10</strong>
                            </div>
                            <input type="range" min="1" max="10" step="1" wire:model.live="intensity" class="mood-intensity__range">
                            <button type="button" wire:click="logMood" wire:loading.attr="disabled" class="mood-btn mood-btn--primary mood-btn--wide">
                                <span wire:loading.remove wire:target="logMood">{{ __('mood.save_mood') }}</span>
                                <span wire:loading wire:target="logMood">…</span>
                            </button>
                        </div>
                    @endif
                @endif

                @if (count($heatmap) > 0)
                    <div class="mood-heatmap">
                        <h3 class="mood-heatmap__title">30-day pulse</h3>
                        <div class="mood-heatmap__grid">
                            @foreach ($heatmap as $point)
                                @php
                                    $color = \App\Support\MoodEmotionCatalog::color(\App\Enums\MoodEmotion::from($point['emotion']));
                                    $alpha = max(0.25, min(1, $point['intensity'] / 10));
                                @endphp
                                <span
                                    class="mood-heatmap__cell"
                                    title="{{ $point['date'] }} · {{ $point['emotion'] }} {{ $point['intensity'] }}/10"
                                    style="background: color-mix(in srgb, {{ $color }} {{ (int) ($alpha * 100) }}%, transparent);"
                                ></span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            {{-- Column 2: Idea Garden --}}
            <section class="mood-panel mood-panel--garden">
                <div class="mood-panel__head">
                    <div>
                        <h2 class="mood-panel__title">{{ __('mood.idea_garden_title') }}</h2>
                        <p class="mood-panel__subtitle">{{ __('mood.idea_garden_subtitle') }}</p>
                    </div>
                </div>

                <form wire:submit="addManualSeed" class="mood-seed-form">
                    <input
                        type="text"
                        wire:model="manualSeed"
                        class="mood-seed-form__input"
                        placeholder="{{ __('mood.no_ideas') }}"
                    >
                    <button type="submit" class="mood-btn mood-btn--ghost">+</button>
                </form>

                @php
                    $totalIdeas = count($garden['raw']) + count($garden['mature']) + count($garden['harvested']);
                @endphp

                @if ($totalIdeas === 0)
                    <p class="mood-empty">{{ __('mood.no_ideas') }}</p>
                @else
                    <div class="mood-kanban">
                        <div class="mood-kanban__col">
                            <h3 class="mood-kanban__title">{{ __('mood.idea_status.raw') }}</h3>
                            @forelse ($garden['raw'] as $idea)
                                <article class="mood-idea-card" wire:key="raw-{{ $idea['id'] }}">
                                    <p>{{ $idea['seed_text'] }}</p>
                                    <button type="button" wire:click="matureIdea({{ $idea['id'] }})" class="mood-btn mood-btn--sm">
                                        {{ __('mood.mature_idea') }}
                                    </button>
                                </article>
                            @empty
                                <p class="mood-kanban__empty">—</p>
                            @endforelse
                        </div>

                        <div class="mood-kanban__col">
                            <h3 class="mood-kanban__title">{{ __('mood.idea_status.mature') }}</h3>
                            @forelse ($garden['mature'] as $idea)
                                <article class="mood-idea-card mood-idea-card--mature" wire:key="mature-{{ $idea['id'] }}">
                                    <p>{{ $idea['seed_text'] }}</p>
                                    @if (is_array($idea['matured_details'] ?? null))
                                        <ul class="mood-idea-card__details">
                                            @foreach ($idea['matured_details'] as $key => $detail)
                                                <li><strong>{{ str($key)->headline() }}:</strong> {{ $detail }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <button type="button" wire:click="openHarvest({{ $idea['id'] }})" class="mood-btn mood-btn--sm mood-btn--primary">
                                        {{ __('mood.harvest_idea') }}
                                    </button>
                                </article>
                            @empty
                                <p class="mood-kanban__empty">—</p>
                            @endforelse
                        </div>

                        <div class="mood-kanban__col">
                            <h3 class="mood-kanban__title">{{ __('mood.idea_status.harvested') }}</h3>
                            @forelse ($garden['harvested'] as $idea)
                                <article class="mood-idea-card mood-idea-card--goal" wire:key="harvested-{{ $idea['id'] }}">
                                    <p>{{ $idea['seed_text'] }}</p>
                                    @if ($idea['goal_cadence_label'])
                                        <span class="mood-idea-card__cadence">{{ $idea['goal_cadence_label'] }}</span>
                                    @endif
                                    <div class="mood-progress">
                                        <input
                                            type="range"
                                            min="0"
                                            max="100"
                                            step="5"
                                            wire:model.live="progressDrafts.{{ $idea['id'] }}"
                                            class="mood-progress__range"
                                        >
                                        <div class="mood-progress__meta">
                                            <span>{{ $progressDrafts[$idea['id']] ?? $idea['progress'] }}%</span>
                                            <button type="button" wire:click="updateProgress({{ $idea['id'] }})" class="mood-btn mood-btn--sm">
                                                {{ __('mood.check_in') }}
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <p class="mood-kanban__empty">—</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </section>

            {{-- Column 3: Community pulse --}}
            <section class="mood-panel mood-panel--community">
                <div class="mood-community-card">
                    <div class="mood-community-card__head">
                        <span class="mood-community-card__icon"><i class="fa-solid fa-users"></i></span>
                        <div>
                            <h2 class="mood-panel__title">{{ __('mood.community_pulse') }}</h2>
                            <p class="mood-panel__subtitle">{{ __('mood.community_subtitle') }}</p>
                        </div>
                    </div>

                    @if ($communityPreview)
                        <article class="mood-community-post">
                            <div class="mood-community-post__meta">
                                <span class="mood-community-post__avatar">
                                    {{ strtoupper(substr($communityPreview->displayName(), 0, 1)) }}
                                </span>
                                <div>
                                    <strong>{{ $communityPreview->displayName() }}</strong>
                                    <span>{{ $communityPreview->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                            <p>{{ Str::limit($communityPreview->content, 180) }}</p>
                        </article>
                    @else
                        <p class="mood-empty">{{ __('community.empty_feed') }}</p>
                    @endif

                    <a href="{{ route('community.feed', ['locale' => $locale]) }}" class="mood-btn mood-btn--primary mood-btn--wide" wire:navigate>
                        {{ __('mood.view_community') }}
                    </a>
                </div>
            </section>
        </div>
    </div>

    {{-- Harvest modal --}}
    <div class="mood-modal-backdrop" x-show="harvestOpen !== null" x-cloak x-transition.opacity>
        <div class="mood-modal" @click.outside="$wire.closeHarvest()">
            <h3 class="mood-modal__title">{{ __('mood.harvest_idea') }}</h3>
            <p class="mood-modal__subtitle">{{ __('mood.idea_garden_subtitle') }}</p>

            <div class="mood-cadence-list">
                @foreach ($cadenceOptions as $value => $label)
                    <label class="mood-cadence-option">
                        <input type="radio" wire:model="harvestCadence" value="{{ $value }}">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>

            <div class="mood-modal__actions">
                <button type="button" wire:click="closeHarvest" class="mood-btn mood-btn--ghost">{{ __('mood.dismiss') }}</button>
                <button type="button" wire:click="confirmHarvest" class="mood-btn mood-btn--primary">{{ __('mood.harvest_idea') }}</button>
            </div>
        </div>
    </div>
</div>
