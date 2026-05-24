<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class AccessGate extends Component
{
    /**
     * @param  list<string>|string|null  $anyPermission
     * @param  list<string>|string|null  $anyRole
     */
    public function __construct(
        public ?string $permission = null,
        public ?string $role = null,
        public array|string|null $anyPermission = null,
        public array|string|null $anyRole = null,
        public bool $guest = false,
    ) {}

    public function allowed(): bool
    {
        if ($this->guest && ! Auth::check()) {
            return true;
        }

        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($this->permission !== null) {
            return $user->can($this->permission);
        }

        if ($this->role !== null) {
            return $user->hasRole($this->role);
        }

        if ($this->anyPermission !== null) {
            $permissions = is_array($this->anyPermission) ? $this->anyPermission : [$this->anyPermission];

            return $user->hasAnyPermission($permissions);
        }

        if ($this->anyRole !== null) {
            $roles = is_array($this->anyRole) ? $this->anyRole : [$this->anyRole];

            return $user->hasAnyRole($roles);
        }

        return true;
    }

    public function render(): View|Closure|string
    {
        return view('components.access-gate');
    }
}
