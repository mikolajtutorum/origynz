<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PersonResource;
use App\Http\Resources\Api\TreeResource;
use App\Models\FamilyTree;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TreeController extends Controller
{
    /**
     * List trees accessible to the authenticated token holder.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $trees = FamilyTree::visibleTo($request->user())
            ->withCount('people')
            ->paginate(20);

        return TreeResource::collection($trees);
    }

    /**
     * Get a single tree.
     */
    public function show(Request $request, FamilyTree $tree): TreeResource
    {
        abort_unless(
            FamilyTree::visibleTo($request->user())->where('id', $tree->id)->exists(),
            403,
        );

        $tree->loadCount('people');

        return new TreeResource($tree);
    }

    /**
     * List all people in a tree.
     */
    public function people(Request $request, FamilyTree $tree): AnonymousResourceCollection
    {
        abort_unless(
            FamilyTree::visibleTo($request->user())->where('id', $tree->id)->exists(),
            403,
        );

        $people = $tree->people()
            ->whereNull('merged_into_id')
            ->paginate(100);

        return PersonResource::collection($people);
    }
}
