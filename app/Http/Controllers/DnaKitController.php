<?php

namespace App\Http\Controllers;

use App\Models\DnaKit;
use App\Services\DnaImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DnaKitController extends Controller
{
    public function __construct(
        private readonly DnaImportService $importer,
    ) {}

    public function index(Request $request): View
    {
        $kits = DnaKit::where('user_id', $request->user()->id)
            ->with('person:id,given_name,surname')
            ->latest()
            ->paginate(15);

        return view('integrations.dna.index', ['kits' => $kits]);
    }

    public function show(DnaKit $kit): View
    {
        abort_unless(auth()->id() === $kit->user_id, 403);

        $kit->load('person:id,given_name,middle_name,surname');

        return view('integrations.dna.show', ['kit' => $kit]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxMb = config('integrations.dna.max_size_mb', 150);

        $validated = $request->validate([
            'file'      => "required|file|max:".($maxMb * 1024),
            'person_id' => 'nullable|string|exists:people,id',
            'notes'     => 'nullable|string|max:1000',
        ]);

        $kit = $this->importer->import(
            $request->user(),
            $validated['file'],
            $validated['person_id'] ?? null,
        );

        if ($validated['notes'] ?? false) {
            $kit->update(['notes' => $validated['notes']]);
        }

        return redirect()->route('integrations.dna.show', $kit)
            ->with('success', __('DNA kit imported — :n SNPs processed.', ['n' => number_format($kit->snp_count)]));
    }

    public function destroy(DnaKit $kit): RedirectResponse
    {
        abort_unless(auth()->id() === $kit->user_id, 403);

        $disk = config('integrations.dna.disk', 'local');
        Storage::disk($disk)->delete($kit->file_path);
        $kit->delete();

        return redirect()->route('integrations.dna.index')
            ->with('success', __('DNA kit deleted.'));
    }
}
