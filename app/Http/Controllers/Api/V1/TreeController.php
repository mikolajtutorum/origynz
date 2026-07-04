<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TreeAccessLevel;
use App\Enums\TreePermission;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PersonResource;
use App\Http\Resources\Api\RelationshipResource;
use App\Http\Resources\Api\TreeResource;
use App\Models\FamilyTree;
use App\Models\MediaItem;
use App\Models\Site;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

class TreeController extends Controller
{
    public function __construct(private readonly TreeAccessService $treeAccess) {}

    /**
     * List trees accessible to the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $trees = FamilyTree::visibleTo($request->user())
            ->withCount('people')
            ->orderBy('name')
            ->paginate(20);

        return TreeResource::collection($trees);
    }

    /**
     * Create a new tree (plus the auto-provisioned owner person).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'home_region' => ['nullable', 'string', 'max:120'],
            'privacy' => ['required', 'in:private,invited,public'],
        ]);

        $user = $request->user();

        if (blank($data['home_region'] ?? null) && $user->country_of_residence) {
            $data['home_region'] = $user->country_of_residence;
        }

        $data['site_id'] = Site::forUser($user)->id;
        $tree = $user->familyTrees()->create($data);
        $this->treeAccess->grantTreeAccess($user, $tree, TreeAccessLevel::Owner);

        $ownerPerson = $tree->people()->create([
            'created_by' => $user->id,
            'given_name' => $user->first_name ?: $user->name,
            'surname' => $user->last_name ?: '',
            'sex' => 'unknown',
            'is_living' => true,
            'headline' => 'Account holder',
            'notes' => 'This profile was created automatically for the tree owner.',
        ]);

        $tree->update(['owner_person_id' => $ownerPerson->id]);
        $tree->loadCount('people');

        return (new TreeResource($tree))->response()->setStatusCode(201);
    }

    /**
     * Get a single tree.
     */
    public function show(Request $request, FamilyTree $tree): TreeResource
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Observe);
        $tree->loadCount('people');

        return new TreeResource($tree);
    }

    /**
     * Update tree settings.
     */
    public function update(Request $request, FamilyTree $tree): TreeResource
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'home_region' => ['nullable', 'string', 'max:120'],
            'privacy' => ['sometimes', 'required', 'in:private,invited,public'],
            // The home person the workspace centres on. Must be a (non-merged)
            // member of this tree so it can never point at another tree's person.
            'owner_person_id' => [
                'sometimes', 'required', 'string',
                Rule::exists('people', 'id')->where(fn ($query) => $query
                    ->where('family_tree_id', $tree->id)
                    ->whereNull('merged_into_id')),
            ],
        ]);

        $tree->update($data);
        $tree->loadCount('people');

        return new TreeResource($tree);
    }

    /**
     * List all people in a tree (paginated).
     */
    public function people(Request $request, FamilyTree $tree): AnonymousResourceCollection
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Observe);

        $people = $tree->people()
            ->whereNull('merged_into_id')
            ->orderBy('surname')
            ->orderBy('given_name')
            ->paginate(100);

        return PersonResource::collection($people);
    }

    /**
     * The full graph (people + relationships) for the interactive workspace.
     * Layout is computed client-side; this returns the raw data + access context.
     */
    public function graph(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Observe);

        $people = $tree->people()->whereNull('merged_into_id')->get();
        $relationships = $tree->relationships()->get();

        // Best avatar per person (personal photo > primary > first image).
        if ($people->isNotEmpty()) {
            $avatars = MediaItem::query()
                ->whereIn('person_id', $people->pluck('id'))
                ->whereNotNull('file_path')
                ->where('mime_type', 'like', 'image/%')
                ->orderByRaw('is_personal_photo DESC, is_primary DESC, id ASC')
                ->get(['id', 'person_id']);

            $byPerson = [];
            foreach ($avatars as $media) {
                if (isset($byPerson[$media->person_id])) {
                    continue;
                }
                $url = URL::temporarySignedRoute(
                    'api.media.file',
                    now()->addHours(6),
                    ['mediaItem' => $media->id, 'mode' => 'preview'],
                );
                // Host-relative for the same reason as MediaResource::signedFileUrl().
                $byPerson[$media->person_id] = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);
            }

            foreach ($people as $person) {
                $person->avatar_url = $byPerson[$person->id] ?? null;
            }
        }

        $level = $this->treeAccess->getTreeAccessLevel($request->user(), $tree);

        return response()->json([
            'tree' => (new TreeResource($tree->loadCount('people')))->resolve($request),
            'people' => PersonResource::collection($people)->resolve($request),
            'relationships' => RelationshipResource::collection($relationships)->resolve($request),
            'owner_person_id' => $tree->owner_person_id,
            'access_level' => $level?->value ?? TreeAccessLevel::Observer->value,
            'can_manage' => $this->treeAccess->can($request->user(), $tree, TreePermission::Manage),
        ]);
    }

    /**
     * Members with access to the tree and their access level.
     */
    public function members(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Observe);

        $members = $this->treeAccess->members($tree)->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'access_level' => $user->tree_access_level,
        ])->values();

        return response()->json(['data' => $members]);
    }
}
