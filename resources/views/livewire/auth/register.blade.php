<section class="container eg-auth-container">
    <div class="eg-auth-card eg-glass">
        <div class="eg-auth-icon" aria-hidden="true">
            <i class="fa-solid fa-user-plus"></i>
        </div>

        <h1 class="eg-display eg-auth-title">{{ __('auth.register_title') }}</h1>
        <p class="eg-auth-subtitle">{{ __('auth.register_subtitle') }}</p>

        @if (session('auth_notice'))
            <div class="alert alert-success eg-auth-alert" role="alert">
                {{ session('auth_notice') }}
            </div>
        @endif

        <form wire:submit="register" class="eg-auth-form">
            <div class="mb-3">
                <label for="register-email" class="form-label">{{ __('auth.email') }}</label>
                <input
                    id="register-email"
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

            <div class="mb-4">
                <label for="register-password" class="form-label">{{ __('auth.password') }}</label>
                <input
                    id="register-password"
                    type="password"
                    wire:model="password"
                    class="form-control form-control-lg eg-auth-input @error('password') is-invalid @enderror"
                    placeholder="{{ __('auth.password_placeholder') }}"
                    autocomplete="new-password"
                >
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn eg-btn-primary w-100 eg-auth-submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="register">{{ __('auth.register_button') }}</span>
                <span wire:loading wire:target="register">{{ __('auth.sending_code') }}</span>
            </button>
        </form>

        <p class="eg-auth-footer-text">
            {{ __('auth.already_have_account') }}
            <a href="{{ route('login') }}" class="eg-auth-link">{{ __('auth.login_link') }}</a>
        </p>
    </div>
</section>
