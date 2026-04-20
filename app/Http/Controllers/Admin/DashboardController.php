<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'users'         => User::count(),
            'trees'         => FamilyTree::count(),
            'people'        => Person::count(),
            'recent_users'  => User::latest()->take(5)->get(),
            'recent_trees'  => FamilyTree::with('user')->latest()->take(5)->get(),
            'recent_logs'   => Activity::with('causer', 'subject')->latest()->take(10)->get(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
