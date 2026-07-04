<?php

namespace App\Services;

use App\Models\Person;
use App\Models\UserIntegration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GeniService
{
    private string $baseUrl;

    private string $apiUrl;

    public function __construct()
    {
        $this->baseUrl = config('integrations.geni.base_url');
        $this->apiUrl = config('integrations.geni.api_url');
    }

    // -------------------------------------------------------------------------
    // OAuth
    // -------------------------------------------------------------------------

    public function authorizationUrl(string $state): string
    {
        return config('integrations.geni.auth_url').'?'.http_build_query([
            'client_id' => config('integrations.geni.client_id'),
            'redirect_uri' => config('services.geni.redirect'),
            'response_type' => 'code',
            'display' => 'page',
            'state' => $state,
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post(config('integrations.geni.token_url'), [
            'client_id' => config('integrations.geni.client_id'),
            'client_secret' => config('integrations.geni.client_secret'),
            'redirect_uri' => config('services.geni.redirect'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]);

        $response->throw();

        return $response->json();
    }

    public function refreshToken(UserIntegration $integration): UserIntegration
    {
        $response = Http::asForm()->post(config('integrations.geni.token_url'), [
            'client_id' => config('integrations.geni.client_id'),
            'client_secret' => config('integrations.geni.client_secret'),
            'refresh_token' => $integration->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        $response->throw();
        $data = $response->json();

        $integration->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $integration->refresh_token,
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $integration->fresh();
    }

    // -------------------------------------------------------------------------
    // Profile operations
    // -------------------------------------------------------------------------

    public function getCurrentUser(UserIntegration $integration): array
    {
        return $this->get($integration, '/profile')->json();
    }

    /**
     * Fetch a Geni profile by Geni ID (e.g. "profile-123456").
     */
    public function getProfile(UserIntegration $integration, string $geniId): array
    {
        return $this->get($integration, '/'.$geniId)->json();
    }

    /**
     * Search Geni for people matching a local Person.
     *
     * @return list<array<string, mixed>>
     */
    public function searchPerson(UserIntegration $integration, Person $person): array
    {
        $response = $this->get($integration, '/search/people', [
            'names' => $person->given_name.' '.$person->surname,
            'count' => 5,
        ]);

        return $response->json('results', []);
    }

    /**
     * Import a Geni profile's data into a local Person (filling empty fields only).
     */
    public function importIntoLocal(Person $person, array $geniProfile): void
    {
        $person->fill(array_filter([
            'given_name' => $person->given_name ?: ($geniProfile['first_name'] ?? null),
            'surname' => $person->surname ?: ($geniProfile['last_name'] ?? null),
            'birth_place' => $person->birth_place ?: ($geniProfile['birth']['location']['city'] ?? null),
            'death_place' => $person->death_place ?: ($geniProfile['death']['location']['city'] ?? null),
            'geni_profile_id' => $geniProfile['id'] ?? null,
        ]))->save();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function get(UserIntegration $integration, string $path, array $query = []): Response
    {
        if ($integration->token_is_expired) {
            $integration = $this->refreshToken($integration);
        }

        return Http::withToken($integration->access_token)
            ->get($this->apiUrl.$path, $query)
            ->throw();
    }
}
