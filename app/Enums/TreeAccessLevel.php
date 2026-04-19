<?php

namespace App\Enums;

enum TreeAccessLevel: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Observer = 'observer';

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => [
                TreePermission::Owner->value,
                TreePermission::Manage->value,
                TreePermission::Observe->value,
            ],
            self::Manager => [
                TreePermission::Manage->value,
                TreePermission::Observe->value,
            ],
            self::Observer => [
                TreePermission::Observe->value,
            ],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Tree owner',
            self::Manager => 'Tree manager',
            self::Observer => 'Tree observer',
        };
    }

    public function rank(): int
    {
        return match ($this) {
            self::Owner => 3,
            self::Manager => 2,
            self::Observer => 1,
        };
    }

    public static function highest(self ...$levels): ?self
    {
        if ($levels === []) {
            return null;
        }

        usort(
            $levels,
            static fn (self $left, self $right): int => $right->rank() <=> $left->rank(),
        );

        return $levels[0];
    }
}
