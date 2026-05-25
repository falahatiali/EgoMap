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
        <div class="col-lg-6 text-center">
            <div class="eg-quiz-card card border-0 shadow-lg">
                <div class="card-body p-5">
                    <i class="fa-solid fa-spinner fa-spin fa-2x text-primary mb-3"></i>
                    <p class="text-muted mb-0">{{ __('quiz.preparing') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@guest
    @script
    <script>
        const slug = @json($slug);
        const storageKey = slug ? `egomap_quiz_${slug}` : null;
        const savedUuid = storageKey ? localStorage.getItem(storageKey) : null;

        $wire.beginOrResume(savedUuid);
    </script>
    @endscript
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
