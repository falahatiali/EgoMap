<?php

use App\Enums\Permission;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Permissions\Index as PermissionsIndex;
use App\Livewire\Admin\Quizzes\Edit as QuizzesEdit;
use App\Livewire\Admin\Quizzes\Index as QuizzesIndex;
use App\Livewire\Admin\Roles\Edit as RolesEdit;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Livewire\Admin\Sessions\Index as SessionsIndex;
use App\Livewire\Admin\Sessions\Show as SessionsShow;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Admin\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');

Route::middleware('permission:'.Permission::AdminUsersManage->value)->group(function (): void {
    Route::livewire('/users', UsersIndex::class)->name('users.index');
    Route::livewire('/users/{user}', UsersEdit::class)->name('users.edit');
});

Route::middleware('permission:'.Permission::AdminQuizzesManage->value)->group(function (): void {
    Route::livewire('/quizzes', QuizzesIndex::class)->name('quizzes.index');
    Route::livewire('/quizzes/{quiz}', QuizzesEdit::class)->name('quizzes.edit');
});

Route::livewire('/sessions', SessionsIndex::class)->name('sessions.index');
Route::livewire('/sessions/{session}', SessionsShow::class)->name('sessions.show');

Route::middleware('permission:'.Permission::AdminRolesManage->value)->group(function (): void {
    Route::livewire('/roles', RolesIndex::class)->name('roles.index');
    Route::livewire('/roles/{role}', RolesEdit::class)->name('roles.edit');
    Route::livewire('/permissions', PermissionsIndex::class)->name('permissions.index');
});
