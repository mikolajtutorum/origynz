<?php

namespace App\Http\Controllers;

use App\Enums\TreePermission;
use App\Models\Person;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonGlobalExclusionController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function update(Request $request, Person $person): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $person->familyTree, TreePermission::Manage);

        $data = $request->validate([
            'exclude_from_global_tree' => ['required', 'boolean'],
        ]);

        $person->update(['exclude_from_global_tree' => (bool) $data['exclude_from_global_tree']]);

        $message = $data['exclude_from_global_tree']
            ? __('This person has been excluded from the Global Tree.')
            : __('This person is now included in the Global Tree (subject to privacy rules).');

        return redirect()->back()->with('status', $message);
    }
}
