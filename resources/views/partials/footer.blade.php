<footer class="eg-footer">
    <div class="container">
        <div class="row align-items-center gy-3">
            <div class="col-md-6 text-center text-md-start">
                <a class="eg-brand d-inline-flex" href="{{ route('home') }}">
                    <span class="eg-brand-icon" aria-hidden="true">
                        <i class="fa-solid fa-compass"></i>
                    </span>
                    <span data-i18n="common.brand">{{ __('common.brand') }}</span>
                </a>
                <p class="eg-text-muted small mb-0 mt-2" data-i18n="common.tagline">{{ __('common.tagline') }}</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="eg-text-muted small mb-0">
                    &copy; {{ date('Y') }}
                    <span data-i18n="common.brand">{{ __('common.brand') }}</span>.
                    <span data-i18n="common.copyright">{{ __('common.copyright') }}</span>
                </p>
            </div>
        </div>
    </div>
</footer>
