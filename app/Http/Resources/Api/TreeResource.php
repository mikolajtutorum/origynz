<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TreeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'home_region' => $this->home_region,
            'privacy' => $this->privacy,
            'global_tree_enabled' => (bool) $this->global_tree_enabled,
            'owner_person_id' => $this->owner_person_id,
            'people_count' => $this->whenCounted('people'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            '_links' => [
                'self' => route('api.v1.trees.show', $this->id),
                'people' => route('api.v1.trees.people.index', $this->id),
            ],
        ];
    }
}
