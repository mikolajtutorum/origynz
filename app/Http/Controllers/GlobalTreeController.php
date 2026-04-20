<?php

namespace App\Http\Controllers;

use App\Models\FamilyTree;
use App\Models\Person;
use App\Services\GlobalTreePedigreeService;
use App\Services\GlobalTreePrivacyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class GlobalTreeController extends Controller
{
    public function __construct(
        private readonly GlobalTreePrivacyService $privacy,
        private readonly GlobalTreePedigreeService $pedigree,
    ) {}

    public function index(Request $request): View
    {
        $search      = $request->get('search', '');
        $treeFilter  = (int) $request->get('tree', 0);
        $ownedTreeIds = FamilyTree::where('user_id', $request->user()->id)->pluck('id')->all();

        $query = Person::query()
            ->whereHas('familyTree', fn ($q) => $q->where('global_tree_enabled', true))
            ->where('exclude_from_global_tree', false)
            ->with('familyTree:id,name');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('given_name', 'like', "%{$search}%")
                    ->orWhere('surname', 'like', "%{$search}%");
            });
        }

        if ($treeFilter > 0) {
            $query->where('family_tree_id', $treeFilter);
        }

        $people = $query
            ->orderBy('surname')
            ->orderBy('given_name')
            ->paginate(50)
            ->withQueryString();

        $branches = FamilyTree::where('global_tree_enabled', true)
            ->withCount(['people as visible_people_count' => function ($q): void {
                $q->where('exclude_from_global_tree', false);
            }])
            ->orderBy('name')
            ->get(['id', 'name']);

        $totalProfiles = $branches->sum('visible_people_count');

        $displayData = $people->getCollection()->map(
            fn (Person $p) => $this->privacy->buildDisplayData($p, $ownedTreeIds)
        );

        return view('global-tree.index', [
            'people'        => $people,
            'displayData'   => $displayData,
            'branches'      => $branches,
            'totalProfiles' => $totalProfiles,
            'search'        => $search,
            'treeFilter'    => $treeFilter,
        ]);
    }

    public function pedigree(Request $request): View
    {
        $user         = $request->user();
        $ownedTreeIds = FamilyTree::where('user_id', $user->id)->pluck('id')->all();

        $hasEnabledTree = $this->pedigree->hasAnyEnabledTree($user);
        $rootPerson     = $hasEnabledTree ? $this->pedigree->findRootPerson($user) : null;

        $chartNodes  = [];
        $chartLines  = [];
        $chartMeta   = ['width' => 1360, 'height' => 760];
        $generations = max(2, min(5, (int) $request->query('generations', 3)));
        $sidebarData = null;

        if ($rootPerson) {
            [$chartNodes, $chartLines, $chartMeta] = $this->pedigree->buildChart($rootPerson, $generations, $ownedTreeIds);
            $sidebarData = $this->pedigree->buildSidebarData($rootPerson, $ownedTreeIds);
        }

        return view('global-tree.pedigree', [
            'hasEnabledTree' => $hasEnabledTree,
            'rootPerson'     => $rootPerson,
            'chartNodes'     => $chartNodes,
            'chartLines'     => $chartLines,
            'chartMeta'      => $chartMeta,
            'generations'    => $generations,
            'sidebarData'    => $sidebarData,
        ]);
    }

    public function pedigreePerson(Request $request, Person $person): \Illuminate\Http\JsonResponse
    {
        $tree = $person->familyTree;
        abort_unless($tree && $tree->global_tree_enabled && ! $person->exclude_from_global_tree, 403);

        $ownedTreeIds = FamilyTree::where('user_id', $request->user()->id)->pluck('id')->all();
        $data         = $this->pedigree->buildSidebarData($person, $ownedTreeIds);

        return response()->json([
            'sidebar_html' => view('global-tree.partials.pedigree-sidebar', $data)->render(),
        ]);
    }
}
