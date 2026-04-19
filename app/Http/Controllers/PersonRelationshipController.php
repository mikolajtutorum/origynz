<?php

namespace App\Http\Controllers;

use App\Enums\TreePermission;
use App\Models\FamilyTree;
use App\Models\PersonRelationship;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PersonRelationshipController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function store(Request $request, FamilyTree $tree): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $personIds = $tree->people()->pluck('id')->all();

        $data = $request->validate([
            'person_id' => ['required', Rule::in($personIds)],
            'related_person_id' => ['required', Rule::in($personIds), 'different:person_id'],
            'type' => ['required', 'in:parent,spouse,child'],
            'subtype' => ['nullable', 'string', 'max:120'],
        ]);

        $data['subtype'] = $this->normalizeSubtype($data['type'], $data['subtype'] ?? null);

        $exists = $tree->relationships()
            ->where($data)
            ->exists();

        if (! $exists) {
            $tree->relationships()->create($data);
        }

        if ($redirect = $this->workspaceRedirect($request)) {
            return redirect()->to($redirect)->with('status', 'Relationship linked.');
        }

        return redirect()
            ->route('trees.show', $tree)
            ->with('status', 'Relationship linked.');
    }

    public function update(Request $request, FamilyTree $tree, PersonRelationship $relationship): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $data = $request->validate([
            'subtype' => ['nullable', 'string', 'max:120'],
            'start_date' => ['nullable', 'date'],
            'start_date_text' => ['nullable', 'string', 'max:120'],
            'place' => ['nullable', 'string', 'max:120'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $relationship->update([
            'subtype' => $data['subtype'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'start_date_text' => $data['start_date_text'] ?? null,
            'place' => $data['place'] ?? null,
        ]);

        if ($redirect = $this->workspaceRedirect($request)) {
            return redirect()->to($redirect)->with('status', 'Relationship updated.');
        }

        return redirect()
            ->route('trees.show', $tree)
            ->with('status', 'Relationship updated.');
    }

    private function workspaceRedirect(Request $request): ?string
    {
        $returnTo = $request->string('return_to')->trim()->value();

        if ($returnTo === '') {
            return null;
        }

        if (str_starts_with($returnTo, '/trees/')) {
            return $returnTo;
        }

        $returnHost = parse_url($returnTo, PHP_URL_HOST);
        $requestHost = parse_url($request->fullUrl(), PHP_URL_HOST);
        $returnPath = parse_url($returnTo, PHP_URL_PATH) ?: '';

        if ($returnHost && $requestHost && $returnHost === $requestHost && str_starts_with($returnPath, '/trees/')) {
            return $returnTo;
        }

        return null;
    }

    private function normalizeSubtype(string $type, ?string $subtype): ?string
    {
        $normalized = strtolower(trim((string) $subtype));

        if ($normalized === '') {
            return null;
        }

        if ($type !== 'parent' && $type !== 'child') {
            return $subtype;
        }

        return match ($normalized) {
            'birth', 'biological' => null,
            'adopted', 'adoptive' => 'adoptive',
            'foster' => 'foster',
            'guardian', 'guardianship' => 'guardian',
            'step', 'stepchild' => 'step',
            'sealing', 'sealed' => 'sealing',
            default => $normalized,
        };
    }
}
