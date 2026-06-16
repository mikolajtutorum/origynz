<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationProvider;
use App\Models\Person;
use App\Models\UserIntegration;
use App\Services\GeniService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GeniController extends Controller
{
    public function __construct(
        private readonly GeniService $geni,
    ) {}

    public function index(Request $request): View
    {
        $integration = UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', IntegrationProvider::Geni)
            ->first();

        return view('integrations.geni', ['integration' => $integration]);
    }

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(40);
        session(['geni_oauth_state' => $state]);

        return redirect($this->geni->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->get('state') !== session('geni_oauth_state')) {
            abort(403, __('OAuth state mismatch.'));
        }

        $tokens = $this->geni->exchangeCode($request->get('code'));

        $integration = UserIntegration::updateOrCreate(
            ['user_id' => $request->user()->id, 'provider' => IntegrationProvider::Geni],
            [
                'access_token'     => $tokens['access_token'],
                'refresh_token'    => $tokens['refresh_token'] ?? null,
                'token_expires_at' => isset($tokens['expires_in'])
                    ? now()->addSeconds($tokens['expires_in'])
                    : null,
            ],
        );

        try {
            $me = $this->geni->getCurrentUser($integration);
            $integration->update([
                'provider_user_id'  => $me['id'] ?? null,
                'provider_username' => trim(($me['first_name'] ?? '').' '.($me['last_name'] ?? '')) ?: null,
            ]);
        } catch (\Throwable) {}

        return redirect()->route('integrations.geni')
            ->with('success', __('Connected to Geni successfully.'));
    }

    public function disconnect(Request $request): RedirectResponse
    {
        UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', IntegrationProvider::Geni)
            ->delete();

        return redirect()->route('integrations.geni')
            ->with('success', __('Disconnected from Geni.'));
    }

    public function importPerson(Request $request, Person $person): RedirectResponse
    {
        $integration = UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', IntegrationProvider::Geni)
            ->firstOrFail();

        $validated = $request->validate(['geni_profile_id' => 'required|string|max:60']);
        $profile   = $this->geni->getProfile($integration, $validated['geni_profile_id']);

        $this->geni->importIntoLocal($person, $profile);

        return back()->with('success', __('Geni profile imported.'));
    }
}
