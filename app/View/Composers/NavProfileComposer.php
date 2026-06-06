<?php

namespace App\View\Composers;

use App\Support\NavProfilePresenter;
use Illuminate\View\View;

class NavProfileComposer
{
    public function __construct(
        private readonly NavProfilePresenter $navProfile,
    ) {}

    public function compose(View $view): void
    {
        $view->with('navProfile', $this->navProfile->forUser(auth()->user()));
    }
}
