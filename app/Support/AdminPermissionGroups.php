<?php

namespace App\Support;

use App\Enums\Permission;

final class AdminPermissionGroups
{
    /**
     * @return array<string, list<array{name: string, label: string}>>
     */
    public static function all(): array
    {
        /** @var array<string, list<array{name: string, label: string}>> $groups */
        $groups = [];

        foreach (Permission::cases() as $permission) {
            $segment = explode('.', $permission->value)[0];

            $groups[$segment][] = [
                'name' => $permission->value,
                'label' => self::label($permission->value),
            ];
        }

        ksort($groups);

        return $groups;
    }

    public static function label(string $permission): string
    {
        return str($permission)
            ->replace('.', ' › ')
            ->replace('-', ' ')
            ->title()
            ->toString();
    }
}
