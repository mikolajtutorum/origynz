<?php

namespace App\Support\Gedcom;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class GedcomDate
{
    public static function toStorage(?string $value): array
    {
        $value = trim((string) $value);

        if ($value === '') {
            return ['date' => null, 'text' => null];
        }

        $normalized = strtoupper(preg_replace('/\s+/', ' ', $value) ?? $value);

        foreach (['j M Y', 'M Y', 'Y'] as $format) {
            $parsed = self::parseExact($normalized, $format);

            if ($parsed) {
                return [
                    'date' => $parsed->toDateString(),
                    'text' => $normalized,
                ];
            }
        }

        return ['date' => null, 'text' => $normalized];
    }

    public static function forExport(?CarbonInterface $date, ?string $text): ?string
    {
        if ($text) {
            return strtoupper(trim($text));
        }

        return $date?->format('d M Y');
    }

    private static function parseExact(string $value, string $format): ?CarbonInterface
    {
        try {
            $parsed = Carbon::createFromFormat($format, $value);

            if ($format === 'M Y') {
                $parsed = $parsed->startOfMonth();
            }

            if ($format === 'Y') {
                $parsed = $parsed->startOfYear();
            }

            return $parsed;
        } catch (\Throwable) {
            return null;
        }
    }
}
