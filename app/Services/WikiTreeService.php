<?php

namespace App\Services;

use App\Models\Person;
use App\Models\UserIntegration;
use Illuminate\Support\Facades\Http;

class WikiTreeService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('integrations.wikitree.base_url');
    }

    // -------------------------------------------------------------------------
    // Authentication (session-based — token stored as access_token)
    // -------------------------------------------------------------------------

    /**
     * Log in with WikiTree credentials and return the session cookie string.
     * Caller must persist the token in UserIntegration.access_token.
     */
    public function login(string $email, string $password): array
    {
        $response = Http::asForm()->post($this->apiUrl, [
            'action'          => 'login',
            'email'           => $email,
            'password'        => $password,
            'doNotEncryptSig' => 1,
        ]);

        $data = $response->json();

        if (($data['result'] ?? '') !== 'Success') {
            throw new \RuntimeException($data['status'] ?? __('WikiTree login failed.'));
        }

        return [
            'user_id'  => $data['userid'],
            'username' => $data['username'],
            'token'    => $data['token'] ?? '',
        ];
    }

    // -------------------------------------------------------------------------
    // Profile operations
    // -------------------------------------------------------------------------

    /**
     * Fetch a WikiTree profile by WikiTree ID (e.g. "Smith-1234").
     */
    public function getProfile(UserIntegration $integration, string $wikitreeId): array
    {
        $response = Http::get($this->apiUrl, [
            'action'  => 'getPerson',
            'key'     => $wikitreeId,
            'fields'  => 'Id,Name,FirstName,MiddleName,LastNameAtBirth,LastNameCurrent,Gender,BirthDate,DeathDate,BirthLocation,DeathLocation,Bio',
            'token'   => $integration->access_token,
        ]);

        $response->throw();
        $data = $response->json();

        return $data['person'] ?? $data[0] ?? [];
    }

    /**
     * Search WikiTree for a person matching a local Person record.
     *
     * @return list<array<string, mixed>>
     */
    public function searchPerson(UserIntegration $integration, Person $person): array
    {
        $response = Http::get($this->apiUrl, [
            'action'     => 'searchPerson',
            'q'          => trim("{$person->given_name} {$person->surname}"),
            'token'      => $integration->access_token,
            'maxResults' => 5,
        ]);

        $response->throw();

        return $response->json('profiles', []);
    }

    /**
     * Import a WikiTree profile into a local Person record, filling empty fields.
     */
    public function importIntoLocal(Person $person, array $wtProfile): void
    {
        $person->fill(array_filter([
            'given_name'  => $person->given_name  ?: ($wtProfile['FirstName'] ?? null),
            'surname'     => $person->surname      ?: ($wtProfile['LastNameAtBirth'] ?? null),
            'birth_place' => $person->birth_place  ?: ($wtProfile['BirthLocation'] ?? null),
            'death_place' => $person->death_place  ?: ($wtProfile['DeathLocation'] ?? null),
            'wikitree_id' => $wtProfile['Name'] ?? null,
        ]))->save();
    }
}
