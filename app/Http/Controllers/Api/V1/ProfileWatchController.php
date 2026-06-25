<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\ProfileWatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileWatchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $watches = ProfileWatch::query()
            ->where('user_id', $request->user()->id)
            ->with(['person.familyTree:id,name'])
            ->latest()
            ->get()
            ->map(fn (ProfileWatch $w) => [
                'id' => $w->id,
                'person_id' => $w->person_id,
                'person_name' => $w->person?->display_name,
                'tree_id' => $w->person?->family_tree_id,
                'tree_name' => $w->person?->familyTree?->name,
            ]);

        return response()->json(['data' => $watches]);
    }

    public function toggle(Request $request, Person $person): JsonResponse
    {
        abort_unless(
            $person->familyTree?->global_tree_enabled && ! $person->exclude_from_global_tree,
            403,
        );

        $existing = ProfileWatch::where('user_id', $request->user()->id)
            ->where('person_id', $person->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json(['watching' => false]);
        }

        ProfileWatch::create(['user_id' => $request->user()->id, 'person_id' => $person->id]);

        return response()->json(['watching' => true]);
    }
}
