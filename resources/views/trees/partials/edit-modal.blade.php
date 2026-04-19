@if ($focusPerson)
@php
    $months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];

    /**
     * Parse a GEDCOM date text + Carbon date into [mode, month, day, year, month2, day2, year2, free_text].
     */
    $parseDateComponents = function (?string $dateText, mixed $exactDate) use ($months): array {
        $defaults = ['mode' => 'exact', 'month' => '', 'day' => '', 'year' => '', 'month2' => '', 'day2' => '', 'year2' => '', 'free_text' => ''];

        $parseFragment = function (string $fragment) use ($months): array {
            $fragment = trim($fragment);
            preg_match('/^(?:(\d{1,2})\s+)?([A-Z]{3})?\s*(\d{4})$/i', $fragment, $m);
            if ($m) {
                $mon = strtoupper($m[2] ?? '');
                return [
                    'month' => in_array($mon, $months) ? $mon : '',
                    'day'   => $m[1] ?? '',
                    'year'  => $m[3] ?? '',
                ];
            }
            if (preg_match('/^(\d{4})$/', $fragment, $m)) {
                return ['month' => '', 'day' => '', 'year' => $m[1]];
            }
            return ['month' => '', 'day' => '', 'year' => $fragment];
        };

        if ($exactDate && (!$dateText || !preg_match('/^(BEF|AFT|ABT|EST|BET|FROM|TO)\s/i', $dateText))) {
            return array_merge($defaults, [
                'mode'  => 'exact',
                'month' => $months[$exactDate->month - 1],
                'day'   => (string) $exactDate->day,
                'year'  => (string) $exactDate->year,
            ]);
        }

        if (!$dateText) return $defaults;
        $text = trim($dateText);

        if (preg_match('/^BEF\s+(.+)$/i', $text, $m))  return array_merge($defaults, ['mode' => 'before'],  $parseFragment($m[1]));
        if (preg_match('/^AFT\s+(.+)$/i', $text, $m))  return array_merge($defaults, ['mode' => 'after'],   $parseFragment($m[1]));
        if (preg_match('/^ABT\s+(.+)$/i', $text, $m))  return array_merge($defaults, ['mode' => 'circa'],   $parseFragment($m[1]));
        if (preg_match('/^EST\s+(.+)$/i', $text, $m))  return array_merge($defaults, ['mode' => 'unsure'],  $parseFragment($m[1]));
        if (preg_match('/^FROM\s+(.+)\s+TO\s+(.+)$/i', $text, $m)) {
            $p1 = $parseFragment($m[1]); $p2 = $parseFragment($m[2]);
            return array_merge($defaults, ['mode' => 'from-to', 'month' => $p1['month'], 'day' => $p1['day'], 'year' => $p1['year'], 'month2' => $p2['month'], 'day2' => $p2['day'], 'year2' => $p2['year']]);
        }
        if (preg_match('/^BET\s+(.+)\s+AND\s+(.+)$/i', $text, $m)) {
            $p1 = $parseFragment($m[1]); $p2 = $parseFragment($m[2]);
            return array_merge($defaults, ['mode' => 'between', 'month' => $p1['month'], 'day' => $p1['day'], 'year' => $p1['year'], 'month2' => $p2['month'], 'day2' => $p2['day'], 'year2' => $p2['year']]);
        }
        if (preg_match('/^FROM\s+(.+)$/i', $text, $m))  return array_merge($defaults, ['mode' => 'from'], $parseFragment($m[1]));
        if (preg_match('/^TO\s+(.+)$/i', $text, $m))    return array_merge($defaults, ['mode' => 'to'],   $parseFragment($m[1]));

        $parts = $parseFragment($text);
        if ($parts['year']) return array_merge($defaults, ['mode' => 'exact'], $parts);

        return array_merge($defaults, ['mode' => 'free', 'free_text' => $text]);
    };

    $birth = $parseDateComponents($focusPerson->birth_date_text, $focusPerson->birth_date);
    $death = $parseDateComponents($focusPerson->death_date_text, $focusPerson->death_date);

    $dateQualifiers = [
        'exact'   => __('Exactly'),
        'before'  => __('Before'),
        'after'   => __('After'),
        'circa'   => __('Circa'),
        'unsure'  => __('Unsure date'),
        'between' => __('Between ... and ...'),
        'from-to' => __('From ... to ...'),
        'from'    => __('From'),
        'to'      => __('To'),
        'free'    => __('Free text'),
    ];

    $prefixOptions = ['', 'Dr.', 'Prof.', 'Rev.', 'Sir', 'Lord', 'Lady', 'Mr.', 'Mrs.', 'Ms.'];
    $suffixOptions = ['', 'Jr.', 'Sr.', 'I', 'II', 'III', 'IV', 'V', 'Esq.', 'PhD', 'MD'];

    $causeOptions = [
        '' => '',
        'Accident' => __('Accident'),
        'At War' => __('At War'),
        'At young age' => __('At young age'),
        'Cancer' => __('Cancer'),
        'Diabetes' => __('Diabetes'),
        'Heart attack' => __('Heart attack'),
        'Holocaust' => __('Holocaust'),
        'Homicide' => __('Homicide'),
        'Illness' => __('Illness'),
        'Medical Problem' => __('Medical Problem'),
        'Miscarriage' => __('Miscarriage'),
        'Natural / Old Age' => __('Natural / Old Age'),
        'Pogrom' => __('Pogrom'),
        'Stillborn' => __('Stillborn'),
        'Stroke' => __('Stroke'),
        'Suicide' => __('Suicide'),
        'Unknown' => __('Unknown'),
    ];

    $subtypeLabels = [
        ''          => __('Partner'),
        'married'   => __('Married'),
        'engaged'   => __('Engaged'),
        'divorced'  => __('Divorced'),
        'separated' => __('Separated'),
        'widowed'   => __('Widowed'),
    ];

    $parseMariageDate = function (array $fact) use ($parseDateComponents): array {
        return $parseDateComponents($fact['start_date_text'], $fact['start_date'] instanceof \Illuminate\Support\Carbon ? $fact['start_date'] : null);
    };
