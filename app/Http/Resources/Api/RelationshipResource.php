<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RelationshipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'type'              => $this->type,
            'subtype'           => $this->subtype,
            'person_id'         => $this->person_id,
            'related_person_id' => $this->related_person_id,
            'start_date_text'   => $this->start_date_text,
            'end_date_text'     => $this->end_date_text,
            'place'             => $this->place,
            'description'       => $this->description,
            '_links'            => [
                'person'         => route('api.v1.people.show', $this->person_id),
                'related_person' => route('api.v1.people.show', $this->related_person_id),
            ],
        ];
    }
}
