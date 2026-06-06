<section id="my-programs" class="container eg-profile-section">
    <div class="eg-profile-section-head mb-3">
        <div>
            <h2 class="eg-display h4 mb-1">{{ __('profile.programs_title') }}</h2>
            <p class="eg-text-muted mb-0">{{ __('profile.programs_subtitle') }}</p>
        </div>
    </div>

    @if ($programRecords->isNotEmpty())
        <div class="eg-profile-programs-grid">
            @foreach ($programRecords as $record)
                <a
                    href="{{ $record['detail_url'] }}"
                    class="eg-profile-program-card eg-glass text-decoration-none text-reset"
                    wire:navigate
                >
                    <div class="eg-profile-program-card-top">
                        <span class="eg-profile-program-card-icon" aria-hidden="true">
                            <i class="fa-solid {{ $record['applied_target'] === 'meal' ? 'fa-utensils' : 'fa-dumbbell' }}"></i>
                        </span>
                        <div>
                            <h3 class="eg-profile-program-card-title">{{ $record['title'] }}</h3>
                            <p class="small eg-text-muted mb-0">{{ $record['summary'] }}</p>
                        </div>
                    </div>
                    <div class="eg-profile-program-card-meta small eg-text-muted">
                        <span>{{ $record['created_at_label'] }}</span>
                        @if ($record['mission_title'])
                            <span class="mx-2" aria-hidden="true">·</span>
                            <span>{{ $record['mission_title'] }}</span>
                        @endif
                    </div>
                    <span class="eg-profile-program-card-cta">
                        {{ __('profile.programs_view_detail') }}
                        <i class="fa-solid fa-arrow-{{ app()->getLocale() === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
                    </span>
                </a>
            @endforeach
        </div>
    @else
        <div class="eg-profile-programs-empty eg-glass">
            <span class="eg-profile-programs-empty-icon" aria-hidden="true">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </span>
            <h3 class="h5 mb-2">{{ __('profile.programs_empty_title') }}</h3>
            <p class="eg-text-muted mb-4">{{ __('profile.programs_empty_body') }}</p>
            <a href="{{ $missionNav['catalog_href'] }}" class="btn eg-btn-mission-browse eg-transition" wire:navigate>
                <i class="fa-solid fa-compass" aria-hidden="true"></i>
                {{ __('missions.browse_missions') }}
            </a>
        </div>
    @endif
</section>
