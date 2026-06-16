{{--
    External memorial links and photo-request partial.
    Required: $person (Person model)
    Required: $fag (FindAGraveService instance — or inject via @inject)
--}}
@inject('fag', 'App\Services\FindAGraveService')

<section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm" x-data="{ editMode: false }">
    <div class="flex items-center justify-between border-b border-[#f0f4f8] px-6 py-4">
        <h2 class="text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('External Links') }}</h2>
        <button @click="editMode = !editMode" class="text-xs text-[#9daab4] hover:text-[#2563eb]">
            <span x-show="!editMode">{{ __('Edit') }}</span>
            <span x-show="editMode">{{ __('Cancel') }}</span>
        </button>
    </div>

    {{-- Edit form --}}
    <div x-show="editMode" class="border-b border-[#f0f4f8] px-6 py-5">
        <form method="POST" action="{{ route('people.external-memorials.update', $person) }}" class="space-y-3">
            @csrf @method('PATCH')
            @foreach ([
                ['findagrave_memorial_id', 'Find A Grave Memorial ID', 'e.g. 12345678'],
                ['billiongraves_id',       'BillionGraves ID',          'e.g. 3456789'],
                ['familysearch_person_id', 'FamilySearch Person ID',    'e.g. LM1H-NVT'],
                ['wikitree_id',            'WikiTree ID',               'e.g. Smith-1234'],
                ['geni_profile_id',        'Geni Profile ID',          'e.g. profile-123456'],
            ] as [$field, $label, $placeholder])
                <div class="flex items-center gap-3">
                    <label class="w-52 shrink-0 text-xs font-medium text-[#6f7b83]">{{ __($label) }}</label>
                    <input type="text" name="{{ $field }}" value="{{ old($field, $person->{$field}) }}"
                           placeholder="{{ $placeholder }}"
                           class="flex-1 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-3 py-1.5 text-sm focus:border-[#93c5fd] focus:outline-none">
                </div>
            @endforeach
            <div class="flex justify-end">
                <button type="submit" class="rounded-[6px] bg-[#2563eb] px-4 py-1.5 text-sm font-medium text-white hover:bg-[#1d4ed8]">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Display links --}}
    <div class="divide-y divide-[#f0f4f8]">
        @php
            $links = array_filter([
                'Find A Grave'   => $person->findagrave_memorial_id ? $fag->memorialUrl($person->findagrave_memorial_id)              : null,
                'BillionGraves'  => $person->billiongraves_id       ? $fag->billionGravesUrl($person->billiongraves_id)               : null,
                'FamilySearch'   => $person->familysearch_person_id ? "https://www.familysearch.org/tree/person/details/{$person->familysearch_person_id}" : null,
                'WikiTree'       => $person->wikitree_id            ? "https://www.wikitree.com/wiki/{$person->wikitree_id}"          : null,
                'Geni'           => $person->geni_profile_id        ? "https://www.geni.com/people/{$person->geni_profile_id}"        : null,
            ]);
        @endphp

        @forelse ($links as $platform => $url)
            <div class="flex items-center justify-between px-6 py-3">
                <span class="text-sm font-medium text-[#1f252b]">{{ $platform }}</span>
                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer"
                   class="text-sm text-[#2563eb] hover:underline">{{ __('View') }} ↗</a>
            </div>
        @empty
            @if (! $person->findagrave_memorial_id)
                <div class="px-6 py-4">
                    <p class="text-sm text-[#9daab4]">{{ __('No external links. Click Edit to add IDs.') }}</p>
                    <div class="mt-2 flex gap-2">
                        <a href="{{ $fag->searchUrl($person) }}" target="_blank" rel="noopener noreferrer"
                           class="text-xs text-[#2563eb] hover:underline">{{ __('Search Find A Grave') }} ↗</a>
                        <span class="text-xs text-[#e3e8ee]">·</span>
                        <a href="{{ $fag->billionGravesSearchUrl($person) }}" target="_blank" rel="noopener noreferrer"
                           class="text-xs text-[#2563eb] hover:underline">{{ __('Search BillionGraves') }} ↗</a>
                    </div>
                </div>
            @endif
        @endforelse
    </div>

    {{-- Photo request --}}
    @if (! $person->is_living)
        <div class="border-t border-[#f0f4f8] px-6 py-4">
            @php
                $pendingRequest = $person->photoRequests
                    ->where('requested_by', auth()->id())
                    ->where('status', 'pending')
                    ->first();
            @endphp

            @if ($pendingRequest)
                <div class="flex items-center justify-between">
                    <p class="text-sm text-[#6f7b83]">{{ __('Photo request submitted.') }}</p>
                    <form method="POST" action="{{ route('people.photo-requests.update', $pendingRequest) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="fulfilled">
                        <button type="submit" class="text-xs text-[#16a34a] hover:underline">{{ __('Mark fulfilled') }}</button>
                    </form>
                </div>
            @else
                <div class="flex items-center justify-between">
                    <p class="text-sm text-[#6f7b83]">
                        @if ($person->findagrave_memorial_id)
                            <a href="{{ $fag->photoRequestUrl($person->findagrave_memorial_id) }}" target="_blank"
                               rel="noopener noreferrer" class="text-[#2563eb] hover:underline">
                                {{ __('Request grave photo on Find A Grave') }} ↗
                            </a>
                        @else
                            {{ __('No Find A Grave memorial linked.') }}
                        @endif
                    </p>
                    <form method="POST" action="{{ route('people.photo-requests.store', $person) }}">
                        @csrf
                        <button type="submit" class="text-xs text-[#9daab4] hover:text-[#2563eb]">
                            {{ __('Track photo request') }}
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @endif
</section>
