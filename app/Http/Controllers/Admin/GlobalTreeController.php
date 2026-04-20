<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyTree;
use App\Models\Person;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class GlobalTreeController extends Controller
{
    public function index(): View
    {
        $trees = FamilyTree::with('user')
            ->withCount([
                'people as total_people_count',
                'people as visible_people_count' => fn ($q) => $q->where('exclude_from_global_tree', false),
            ])
            ->orderByDesc('global_tree_enabled')
            ->orderBy('name')
            ->paginate(30);

        $enabledCount = FamilyTree::where('global_tree_enabled', true)->count();

        $totalPublicProfiles = Person::whereHas(
            'familyTree',
            fn ($q) => $q->where('global_tree_enabled', true)
        )->where('exclude_from_global_tree', false)->count();

        return view('admin.global-tree.index', [
            'trees'               => $trees,
            'enabledCount'        => $enabledCount,
            'totalPublicProfiles' => $totalPublicProfiles,
        ]);
    }

    public function toggle(FamilyTree $tree): RedirectResponse
    {
        $tree->update(['global_tree_enabled' => ! $tree->global_tree_enabled]);

        $message = $tree->global_tree_enabled
            ? __('Tree ":name" has been added to the Global Tree.', ['name' => $tree->name])
            : __('Tree ":name" has been removed from the Global Tree.', ['name' => $tree->name]);

        return redirect()
            ->route('admin.global-tree.index')
            ->with('status', $message);
    }
}
