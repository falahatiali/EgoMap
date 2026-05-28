<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UserSessionService
{
    public function revokeOtherSessions(User $user, ?string $currentSessionId = null): int
    {
        $currentSessionId ??= session()->getId();

        $deleted = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->when(
                is_string($currentSessionId) && $currentSessionId !== '',
                fn ($query) => $query->where('id', '!=', $currentSessionId),
            )
            ->delete();

        $user->forceFill([
            'remember_token' => Str::random(60),
        ])->save();

        return $deleted;
    }

    public function activeSessionCount(User $user): int
    {
        return (int) DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->count();
    }
}
