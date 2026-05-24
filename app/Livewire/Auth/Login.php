<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(EmailVerificationService $verificationService): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        $user = User::query()->where('email', $this->email)->first();

        if ($user === null || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        if ($user->email_verified_at === null) {
            if (! $verificationService->hasPendingCode($user)) {
                $verificationService->generateAndSend($user);
            }

            session(['pending_verification_user_id' => $user->id]);
            session()->flash('auth_notice', __('auth.verify_to_continue'));

            $this->redirectRoute('verification.notice', navigate: true);

            return;
        }

        Auth::login($user, $this->remember);

        session()->regenerate();

        $this->redirectIntended(default: route('profile'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => [__('auth.too_many_attempts', ['seconds' => $seconds])],
        ]);
    }

    private function throttleKey(): string
    {
        return 'login:'.strtolower($this->email).'|'.request()->ip();
    }
}
