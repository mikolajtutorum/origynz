<?php

namespace App\Enums;

enum TreePermission: string
{
    case Owner = 'tree.owner';
    case Manage = 'tree.manage';
    case Observe = 'tree.observe';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            self::cases(),
        );
    }

    /**
     * @return list<string>
     */
    public static function visibilityValues(): array
    {
        return [
            self::Owner->value,
            self::Manage->value,
            self::Observe->value,
        ];
    }
}
