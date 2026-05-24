<section class="container eg-auth-container">
    <div class="eg-auth-card eg-glass">
        <div class="eg-auth-icon" aria-hidden="true">
            <i class="fa-solid fa-right-to-bracket"></i>
        </div>

        <h1 class="eg-display eg-auth-title">{{ __('auth.login_title') }}</h1>
        <p class="eg-auth-subtitle">{{ __('auth.login_subtitle') }}</p>

        @if (session('auth_notice'))
            <div class="alert alert-success eg-auth-alert" role="alert">
                {{ session('auth_notice') }}
            </div>
        @endif

        <form wire:submit="login" class="eg-auth-form">
            <div class="mb-3">
                <label for="login-email" class="form-label">{{ __('auth.email') }}</label>
                <input
                    id="login-email"
                    type="email"
                    wire:model="email"
                    class="form-control form-control-lg eg-auth-input @error('email') is-invalid @enderror"
                    placeholder="{{ __('auth.email_placeholder') }}"
                    autocomplete="email"
                    autofocus
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="login-password" class="form-label">{{ __('auth.password') }}</label>
                <input
                    id="login-password"
                    type="password"
                    wire:model="password"
                    class="form-control form-control-lg eg-auth-input @error('password') is-invalid @enderror"
                    placeholder="{{ __('auth.password_placeholder') }}"
                    autocomplete="current-password"
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4 form-check">
                <input
                    id="login-remember"
                    type="checkbox"
                    wire:model="remember"
                    class="form-check-input"
                >
                <label class="form-check-label" for="login-remember">{{ __('auth.remember_me') }}</label>
            </div>

            <button type="submit" class="btn eg-btn-primary w-100 eg-auth-submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">{{ __('auth.login_button') }}</span>
                <span wire:loading wire:target="login">{{ __('auth.logging_in') }}</span>
            </button>
        </form>

        <p class="eg-auth-footer-text">
            {{ __('auth.no_account') }}
            <a href="{{ route('register') }}" class="eg-auth-link">{{ __('auth.register_link') }}</a>
        </p>
    </div>
</section>
