<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class VerifyEmail extends Component
{
    public string $code = '';

    public ?User $user = null;

    public function mount(EmailVerificationService $verificationService): void
    {
        if (Auth::check() && Auth::user()->email_verified_at !== null) {
            $this->redirectRoute('home', navigate: true);

            return;
        }

        $userId = session('pending_verification_user_id');

        if ($userId === null) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $this->user = User::query()->find($userId);

        if ($this->user === null) {
            session()->forget('pending_verification_user_id');
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if ($this->user->email_verified_at !== null) {
            session()->forget('pending_verification_user_id');
            Auth::login($this->user);
            session()->regenerate();
            $this->redirectRoute('home', navigate: true);

            return;
        }

        if (! $verificationService->hasPendingCode($this->user)) {
            $verificationService->generateAndSend($this->user->fresh());
            $this->user->refresh();
            session()->flash('auth_notice', __('auth.verification_sent'));
        }
    }

    public function verify(EmailVerificationService $verificationService): void
    {
        $this->validate([
            'code' => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
        ]);

        $user = $this->pendingUser();

        $verificationService->verify($user, $this->code);

        session()->forget('pending_verification_user_id');

        Auth::login($user);
        session()->regenerate();

        session()->flash('auth_notice', __('auth.welcome_back'));

        $this->redirectIntended(default: route('profile'), navigate: true);
    }

    public function resendCode(EmailVerificationService $verificationService): void
    {
        $user = $this->pendingUser();

        if (! $verificationService->isExpired($user)) {
            return;
        }

        $verificationService->resend($user);
        $this->user = $user->fresh();
        $this->code = '';

        session()->flash('auth_notice', __('auth.verification_sent'));
    }

    public function getRemainingSecondsProperty(EmailVerificationService $verificationService): int
    {
        if ($this->user === null) {
            return 0;
        }

        return $verificationService->remainingSeconds($this->user);
    }

    public function getExpiresAtTimestampProperty(EmailVerificationService $verificationService): ?int
    {
        if ($this->user === null) {
            return null;
        }

        return $verificationService->expiresAtTimestamp($this->user);
    }

    public function render(): View
    {
        return view('livewire.auth.verify-email');
    }

    private function pendingUser(): User
    {
        $userId = session('pending_verification_user_id');

        abort_unless($userId !== null, 403);

        $user = User::query()->findOrFail($userId);

        return $user;
    }
}
