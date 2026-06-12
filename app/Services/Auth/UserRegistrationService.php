<?php

namespace App\Services\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Support\Str;

class UserRegistrationService
{
    public function register(string $email, string $password): User
    {
        $user = User::query()->create([
            'name' => Str::before($email, '@'),
            'email' => $email,
            'password' => $password,
        ]);

        $user->assignRole(RoleName::Member->value);

        return $user;
    }
}
