<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Services\RelationshipCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalTreeController extends Controller
{
    public function __construct(private readonly RelationshipCalculatorService $calculator) {}

    /**
     * Compute the relationship path between two people in the Global Tree.
     */
    public function relationshipPath(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'person_a_id' => 'required|string|exists:people,id',
            'person_b_id' => 'required|string|exists:people,id',
        ]);

        $personA = Person::findOrFail($validated['person_a_id']);
        $personB = Person::findOrFail($validated['person_b_id']);

        foreach ([$personA, $personB] as $p) {
            abort_unless(
                $p->familyTree?->global_tree_enabled && ! $p->exclude_from_global_tree,
                403,
                __('Person is not part of the Global Tree.'),
            );
        }

        $path = $this->calculator->findPath($personA, $personB);

        if ($path === null) {
            return response()->json(['connected' => false, 'path' => []]);
        }

        return response()->json([
            'connected' => true,
            'path' => array_map(fn (array $step) => [
                'id' => $step['person']->id,
                'name' => $step['person']->display_name,
                'via' => $step['via'],
            ], $path),
        ]);
    }
}
