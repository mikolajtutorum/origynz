<?php

namespace App\Services;

use App\Models\Person;

class TrustScoreService
{
    /**
     * Compute a 0-100 trust score for a person based on data completeness and sourcing.
     * Saves the result to person.trust_score.
     */
    public function recalculate(Person $person): int
    {
        $score = 0;

        // Identity (25 pts)
        if ($person->given_name && $person->surname) {
            $score += 10;
        }
        if ($person->birth_date || $person->birth_date_text) {
            $score += 15;
        }

        // Location (15 pts)
        if ($person->birth_place) {
            $score += 10;
        }
        if ($person->death_place) {
            $score += 5;
        }

        // Death data — only counted for non-living people (15 pts)
        if (! $person->is_living) {
            if ($person->death_date || $person->death_date_text) {
                $score += 15;
            }
        } else {
            // Living person cannot lose score for missing death date — award it
            $score += 15;
        }

        // Sources (35 pts)
        $citationCount = $person->sourceCitations()->count();
        if ($citationCount >= 1) {
            $score += 25;
        }
        if ($citationCount >= 3) {
            $score += 10;
        }

        // Narrative / events (10 pts)
        if ($person->events()->exists()) {
            $score += 5;
        }
        if ($person->notes || $person->headline) {
            $score += 5;
        }

        $score = min(100, $score);

        $person->withoutEvents(function () use ($person, $score): void {
            $person->trust_score = $score;
            $person->saveQuietly();
        });

        return $score;
    }

    /**
     * Return a human-readable label for a trust score.
     */
    public function label(int $score): string
    {
        return match (true) {
            $score >= 80 => 'High',
            $score >= 50 => 'Medium',
            $score >= 20 => 'Low',
            default      => 'Minimal',
        };
    }

    /**
     * Return a Tailwind colour class for the score badge.
     */
    public function colourClass(int $score): string
    {
        return match (true) {
            $score >= 80 => 'bg-green-100 text-green-800',
            $score >= 50 => 'bg-yellow-100 text-yellow-800',
            $score >= 20 => 'bg-orange-100 text-orange-800',
            default      => 'bg-red-100 text-red-800',
        };
    }
}
