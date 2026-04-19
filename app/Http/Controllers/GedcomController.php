<?php

namespace App\Http\Controllers;

use App\Enums\TreeAccessLevel;
use App\Enums\TreePermission;
use App\Jobs\ImportGedcomJob;
use App\Models\FamilyTree;
use App\Support\Authorization\TreeAccessService;
use App\Support\Gedcom\GedcomExporter;
use App\Support\Gedcom\GedcomImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GedcomController extends Controller
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function export(Request $request, FamilyTree $tree, GedcomExporter $exporter): Response
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $content = $exporter->export($tree);
        $filename = str($tree->name)->slug()->value() ?: 'family-tree';

        activity('audit')
            ->causedBy($request->user())
            ->performedOn($tree)
            ->event('exported')
            ->withProperties([
                'filename' => $filename.'.ged',
            ])
            ->log('exported gedcom');

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.ged"',
        ]);
    }

    public function import(Request $request, FamilyTree $tree): JsonResponse
    {
        $this->treeAccess->authorize($request->user(), $tree, TreePermission::Manage);

        $data = $request->validate([
            'gedcom_file' => ['required', 'file', 'max:102400'],
        ]);

        return $this->dispatchImport($request, $tree, $data['gedcom_file']);
    }

    public function importFromPage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tree_id' => ['nullable', 'integer'],
            'tree_name' => ['nullable', 'string', 'max:120'],
            'gedcom_file' => ['required', 'file', 'max:102400'],
        ]);

        $tree = filled($data['tree_id'] ?? null)
            ? FamilyTree::query()
                ->manageableBy($request->user())
                ->findOrFail((int) $data['tree_id'])
            : $this->createTreeForImport($request, $data);

        return $this->dispatchImport($request, $tree, $data['gedcom_file']);
    }

    public function importProgress(Request $request, string $importId): JsonResponse
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
            'progress' => $data['progress'],
            'message' => $data['message'],
        ]);
    }

    public function completeImport(Request $request, string $importId): RedirectResponse
    {
        $data = Cache::get("gedcom_import_{$importId}");

        if (! $data || $data['status'] !== 'done') {
            abort(404);
        }

        if (isset($data['user_id']) && $data['user_id'] !== $request->user()?->id) {
            abort(403);
        }

        $tree = FamilyTree::findOrFail($data['tree_id']);

        Cache::forget("gedcom_import_{$importId}");

        return redirect()
            ->route('trees.show', array_filter([
                'tree' => $tree,
                'focus' => $data['first_person_id'] ?? null,
            ], fn ($value) => $value !== null))
            ->with('status', $data['message'])
            ->with('owner_selection_required', $data['owner_selection_required'] ?? false);
    }

    /**
     * @param  array{tree_id?: int|string|null, tree_name?: string|null, gedcom_file: mixed}  $data
     */
    private function createTreeForImport(Request $request, array $data): FamilyTree
    {
        $user = $request->user();
        $uploadedFile = $data['gedcom_file'];
        $fallbackName = pathinfo((string) $uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $treeName = trim((string) ($data['tree_name'] ?? '')) ?: $this->normalizeImportedTreeName($fallbackName);

        $tree = $user->familyTrees()->create([
            'name' => $treeName,
            'description' => 'Created automatically from a GEDCOM import.',
            'home_region' => $user->country_of_residence,
            'privacy' => 'private',
        ]);

        $this->treeAccess->grantTreeAccess($user, $tree, TreeAccessLevel::Owner);

        return $tree;
    }

    private function normalizeImportedTreeName(string $name): string
    {
        $normalized = str($name)
            ->replace(['_', '-'], ' ')
            ->squish()
            ->trim()
            ->limit(120, '');

        return $normalized->value() !== '' ? $normalized->value() : 'Imported family tree';
    }

    private function dispatchImport(Request $request, FamilyTree $tree, mixed $file): JsonResponse
    {
        $importId = Str::uuid()->toString();
        $filename = $importId.'.ged';

        $file->storeAs('gedcom-temp', $filename, 'local');
        $filePath = Storage::disk('local')->path('gedcom-temp/'.$filename);

        Cache::put("gedcom_import_{$importId}", [
            'status' => 'queued',
            'progress' => 0,
            'message' => 'Queued for processing...',
            'user_id' => $request->user()->id,
        ], now()->addHour());

        ImportGedcomJob::dispatch($importId, $tree->id, $filePath, $request->user()->id);

        return response()->json(['import_id' => $importId]);
    }
}
