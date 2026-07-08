<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\IntegrationProvider;
use App\Http\Controllers\Controller;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\User;
use App\Models\UserIntegration;
use App\Services\FamilySearchService;
use App\Services\FindAGraveService;
use App\Services\GeniService;
use App\Services\WikiTreeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class IntegrationController extends Controller
{
    public function __construct(
        private readonly FamilySearchService $familySearch,
        private readonly GeniService $geni,
        private readonly WikiTreeService $wikiTree,
        private readonly FindAGraveService $findAGrave,
    ) {}

    /**
     * List every provider with the current user's connection status.
     */
    public function index(Request $request): JsonResponse
    {
        $connected = UserIntegration::where('user_id', $request->user()->id)
            ->get()
            ->keyBy(fn (UserIntegration $i) => $i->provider->value);

        $providers = collect(IntegrationProvider::cases())->map(function (IntegrationProvider $p) use ($connected) {
            $integration = $connected->get($p->value);

            return [
                'provider' => $p->value,
                'label' => $p->label(),
                'logo_url' => $p->logoUrl(),
                'type' => $p === IntegrationProvider::WikiTree ? 'credentials' : 'oauth',
                'configured' => $this->isConfigured($p),
                'connected' => $integration !== null,
                'username' => $integration?->provider_username,
                'connected_at' => $integration?->created_at?->toIso8601String(),
            ];
        });

        return response()->json(['data' => $providers]);
    }

    /**
     * Begin an OAuth connection: return the provider's authorization URL, with a
     * signed state that carries the user id (the SPA is bearer-based, so the web
     * callback cannot rely on a session).
     */
    public function authorize(Request $request, string $provider): JsonResponse
    {
        $p = $this->resolveProvider($provider);
        abort_unless($p === IntegrationProvider::FamilySearch || $p === IntegrationProvider::Geni, 422, __('This provider does not use OAuth.'));
        abort_unless($this->isConfigured($p), 422, __('This integration is not configured on the server.'));

        $state = Crypt::encryptString(json_encode([
            'user_id' => $request->user()->id,
            'provider' => $p->value,
            'ts' => now()->timestamp,
        ]));

        $url = $p === IntegrationProvider::FamilySearch
            ? $this->familySearch->authorizationUrl($state)
            : $this->geni->authorizationUrl($state);

        return response()->json(['url' => $url]);
    }

    /**
     * OAuth redirect target (web route, unauthenticated — identity comes from the
     * signed state). Exchanges the code, stores the integration, and bounces the
     * user back to the SPA settings page.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');
        $settingsUrl = ($frontend ?: '').'/settings';

        try {
            $p = $this->resolveProvider($provider);
            $state = json_decode(Crypt::decryptString((string) $request->query('state')), true);
            abort_unless(is_array($state) && ($state['provider'] ?? null) === $p->value, 400);

            $user = User::findOrFail($state['user_id']);
            $code = (string) $request->query('code');
            abort_if($code === '', 400);

            $tokens = $p === IntegrationProvider::FamilySearch
                ? $this->familySearch->exchangeCode($code)
                : $this->geni->exchangeCode($code);

            $this->storeTokens($user, $p, $tokens);

            return redirect()->away($settingsUrl.'?integration='.$p->value.'&status=connected');
        } catch (\Throwable $e) {
            Log::warning('Integration callback failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return redirect()->away($settingsUrl.'?integration='.$provider.'&status=error');
        }
    }

    /**
     * Connect WikiTree using the member's own API login (no OAuth app needed).
     */
    public function connectWikiTree(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $result = $this->wikiTree->login($validated['email'], $validated['password']);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['email' => [__('WikiTree login failed.')]]);
        }

        if (empty($result['token'])) {
            throw ValidationException::withMessages(['email' => [__('WikiTree login failed.')]]);
        }

        UserIntegration::updateOrCreate(
            ['user_id' => $request->user()->id, 'provider' => IntegrationProvider::WikiTree->value],
            [
                'access_token' => $result['token'],
                'provider_user_id' => $result['user_id'] ?? null,
                'provider_username' => $result['username'] ?? $validated['email'],
            ],
        );

        return response()->json(['connected' => true], 201);
    }

    public function destroy(Request $request, string $provider): JsonResponse
    {
        $p = $this->resolveProvider($provider);

        UserIntegration::where('user_id', $request->user()->id)
            ->where('provider', $p->value)
            ->delete();

        return response()->json(['disconnected' => true]);
    }

    /**
     * Off-site research links for a person (no account required).
     */
    public function researchLinks(Request $request, Person $person): JsonResponse
    {
        abort_unless(
            FamilyTree::visibleTo($request->user())->where('id', $person->family_tree_id)->exists(),
            403,
        );

        return response()->json([
            'findagrave' => [
                'search' => $this->findAGrave->searchUrl($person),
                'memorial' => $person->findagrave_memorial_id
                    ? $this->findAGrave->memorialUrl($person->findagrave_memorial_id)
                    : null,
            ],
            'billiongraves' => [
                'search' => $this->findAGrave->billionGravesSearchUrl($person),
                'grave' => $person->billiongraves_id
                    ? $this->findAGrave->billionGravesUrl($person->billiongraves_id)
                    : null,
            ],
        ]);
    }

    // -------------------------------------------------------------------------

    private function isConfigured(IntegrationProvider $p): bool
    {
        return match ($p) {
            IntegrationProvider::FamilySearch => (bool) config('integrations.familysearch.client_id'),
            IntegrationProvider::Geni => (bool) config('integrations.geni.client_id'),
            IntegrationProvider::WikiTree => true,
        };
    }

    private function resolveProvider(string $provider): IntegrationProvider
    {
        return IntegrationProvider::tryFrom($provider) ?? abort(404);
    }

    /**
     * @param  array<string, mixed>  $tokens
     */
    private function storeTokens(User $user, IntegrationProvider $p, array $tokens): void
    {
        UserIntegration::updateOrCreate(
            ['user_id' => $user->id, 'provider' => $p->value],
            [
                'access_token' => $tokens['access_token'] ?? '',
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'token_expires_at' => isset($tokens['expires_in']) ? now()->addSeconds((int) $tokens['expires_in']) : null,
            ],
        );
    }
}
