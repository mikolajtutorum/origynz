<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $trees = $request->user()
            ->familyTrees()
            ->withCount('people')
            ->latest()
            ->get();

        $recentPeople = $request->user()
            ->people()
            ->with('familyTree')
            ->latest()
            ->take(8)
            ->get();

        return view('dashboard', [
            'trees' => $trees,
            'recentPeople' => $recentPeople,
            'stats' => [
                'trees' => $trees->count(),
                'profiles' => $trees->sum('people_count'),
                'living' => $request->user()->people()->where('is_living', true)->count(),
                'relationships' => $request->user()->familyTrees()->withCount('relationships')->get()->sum('relationships_count'),
            ],
        ]);
    }
}
