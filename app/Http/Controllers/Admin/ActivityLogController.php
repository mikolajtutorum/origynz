<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Activity::with('causer', 'subject')->latest();

        if ($search = $request->get('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        if ($causerId = $request->get('causer_id')) {
            $query->where('causer_id', $causerId)->where('causer_type', User::class);
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('admin.activity.index', compact('logs'));
    }
}
