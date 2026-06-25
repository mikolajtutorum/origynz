<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TreePermission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MediaResource;
use App\Models\FamilyTree;
use App\Models\MediaItem;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(private readonly TreeAccessService $treeAccess) {}

    /**
     * Media across all trees the user can see (the global library).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $treeIds = FamilyTree::visibleTo($request->user())->pluck('id');

        return MediaResource::collection(
            $this->filtered(MediaItem::query()->with('familyTree:id,name')->whereIn('family_tree_id', $treeIds), $request)
                ->latest('id')
                ->paginate(24),
        );
    }

    /**
     * Media for a single tree.
     */
    public function treeIndex(Request $request, FamilyTree $tree): AnonymousResourceCollection
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Observe);

        return MediaResource::collection(
            $this->filtered($tree->mediaItems()->getQuery(), $request)->latest('id')->paginate(24),
        );
    }

    public function show(Request $request, MediaItem $mediaItem): MediaResource
    {
        $this->treeAccess->authorize($request->user(), $mediaItem->familyTree, TreePermission::Observe);

        return new MediaResource($mediaItem);
    }

    /**
     * Upload a media item to a tree (optionally linked to a person).
     */
    public function store(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $personIds = $tree->people()->pluck('id')->all();

        $data = $request->validate([
            'person_id' => ['nullable', Rule::in($personIds)],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:4000'],
            'is_primary' => ['nullable', 'boolean'],
            'media_file' => ['required', 'file', 'max:12288'],
        ]);

        $file = $data['media_file'];
        $path = $file->store('media-items', 'local');

        $media = $tree->mediaItems()->create([
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

        return (new MediaResource($media))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, MediaItem $mediaItem): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $mediaItem->familyTree, TreePermission::Manage);

        if ($mediaItem->file_path) {
            Storage::disk('local')->delete($mediaItem->file_path);
        }
        $mediaItem->delete();

        return response()->json(['message' => 'Media removed.']);
    }

    /**
     * Stream a media file. Public but protected by a temporary signed URL so it
     * can be used directly as an <img> src in the SPA (where no bearer is sent).
     */
    public function signedFile(Request $request, MediaItem $mediaItem): StreamedResponse
    {
        abort_unless($mediaItem->file_path !== null, 404);

        if ($request->query('mode') === 'download') {
            return Storage::disk('local')->download($mediaItem->file_path, $mediaItem->file_name);
        }

        abort_unless(str_starts_with((string) $mediaItem->mime_type, 'image/'), 404);

        return Storage::disk('local')->response($mediaItem->file_path, $mediaItem->file_name, [
            'Content-Type' => $mediaItem->mime_type,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function filtered(Builder $query, Request $request): Builder
    {
        $q = $request->string('q')->trim()->value();
        if ($q !== '') {
            $query->where(fn ($b) => $b
                ->where('title', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%")
                ->orWhere('file_name', 'like', "%{$q}%"));
        }

        if ($request->string('kind')->value() === 'images') {
            $query->whereNotNull('file_path')->where('mime_type', 'like', 'image/%');
        }

        if ($request->string('linked')->value() === 'linked') {
            $query->whereNotNull('person_id');
        } elseif ($request->string('linked')->value() === 'unlinked') {
            $query->whereNull('person_id');
        }

        if ($treeId = $request->string('tree_id')->value()) {
            $query->where('family_tree_id', $treeId);
        }

        return $query;
    }
}
