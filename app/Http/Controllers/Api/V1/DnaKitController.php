<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TreePermission;
use App\Http\Controllers\Controller;
use App\Models\DnaKit;
use App\Models\Person;
use App\Services\DnaImportService;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DnaKitController extends Controller
{
    public function __construct(
        private readonly DnaImportService $importer,
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $kits = DnaKit::query()
            ->where('user_id', $request->user()->id)
            ->with('person:id,given_name,surname')
            ->latest()
            ->get()
            ->map(fn (DnaKit $kit) => $this->present($kit));

        return response()->json(['data' => $kits]);
    }

    public function store(Request $request): JsonResponse
    {
        $maxKb = (int) config('integrations.dna.max_size_mb', 150) * 1024;

        $validated = $request->validate([
            'file' => ['required', 'file', "max:{$maxKb}"],
            'person_id' => ['nullable', 'string', 'exists:people,id'],
        ]);

        $personId = $validated['person_id'] ?? null;
        if ($personId) {
            $this->authorizePerson($request, $personId);
        }

        $kit = $this->importer->import($request->user(), $request->file('file'), $personId);

        return response()->json($this->present($kit), 201);
    }

    public function update(Request $request, DnaKit $dnaKit): JsonResponse
    {
        abort_unless($dnaKit->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'kit_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'person_id' => ['sometimes', 'nullable', 'string', 'exists:people,id'],
        ]);

        if (! empty($validated['person_id'])) {
            $this->authorizePerson($request, $validated['person_id']);
        }

        $dnaKit->update($validated);

        return response()->json($this->present($dnaKit->fresh(['person'])));
    }

    public function destroy(Request $request, DnaKit $dnaKit): JsonResponse
    {
        abort_unless($dnaKit->user_id === $request->user()->id, 403);

        $disk = config('integrations.dna.disk', 'local');
        if ($dnaKit->file_path && Storage::disk($disk)->exists($dnaKit->file_path)) {
            Storage::disk($disk)->delete($dnaKit->file_path);
        }

        $dnaKit->delete();

        return response()->json(['deleted' => true]);
    }

    // -------------------------------------------------------------------------

    private function authorizePerson(Request $request, string $personId): void
    {
        $person = Person::with('familyTree')->findOrFail($personId);
        abort_unless(
            $this->treeAccess->can($request->user(), $person->familyTree, TreePermission::Observe),
            403,
            __('You cannot link a kit to that person.'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function present(DnaKit $kit): array
    {
        return [
            'id' => $kit->id,
            'provider' => $kit->provider->value,
            'provider_label' => $kit->provider->label(),
            'kit_name' => $kit->kit_name,
            'snp_count' => $kit->snp_count,
            'haplogroup_y' => $kit->haplogroup_y,
            'haplogroup_mt' => $kit->haplogroup_mt,
            'ancestry_composition' => $kit->ancestry_composition,
            'sample_date' => $kit->sample_date?->toDateString(),
            'notes' => $kit->notes,
            'person_id' => $kit->person_id,
            'person_name' => $kit->person?->display_name,
            'created_at' => $kit->created_at?->toIso8601String(),
        ];
    }
}
