@php
    use App\Enums\QuizType;

    $locale = app()->getLocale();
    $nameEn = $quiz->getTranslation('name', 'en');
    $nameFa = $quiz->getTranslation('name', 'fa');
    $descriptionEn = $quiz->getTranslation('description', 'en');
    $descriptionFa = $quiz->getTranslation('description', 'fa');
    $isFeatured = $featured ?? false;

    $typeIcon = match ($quiz->type) {
        QuizType::Mbti => 'fa-brain',
        QuizType::Likert => 'fa-heart-pulse',
        default => 'fa-compass',
    };

    $typeLabelKey = match ($quiz->type) {
        QuizType::Mbti => 'home.test_type_mbti',
        QuizType::Likert => 'home.test_type_likert',
        default => 'home.test_type_assessment',
    };
@endphp

<article @class(['eg-test-card eg-glass eg-transition', 'eg-test-card-featured' => $isFeatured])>
    <div class="eg-test-card-glow" aria-hidden="true"></div>

    <div class="eg-test-card-body">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
            <div class="eg-test-card-icon" aria-hidden="true">
                <i class="fa-solid {{ $typeIcon }}"></i>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                @if ($isFeatured)
                    <span class="eg-test-chip eg-test-chip-accent">
                        <i class="fa-solid fa-star"></i>
                        <span data-i18n="home.test_card_featured">{{ __('home.test_card_featured') }}</span>
                    </span>
                @endif
                <span class="eg-test-chip">
                    <span data-i18n="home.test_card_free">{{ __('home.test_card_free') }}</span>
                </span>
            </div>
        </div>

        <p class="eg-test-type-label mb-2">
            <span data-i18n="{{ $typeLabelKey }}">{{ __($typeLabelKey) }}</span>
        </p>

        <h3
            class="eg-test-card-title h4 mb-2"
            data-locale-field
            data-en="{{ $nameEn }}"
            data-fa="{{ $nameFa }}"
        >{{ $quiz->getTranslation('name', $locale) }}</h3>

        <p
            class="eg-test-card-desc eg-text-muted small mb-4"
            data-locale-field
            data-en="{{ $descriptionEn }}"
            data-fa="{{ $descriptionFa }}"
        >{{ $quiz->getTranslation('description', $locale) }}</p>

        @if ($quiz->type === QuizType::Mbti)
            <div class="eg-test-dimensions mb-4" aria-hidden="true">
                @foreach (['E', 'I', 'S', 'N', 'T', 'F', 'J', 'P'] as $letter)
                    <span class="eg-test-dimension">{{ $letter }}</span>
                @endforeach
            </div>
        @endif

        <div class="d-flex flex-wrap align-items-center gap-3 mb-4 eg-test-meta">
            <span class="eg-text-muted small">
                <i class="fa-solid fa-list-check me-1"></i>
                <span
                    data-locale-field
                    data-en="{{ trans('home.test_card_questions', ['count' => $quiz->questions_count], 'en') }}"
                    data-fa="{{ trans('home.test_card_questions', ['count' => $quiz->questions_count], 'fa') }}"
                >{{ __('home.test_card_questions', ['count' => $quiz->questions_count]) }}</span>
            </span>
            @if ($quiz->estimated_minutes)
                <span class="eg-text-muted small">
                    <i class="fa-solid fa-clock me-1"></i>
                    <span
                        data-locale-field
                        data-en="{{ trans('home.test_card_minutes', ['minutes' => $quiz->estimated_minutes], 'en') }}"
                        data-fa="{{ trans('home.test_card_minutes', ['minutes' => $quiz->estimated_minutes], 'fa') }}"
                    >{{ __('home.test_card_minutes', ['minutes' => $quiz->estimated_minutes]) }}</span>
                </span>
            @endif
            <span class="eg-text-muted small">
                <i class="fa-solid fa-user-secret me-1"></i>
                <span data-i18n="home.trust_anonymous">{{ __('home.trust_anonymous') }}</span>
            </span>
        </div>

        <a href="{{ route('quiz.start', $quiz->slug) }}" class="eg-btn-primary eg-transition eg-shadow-glow w-100 eg-test-card-cta">
            <span data-i18n="home.test_card_start">{{ __('home.test_card_start') }}</span>
            <i class="fa-solid fa-arrow-{{ $locale === 'fa' ? 'left' : 'right' }}" data-icon-directional></i>
        </a>
    </div>
</article>
