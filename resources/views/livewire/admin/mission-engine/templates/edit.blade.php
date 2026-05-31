<div class="eg-admin-page eg-admin-page--wide">
    @include('partials.admin.page-head', [
        'title' => __('admin.mission_engine.edit_title'),
        'subtitle' => $template->slug,
        'backRoute' => 'admin.mission-engine.templates.index',
        'backLabel' => __('admin.mission_engine.back_to_list'),
    ])

    @if ($pageNotice)
        <div @class([
            'eg-admin-notice mb-4',
            'eg-admin-notice--success' => $pageNoticeType === 'success',
            'eg-admin-notice--danger' => $pageNoticeType === 'danger',
        ]) wire:transition>
            <i class="fa-solid {{ $pageNoticeType === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check' }}" aria-hidden="true"></i>
            <span>{{ $pageNotice }}</span>
        </div>
    @endif

    @if ($lastSaveErrors !== [])
        <div class="eg-admin-readiness eg-admin-readiness--danger mb-4" role="alert">
            <p class="eg-admin-readiness__title mb-2">{{ __('admin.mission_engine.publish_blocked') }}</p>
            <ul class="eg-admin-readiness__list mb-0">
                @foreach ($lastSaveErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="eg-admin-mission-hero eg-admin-panel eg-admin-panel--padded mb-4">
        <div class="eg-admin-mission-hero__main">
            <div class="eg-admin-mission-hero__icon" aria-hidden="true">
                <i class="fa-solid {{ $template->icon ?: 'fa-compass' }}"></i>
            </div>
            <div>
                <h2 class="eg-admin-mission-hero__title mb-1">{{ $template->getTranslation('title', 'en', true) }}</h2>
                <p class="eg-admin-page-sub mb-2">{{ $template->getTranslation('summary', 'en', true) ?: __('admin.mission_engine.no_summary') }}</p>
                <div class="eg-admin-meta-row">
                    <span class="eg-admin-tag">{{ __('admin.table.status') }}: {{ str($template->status->value)->headline() }}</span>
                    <span class="eg-admin-tag">{{ __('admin.mission_engine.enrollments') }}: {{ number_format($enrollmentsCount) }}</span>
                    <span class="eg-admin-tag">{{ __('admin.mission_engine.fields') }}: {{ number_format($fieldsCount) }}</span>
                    <span class="eg-admin-tag">{{ __('admin.mission_engine.phases') }}: {{ number_format($phasesCount) }}</span>
                    <span class="eg-admin-tag">v{{ $template->version }}</span>
                </div>
            </div>
        </div>
        <div class="eg-admin-mission-hero__actions">
            @if ($catalogPreviewUrl)
                <a href="{{ $catalogPreviewUrl }}" class="eg-admin-btn eg-admin-btn--sm" target="_blank" rel="noopener">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>
                    {{ __('admin.mission_engine.preview_catalog') }}
                </a>
            @endif
            <button type="button" class="eg-admin-btn eg-admin-btn--sm" wire:click="duplicateTemplate" wire:confirm="{{ __('admin.mission_engine.duplicate_confirm') }}">
                <i class="fa-solid fa-copy me-1"></i>
                {{ __('admin.mission_engine.duplicate') }}
            </button>
        </div>
    </div>

    @if (! $readiness['ok'])
        <div class="eg-admin-readiness mb-4" role="status">
            <p class="eg-admin-readiness__title"><i class="fa-solid fa-triangle-exclamation me-2"></i>{{ __('admin.mission_engine.readiness_title') }}</p>
            <ul class="eg-admin-readiness__list mb-0">
                @foreach ($readiness['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('livewire.admin.mission-engine.templates.partials.builder-glossary')

    <nav class="eg-admin-tabs mb-4" aria-label="{{ __('admin.mission_engine.builder_tabs') }}">
        @foreach ([
            'details' => ['icon' => 'fa-sliders', 'label' => __('admin.mission_engine.tab_details')],
            'capabilities' => ['icon' => 'fa-puzzle-piece', 'label' => __('admin.mission_engine.tab_capabilities')],
            'fields' => ['icon' => 'fa-list-check', 'label' => __('admin.mission_engine.tab_fields'), 'count' => $fieldsCount],
            'phases' => ['icon' => 'fa-layer-group', 'label' => __('admin.mission_engine.tab_phases'), 'count' => $phasesCount],
            'enrollments' => ['icon' => 'fa-users', 'label' => __('admin.mission_engine.tab_enrollments'), 'count' => $enrollmentsCount],
        ] as $tabKey => $tab)
            <button
                type="button"
                class="eg-admin-tab @if ($activeTab === $tabKey) is-active @endif"
                wire:click="setTab('{{ $tabKey }}')"
            >
                <i class="fa-solid {{ $tab['icon'] }} me-1" aria-hidden="true"></i>
                {{ $tab['label'] }}
                @if (isset($tab['count']))
                    <span class="eg-admin-tab-count">{{ number_format($tab['count']) }}</span>
                @endif
            </button>
        @endforeach
    </nav>

    @if ($activeTab === 'details')
        @include('livewire.admin.mission-engine.templates.partials.edit-details')
    @elseif ($activeTab === 'capabilities')
        @include('livewire.admin.mission-engine.templates.partials.edit-capabilities')
    @elseif ($activeTab === 'fields')
        @include('livewire.admin.mission-engine.templates.partials.edit-fields')
    @elseif ($activeTab === 'phases')
        @include('livewire.admin.mission-engine.templates.partials.edit-phases')
    @else
        @include('livewire.admin.mission-engine.templates.partials.edit-enrollments')
    @endif
</div>
