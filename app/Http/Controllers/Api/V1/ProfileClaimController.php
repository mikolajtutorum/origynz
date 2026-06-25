<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProfileClaimStatus;
use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\ProfileClaim;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileClaimController extends Controller
{
    /**
     * Claims awaiting review on profiles in trees owned by the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $claims = ProfileClaim::query()
            ->whereHas('person.familyTree', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['person.familyTree:id,name', 'user:id,name'])
            ->orderBy('status')
            ->latest()
            ->get()
            ->map(fn (ProfileClaim $c) => [
                'id' => $c->id,
                'status' => $c->status,
                'message' => $c->message,
                'person_id' => $c->person_id,
                'person_name' => $c->person?->display_name,
                'claimant' => $c->user?->name,
                'created_at' => $c->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $claims]);
    }

    public function store(Request $request, Person $person): JsonResponse
    {
        abort_unless(
            $person->familyTree?->global_tree_enabled && ! $person->exclude_from_global_tree,
            403,
        );

        if (ProfileClaim::where('user_id', $request->user()->id)->where('person_id', $person->id)->exists()) {
            throw ValidationException::withMessages(['person' => [__('You already have a claim for this profile.')]]);
        }

        $validated = $request->validate(['message' => 'nullable|string|max:1000']);

        $claim = ProfileClaim::create([
            'user_id' => $request->user()->id,
            'person_id' => $person->id,
            'status' => ProfileClaimStatus::Pending,
            'message' => $validated['message'] ?? null,
        ]);

        return response()->json(['id' => $claim->id, 'status' => $claim->status], 201);
    }

    public function review(Request $request, ProfileClaim $claim): JsonResponse
    {
        abort_unless($claim->person->familyTree?->user_id === $request->user()->id, 403);

        $validated = $request->validate(['decision' => 'required|in:approved,rejected']);

        $claim->update([
            'status' => ProfileClaimStatus::from($validated['decision']),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return response()->json(['status' => $claim->status]);
    }
}
