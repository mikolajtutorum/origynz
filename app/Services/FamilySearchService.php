<?php

namespace App\Services;

use App\Enums\IntegrationProvider;
use App\Models\Person;
use App\Models\User;
use App\Models\UserIntegration;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FamilySearchService
{
    private string $baseUrl;
    private string $authUrl;
    private string $tokenUrl;

    public function __construct()
    {
        $this->baseUrl  = config('integrations.familysearch.base_url');
        $this->authUrl  = config('integrations.familysearch.auth_url');
        $this->tokenUrl = config('integrations.familysearch.token_url');
    }

    // -------------------------------------------------------------------------
    // OAuth
    // -------------------------------------------------------------------------

    public function authorizationUrl(string $state): string
    {
        return $this->authUrl.'?'.http_build_query([
            'client_id'     => config('integrations.familysearch.client_id'),
            'response_type' => 'code',
            'redirect_uri'  => config('services.familysearch.redirect'),
            'state'         => $state,
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => config('services.familysearch.redirect'),
            'client_id'     => config('integrations.familysearch.client_id'),
            'client_secret' => config('integrations.familysearch.client_secret'),
        ]);

        $response->throw();

        return $response->json();
    }

    public function refreshToken(UserIntegration $integration): UserIntegration
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $integration->refresh_token,
            'client_id'     => config('integrations.familysearch.client_id'),
            'client_secret' => config('integrations.familysearch.client_secret'),
        ]);

        $response->throw();

        $data = $response->json();

        $integration->update([
            'access_token'     => $data['access_token'],
            'refresh_token'    => $data['refresh_token'] ?? $integration->refresh_token,
            'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $integration->fresh();
    }

    // -------------------------------------------------------------------------
    // Current user
    // -------------------------------------------------------------------------

    public function getCurrentUser(UserIntegration $integration): array
    {
        return $this->get($integration, '/platform/users/current')->json();
    }

    // -------------------------------------------------------------------------
    // Person operations
    // -------------------------------------------------------------------------

    /**
     * Search FamilySearch for a person matching our local record.
     *
     * @return list<array<string, mixed>>
     */
    public function searchPerson(UserIntegration $integration, Person $person): array
    {
        $params = array_filter([
            'q.givenName'   => $person->given_name,
            'q.surname'     => $person->surname,
            'q.birthDate'   => $person->birth_date?->format('Y'),
            'q.birthPlace'  => $person->birth_place,
            'q.deathDate'   => $person->death_date?->format('Y'),
            'count'         => 5,
        ]);

        $response = $this->get($integration, '/platform/tree/persons', $params);

        return $response->json('persons', []);
    }

    /**
     * Fetch a person by their FamilySearch PID.
     */
    public function getPerson(UserIntegration $integration, string $pid): array
    {
        return $this->get($integration, "/platform/tree/persons/{$pid}")->json('persons.0', []);
    }

    /**
     * Import data from FamilySearch into a local Person, filling in only empty fields.
     */
    public function importIntoLocal(Person $person, array $fsPerson): void
    {
        $names      = $fsPerson['names'][0]['nameForms'][0] ?? [];
        $facts      = collect($fsPerson['facts'] ?? []);
        $birthFact  = $facts->firstWhere('type', 'http://gedcomx.org/Birth');
        $deathFact  = $facts->firstWhere('type', 'http://gedcomx.org/Death');

        $person->fill(array_filter([
            'given_name'  => $person->given_name  ?: ($names['parts'][0]['value'] ?? null),
            'surname'     => $person->surname      ?: ($names['parts'][1]['value'] ?? null),
            'birth_date'  => $person->birth_date   ?: ($birthFact['date']['normalized'][0]['value'] ?? null),
            'birth_place' => $person->birth_place  ?: ($birthFact['place']['normalized'][0]['value'] ?? null),
            'death_date'  => $person->death_date   ?: ($deathFact['date']['normalized'][0]['value'] ?? null),
            'death_place' => $person->death_place  ?: ($deathFact['place']['normalized'][0]['value'] ?? null),
            'familysearch_person_id' => $fsPerson['id'] ?? null,
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
            ->accept('application/x-gedcomx-v1+json')
            ->get($this->baseUrl.$path, $query)
            ->throw();
    }
}
