<?php

namespace App\Http\Controllers;

use App\Enums\ProfileClaimStatus;
use App\Models\Person;
use App\Models\ProfileClaim;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileClaimController extends Controller
{
    /**
     * Show all claims for profiles in trees owned/managed by the current user.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $claims = ProfileClaim::query()
            ->whereHas('person.familyTree', fn ($q) => $q->where('user_id', $user->id))
            ->with(['person.familyTree:id,name', 'user'])
            ->orderBy('status')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('people.claims.index', ['claims' => $claims]);
    }

    /**
     * Submit a claim for a profile.
     */
    public function store(Request $request, Person $person): RedirectResponse
    {
        abort_unless(
            $person->familyTree?->global_tree_enabled && ! $person->exclude_from_global_tree,
            403,
        );

        $user = $request->user();

        $existing = ProfileClaim::where('user_id', $user->id)
            ->where('person_id', $person->id)
            ->first();

        if ($existing) {
            return back()->with('info', __('You already have a pending or reviewed claim for this profile.'));
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
        ]);

        ProfileClaim::create([
            'user_id'   => $user->id,
            'person_id' => $person->id,
            'status'    => ProfileClaimStatus::Pending,
            'message'   => $validated['message'] ?? null,
        ]);

        return back()->with('success', __('Your claim has been submitted and is pending review.'));
    }

    /**
     * Approve or reject a claim (tree owner/manager only).
     */
    public function review(Request $request, ProfileClaim $claim): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $claim->person->familyTree?->user_id === $user->id,
            403,
        );

        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
        ]);

        $claim->update([
            'status'      => ProfileClaimStatus::from($validated['decision']),
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', __('Claim :status.', ['status' => $validated['decision']]));
    }
}
