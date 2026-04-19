<?php

namespace App\Jobs;

use App\Models\FamilyTree;
use App\Models\User;
use App\Support\Gedcom\GedcomImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ImportGedcomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        private readonly string $importId,
        private readonly int $treeId,
        private readonly string $filePath,
        private readonly int $userId,
    ) {}

    public function handle(GedcomImporter $importer): void
    {
        $this->updateProgress(0, 'Starting import...');

        $tree = FamilyTree::findOrFail($this->treeId);
        $user = User::findOrFail($this->userId);

        $result = $importer->importFromPath(
            $tree,
            $this->filePath,
            $user,
            fn (int $pct, string $message) => $this->updateProgress($pct, $message),
        );

        Cache::put("gedcom_import_{$this->importId}", [
            'status' => 'done',
            'progress' => 100,
            'message' => sprintf(
                'Import complete! %d %s and %d %s added.',
                $result['people_created'],
                $result['people_created'] === 1 ? 'person' : 'people',
                $result['relationships_created'],
                $result['relationships_created'] === 1 ? 'relationship' : 'relationships',
            ),
            'user_id' => $this->userId,
            'tree_id' => $tree->id,
            'first_person_id' => $result['first_person_id'],
            'owner_selection_required' => $result['owner_selection_required'],
            'people_created' => $result['people_created'],
            'relationships_created' => $result['relationships_created'],
        ], now()->addHour());

        activity('audit')
            ->causedBy($user)
            ->performedOn($tree)
            ->event('imported')
            ->withProperties([
                'import_id' => $this->importId,
                'people_created' => $result['people_created'],
                'relationships_created' => $result['relationships_created'],
                'owner_selection_required' => $result['owner_selection_required'],
            ])
            ->log('imported gedcom');

        @unlink($this->filePath);
    }

    public function failed(Throwable $e): void
    {
        Cache::put("gedcom_import_{$this->importId}", [
            'status' => 'failed',
            'progress' => 0,
            'message' => 'Import failed: '.$e->getMessage(),
            'user_id' => $this->userId,
        ], now()->addHour());

        $activity = activity('audit')
            ->causedBy(User::find($this->userId))
            ->event('import_failed')
            ->withProperties([
                'import_id' => $this->importId,
                'tree_id' => $this->treeId,
                'error' => $e->getMessage(),
            ]);

        if ($tree = FamilyTree::find($this->treeId)) {
            $activity->performedOn($tree);
        }

        $activity->log('failed gedcom import');

        @unlink($this->filePath);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return [
            'gedcom-import',
            'import:'.$this->importId,
            'tree:'.$this->treeId,
            'user:'.$this->userId,
        ];
    }

    private function updateProgress(int $pct, string $message): void
    {
        Cache::put("gedcom_import_{$this->importId}", [
            'status' => 'processing',
            'progress' => $pct,
            'message' => $message,
            'user_id' => $this->userId,
        ], now()->addHour());
    }
}
