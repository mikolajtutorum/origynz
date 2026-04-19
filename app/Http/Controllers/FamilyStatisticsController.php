<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class FamilyStatisticsController extends Controller
{
    public function __invoke(Request $request): View
    {
        $trees = $request->user()
            ->familyTrees()
            ->withCount(['people', 'relationships'])
            ->orderBy('name')
            ->get();

        $treeIds = $trees->pluck('id');
        $people = Person::query()
            ->whereIn('family_tree_id', $treeIds)
            ->get([
                'id',
                'family_tree_id',
                'birth_place',
                'death_place',
                'is_living',
            ]);

        $profiles = $people->count();
        $living = $people->where('is_living', true)->count();
        $deceased = $profiles - $living;
        $relationships = $trees->sum('relationships_count');
        $averageTreeSize = $trees->count() > 0 ? round($profiles / $trees->count(), 1) : 0;

        $topBirthPlaces = $people
            ->pluck('birth_place')
            ->filter()
            ->map(fn (string $place) => trim($place))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5);

        $topDeathPlaces = $people
            ->pluck('death_place')
            ->filter()
            ->map(fn (string $place) => trim($place))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(5);

        return view('statistics.index', [
            'trees' => $trees,
            'summary' => [
                'trees' => $trees->count(),
                'profiles' => $profiles,
                'living' => $living,
                'deceased' => $deceased,
                'relationships' => $relationships,
                'average_tree_size' => $averageTreeSize,
            ],
            'largestTree' => $trees->sortByDesc('people_count')->first(),
            'topBirthPlaces' => $topBirthPlaces,
            'topDeathPlaces' => $topDeathPlaces,
        ]);
    }
}