@endphp

<div class="workspace-person-modal is-hidden" data-edit-profile-modal>
    <div class="workspace-person-modal-card">
        <div class="flex items-start justify-between gap-4">
            <h3 class="text-[22px] font-semibold text-[#24313b]">{{ __('Edit :name\'s profile', ['name' => $focusPerson->given_name]) }}</h3>
            <button type="button" class="workspace-person-modal-close" data-edit-profile-close>✕</button>
        </div>

        <form method="POST" action="{{ route('people.update', $focusPerson) }}" class="mt-6 space-y-5" data-edit-profile-form>
            @csrf
            @method('PATCH')
            <input type="hidden" name="return_to" value="{{ $currentUrl }}" />
            <input type="hidden" name="birth_date" value="" data-edit-birth-date-hidden />
            <input type="hidden" name="birth_date_text" value="" data-edit-birth-date-text-hidden />
            <input type="hidden" name="death_date" value="" data-edit-death-date-hidden />
            <input type="hidden" name="death_date_text" value="" data-edit-death-date-text-hidden />

            {{-- Gender --}}
            <div class="flex flex-wrap gap-6 text-[14px] text-[#62707b]">
                <label class="flex items-center gap-2">
                    <input type="radio" name="sex" value="male" @checked(old('sex', $focusPerson->sex) === 'male') data-edit-sex />
                    <span>{{ __('Male') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="sex" value="female" @checked(old('sex', $focusPerson->sex) === 'female') data-edit-sex />
                    <span>{{ __('Female') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="sex" value="unknown" @checked(old('sex', $focusPerson->sex) === 'unknown') data-edit-sex />
                    <span>{{ __('Unknown') }}</span>
                </label>
            </div>

            {{-- Name --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="workspace-modal-label">{{ __('First (and middle) name:') }}</label>
                    <input name="given_name" required class="workspace-input workspace-modal-input" value="{{ old('given_name', $focusPerson->given_name) }}" />
                </div>
                <div>
                    <label class="workspace-modal-label">{{ __('Last name:') }}</label>
                    <input name="surname" required class="workspace-input workspace-modal-input" value="{{ old('surname', $focusPerson->surname) }}" />
                </div>
            </div>

            {{-- Prefix / Suffix --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="workspace-modal-label">{{ __('Prefix:') }}</label>
                    <select name="prefix" class="workspace-input workspace-modal-input">
                        @foreach ($prefixOptions as $opt)
                            <option value="{{ $opt }}" @selected(old('prefix', $focusPerson->prefix) === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="workspace-modal-label">{{ __('Suffix:') }}</label>
                    <select name="suffix" class="workspace-input workspace-modal-input">
                        @foreach ($suffixOptions as $opt)
                            <option value="{{ $opt }}" @selected(old('suffix', $focusPerson->suffix) === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Nickname --}}
            <div>
                <label class="workspace-modal-label">{{ __('Nickname:') }}</label>
                <input name="nickname" class="workspace-input workspace-modal-input" value="{{ old('nickname', $focusPerson->nickname) }}" />
            </div>

            {{-- Birth date + place --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="workspace-modal-label">{{ __('Birth date:') }}</label>
                    @include('trees.partials.date-picker', ['ns' => 'edit-birth', 'components' => $birth, 'qualifiers' => $dateQualifiers])
                </div>
                <div>
                    <label class="workspace-modal-label">{{ __('Birth place:') }}</label>
                    <input name="birth_place" class="workspace-input workspace-modal-input" value="{{ old('birth_place', $focusPerson->birth_place) }}" />
                </div>
            </div>

            {{-- Living / Deceased --}}
            <div class="flex flex-wrap gap-6 border-t border-[#ececec] pt-4 text-[14px] text-[#62707b]">
                <label class="flex items-center gap-2">
                    <input type="radio" name="is_living" value="1" @checked(old('is_living', $focusPerson->is_living ? '1' : '0') === '1') data-edit-is-living />
                    <span>{{ __('Living') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" name="is_living" value="0" @checked(old('is_living', $focusPerson->is_living ? '1' : '0') === '0') data-edit-is-living />
                    <span>{{ __('Deceased') }}</span>
                </label>
            </div>

            {{-- Death date + place --}}
            <div class="grid grid-cols-2 gap-4" data-edit-death-section @class(['hidden' => old('is_living', $focusPerson->is_living ? '1' : '0') === '1'])>
                <div>
                    <label class="workspace-modal-label">{{ __('Death date:') }}</label>
                    @include('trees.partials.date-picker', ['ns' => 'edit-death', 'components' => $death, 'qualifiers' => $dateQualifiers])
                </div>
                <div>
                    <label class="workspace-modal-label">{{ __('Death place:') }}</label>
                    <input name="death_place" class="workspace-input workspace-modal-input" value="{{ old('death_place', $focusPerson->death_place) }}" />
                </div>
            </div>

            {{-- Cause of death + burial place --}}
            <div class="grid grid-cols-2 gap-4" data-edit-death-section @class(['hidden' => old('is_living', $focusPerson->is_living ? '1' : '0') === '1'])>
                <div>
                    <label class="workspace-modal-label">{{ __('Cause of death:') }}</label>
                    <input
                        type="text"
                        name="cause_of_death"
                        list="cause-of-death-hints"
                        class="workspace-input workspace-modal-input"
                        value="{{ old('cause_of_death', $focusPerson->cause_of_death) }}"
                        placeholder="{{ __('e.g. Heart attack') }}"
                        autocomplete="off"
                    />
                    <datalist id="cause-of-death-hints">
                        @foreach (array_keys($causeOptions) as $hint)
                            @if ($hint !== '')
                                <option value="{{ $hint }}">
                            @endif
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="workspace-modal-label">{{ __('Burial place or cemetery:') }}</label>
                    <input name="burial_place" class="workspace-input workspace-modal-input" value="{{ old('burial_place', $focusPerson->burial_place) }}" />
                </div>
            </div>

            {{-- Physical description --}}
            <div>
                <label class="workspace-modal-label">{{ __('Physical description:') }}</label>
                <textarea name="physical_description" rows="2" class="workspace-input workspace-modal-input">{{ old('physical_description', $focusPerson->physical_description) }}</textarea>
            </div>

            {{-- Spouse relationship sections --}}
            @foreach ($focusFamily['spouses'] as $spouse)
                @php
                    $spouseFact = $focusRelationshipFacts->firstWhere('person.id', $spouse->id);
                    $mDate = $spouseFact ? $parseMariageDate($spouseFact) : ['mode'=>'exact','month'=>'','day'=>'','year'=>'','month2'=>'','day2'=>'','year2'=>'','free_text'=>''];
                    $relId  = $spouseFact['id'] ?? null;
                    $avatarUrl = $personAvatarUrls[$spouse->id] ?? null;
                @endphp
                @if ($relId)
                <div class="border-t border-[#ececec] pt-5">
                    <div class="mb-4 flex items-center gap-3">
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="" class="h-12 w-12 rounded-full object-cover" />
                        @else
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#e9eef3] text-[18px] text-[#7b8791]">{{ mb_substr($spouse->given_name, 0, 1) }}</div>
                        @endif
                        <div>
                            <div class="text-[14px] font-semibold text-[#3d4e58]">
                                @if ($spouse->sex === 'female') {{ __('Wife:') }} @elseif ($spouse->sex === 'male') {{ __('Husband:') }} @else {{ __('Partner:') }} @endif
                                {{ $spouse->display_name }}{{ $spouse->birth_surname ? ' (' . __('born') . ' ' . $spouse->birth_surname . ')' : '' }}
                            </div>
                        </div>
                    </div>

                    {{-- Hidden PATCH form for this relationship --}}
                    <input type="hidden" name="_rel_{{ $relId }}_id" value="{{ $relId }}" data-rel-id />
                    <input type="hidden" name="_rel_{{ $relId }}_start_date" value="" data-edit-rel-start-date-hidden data-rel="{{ $relId }}" />
                    <input type="hidden" name="_rel_{{ $relId }}_start_date_text" value="" data-edit-rel-start-date-text-hidden data-rel="{{ $relId }}" />

                    <div class="space-y-3">
                        <div>
                            <label class="workspace-modal-label">{{ __('Relationship:') }}</label>
                            <select name="_rel_{{ $relId }}_subtype" class="workspace-input workspace-modal-input w-auto">
                                @foreach ($subtypeLabels as $val => $label)
                                    <option value="{{ $val }}" @selected(($spouseFact['subtype'] ?? '') === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="workspace-modal-label">{{ __('Marriage date:') }}</label>
                                @include('trees.partials.date-picker', ['ns' => 'edit-rel-' . $relId, 'components' => $mDate, 'qualifiers' => $dateQualifiers])
                            </div>
                            <div>
                                <label class="workspace-modal-label">{{ __('Marriage place:') }}</label>
                                <input name="_rel_{{ $relId }}_place" class="workspace-input workspace-modal-input" value="{{ old('_rel_' . $relId . '_place', $spouseFact['place'] ?? '') }}" />
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach

            <div class="flex items-center justify-between border-t border-[#ececec] pt-4">
                <button type="button" class="text-[14px] font-medium text-[#ff6c2f]">{{ __('Edit more (bio, more facts...)') }}</button>
                <div class="flex gap-3">
                    <button type="button" class="workspace-modal-secondary" data-edit-profile-close>{{ __('Cancel') }}</button>
                    <button type="submit" class="workspace-modal-primary">{{ __('OK') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
