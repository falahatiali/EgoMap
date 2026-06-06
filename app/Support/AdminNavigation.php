<?php

namespace App\Support;

use App\Enums\Permission;
use App\Models\User;

final class AdminNavigation
{
    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     icon: string,
     *     route: ?string,
     *     enabled: bool,
     *     badge: ?string,
     *     permission: ?string
     * }>
     */
    public static function items(?User $user = null): array
    {
        $user ??= auth()->user();

        $items = [
            [
                'key' => 'dashboard',
                'label' => __('admin.nav.dashboard'),
                'icon' => 'fa-gauge-high',
                'route' => 'admin.dashboard',
                'enabled' => true,
                'badge' => null,
                'permission' => Permission::AdminAccess->value,
            ],
            [
                'key' => 'users',
                'label' => __('admin.nav.users'),
                'icon' => 'fa-users',
                'route' => 'admin.users.index',
                'enabled' => true,
                'badge' => null,
                'permission' => Permission::AdminUsersManage->value,
            ],
            [
                'key' => 'subscriptions',
                'label' => __('admin.nav.subscriptions'),
                'icon' => 'fa-crown',
                'route' => 'admin.subscriptions.index',
                'enabled' => true,
                'badge' => null,
                'permission' => Permission::AdminUsersManage->value,
            ],
            [
                'key' => 'quizzes',
                'label' => __('admin.nav.quizzes'),
                'icon' => 'fa-flask',
                'route' => 'admin.quizzes.index',
                'enabled' => true,
                'badge' => null,
                'permission' => Permission::AdminQuizzesManage->value,
            ],
            [
                'key' => 'mission-engine',
                'label' => __('admin.nav.mission_engine'),
                'icon' => 'fa-route',
                'route' => 'admin.mission-engine.templates.index',
                'enabled' => true,
                'badge' => null,
                'permission' => Permission::AdminMissionsManage->value,
            ],
            [
                'key' => 'gamification',
                'label' => __('admin.nav.gamification'),
                'icon' => 'fa-trophy',
                'route' => 'admin.gamification.catalog',
                'enabled' => true,
                'badge' => null,
                'permission' => Permission::AdminGamificationManage->value,
            ],
            [
                'key' => 'sessions',
                'label' => __('admin.nav.sessions'),
                'icon' => 'fa-clipboard-list',
                'route' => 'admin.sessions.index',
                'enabled' => true,
                'badge' => null,
                'permission' => Permission::AdminAccess->value,
            ],
            [
                'key' => 'roles',
                'label' => __('admin.nav.roles'),
                'icon' => 'fa-shield-halved',
                'route' => 'admin.roles.index',
                'enabled' => true,
                'badge' => null,
                'permission' => Permission::AdminRolesManage->value,
            ],
            [
                'key' => 'permissions',
                'label' => __('admin.nav.permissions'),
                'icon' => 'fa-key',
                'route' => 'admin.permissions.index',
                'enabled' => true,
                'badge' => null,
                'permission' => Permission::AdminRolesManage->value,
            ],
        ];

        return array_values(array_filter(
            $items,
            fn (array $item): bool => $user !== null
                && ($item['permission'] === null || $user->can($item['permission'])),
        ));
    }
}
