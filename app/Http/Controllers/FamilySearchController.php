<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationProvider;
use App\Models\Person;
use App\Models\UserIntegration;
use App\Services\FamilySearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FamilySearchController extends Controller
{
    public function __construct(
        private readonly FamilySearchService $fs,
    ) {}

    public function index(Request $request): View
    {
        $integration = UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', IntegrationProvider::FamilySearch)
            ->first();

        return view('integrations.familysearch', ['integration' => $integration]);
    }

    // -------------------------------------------------------------------------
    // OAuth flow
    // -------------------------------------------------------------------------

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        session(['fs_oauth_state' => $state]);

        return redirect($this->fs->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->get('state') !== session('fs_oauth_state')) {
            abort(403, __('OAuth state mismatch.'));
        }

        $tokens = $this->fs->exchangeCode($request->get('code'));

        $integration = UserIntegration::updateOrCreate(
            ['user_id' => $request->user()->id, 'provider' => IntegrationProvider::FamilySearch],
            [
                'access_token'     => $tokens['access_token'],
                'refresh_token'    => $tokens['refresh_token'] ?? null,
                'token_expires_at' => isset($tokens['expires_in'])
                    ? now()->addSeconds($tokens['expires_in'])
                    : null,
            ],
        );

        // Fetch provider user info
        try {
            $me = $this->fs->getCurrentUser($integration);
            $user = $me['users'][0] ?? [];
            $integration->update([
                'provider_user_id' => $user['personId'] ?? null,
                'provider_username' => $user['contactName'] ?? null,
            ]);
        } catch (\Throwable) {
            // non-fatal
        }

        return redirect()->route('integrations.familysearch')
            ->with('success', __('Connected to FamilySearch successfully.'));
    }

    public function disconnect(Request $request): RedirectResponse
    {
        UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', IntegrationProvider::FamilySearch)
            ->delete();

        return redirect()->route('integrations.familysearch')
            ->with('success', __('Disconnected from FamilySearch.'));
    }

    // -------------------------------------------------------------------------
    // Person sync
    // -------------------------------------------------------------------------

    public function searchPerson(Request $request, Person $person): \Illuminate\Http\JsonResponse
    {
        $integration = $this->requireIntegration($request);

        $results = $this->fs->searchPerson($integration, $person);

        return response()->json(['results' => $results]);
    }

    public function importPerson(Request $request, Person $person): RedirectResponse
    {
        $integration = $this->requireIntegration($request);

        $validated = $request->validate(['fs_person_id' => 'required|string|max:30']);
        $fsPerson  = $this->fs->getPerson($integration, $validated['fs_person_id']);

        $this->fs->importIntoLocal($person, $fsPerson);

        return back()->with('success', __('FamilySearch data imported.'));
    }

    // -------------------------------------------------------------------------

    private function requireIntegration(Request $request): UserIntegration
    {
        $integration = UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', IntegrationProvider::FamilySearch)
            ->firstOrFail();

        abort_if($integration->token_is_expired && ! $integration->refresh_token, 403,
            __('FamilySearch token has expired. Please reconnect.'));

        return $integration;
    }
}
