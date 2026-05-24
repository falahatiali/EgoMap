<?php

namespace App\Livewire\Auth;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class Register extends Component
{
    public string $email = '';

    public string $password = '';

    public function register(EmailVerificationService $verificationService): void
    {
        $validated = $this->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => Str::before($validated['email'], '@'),
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->assignRole(RoleName::Member->value);

        $verificationService->generateAndSend($user);

        session(['pending_verification_user_id' => $user->id]);

        session()->flash('auth_notice', __('auth.verification_sent'));

        $this->redirectRoute('verification.notice', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
