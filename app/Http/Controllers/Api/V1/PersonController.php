<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PersonResource;
use App\Http\Resources\Api\RelationshipResource;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\PersonRelationship;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonController extends Controller
{
    public function show(Request $request, Person $person): PersonResource
    {
        $this->authorizeAccess($request, $person);

        return new PersonResource($person);
    }

    /**
     * Relationships for a person — both outgoing and incoming.
     */
    public function relationships(Request $request, Person $person): AnonymousResourceCollection
    {
        $this->authorizeAccess($request, $person);

        $relationships = PersonRelationship::where('person_id', $person->id)
            ->orWhere('related_person_id', $person->id)
            ->get();

        return RelationshipResource::collection($relationships);
    }

    /**
     * Search people across all accessible trees.
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'q'       => 'required|string|min:2|max:100',
            'tree_id' => 'nullable|string|exists:family_trees,id',
        ]);

        $accessibleTreeIds = FamilyTree::visibleTo($request->user())->pluck('id');

        $people = Person::whereIn('family_tree_id', $accessibleTreeIds)
            ->whereNull('merged_into_id')
            ->where(fn ($q) => $q
                ->where('given_name', 'like', '%'.$validated['q'].'%')
                ->orWhere('surname', 'like', '%'.$validated['q'].'%'))
            ->when($validated['tree_id'] ?? null, fn ($q, $id) => $q->where('family_tree_id', $id))
            ->limit(50)
            ->get();

        return PersonResource::collection($people);
    }

    private function authorizeAccess(Request $request, Person $person): void
    {
        abort_unless(
            FamilyTree::visibleTo($request->user())->where('id', $person->family_tree_id)->exists(),
            403,
        );
    }
}
