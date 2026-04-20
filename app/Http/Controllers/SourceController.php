<?php

namespace App\Http\Controllers;

use App\Enums\TreePermission;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\Source;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SourceController extends Controller
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
            'title' => ['required', 'string', 'max:160'],
            'author' => ['nullable', 'string', 'max:160'],
            'publication_facts' => ['nullable', 'string', 'max:255'],
            'repository' => ['nullable', 'string', 'max:160'],
            'call_number' => ['nullable', 'string', 'max:160'],
            'url' => ['nullable', 'url', 'max:255'],
            'text' => ['nullable', 'string', 'max:4000'],
            'source_quality' => ['nullable', 'integer', 'between:0,3'],
            'page' => ['nullable', 'string', 'max:255'],
            'quotation' => ['nullable', 'string', 'max:4000'],
            'citation_note' => ['nullable', 'string', 'max:4000'],
            'citation_quality' => ['nullable', 'integer', 'between:0,3'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var Person $person */
        $person = $tree->people()->findOrFail($data['person_id']);

        $source = $tree->sources()->create([
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'author' => $data['author'] ?? null,
            'publication_facts' => $data['publication_facts'] ?? null,
            'repository' => $data['repository'] ?? null,
            'call_number' => $data['call_number'] ?? null,
            'url' => $data['url'] ?? null,
            'text' => $data['text'] ?? null,
            'quality' => $data['source_quality'] ?? null,
        ]);

        $person->sourceCitations()->create([
            'source_id' => $source->id,
            'page' => $data['page'] ?? null,
            'quotation' => $data['quotation'] ?? null,
            'note' => $data['citation_note'] ?? null,
            'quality' => $data['citation_quality'] ?? $data['source_quality'] ?? null,
        ]);

        return redirect()->to($this->workspaceRedirect($request, $tree))
            ->with('status', 'Source citation added.');
    }

    private function workspaceRedirect(Request $request, FamilyTree $tree): string
    {
        $returnTo = $request->string('return_to')->trim()->value();

        if ($returnTo !== '') {
            return $returnTo;
        }

        return route('trees.show', $tree);
    }
}
