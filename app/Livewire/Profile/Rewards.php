<?php

namespace App\Livewire\Profile;

use App\Services\Profile\UserGamificationProfileService;
use App\Support\LocaleConfig;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Rewards extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::check(), 401);
    }

    public function render(UserGamificationProfileService $profileService): View
    {
        $user = Auth::user();

        return view('livewire.profile.rewards', [
            'user' => $user,
            'data' => $profileService->forUser($user),
            'locale' => LocaleConfig::fromRoute(),
        ]);
    }
}
