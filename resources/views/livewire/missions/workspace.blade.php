<div class="eg-missions-page" x-data="{ saved: false }" x-on:mission-saved.window="saved = true; setTimeout(() => saved = false, 2500)">
    <section class="container pt-3">
        @include('partials.page-nav-actions', [
            'links' => [
                ['href' => route('missions.catalog', ['locale' => $locale]), 'label' => __('missions.back_catalog'), 'icon' => 'fa-compass'],
                ['href' => route('profile', ['locale' => $locale]), 'label' => __('missions.back_profile'), 'icon' => 'fa-user'],
            ],
        ])
    </section>

    <section class="container pb-5">
        <div class="eg-mission-workspace-header eg-glass mb-4">
            <div class="eg-mission-workspace-header__main">
                <div class="eg-mission-workspace-header__icon" aria-hidden="true">
                    <i class="fa-solid {{ $missionIcon }}"></i>
                </div>
                <div class="eg-mission-workspace-header__copy">
                    <h1 class="eg-display h4 mb-1">{{ $presenter->title($locale) }}</h1>
                    <p class="eg-text-muted small mb-0">{{ __('missions.workspace_subtitle') }}</p>
                </div>
            </div>
            <div x-show="saved" x-cloak class="eg-mission-save-toast" role="status">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                {{ __('missions.saved') }}
            </div>
            @if (session('mission_ai_status'))
                <div class="alert alert-success mb-0 mt-2 py-2 px-3 small" role="status">
                    <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>
                    {{ session('mission_ai_status') }}
                </div>
            @endif
            <div class="eg-mission-log-date">
                <label class="form-label mb-1" for="mission-log-date">{{ __('missions.log_date') }}</label>
                <input type="date" id="mission-log-date" class="form-control" wire:model.live="logDate">
                <p class="eg-text-muted small mb-0 mt-1">{{ __('missions.log_date_help') }}</p>
            </div>
        </div>

        <div class="eg-mission-workspace">
            <nav class="eg-mission-tabs eg-glass" aria-label="{{ __('missions.workspace_title') }}">
                @foreach ([
                    'program' => ['icon' => 'fa-bolt', 'label' => __('missions.tab_program')],
                    'supplements' => ['icon' => 'fa-capsules', 'label' => __('missions.tab_supplements')],
                    'daily' => ['icon' => 'fa-calendar-day', 'label' => __('missions.tab_daily')],
                    'schedule' => ['icon' => 'fa-calendar-week', 'label' => __('missions.tab_schedule')],
                    'equipment' => ['icon' => 'fa-bag-shopping', 'label' => __('missions.tab_equipment')],
                    'registration' => ['icon' => 'fa-clipboard-check', 'label' => __('missions.tab_registration')],
                ] as $tab => $tabMeta)
                    <button
                        type="button"
                        class="eg-mission-tab {{ $activeTab === $tab ? 'is-active' : '' }}"
                        wire:click="setTab('{{ $tab }}')"
                    >
                        <i class="fa-solid {{ $tabMeta['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $tabMeta['label'] }}</span>
                    </button>
                @endforeach
            </nav>

            <div class="eg-mission-panel eg-glass">
                @if ($activeTab === 'program')
                    @include('livewire.missions.partials.workspace-program')
                @elseif ($activeTab === 'supplements')
                    @include('livewire.missions.partials.workspace-supplements')
                @elseif ($activeTab === 'daily')
                    @include('livewire.missions.partials.workspace-daily')
                @elseif ($activeTab === 'schedule')
                    @include('livewire.missions.partials.workspace-schedule')
                @elseif ($activeTab === 'equipment')
                    @include('livewire.missions.partials.workspace-equipment')
                @elseif ($activeTab === 'registration')
                    @include('livewire.missions.partials.workspace-registration')
                @endif
            </div>
        </div>
    </section>

    @include('livewire.missions.partials.workspace-ai-questionnaire')
</div>
