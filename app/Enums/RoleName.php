<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Pro = 'pro';
    case Member = 'member';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
