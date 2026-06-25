<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\ProfileDiscussion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileDiscussionController extends Controller
{
    public function index(Request $request, Person $person): JsonResponse
    {
        $this->ensureGlobal($person);

        $userId = $request->user()->id;
        $isCurator = $request->user()->hasAnyRole(['super admin', 'admin', 'curator']);

        $discussions = ProfileDiscussion::query()
            ->where('person_id', $person->id)
            ->where('is_deleted', false)
            ->with('user:id,name')
            ->oldest()
            ->get()
            ->map(fn (ProfileDiscussion $d) => [
                'id' => $d->id,
                'body' => $d->body,
                'parent_id' => $d->parent_id,
                'author' => $d->user?->name,
                'created_at' => $d->created_at?->toIso8601String(),
                'can_delete' => $isCurator || $d->user_id === $userId,
            ]);

        return response()->json(['data' => $discussions]);
    }

    public function store(Request $request, Person $person): JsonResponse
    {
        $this->ensureGlobal($person);

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|string|exists:profile_discussions,id',
        ]);

        $discussion = ProfileDiscussion::create([
            'person_id' => $person->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
        ]);

        return response()->json(['id' => $discussion->id], 201);
    }

    public function destroy(Request $request, ProfileDiscussion $discussion): JsonResponse
    {
        $isAuthor = $discussion->user_id === $request->user()->id;
        $isCurator = $request->user()->hasAnyRole(['super admin', 'admin', 'curator']);
        abort_unless($isAuthor || $isCurator, 403);

        $discussion->update(['is_deleted' => true]);

        return response()->json(['message' => 'Comment removed.']);
    }

    private function ensureGlobal(Person $person): void
    {
        abort_unless(
            $person->familyTree?->global_tree_enabled && ! $person->exclude_from_global_tree,
            403,
        );
    }
}
