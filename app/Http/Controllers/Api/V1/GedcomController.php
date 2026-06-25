<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TreeAccessLevel;
use App\Enums\TreePermission;
use App\Http\Controllers\Controller;
use App\Jobs\ImportGedcomJob;
use App\Models\FamilyTree;
use App\Support\Authorization\TreeAccessService;
use App\Support\Gedcom\GedcomExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GedcomController extends Controller
{
    public function __construct(private readonly TreeAccessService $treeAccess) {}

    /**
     * Download a tree as a .ged file. (Bearer-authenticated.)
     */
    public function export(Request $request, FamilyTree $tree, GedcomExporter $exporter): Response
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $content = $exporter->export($tree);
        $filename = (str($tree->name)->slug()->value() ?: 'family-tree').'.ged';

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Import a GEDCOM file into an existing tree (async). Returns an import id to poll.
     */
    public function import(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $data = $request->validate(['gedcom_file' => ['required', 'file', 'max:102400']]);

        return $this->dispatchImport($request, $tree, $data['gedcom_file']);
    }

    /**
     * Import a GEDCOM file, creating a new tree (or targeting tree_id).
     */
    public function importNew(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tree_id' => ['nullable', 'string'],
            'tree_name' => ['nullable', 'string', 'max:120'],
            'gedcom_file' => ['required', 'file', 'max:102400'],
        ]);

        if (filled($data['tree_id'] ?? null)) {
            $tree = FamilyTree::query()->manageableBy($request->user())->findOrFail($data['tree_id']);
        } else {
            $user = $request->user();
            $fallback = pathinfo((string) $data['gedcom_file']->getClientOriginalName(), PATHINFO_FILENAME);
            $name = trim((string) ($data['tree_name'] ?? '')) ?: (str($fallback)->replace(['_', '-'], ' ')->squish()->limit(120, '')->value() ?: 'Imported family tree');

            $tree = $user->familyTrees()->create([
                'name' => $name,
                'description' => 'Created automatically from a GEDCOM import.',
                'home_region' => $user->country_of_residence,
                'privacy' => 'private',
            ]);
            $this->treeAccess->grantTreeAccess($user, $tree, TreeAccessLevel::Owner);
        }

        return $this->dispatchImport($request, $tree, $data['gedcom_file']);
    }

    /**
     * Poll import progress; includes tree_id + first_person_id once complete.
     */
    public function progress(Request $request, string $importId): JsonResponse
    {
        $data = Cache::get("gedcom_import_{$importId}");

        if (! $data) {
            return response()->json(['status' => 'not_found', 'progress' => 0, 'message' => 'Import not found.'], 404);
        }

        if (isset($data['user_id']) && $data['user_id'] !== $request->user()?->id) {
            abort(403);
        }

        return response()->json([
            'status' => $data['status'],
            'stage' => $data['stage'] ?? $data['status'],
            'progress' => $data['progress'],
            'message' => $data['message'],
            'current' => $data['current'] ?? null,
            'total' => $data['total'] ?? null,
            'tree_id' => $data['tree_id'] ?? null,
            'first_person_id' => $data['first_person_id'] ?? null,
            'people_created' => $data['people_created'] ?? null,
            'relationships_created' => $data['relationships_created'] ?? null,
            // True when the importer could not confidently pick the home person,
            // so the SPA should prompt the user to choose themselves.
            'owner_selection_required' => $data['owner_selection_required'] ?? false,
        ]);
    }

    private function dispatchImport(Request $request, FamilyTree $tree, mixed $file): JsonResponse
    {
        $importId = Str::uuid()->toString();
        $filename = $importId.'.ged';

        $file->storeAs('gedcom-temp', $filename, 'local');
        $filePath = Storage::disk('local')->path('gedcom-temp/'.$filename);

        Cache::put("gedcom_import_{$importId}", [
            'status' => 'queued',
            'stage' => 'queued',
            'progress' => 0,
            'message' => 'Queued for processing...',
            'user_id' => $request->user()->id,
            'tree_id' => $tree->id,
        ], now()->addHour());

        ImportGedcomJob::dispatch($importId, $tree->id, $filePath, $request->user()->id);

        return response()->json(['import_id' => $importId, 'tree_id' => $tree->id]);
    }
}
