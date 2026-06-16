<?php

use App\Enums\Permission;
use App\Livewire\Admin\Community\Posts\Index as CommunityPostsIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Gamification\Analytics\Dashboard as GamificationAnalyticsDashboard;
use App\Livewire\Admin\Gamification\Badges\Edit as GamificationBadgesEdit;
use App\Livewire\Admin\Gamification\Badges\Index as GamificationBadgesIndex;
use App\Livewire\Admin\Gamification\Catalog\Index as GamificationCatalogIndex;
use App\Livewire\Admin\Gamification\Perks\Edit as GamificationPerksEdit;
use App\Livewire\Admin\Gamification\Perks\Index as GamificationPerksIndex;
use App\Livewire\Admin\Gamification\Punishments\Edit as GamificationPunishmentsEdit;
use App\Livewire\Admin\Gamification\Punishments\Index as GamificationPunishmentsIndex;
use App\Livewire\Admin\Gamification\Rules\Edit;
use App\Livewire\Admin\Gamification\Rules\Index;
use App\Livewire\Admin\Gamification\Shop\Edit as GamificationShopEdit;
use App\Livewire\Admin\Gamification\Shop\Index as GamificationShopIndex;
use App\Livewire\Admin\Gamification\Simulator\Index as GamificationSimulatorIndex;
use App\Livewire\Admin\Gamification\Transactions\Index as GamificationTransactionsIndex;
use App\Livewire\Admin\Gamification\Wallets\Index as GamificationWalletsIndex;
use App\Livewire\Admin\MissionEngine\Templates\Create as MissionTemplatesCreate;
use App\Livewire\Admin\MissionEngine\Templates\Edit as MissionTemplatesEdit;
use App\Livewire\Admin\MissionEngine\Templates\Index as MissionTemplatesIndex;
use App\Livewire\Admin\Permissions\Index as PermissionsIndex;
use App\Livewire\Admin\Quizzes\Edit as QuizzesEdit;
use App\Livewire\Admin\Quizzes\Index as QuizzesIndex;
use App\Livewire\Admin\Roles\Edit as RolesEdit;
use App\Livewire\Admin\Roles\Index as RolesIndex;
use App\Livewire\Admin\Sessions\Index as SessionsIndex;
use App\Livewire\Admin\Sessions\Show as SessionsShow;
use App\Livewire\Admin\Subscriptions\Index as SubscriptionsIndex;
use App\Livewire\Admin\Users\Edit as UsersEdit;
use App\Livewire\Admin\Users\Index as UsersIndex;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Dashboard::class)->name('dashboard');

Route::middleware('permission:'.Permission::AdminUsersManage->value)->group(function (): void {
    Route::livewire('/users', UsersIndex::class)->name('users.index');
    Route::livewire('/users/{user}', UsersEdit::class)->name('users.edit');
    Route::livewire('/subscriptions', SubscriptionsIndex::class)->name('subscriptions.index');
});

Route::middleware('permission:'.Permission::AdminQuizzesManage->value)->group(function (): void {
    Route::livewire('/quizzes', QuizzesIndex::class)->name('quizzes.index');
    Route::livewire('/quizzes/{quiz}', QuizzesEdit::class)->name('quizzes.edit');
});

Route::middleware('permission:'.Permission::AdminMissionsManage->value)
    ->prefix('mission-engine')
    ->name('mission-engine.')
    ->group(function (): void {
        Route::livewire('/templates', MissionTemplatesIndex::class)->name('templates.index');
        Route::livewire('/templates/create', MissionTemplatesCreate::class)->name('templates.create');
        Route::livewire('/templates/{template}', MissionTemplatesEdit::class)->name('templates.edit');
    });

Route::middleware('permission:'.Permission::AdminGamificationManage->value)
    ->prefix('gamification')
    ->name('gamification.')
    ->group(function (): void {
        Route::livewire('/catalog', GamificationCatalogIndex::class)->name('catalog');
        Route::livewire('/rules', Index::class)->name('rules.index');
        Route::livewire('/rules/create', Edit::class)->name('rules.create');
        Route::livewire('/rules/{rule}', Edit::class)->name('rules.edit');
        Route::livewire('/badges', GamificationBadgesIndex::class)->name('badges.index');
        Route::livewire('/badges/create', GamificationBadgesEdit::class)->name('badges.create');
        Route::livewire('/badges/{badge}', GamificationBadgesEdit::class)->name('badges.edit');
        Route::livewire('/perks', GamificationPerksIndex::class)->name('perks.index');
        Route::livewire('/perks/create', GamificationPerksEdit::class)->name('perks.create');
        Route::livewire('/perks/{perk}', GamificationPerksEdit::class)->name('perks.edit');
        Route::livewire('/punishments', GamificationPunishmentsIndex::class)->name('punishments.index');
        Route::livewire('/punishments/create', GamificationPunishmentsEdit::class)->name('punishments.create');
        Route::livewire('/punishments/{punishment}', GamificationPunishmentsEdit::class)->name('punishments.edit');
        Route::livewire('/shop', GamificationShopIndex::class)->name('shop.index');
        Route::livewire('/shop/create', GamificationShopEdit::class)->name('shop.create');
        Route::livewire('/shop/{item}', GamificationShopEdit::class)->name('shop.edit');
        Route::livewire('/transactions', GamificationTransactionsIndex::class)->name('transactions.index');
        Route::livewire('/analytics', GamificationAnalyticsDashboard::class)->name('analytics');
        Route::livewire('/simulator', GamificationSimulatorIndex::class)->name('simulator');
        Route::livewire('/wallets', GamificationWalletsIndex::class)->name('wallets.index');
    });

Route::middleware('permission:'.Permission::AdminUsersManage->value)
    ->prefix('community')
    ->name('community.')
    ->group(function (): void {
        Route::livewire('/posts', CommunityPostsIndex::class)->name('posts.index');
    });

Route::livewire('/sessions', SessionsIndex::class)->name('sessions.index');
Route::livewire('/sessions/{session}', SessionsShow::class)->name('sessions.show');

Route::middleware('permission:'.Permission::AdminRolesManage->value)->group(function (): void {
    Route::livewire('/roles', RolesIndex::class)->name('roles.index');
    Route::livewire('/roles/{role}', RolesEdit::class)->name('roles.edit');
    Route::livewire('/permissions', PermissionsIndex::class)->name('permissions.index');
});
