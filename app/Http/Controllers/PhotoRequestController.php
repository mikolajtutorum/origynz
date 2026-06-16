<?php

namespace App\Http\Controllers;

use App\Enums\PhotoRequestStatus;
use App\Models\Person;
use App\Models\PhotoRequest;
use App\Services\FindAGraveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PhotoRequestController extends Controller
{
    public function __construct(
        private readonly FindAGraveService $fag,
    ) {}

    /**
     * Submit a photo request for a person's grave.
     */
    public function store(Request $request, Person $person): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $existing = PhotoRequest::where('person_id', $person->id)
            ->where('requested_by', $request->user()->id)
            ->whereIn('status', [PhotoRequestStatus::Pending->value])
            ->exists();

        if ($existing) {
            return back()->with('info', __('You already have a pending photo request for this profile.'));
        }

        PhotoRequest::create([
            'person_id'               => $person->id,
            'requested_by'            => $request->user()->id,
            'findagrave_memorial_id'  => $person->findagrave_memorial_id,
            'status'                  => PhotoRequestStatus::Pending,
            'notes'                   => $validated['notes'] ?? null,
        ]);

        return back()->with('success', __('Photo request submitted.'));
    }

    /**
     * Mark a photo request as fulfilled or closed (requester only).
     */
    public function update(Request $request, PhotoRequest $photoRequest): RedirectResponse
    {
        abort_unless($photoRequest->requested_by === $request->user()->id, 403);

        $validated = $request->validate([
            'status' => 'required|in:fulfilled,closed',
        ]);

        $photoRequest->update(['status' => PhotoRequestStatus::from($validated['status'])]);

        return back()->with('success', __('Photo request updated.'));
    }
}
