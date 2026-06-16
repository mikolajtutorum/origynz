<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\ProfileDiscussion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileDiscussionController extends Controller
{
    /**
     * Post a new top-level comment or a reply.
     */
    public function store(Request $request, Person $person): RedirectResponse
    {
        abort_unless(
            $person->familyTree?->global_tree_enabled && ! $person->exclude_from_global_tree,
            403,
        );

        $validated = $request->validate([
            'body'      => 'required|string|max:2000',
            'parent_id' => 'nullable|string|exists:profile_discussions,id',
        ]);

        ProfileDiscussion::create([
            'person_id' => $person->id,
            'user_id'   => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body'      => $validated['body'],
        ]);

        return back()->with('success', __('Comment posted.'));
    }

    /**
     * Soft-delete a comment (author or curator/admin).
     */
    public function destroy(Request $request, ProfileDiscussion $discussion): RedirectResponse
    {
        $user = $request->user();
        $isAuthor = $discussion->user_id === $user->id;
        $isCurator = $user->hasAnyRole(['super admin', 'admin', 'curator']);

        abort_unless($isAuthor || $isCurator, 403);

        $discussion->update(['is_deleted' => true]);

        return back()->with('success', __('Comment removed.'));
    }
}
