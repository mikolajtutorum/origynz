<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PhotoRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Models\PhotoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhotoRequestController extends Controller
{
    public function store(Request $request, Person $person): JsonResponse
    {
        $validated = $request->validate(['notes' => 'nullable|string|max:500']);

        $pending = PhotoRequest::where('person_id', $person->id)
            ->where('requested_by', $request->user()->id)
            ->where('status', PhotoRequestStatus::Pending->value)
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages(['person' => [__('You already have a pending photo request for this profile.')]]);
        }

        $photoRequest = PhotoRequest::create([
            'person_id' => $person->id,
            'requested_by' => $request->user()->id,
            'findagrave_memorial_id' => $person->findagrave_memorial_id,
            'status' => PhotoRequestStatus::Pending,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['id' => $photoRequest->id, 'status' => $photoRequest->status], 201);
    }

    public function update(Request $request, PhotoRequest $photoRequest): JsonResponse
    {
        abort_unless($photoRequest->requested_by === $request->user()->id, 403);

        $validated = $request->validate(['status' => 'required|in:fulfilled,closed']);
        $photoRequest->update(['status' => PhotoRequestStatus::from($validated['status'])]);

        return response()->json(['status' => $photoRequest->status]);
    }
}
