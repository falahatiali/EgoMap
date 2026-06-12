<?php

namespace App\Services\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public const CODE_LENGTH = 4;

    public const EXPIRY_MINUTES = 2;

    public function generateAndSend(User $user): void
    {
        $code = $this->generateCode();

        $user->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ])->save();

        Mail::to($user)->queue(new EmailVerificationCodeMail($user, $code));
    }

    public function verify(User $user, string $code): void
    {
        $this->ensureCanAttempt($user, 'verify');

        if ($this->isExpired($user)) {
            throw ValidationException::withMessages([
                'code' => [__('auth.code_expired')],
            ]);
        }

        if ($user->email_verification_code === null || ! Hash::check($code, $user->email_verification_code)) {
            RateLimiter::hit($this->throttleKey($user, 'verify'), 300);

            throw ValidationException::withMessages([
                'code' => [__('auth.code_invalid')],
            ]);
        }

        RateLimiter::clear($this->throttleKey($user, 'verify'));

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
        ])->save();
    }

    public function resend(User $user): void
    {
        $this->ensureCanAttempt($user, 'resend');

        $this->generateAndSend($user);

        RateLimiter::hit($this->throttleKey($user, 'resend'), 60);
    }

    public function remainingSeconds(User $user): int
    {
        if ($user->email_verification_expires_at === null) {
            return 0;
        }

        return max(0, $user->email_verification_expires_at->getTimestamp() - now()->getTimestamp());
    }

    public function isExpired(User $user): bool
    {
        return $this->remainingSeconds($user) === 0;
    }

    public function expiresAtTimestamp(User $user): ?int
    {
        return $user->email_verification_expires_at?->getTimestamp();
    }

    public function hasPendingCode(User $user): bool
    {
        return $user->email_verification_code !== null
            && $user->email_verification_expires_at !== null
            && ! $this->isExpired($user);
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 9999), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function ensureCanAttempt(User $user, string $action): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($user, $action), $action === 'resend' ? 3 : 10)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($user, $action));

            throw ValidationException::withMessages([
                'code' => [__('auth.too_many_attempts', ['seconds' => $seconds])],
            ]);
        }
    }

    private function throttleKey(User $user, string $action): string
    {
        return "email-verification:{$action}:{$user->id}";
    }
}
