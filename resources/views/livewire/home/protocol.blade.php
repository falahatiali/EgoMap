<div class="eg-protocol rh-shell" wire:cloak>
    @if (session('quiz_notice'))
        <div class="rh-notice" role="alert">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            {{ session('quiz_notice') }}
        </div>
    @endif

    @if ($screen === 'landing')
        @include('livewire.home.landing', [
            'ncPreviewDay' => $ncPreviewDay,
            'ncPreviewTotal' => $ncPreviewTotal,
        ])
    @else
        <div class="rh-triage-top">
            <a href="{{ route('home') }}" class="rh-link-back" wire:navigate>
                <i class="fa-solid fa-arrow-left" data-icon-directional aria-hidden="true"></i>
                {{ __('recovery.back') }}
            </a>
            @include('partials.language-switcher')
        </div>
        <section
            class="rh-triage"
            wire:transition:enter="rh-fade-in"
            wire:transition:enter.duration.400ms
        >
            @if ($triage !== null)
                @include('livewire.partials.recovery-triage-wizard', $triage)
            @endif
        </section>
    @endif
</div>
