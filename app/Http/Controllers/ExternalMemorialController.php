<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\FindAGraveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExternalMemorialController extends Controller
{
    public function __construct(
        private readonly FindAGraveService $fag,
    ) {}

    /**
     * Store or clear external memorial IDs on a person.
     */
    public function update(Request $request, Person $person): RedirectResponse
    {
        $validated = $request->validate([
            'findagrave_memorial_id' => ['nullable', 'string', 'max:20', function ($attr, $value, $fail): void {
                if ($value && ! $this->fag->isValidMemorialId($value)) {
                    $fail(__('Find A Grave memorial ID must be numeric (e.g. 12345678).'));
                }
            }],
            'billiongraves_id'   => 'nullable|string|max:60',
            'wikitree_id'        => 'nullable|string|max:80',
            'familysearch_person_id' => 'nullable|string|max:30',
            'geni_profile_id'    => 'nullable|string|max:60',
        ]);

        $person->update($validated);

        return back()->with('success', __('External links saved.'));
    }
}
