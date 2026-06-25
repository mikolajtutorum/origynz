<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TreePermission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RelationshipResource;
use App\Models\FamilyTree;
use App\Models\PersonRelationship;
use App\Services\RelativeService;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RelationshipController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
        private readonly RelativeService $relatives,
    ) {}

    /**
     * Link two existing people in a tree.
     */
    public function store(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $personIds = $tree->people()->pluck('id')->all();

        $data = $request->validate([
            'person_id' => ['required', Rule::in($personIds)],
            'related_person_id' => ['required', Rule::in($personIds), 'different:person_id'],
            'type' => ['required', 'in:parent,spouse,child'],
            'subtype' => ['nullable', 'string', 'max:120'],
        ]);

        $data['subtype'] = $this->relatives->normalizeSubtype($data['subtype'] ?? null);

        $relationship = $tree->relationships()
            ->where($data)
            ->first() ?? $tree->relationships()->create($data);

        return (new RelationshipResource($relationship))->response()->setStatusCode(201);
    }

    /**
     * Update a relationship's metadata.
     */
    public function update(Request $request, FamilyTree $tree, PersonRelationship $relationship): RelationshipResource
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);
        abort_unless($relationship->family_tree_id === $tree->id, 404);

        $data = $request->validate([
            'subtype' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'start_date_text' => ['nullable', 'string', 'max:120'],
            'end_date' => ['nullable', 'date'],
            'end_date_text' => ['nullable', 'string', 'max:120'],
            'place' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $relationship->update($data);

        return new RelationshipResource($relationship);
    }

    /**
     * Remove a relationship edge.
     */
    public function destroy(Request $request, FamilyTree $tree, PersonRelationship $relationship): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);
        abort_unless($relationship->family_tree_id === $tree->id, 404);

        $relationship->delete();

        return response()->json(['message' => 'Relationship removed.']);
    }
}
