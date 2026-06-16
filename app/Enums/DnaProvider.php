<?php

namespace App\Enums;

enum DnaProvider: string
{
    case TwentyThreeAndMe = '23andme';
    case AncestryDNA      = 'ancestrydna';
    case FTDNA            = 'ftdna';
    case MyHeritage       = 'myheritage';
    case LivingDNA        = 'livingdna';
    case Other            = 'other';

    public function label(): string
    {
        return match ($this) {
            self::TwentyThreeAndMe => '23andMe',
            self::AncestryDNA      => 'AncestryDNA',
            self::FTDNA            => 'FamilyTreeDNA (FTDNA)',
            self::MyHeritage       => 'MyHeritage DNA',
            self::LivingDNA        => 'Living DNA',
            self::Other            => 'Other',
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
