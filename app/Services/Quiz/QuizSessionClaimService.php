<?php

namespace App\Services\Quiz;

use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

class QuizSessionClaimService
{
    public function rememberGuestSession(QuizSession $session): void
    {
        if (auth()->check() || $session->user_id !== null) {
            return;
        }

        $uuids = session('guest_quiz_uuids', []);

        if (! in_array($session->uuid, $uuids, true)) {
            $uuids[] = $session->uuid;
        }

        session(['guest_quiz_uuids' => $uuids]);
    }

    public function claimSession(QuizSession $session, User $user): bool
    {
        if ($session->user_id !== null) {
            return $session->user_id === $user->id;
        }

        $session->update(['user_id' => $user->id]);

        return true;
    }

    public function claimForUser(User $user, ?string $guestToken = null): int
    {
        $guestToken ??= request()->cookie('egomap_guest');
        $uuids = session('guest_quiz_uuids', []);

        $claimed = QuizSession::query()
            ->whereNull('user_id')
            ->where(function ($query) use ($user, $guestToken, $uuids): void {
                $query->where('email', $user->email);

                if ($guestToken !== null) {
                    $query->orWhere('guest_token', $guestToken);
                }

                if ($uuids !== []) {
                    $query->orWhereIn('uuid', $uuids);
                }
            })
            ->update(['user_id' => $user->id]);

        session()->forget('guest_quiz_uuids');

        return $claimed;
    }

    public function ensureGuestToken(): ?string
    {
        if (auth()->check()) {
            return null;
        }

        $token = request()->cookie('egomap_guest');

        if ($token !== null) {
            return $token;
        }

        $token = bin2hex(random_bytes(20));

        Cookie::queue(
            Cookie::make('egomap_guest', $token, 60 * 24 * 365, '/', null, false, true, false, 'lax')
        );

        return $token;
    }
}
