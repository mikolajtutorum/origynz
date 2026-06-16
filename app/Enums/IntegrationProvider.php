<?php

namespace App\Enums;

enum IntegrationProvider: string
{
    case FamilySearch = 'familysearch';
    case WikiTree     = 'wikitree';
    case Geni         = 'geni';

    public function label(): string
    {
        return match ($this) {
            self::FamilySearch => 'FamilySearch',
            self::WikiTree     => 'WikiTree',
            self::Geni         => 'Geni',
        };
    }

    public function logoUrl(): string
    {
        return match ($this) {
            self::FamilySearch => 'https://www.familysearch.org/favicon.ico',
            self::WikiTree     => 'https://www.wikitree.com/favicon.ico',
            self::Geni         => 'https://www.geni.com/favicon.ico',
        };
    }
}
