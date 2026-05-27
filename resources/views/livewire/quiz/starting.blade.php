<div class="container py-5">
    <div class="mb-4">
        @include('partials.page-nav-actions', [
            'links' => [
                [
                    'href' => route('home'),
                    'label' => __('quiz.back_home'),
                    'icon' => 'fa-house',
                ],
            ],
        ])
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            @if ($quiz && ($quiz->settings['show_welcome'] ?? false))
                <div class="eg-quiz-card card border-0 shadow-lg mb-4">
                    <div class="card-body p-5">
                        @php $welcomeLocale = app()->getLocale(); @endphp
                        <p class="text-uppercase small fw-bold text-success mb-2">{{ $quiz->getTranslation('name', $welcomeLocale) }}</p>
                        <h1 class="h3 mb-3">{{ __('quiz.reboot.welcome_title') }}</h1>
                        <p class="text-muted mb-4">{{ $quiz->settings['welcome'][$welcomeLocale] ?? $quiz->settings['welcome']['en'] ?? '' }}</p>
                        <p class="small text-muted mb-4">{{ __('quiz.reboot.welcome_note') }}</p>
                        <button
                            type="button"
                            class="btn eg-btn-primary btn-lg w-100"
                            wire:click="beginOrResume(null)"
                        >
                            {{ __('quiz.reboot.begin') }}
                        </button>
                    </div>
                </div>
            @else
                <div class="eg-quiz-card card border-0 shadow-lg text-center">
                    <div class="card-body p-5">
                        <i class="fa-solid fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                        <p class="text-muted mb-0">{{ __('quiz.preparing') }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@guest
    @if ($quiz && ! ($quiz->settings['show_welcome'] ?? false))
        @script
        <script>
            const slug = @json($slug);
            const storageKey = slug ? `egomap_quiz_${slug}` : null;
            const savedUuid = storageKey ? localStorage.getItem(storageKey) : null;

            $wire.beginOrResume(savedUuid);
        </script>
        @endscript
    @endif
@endguest

@push('scripts')
<script>
    document.addEventListener('quiz-clear-stored-session', (event) => {
        const slug = event.detail?.slug;

        if (slug) {
            localStorage.removeItem(`egomap_quiz_${slug}`);
        }
    });
</script>
@endpush
