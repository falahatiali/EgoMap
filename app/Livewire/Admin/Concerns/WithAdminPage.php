<?php

namespace App\Livewire\Admin\Concerns;

use Illuminate\Contracts\View\View;

trait WithAdminPage
{
    /**
     * @param  array<string, mixed>  $data
     */
    protected function adminView(string $view, array $data, string $activeNav): View
    {
        return view($view, $data)->layout('layouts.admin', [
            'activeNav' => $activeNav,
        ]);
    }

    protected function adminFlash(string $message, string $type = 'success'): void
    {
        session()->flash('admin_notice', $message);
        session()->flash('admin_notice_type', $type);
    }
}
