<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'given_name' => $this->given_name,
            'middle_name' => $this->middle_name,
            'surname' => $this->surname,
            'birth_surname' => $this->birth_surname,
            'nickname' => $this->nickname,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'sex' => $this->sex,
            'display_name' => $this->display_name,
            'life_span' => $this->life_span,
            'is_living' => $this->is_living,
            'birth_date' => $this->birth_date?->toDateString(),
            'birth_date_text' => $this->birth_date_text,
            'readable_birth_date' => $this->readable_birth_date,
            'birth_place' => $this->birth_place,
            'death_date' => $this->death_date?->toDateString(),
            'death_date_text' => $this->death_date_text,
            'readable_death_date' => $this->readable_death_date,
            'death_place' => $this->death_place,
            'cause_of_death' => $this->cause_of_death,
            'burial_place' => $this->burial_place,
            'headline' => $this->when(! $this->is_living, $this->headline),
            'notes' => $this->when(! $this->is_living, $this->notes),
            'trust_score' => $this->trust_score,
            'family_tree_id' => $this->family_tree_id,
            // Set transiently by the tree graph endpoint; null elsewhere.
            'avatar_url' => $this->avatar_url,
            'external_links' => $this->when(! $this->is_living, fn () => array_filter([
                'findagrave' => $this->findagrave_memorial_id
                    ? "https://www.findagrave.com/memorial/{$this->findagrave_memorial_id}"
                    : null,
                'billiongraves' => $this->billiongraves_id
                    ? "https://billiongraves.com/grave/{$this->billiongraves_id}"
                    : null,
                'familysearch' => $this->familysearch_person_id
                    ? "https://www.familysearch.org/tree/person/details/{$this->familysearch_person_id}"
                    : null,
                'wikitree' => $this->wikitree_id
                    ? "https://www.wikitree.com/wiki/{$this->wikitree_id}"
                    : null,
                'geni' => $this->geni_profile_id
                    ? "https://www.geni.com/people/{$this->geni_profile_id}"
                    : null,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
