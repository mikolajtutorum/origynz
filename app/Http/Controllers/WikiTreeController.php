<?php

namespace App\Http\Controllers;

use App\Enums\IntegrationProvider;
use App\Models\Person;
use App\Models\UserIntegration;
use App\Services\WikiTreeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WikiTreeController extends Controller
{
    public function __construct(
        private readonly WikiTreeService $wt,
    ) {}

    public function index(Request $request): View
    {
        $integration = UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', IntegrationProvider::WikiTree)
            ->first();

        return view('integrations.wikitree', ['integration' => $integration]);
    }

    /**
     * Connect using WikiTree username + password (no OAuth — WikiTree uses session keys).
     */
    public function connect(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email'    => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        $result = $this->wt->login($validated['email'], $validated['password']);

        UserIntegration::updateOrCreate(
            ['user_id' => $request->user()->id, 'provider' => IntegrationProvider::WikiTree],
            [
                'access_token'      => $result['token'],
                'provider_user_id'  => (string) $result['user_id'],
                'provider_username' => $result['username'],
            ],
        );

        return redirect()->route('integrations.wikitree')
            ->with('success', __('Connected to WikiTree as :name.', ['name' => $result['username']]));
    }

    public function disconnect(Request $request): RedirectResponse
    {
        UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', IntegrationProvider::WikiTree)
            ->delete();

        return redirect()->route('integrations.wikitree')
            ->with('success', __('Disconnected from WikiTree.'));
    }

    public function importPerson(Request $request, Person $person): RedirectResponse
    {
        $integration = UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', IntegrationProvider::WikiTree)
            ->firstOrFail();

        $validated = $request->validate(['wikitree_id' => 'required|string|max:80']);
        $profile   = $this->wt->getProfile($integration, $validated['wikitree_id']);

        $this->wt->importIntoLocal($person, $profile);

        return back()->with('success', __('WikiTree profile imported.'));
    }
}
