<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function index(Request $request): View
    {
        $tokens = $request->user()
            ->tokens()
            ->where('tokenable_type', get_class($request->user()))
            ->latest()
            ->get();

        return view('settings.api-tokens', ['tokens' => $tokens]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'abilities'   => 'array',
            'abilities.*' => 'string|in:read,write',
        ]);

        $abilities = $validated['abilities'] ?? ['read'];

        $token = $request->user()->createToken(
            $validated['name'],
            $abilities,
            config('integrations.api.token_expiry') > 0
                ? now()->addDays(config('integrations.api.token_expiry'))
                : null,
        );

        return redirect()->route('settings.api-tokens')
            ->with('new_token', $token->plainTextToken)
            ->with('success', __('API token created.'));
    }

    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        abort_unless($token->tokenable_id === $request->user()->id, 403);

        $token->delete();

        return redirect()->route('settings.api-tokens')
            ->with('success', __('Token revoked.'));
    }
}
