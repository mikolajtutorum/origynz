<?php

namespace App\Services;

use App\Models\Person;

class FindAGraveService
{
    private const FAG_BASE = 'https://www.findagrave.com/memorial';

    private const BG_BASE = 'https://billiongraves.com/grave';

    // -------------------------------------------------------------------------
    // URL builders
    // -------------------------------------------------------------------------

    public function memorialUrl(string $memorialId): string
    {
        return self::FAG_BASE.'/'.$memorialId;
    }

    public function photoRequestUrl(string $memorialId): string
    {
        return self::FAG_BASE.'/'.$memorialId.'/photo-request';
    }

    public function billionGravesUrl(string $bgId): string
    {
        return self::BG_BASE.'/'.$bgId;
    }

    /**
     * Build a pre-filled "Add to Find A Grave" search URL for a person.
     */
    public function searchUrl(Person $person): string
    {
        $params = array_filter([
            'firstname' => $person->given_name,
            'lastname' => $person->surname,
            'birthyear' => $person->birth_date?->format('Y'),
            'deathyear' => $person->death_date?->format('Y'),
        ]);

        return 'https://www.findagrave.com/memorial/search?'.http_build_query($params);
    }

    public function billionGravesSearchUrl(Person $person): string
    {
        return 'https://billiongraves.com/search/results?'.http_build_query(array_filter([
            'first_name' => $person->given_name,
            'last_name' => $person->surname,
        ]));
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * A Find A Grave memorial ID is a numeric string (up to ~12 digits).
     */
    public function isValidMemorialId(string $id): bool
    {
        return (bool) preg_match('/^\d{1,12}$/', trim($id));
    }

    public function isValidBgId(string $id): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9\-]{1,60}$/', trim($id));
    }

    // -------------------------------------------------------------------------
    // Instructions helper — shown in the UI when a photo is needed
    // -------------------------------------------------------------------------

    /**
     * Return the set of steps the user should follow to request a grave photo.
     *
     * @return list<string>
     */
    public function photoRequestInstructions(Person $person): array
    {
        if ($person->findagrave_memorial_id) {
            return [
                __('Visit the memorial page using the link below.'),
                __('Click "Request Photo" and submit the request to a Find A Grave volunteer.'),
                __('Return here and mark this request as fulfilled once the photo is added.'),
            ];
        }

        return [
            __('Search Find A Grave for :name using the link below.', ['name' => $person->display_name]),
            __('If a memorial exists, note the memorial ID from the URL (e.g. /memorial/12345678).'),
            __('Add the memorial ID to this profile, then submit a photo request from the memorial page.'),
            __('If no memorial exists, you can create one at findagrave.com.'),
        ];
    }
}
