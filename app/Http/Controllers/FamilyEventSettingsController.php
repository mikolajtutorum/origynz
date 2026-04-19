<?php

namespace App\Http\Controllers;

use App\Enums\TreePermission;
use App\Models\FamilyTree;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FamilyEventSettingsController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function edit(Request $request, FamilyTree $tree): View
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        return view('trees.event-settings', [
            'tree' => $tree,
        ]);
    }

    public function update(Request $request, FamilyTree $tree): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $tree->update([
            'show_birthdays_in_events' => $request->boolean('show_birthdays_in_events'),
            'show_wedding_anniversaries_in_events' => $request->boolean('show_wedding_anniversaries_in_events'),
            'show_death_anniversaries_in_events' => $request->boolean('show_death_anniversaries_in_events'),
        ]);

        return redirect()
            ->route('trees.events.settings.edit', $tree)
            ->with('status', __('Calendar settings updated.'));
    }
}
