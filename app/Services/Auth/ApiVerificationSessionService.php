<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ApiVerificationSessionService
{
    private const CACHE_PREFIX = 'api-email-verification:';

    private const TTL_MINUTES = 30;

    public function issue(User $user): string
    {
        $token = Str::random(64);

        Cache::put($this->cacheKey($token), $user->id, now()->addMinutes(self::TTL_MINUTES));

        return $token;
    }

    public function userForToken(string $token): ?User
    {
        $userId = Cache::get($this->cacheKey($token));

        if (! is_int($userId)) {
            return null;
        }

        return User::query()->find($userId);
    }

    public function forget(string $token): void
    {
        Cache::forget($this->cacheKey($token));
    }

    private function cacheKey(string $token): string
    {
        return self::CACHE_PREFIX.$token;
    }
}
