<?php

namespace App\Enums;

enum SiteRole: string
{
    case SuperAdmin = 'super admin';
    case Admin = 'admin';
    case Member = 'member';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $role): string => $role->value,
            self::cases(),
        );
    }
}
