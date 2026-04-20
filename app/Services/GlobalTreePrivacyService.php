<?php

namespace App\Services;

use App\Models\Person;

class GlobalTreePrivacyService
{
    /**
     * Determine whether a person must be anonymised in the Global Tree.
     *
     * Rules (in order):
     *  1. is_living = true  → living
     *  2. has a death date  → deceased, show full data
     *  3. born ≥ 100 years ago → treat as deceased
     *  4. born < 100 years ago → treat as living
     *  5. no dates at all   → safest default is living (GDPR data-minimisation)
     */
    public function isConsideredLiving(Person $person): bool
    {
        if ($person->is_living) {
            return true;
        }

        if ($person->death_date || $person->death_date_text) {
            return false;
        }

        if ($person->birth_date) {
            return $person->birth_date->year >= (now()->year - 100);
        }

        $yearFromText = $this->extractYear($person->birth_date_text);
        if ($yearFromText !== null) {
            return (int) $yearFromText >= (now()->year - 100);
        }

        return true;
    }

    /**
     * Build the display-safe data array for a person in the Global Tree.
     *
     * Living persons are replaced with an anonymous record UNLESS their tree
     * belongs to the viewer (i.e., its ID is in $ownedTreeIds), in which case
     * full details are always shown.
     *
     * @param  list<int>  $ownedTreeIds  Tree IDs that belong to the current viewer.
     * @return array<string, mixed>
     */
    public function buildDisplayData(Person $person, array $ownedTreeIds = []): array
    {
        $treeName = $person->relationLoaded('familyTree')
            ? $person->familyTree->name
            : $person->familyTree()->value('name');

        $isPrivate = $this->isConsideredLiving($person)
            && ! in_array($person->family_tree_id, $ownedTreeIds, true);

        if ($isPrivate) {
            return [
                'id'             => $person->id,
                'is_private'     => true,
                'display_name'   => __('Private Person'),
                'life_span'      => null,
                'birth_place'    => null,
                'sex'            => null,
                'family_tree'    => $treeName,
                'family_tree_id' => $person->family_tree_id,
            ];
        }

        return [
            'id'             => $person->id,
            'is_private'     => false,
            'display_name'   => $person->display_name,
            'life_span'      => $person->life_span,
            'birth_place'    => $person->birth_place,
            'sex'            => $person->sex,
            'family_tree'    => $treeName,
            'family_tree_id' => $person->family_tree_id,
        ];
    }

    private function extractYear(?string $value): ?int
    {
        if (! $value) {
            return null;
        }

        preg_match('/\b(\d{4})\b/', $value, $matches);

        return isset($matches[1]) ? (int) $matches[1] : null;
    }
}
