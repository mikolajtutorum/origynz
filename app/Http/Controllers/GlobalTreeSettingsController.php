<?php

namespace App\Http\Controllers;

use App\Enums\TreePermission;
use App\Models\FamilyTree;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GlobalTreeSettingsController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function update(Request $request, FamilyTree $tree): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $data = $request->validate([
            'global_tree_enabled' => ['required', 'boolean'],
            'consent'             => ['required_if:global_tree_enabled,1', 'accepted'],
        ]);

        $tree->update(['global_tree_enabled' => (bool) $data['global_tree_enabled']]);

        $message = $data['global_tree_enabled']
            ? __('Your tree is now part of the Global Tree. Living persons are automatically anonymised.')
            : __('Your tree has been removed from the Global Tree.');

        return redirect()
            ->route('trees.managers.show', $tree)
            ->with('status', $message);
    }
}
