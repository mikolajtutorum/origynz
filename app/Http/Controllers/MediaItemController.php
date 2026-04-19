<?php

namespace App\Http\Controllers;

use App\Enums\TreePermission;
use App\Models\FamilyTree;
use App\Models\MediaItem;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaItemController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function store(Request $request, FamilyTree $tree): RedirectResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $personIds = $tree->people()->pluck('id')->all();

        $data = $request->validate([
            'person_id' => ['nullable', Rule::in($personIds)],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'is_primary' => ['nullable', 'boolean'],
            'media_file' => ['required', 'file', 'max:12288'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $file = $data['media_file'];
        $path = $file->store('media-items', 'local');

        $tree->mediaItems()->create([
            'person_id' => $data['person_id'] ?? null,
            'uploaded_by' => $request->user()->id,
            'title' => $data['title'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
            'description' => $data['description'] ?? null,
            'is_primary' => (bool) ($data['is_primary'] ?? false),
        ]);

        return redirect()->to($this->workspaceRedirect($request, $tree))
            ->with('status', 'Media item added.');
    }

    public function globalIndex(Request $request): View
    {
        $visibleTrees = FamilyTree::query()
            ->visibleTo($request->user())
            ->orderBy('name')
            ->get(['id', 'name']);

        $baseQuery = MediaItem::query()
            ->with([
                'familyTree:id,name',
                'person:id,given_name,middle_name,surname',
                'uploader:id,name',
            ])
            ->whereIn('family_tree_id', $visibleTrees->pluck('id'));

        $filteredQuery = $this->applyLibraryFilters(clone $baseQuery, $request);

        return view('media.index', [
            'tree' => null,
            'mediaItems' => $filteredQuery->latest('id')->paginate(24)->withQueryString(),
            'availableTrees' => $visibleTrees,
            'filters' => $this->libraryFilters($request),
            'stats' => $this->libraryStats(clone $filteredQuery),
            'isGlobalLibrary' => true,
            'activeNav' => 'photos',
        ]);
    }

    public function index(Request $request, FamilyTree $tree): View
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Observe);

        $baseQuery = $tree->mediaItems()
            ->with(['person:id,given_name,middle_name,surname', 'uploader:id,name', 'familyTree:id,name']);

        $filteredQuery = $this->applyLibraryFilters($baseQuery, $request);

        return view('media.index', [
            'tree' => $tree,
            'mediaItems' => $filteredQuery->latest('id')->paginate(24)->withQueryString(),
            'availableTrees' => collect([$tree])->map->only(['id', 'name']),
            'filters' => $this->libraryFilters($request),
            'stats' => $this->libraryStats(clone $filteredQuery),
            'isGlobalLibrary' => false,
            'activeNav' => 'photos',
        ]);
    }

    public function show(Request $request, MediaItem $mediaItem): View
    {
        $this->treeAccess->authorize($request->user(), $mediaItem->familyTree, TreePermission::Observe);

        $mediaItem->loadMissing([
            'familyTree:id,name',
            'person:id,given_name,middle_name,surname',
            'uploader:id,name',
        ]);

        $relatedMedia = $mediaItem->familyTree->mediaItems()
            ->whereKeyNot($mediaItem->getKey())
            ->latest('id')
            ->limit(8)
            ->get();

        return view('media.show', [
            'mediaItem' => $mediaItem,
            'tree' => $mediaItem->familyTree,
            'relatedMedia' => $relatedMedia,
            'activeNav' => 'photos',
        ]);
    }

    public function download(Request $request, MediaItem $mediaItem): StreamedResponse
    {
        $this->treeAccess->authorize($request->user(), $mediaItem->familyTree, TreePermission::Observe);
        abort_unless($mediaItem->file_path !== null, 404);

        return Storage::disk('local')->download($mediaItem->file_path, $mediaItem->file_name);
    }

    public function preview(Request $request, MediaItem $mediaItem): StreamedResponse
    {
        $this->treeAccess->authorize($request->user(), $mediaItem->familyTree, TreePermission::Observe);
        abort_unless($mediaItem->file_path !== null, 404);
        abort_unless(str_starts_with((string) $mediaItem->mime_type, 'image/'), 404);

        return Storage::disk('local')->response($mediaItem->file_path, $mediaItem->file_name, [
            'Content-Type' => $mediaItem->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function workspaceRedirect(Request $request, FamilyTree $tree): string
    {
        $returnTo = $request->string('return_to')->trim()->value();

        if ($returnTo !== '') {
            return $returnTo;
        }

        return route('trees.show', $tree);
    }

    private function applyLibraryFilters(Builder|HasMany $query, Request $request): Builder|HasMany
    {
        $filters = $this->libraryFilters($request);

        if ($filters['q'] !== '') {
            $search = $filters['q'];

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%");
            });
        }

        if ($filters['kind'] === 'images') {
            $query
                ->whereNotNull('file_path')
                ->where('mime_type', 'like', 'image/%');
        }

        if ($filters['linked'] === 'linked') {
            $query->whereNotNull('person_id');
        }

        if ($filters['linked'] === 'unlinked') {
            $query->whereNull('person_id');
        }

        if ($filters['tree_id'] !== null) {
            $query->where('family_tree_id', $filters['tree_id']);
        }

        return $query;
    }

    /**
     * @return array{q:string,kind:string,linked:string,tree_id:int|null}
     */
    private function libraryFilters(Request $request): array
    {
        $kind = $request->string('kind')->trim()->value();
        $linked = $request->string('linked')->trim()->value();
        $treeId = $request->integer('tree');

        return [
            'q' => $request->string('q')->trim()->value(),
            'kind' => in_array($kind, ['all', 'images'], true) ? $kind : 'all',
            'linked' => in_array($linked, ['all', 'linked', 'unlinked'], true) ? $linked : 'all',
            'tree_id' => $treeId > 0 ? $treeId : null,
        ];
    }

    /**
     * @return array{total:int,images:int,linked:int}
     */
    private function libraryStats(Builder|HasMany $query): array
    {
        return [
            'total' => (clone $query)->count(),
            'images' => (clone $query)
                ->whereNotNull('file_path')
                ->where('mime_type', 'like', 'image/%')
                ->count(),
            'linked' => (clone $query)->whereNotNull('person_id')->count(),
        ];
    }
}
