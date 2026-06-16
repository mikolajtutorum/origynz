<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\ProfileWatch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileWatchController extends Controller
{
    /**
     * Show the current user's watch list.
     */
    public function index(Request $request): View
    {
        $watches = ProfileWatch::query()
            ->where('user_id', $request->user()->id)
            ->with(['person.familyTree:id,name'])
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('people.watch-list', ['watches' => $watches]);
    }

    /**
     * Toggle watch status for a person. Returns JSON with the new state.
     */
    public function toggle(Request $request, Person $person): JsonResponse
    {
        abort_unless(
            $person->familyTree?->global_tree_enabled && ! $person->exclude_from_global_tree,
            403,
        );

        $user = $request->user();

        $existing = ProfileWatch::where('user_id', $user->id)
            ->where('person_id', $person->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $watching = false;
        } else {
            ProfileWatch::create([
                'user_id'   => $user->id,
                'person_id' => $person->id,
            ]);
            $watching = true;
        }

        return response()->json(['watching' => $watching]);
    }
}
