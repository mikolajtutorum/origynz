<?php

namespace App\Http\Controllers;

use App\Models\FamilyTree;
use App\Models\MediaItem;
use App\Models\Person;
use App\Models\PersonRelationship;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FamilyEventController extends Controller
{
    public function index(Request $request): View
    {
        $ownedTrees = $request->user()
            ->familyTrees()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'show_birthdays_in_events',
                'show_wedding_anniversaries_in_events',
                'show_death_anniversaries_in_events',
            ]);

        $selectedTreeValue = $request->string('tree')->toString() ?: 'all';
        $selectedTree = $selectedTreeValue !== 'all'
            ? $ownedTrees->firstWhere('id', (int) $selectedTreeValue)
            : null;

        abort_if($selectedTreeValue !== 'all' && ! $selectedTree, 404);

        $scopedTrees = $selectedTree ? collect([$selectedTree]) : $ownedTrees;
        $scopedTreeIds = $scopedTrees->pluck('id');

        $scope = $request->string('scope')->toString() ?: 'upcoming';
        $scope = in_array($scope, ['upcoming', 'month', 'year'], true) ? $scope : 'upcoming';
        $search = trim($request->string('search')->toString());
        $typeFilter = $request->string('type')->toString() ?: 'all';
        $typeFilter = in_array($typeFilter, ['all', 'birthdays', 'anniversaries', 'death-anniversaries'], true) ? $typeFilter : 'all';

        $today = CarbonImmutable::today(config('app.timezone'));
        $selectedMonth = max(1, min(12, (int) $request->integer('month', $today->month)));
        $selectedYear = max(1900, min(2100, (int) $request->integer('year', $today->year)));
        $monthDate = CarbonImmutable::create($selectedYear, $selectedMonth, 1, 0, 0, 0, config('app.timezone'));

        $people = Person::query()
            ->whereIn('family_tree_id', $scopedTreeIds)
            ->orderBy('surname')
            ->orderBy('given_name')
            ->get([
                'id',
                'family_tree_id',
                'given_name',
                'middle_name',
                'surname',
                'birth_surname',
                'sex',
                'birth_date',
                'birth_date_text',
                'death_date',
                'death_date_text',
                'birth_place',
                'death_place',
                'is_living',
                'headline',
            ]);

        $avatarUrls = $this->personAvatarUrls($people);

        $events = collect();

        $birthdayPeople = $selectedTree
            ? ($selectedTree->show_birthdays_in_events ? $people : collect())
            : $people->filter(fn (Person $person) => (bool) $ownedTrees->firstWhere('id', $person->family_tree_id)?->show_birthdays_in_events);

        if ($birthdayPeople->isNotEmpty()) {
            $events = $events->concat($this->buildBirthdayEvents($birthdayPeople, $avatarUrls, $today, $scope, $monthDate, $selectedYear, $ownedTrees));
        }

        $anniversaryTreeIds = $selectedTree
            ? ($selectedTree->show_wedding_anniversaries_in_events ? collect([$selectedTree->id]) : collect())
            : $ownedTrees->filter(fn (FamilyTree $tree) => $tree->show_wedding_anniversaries_in_events)->pluck('id');

        if ($anniversaryTreeIds->isNotEmpty()) {
            $events = $events->concat($this->buildAnniversaryEvents($anniversaryTreeIds, $avatarUrls, $today, $scope, $monthDate, $selectedYear, $ownedTrees));
        }

        $deathAnniversaryPeople = $selectedTree
            ? ($selectedTree->show_death_anniversaries_in_events ? $people : collect())
            : $people->filter(fn (Person $person) => (bool) $ownedTrees->firstWhere('id', $person->family_tree_id)?->show_death_anniversaries_in_events);

        if ($deathAnniversaryPeople->isNotEmpty()) {
            $events = $events->concat($this->buildDeathAnniversaryEvents($deathAnniversaryPeople, $avatarUrls, $today, $scope, $monthDate, $selectedYear, $ownedTrees));
        }

        if ($typeFilter !== 'all') {
            $events = $events->where('group', $typeFilter);
        }

        if ($search !== '') {
            $needle = Str::lower($search);
            $events = $events->filter(function (array $event) use ($needle): bool {
                return Str::contains(Str::lower(implode(' ', [
                    $event['title'],
                    $event['subtitle'] ?? '',
                    $event['meta'] ?? '',
                    $event['place'] ?? '',
                ])), $needle);
            });
        }

        $events = $events
            ->sortBy([
                ['occurs_on', 'asc'],
                ['sort_rank', 'asc'],
                ['title', 'asc'],
            ])
            ->values();

        $groupedEvents = $events
            ->groupBy(fn (array $event) => $event['occurs_on']->format('F Y'))
            ->map(fn (Collection $group) => $group->values());

        $missingEvents = $birthdayPeople->isNotEmpty()
            ? $people
                ->filter(function (Person $person) use ($ownedTrees): bool {
                    return $person->is_living
                        && blank($person->birth_date)
                        && blank($person->birth_date_text)
                        && (bool) $ownedTrees->firstWhere('id', $person->family_tree_id)?->show_birthdays_in_events;
                })
                ->take(9)
                ->map(function (Person $person) use ($avatarUrls, $ownedTrees): array {
                    $treeName = $ownedTrees->firstWhere('id', $person->family_tree_id)?->name;

                    return [
                    'title' => __('Birthday of :name', ['name' => $person->display_name]),
                    'subtitle' => $treeName ?: ($person->headline ?: __('Date not added yet')),
                    'avatar_url' => $avatarUrls[$person->id] ?? null,
                    'initials' => $this->personInitials($person),
                    'tree_url' => route('trees.show', $person->family_tree_id),
                ];
            })
            ->values()
            : collect();

        return view('trees.events', [
            'ownedTrees' => $ownedTrees,
            'selectedTree' => $selectedTree,
            'selectedTreeValue' => $selectedTree?->id ? (string) $selectedTree->id : 'all',
            'scope' => $scope,
            'search' => $search,
            'typeFilter' => $typeFilter,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'monthDate' => $monthDate,
            'groupedEvents' => $groupedEvents,
            'missingEvents' => $missingEvents,
            'totalEvents' => $events->count(),
        ]);
    }

    /**
     * @param  Collection<int, Person>  $people
     * @return array<int, string>
     */
    private function personAvatarUrls(Collection $people): array
    {
        if ($people->isEmpty()) {
            return [];
        }

        $avatarUrls = [];
        $media = MediaItem::query()
            ->whereIn('person_id', $people->pluck('id'))
            ->whereNotNull('file_path')
            ->where('mime_type', 'like', 'image/%')
            ->orderByRaw('is_personal_photo DESC, is_primary DESC, id ASC')
            ->get(['id', 'person_id']);

        foreach ($media as $item) {
            if (! isset($avatarUrls[$item->person_id])) {
                $avatarUrls[$item->person_id] = route('media.preview', $item->id);
            }
        }

        return $avatarUrls;
    }

    /**
     * @param  Collection<int, Person>  $people
     * @return Collection<int, array<string, mixed>>
     */
    private function buildBirthdayEvents(
        Collection $people,
        array $avatarUrls,
        CarbonImmutable $today,
        string $scope,
        CarbonImmutable $monthDate,
        int $selectedYear,
        Collection $treesById,
    ): Collection {
        return $people
            ->filter(fn (Person $person) => $person->is_living && $person->birth_date !== null)
            ->map(function (Person $person) use ($today, $scope, $monthDate, $selectedYear, $treesById): ?array {
                $occursOn = $this->occurrenceForDate($person->birth_date, $today, $scope, $monthDate, $selectedYear);

                if (! $occursOn) {
                    return null;
                }

                $age = $occursOn->year - $person->birth_date->year;

                if ($age >= 100) {
                    return null;
                }

                return [
                    'group' => 'birthdays',
                    'sort_rank' => 10,
                    'occurs_on' => $occursOn,
                    'title' => __(':age birthday of :name', ['age' => $age, 'name' => $person->display_name]),
                    'subtitle' => $person->headline ?: ($person->birth_place ?: __('Family member')),
                    'tree_name' => $treesById->firstWhere('id', $person->family_tree_id)?->name,
                    'meta' => __('Birthday'),
                    'place' => $person->birth_place,
                    'avatar_url' => $avatarUrls[$person->id] ?? null,
                    'initials' => $this->personInitials($person),
                    'icon' => 'balloons',
                    'event_url' => route('trees.show', $person->family_tree_id),
                ];
            })
            ->filter();
    }

    /**
     * @param  array<int, string>  $avatarUrls
     * @return Collection<int, array<string, mixed>>
     */
    private function buildAnniversaryEvents(
        Collection $treeIds,
        array $avatarUrls,
        CarbonImmutable $today,
        string $scope,
        CarbonImmutable $monthDate,
        int $selectedYear,
        Collection $treesById,
    ): Collection {
        $seen = [];

        return PersonRelationship::query()
            ->whereIn('family_tree_id', $treeIds)
            ->where('type', 'spouse')
            ->whereNotNull('start_date')
            ->with([
                'person:id,given_name,middle_name,surname,birth_surname,headline,is_living',
                'relatedPerson:id,given_name,middle_name,surname,birth_surname,headline,is_living',
            ])
            ->get()
            ->map(function (PersonRelationship $relationship) use (&$seen, $avatarUrls, $today, $scope, $monthDate, $selectedYear, $treesById): ?array {
                if (! $relationship->start_date || ! $relationship->person || ! $relationship->relatedPerson) {
                    return null;
                }

                if (! $relationship->person->is_living || ! $relationship->relatedPerson->is_living) {
                    return null;
                }

                $pair = collect([$relationship->person_id, $relationship->related_person_id])->sort()->values();
                $key = implode(':', [$pair[0], $pair[1], $relationship->start_date->format('Y-m-d')]);

                if (isset($seen[$key])) {
                    return null;
                }

                $seen[$key] = true;
                $occursOn = $this->occurrenceForDate($relationship->start_date, $today, $scope, $monthDate, $selectedYear);

                if (! $occursOn) {
                    return null;
                }

                $years = $occursOn->year - $relationship->start_date->year;

                return [
                    'group' => 'anniversaries',
                    'sort_rank' => 20,
                    'occurs_on' => $occursOn,
                    'title' => __(':years anniversary of :left and :right', [
                        'years' => $years,
                        'left' => $relationship->person->display_name,
                        'right' => $relationship->relatedPerson->display_name,
                    ]),
                    'subtitle' => $relationship->description ?: __('Relationship milestone'),
                    'tree_name' => $treesById->firstWhere('id', $relationship->family_tree_id)?->name,
                    'meta' => __('Anniversary'),
                    'place' => $relationship->place,
                    'avatar_url' => $avatarUrls[$relationship->person_id] ?? null,
                    'secondary_avatar_url' => $avatarUrls[$relationship->related_person_id] ?? null,
                    'initials' => $this->personInitials($relationship->person),
                    'secondary_initials' => $this->personInitials($relationship->relatedPerson),
                    'icon' => 'rings',
                    'event_url' => route('trees.show', $relationship->family_tree_id),
                ];
            })
            ->filter();
    }

    /**
     * @param  Collection<int, Person>  $people
     * @param  array<int, string>  $avatarUrls
     * @return Collection<int, array<string, mixed>>
     */
    private function buildDeathAnniversaryEvents(
        Collection $people,
        array $avatarUrls,
        CarbonImmutable $today,
        string $scope,
        CarbonImmutable $monthDate,
        int $selectedYear,
        Collection $treesById,
    ): Collection {
        return $people
            ->filter(fn (Person $person) => ! $person->is_living && $person->death_date !== null)
            ->map(function (Person $person) use ($avatarUrls, $today, $scope, $monthDate, $selectedYear, $treesById): ?array {
                $occursOn = $this->occurrenceForDate($person->death_date, $today, $scope, $monthDate, $selectedYear);

                if (! $occursOn) {
                    return null;
                }

                $years = $occursOn->year - $person->death_date->year;

                return [
                    'group' => 'death-anniversaries',
                    'sort_rank' => 25,
                    'occurs_on' => $occursOn,
                    'title' => __(':years death anniversary of :name', ['years' => $years, 'name' => $person->display_name]),
                    'subtitle' => $person->headline ?: __('Remembered family member'),
                    'tree_name' => $treesById->firstWhere('id', $person->family_tree_id)?->name,
                    'meta' => __('Death anniversary'),
                    'place' => $person->death_place,
                    'avatar_url' => $avatarUrls[$person->id] ?? null,
                    'initials' => $this->personInitials($person),
                    'icon' => 'memorial',
                    'event_url' => route('trees.show', $person->family_tree_id),
                ];
            })
            ->filter();
    }

    private function occurrenceForDate(
        \Carbon\CarbonInterface $sourceDate,
        CarbonImmutable $today,
        string $scope,
        CarbonImmutable $monthDate,
        int $selectedYear,
    ): ?CarbonImmutable {
        $candidateYear = match ($scope) {
            'month' => $monthDate->year,
            'year' => $selectedYear,
            default => $today->year,
        };

        try {
            $occursOn = CarbonImmutable::create($candidateYear, $sourceDate->month, $sourceDate->day, 0, 0, 0, config('app.timezone'));
        } catch (\InvalidArgumentException) {
            return null;
        }

        if ($scope === 'upcoming') {
            if ($occursOn->lt($today)) {
                $occursOn = $occursOn->addYear();
            }

            return $occursOn->diffInDays($today) <= 365 ? $occursOn : null;
        }

        if ($scope === 'month') {
            return $occursOn->month === $monthDate->month ? $occursOn : null;
        }

        return $occursOn;
    }

    private function personInitials(Person $person): string
    {
        $parts = preg_split('/\s+/', trim($person->display_name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : '?';
    }
}
