<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TreePermission;
use App\Http\Controllers\Controller;
use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\PersonSourceCitation;
use App\Models\Source;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SourceController extends Controller
{
    public function __construct(private readonly TreeAccessService $treeAccess) {}

    // ── Sources (tree-level) ─────────────────────────────────────────────────

    public function index(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Observe);

        $sources = $tree->sources()
            ->withCount('citations')
            ->orderBy('title')
            ->get()
            ->map(fn (Source $s) => $this->presentSource($s));

        return response()->json(['data' => $sources]);
    }

    public function store(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $validated = $this->validateSource($request);

        $source = $tree->sources()->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($this->presentSource($source->loadCount('citations')), 201);
    }

    public function update(Request $request, Source $source): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $source->familyTree, TreePermission::Manage);

        $source->update($this->validateSource($request, partial: true));

        return response()->json($this->presentSource($source->loadCount('citations')));
    }

    public function destroy(Request $request, Source $source): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $source->familyTree, TreePermission::Manage);

        $source->delete();

        return response()->json(['deleted' => true]);
    }

    // ── Citations (link a source to a person) ─────────────────────────────────

    public function citations(Request $request, Person $person): JsonResponse
    {
        $this->authorizeView($request, $person);

        $citations = $person->sourceCitations()
            ->with('source:id,title,author,repository,url')
            ->get()
            ->map(fn (PersonSourceCitation $c) => $this->presentCitation($c));

        return response()->json(['data' => $citations]);
    }

    public function storeCitation(Request $request, Person $person): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $person->familyTree, TreePermission::Manage);

        $validated = $request->validate([
            'source_id' => [
                'required', 'string',
                // The source must live in the same tree as the person.
                Rule::exists('sources', 'id')->where(fn ($q) => $q->where('family_tree_id', $person->family_tree_id)),
            ],
            'page' => ['nullable', 'string', 'max:255'],
            'quotation' => ['nullable', 'string', 'max:5000'],
            'note' => ['nullable', 'string', 'max:5000'],
            'quality' => ['nullable', 'integer', 'between:0,3'],
            'event_name' => ['nullable', 'string', 'max:255'],
        ]);

        $citation = $person->sourceCitations()->create($validated);

        return response()->json(
            $this->presentCitation($citation->load('source:id,title,author,repository,url')),
            201,
        );
    }

    public function destroyCitation(Request $request, PersonSourceCitation $citation): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $citation->person->familyTree, TreePermission::Manage);

        $citation->delete();

        return response()->json(['deleted' => true]);
    }

    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function validateSource(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'publication_facts' => ['nullable', 'string', 'max:2000'],
            'repository' => ['nullable', 'string', 'max:255'],
            'call_number' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'url', 'max:2000'],
            'text' => ['nullable', 'string', 'max:10000'],
            'quality' => ['nullable', 'integer', 'between:0,3'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_medium' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function authorizeView(Request $request, Person $person): void
    {
        abort_unless(
            FamilyTree::visibleTo($request->user())->where('id', $person->family_tree_id)->exists(),
            403,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function presentSource(Source $s): array
    {
        return [
            'id' => $s->id,
            'title' => $s->title,
            'author' => $s->author,
            'publication_facts' => $s->publication_facts,
            'repository' => $s->repository,
            'call_number' => $s->call_number,
            'url' => $s->url,
            'text' => $s->text,
            'quality' => $s->quality,
            'source_type' => $s->source_type,
            'source_medium' => $s->source_medium,
            'citations_count' => $s->citations_count ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentCitation(PersonSourceCitation $c): array
    {
        return [
            'id' => $c->id,
            'source_id' => $c->source_id,
            'source_title' => $c->source?->title,
            'source_author' => $c->source?->author,
            'source_url' => $c->source?->url,
            'page' => $c->page,
            'quotation' => $c->quotation,
            'note' => $c->note,
            'quality' => $c->quality,
            'event_name' => $c->event_name,
        ];
    }
}
