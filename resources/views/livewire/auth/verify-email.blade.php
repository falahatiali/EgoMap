<section
    class="container eg-auth-container"
    wire:key="verify-{{ $this->expiresAtTimestamp ?? 'none' }}"
    x-data="egVerifyPage(@js($this->expiresAtTimestamp))"
>
    <div class="eg-auth-card eg-glass eg-auth-card--verify">
        <div class="eg-auth-icon eg-auth-icon--verify" aria-hidden="true">
            <i class="fa-solid fa-envelope-circle-check"></i>
        </div>

        <h1 class="eg-display eg-auth-title">{{ __('auth.verify_title') }}</h1>
        <p class="eg-auth-subtitle">
            {{ __('auth.verify_subtitle', ['email' => $user?->email ?? '']) }}
        </p>

        @if (session('auth_notice'))
            <div class="alert alert-success eg-auth-alert" role="alert">
                {{ session('auth_notice') }}
            </div>
        @endif

        <form wire:submit="verify" class="eg-auth-form" x-data="egOtpInput()">
            <div
                class="eg-otp-inputs @error('code') is-invalid @enderror"
                x-on:paste.prevent="handlePaste($event)"
            >
                @foreach (range(0, 3) as $index)
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        pattern="[0-9]"
                        class="eg-otp-digit form-control"
                        aria-label="{{ __('auth.digit', ['n' => $index + 1]) }}"
                        x-ref="digit{{ $index }}"
                        x-on:input="handleInput($event, {{ $index }})"
                        x-on:keydown.backspace="handleBackspace($event, {{ $index }})"
                        autocomplete="one-time-code"
                        @if ($index === 0) autofocus @endif
                    >
                @endforeach
            </div>

            <input type="hidden" wire:model="code">

            @error('code')
                <div class="eg-auth-error text-center">{{ $message }}</div>
            @enderror

            <div class="eg-auth-timer text-center" aria-live="polite">
                <template x-if="!expired">
                    <div>
                        <p class="eg-auth-timer-label mb-1">{{ __('auth.code_expires_in') }}</p>
                        <p class="eg-auth-timer-value eg-display" x-text="formatted"></p>
                    </div>
                </template>
                <template x-if="expired">
                    <p class="eg-auth-timer-expired">{{ __('auth.timer_expired') }}</p>
                </template>
            </div>

            <button
                type="submit"
                class="btn eg-btn-primary w-100 eg-auth-submit"
                wire:loading.attr="disabled"
                x-bind:disabled="expired"
            >
                <span wire:loading.remove wire:target="verify">{{ __('auth.verify_button') }}</span>
                <span wire:loading wire:target="verify">{{ __('auth.verifying') }}</span>
            </button>
        </form>

        <div class="text-center mt-3" x-show="expired" x-cloak>
            <button
                type="button"
                class="btn btn-link eg-auth-link-btn"
                wire:click="resendCode"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="resendCode">{{ __('auth.resend_code') }}</span>
                <span wire:loading wire:target="resendCode">{{ __('auth.sending_code') }}</span>
            </button>
        </div>

        <p class="eg-auth-footer-text">
            <a href="{{ route('login') }}" class="eg-auth-link">{{ __('auth.back_to_login') }}</a>
        </p>
    </div>
</section>

@script
<script>
    Alpine.data('egVerifyPage', (expiresAtUnix) => ({
        expiresAtUnix,
        remaining: 0,
        expired: true,
        timerId: null,

        init() {
            this.sync();
            this.timerId = setInterval(() => this.sync(), 1000);
        },

        destroy() {
            if (this.timerId) {
                clearInterval(this.timerId);
            }
        },

        sync() {
            if (!this.expiresAtUnix) {
                this.remaining = 0;
                this.expired = true;

                return;
            }

            this.remaining = Math.max(0, this.expiresAtUnix - Math.floor(Date.now() / 1000));
            this.expired = this.remaining <= 0;
        },

        get formatted() {
            const minutes = Math.floor(this.remaining / 60);
            const seconds = this.remaining % 60;

            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },
    }));

    Alpine.data('egOtpInput', () => ({
        handleInput(event, index) {
            const digit = event.target.value.replace(/\D/g, '').slice(-1);
            event.target.value = digit;

            this.$wire.set('code', this.collectDigits().join(''));

            if (digit && index < 3) {
                this.$refs[`digit${index + 1}`]?.focus();
            }
        },

        handleBackspace(event, index) {
            if (event.target.value === '' && index > 0) {
                this.$refs[`digit${index - 1}`]?.focus();
            }

            this.$wire.set('code', this.collectDigits().join(''));
        },

        handlePaste(event) {
            const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 4);

            pasted.split('').forEach((char, i) => {
                if (this.$refs[`digit${i}`]) {
                    this.$refs[`digit${i}`].value = char;
                }
            });

            this.$wire.set('code', pasted);

            if (pasted.length === 4) {
                this.$refs.digit3?.focus();
            }
        },

        collectDigits() {
            return [0, 1, 2, 3].map((i) => this.$refs[`digit${i}`]?.value?.replace(/\D/g, '') || '');
        },
    }));
</script>
@endscript
