<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\PersonalAccessToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Personal API tokens for third-party access to the public REST API.
 */
class ApiTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()
            ->where('tokenable_type', $request->user()->getMorphClass())
            ->latest()
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $tokens]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['array'],
            'abilities.*' => ['string', 'in:read,write'],
        ]);

        $expiry = (int) config('integrations.api.token_expiry');

        $token = $request->user()->createToken(
            $validated['name'],
            $validated['abilities'] ?? ['read'],
            $expiry > 0 ? now()->addDays($expiry) : null,
        );

        return response()->json([
            'plain_text_token' => $token->plainTextToken,
            'token' => [
                'id' => $token->accessToken->id,
                'name' => $token->accessToken->name,
                'abilities' => $token->accessToken->abilities,
            ],
        ], 201);
    }

    public function destroy(Request $request, PersonalAccessToken $token): JsonResponse
    {
        abort_unless($token->tokenable_id === $request->user()->id, 403);

        $token->delete();

        return response()->json(['message' => __('Token revoked.')]);
    }
}
