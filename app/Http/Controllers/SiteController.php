<?php

namespace App\Http\Controllers;

use App\Models\FamilyTree;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /**
     * Redirect to the first accessible tree in the given site.
     */
    public function open(Request $request, Site $site): RedirectResponse
    {
        $user = $request->user();

        // Ensure the user actually has access to this site.
        $accessible = Site::query()
            ->where('id', $site->id)
            ->accessibleTo($user)
            ->exists();

        abort_unless($accessible, 403);

        $tree = FamilyTree::query()
            ->where('site_id', $site->id)
            ->visibleTo($user)
            ->orderBy('name')
            ->first();

        if (! $tree) {
            return redirect()->route('trees.manage')
                ->with('status', __('No accessible trees found in that site.'));
        }

        return redirect()->route('trees.show', $tree);
    }
}
