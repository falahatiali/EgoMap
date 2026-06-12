<?php

namespace App\Services\Quiz;

use Illuminate\Http\Request;

class ApiQuizGuestTokenService
{
    public const HEADER = 'X-Guest-Token';

    public function resolveFromRequest(Request $request): ?string
    {
        $token = $request->header(self::HEADER);

        if (! is_string($token) || $token === '') {
            return null;
        }

        return $token;
    }

    public function issue(): string
    {
        return bin2hex(random_bytes(20));
    }
}
