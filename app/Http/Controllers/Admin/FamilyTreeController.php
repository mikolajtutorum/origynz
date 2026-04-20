<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyTree;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FamilyTreeController extends Controller
{
    public function index(Request $request): View
    {
        $query = FamilyTree::with('user')
            ->withCount(['people', 'relationships', 'mediaItems'])
            ->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('home_region', 'like', "%{$search}%");
            });
        }

        $trees = $query->paginate(25)->withQueryString();

        return view('admin.trees.index', compact('trees'));
    }

    public function show(FamilyTree $tree): View
    {
        $tree->load('user')
             ->loadCount(['people', 'relationships', 'mediaItems', 'sources', 'invitations', 'membershipRequests']);

        $recentActivity = \Spatie\Activitylog\Models\Activity::where('subject_type', FamilyTree::class)
            ->where('subject_id', $tree->id)
            ->latest()
            ->take(15)
            ->get();

        return view('admin.trees.show', compact('tree', 'recentActivity'));
    }

    public function destroy(FamilyTree $tree): RedirectResponse
    {
        $name = $tree->name;
        $tree->delete();

        return redirect()->route('admin.trees.index')->with('status', "Tree \"{$name}\" deleted.");
    }
}
