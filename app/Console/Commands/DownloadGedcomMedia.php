<?php

namespace App\Console\Commands;

use App\Jobs\DownloadGedcomMediaJob;
use App\Models\MediaItem;
use App\Support\Gedcom\GedcomMediaDownloader;
use Illuminate\Console\Command;

class DownloadGedcomMedia extends Command
{
    protected $signature = 'gedcom:download-media
        {--tree= : Limit to a single family tree id}
        {--sync : Download immediately in this process instead of queueing}';

    protected $description = 'Download any imported media whose remote file has not been fetched yet (re-runs failed/pending GEDCOM media downloads).';

    public function handle(GedcomMediaDownloader $downloader): int
    {
        $query = MediaItem::query()
            ->whereNull('file_path')
            ->whereNotNull('external_reference');

        if ($treeId = $this->option('tree')) {
            $query->where('family_tree_id', $treeId);
        }

        $ids = $query->get(['id', 'external_reference'])
            ->filter(fn (MediaItem $media) => $downloader->isRemoteReference($media->external_reference))
            ->pluck('id')
            ->values();

        if ($ids->isEmpty()) {
            $this->info('No pending remote media to download.');

            return self::SUCCESS;
        }

        if ($this->option('sync')) {
            $this->withProgressBar($ids, function ($id) use ($downloader): void {
                if ($media = MediaItem::find($id)) {
                    $downloader->download($media);
                }
            });
            $this->newLine(2);
            $this->info("Downloaded media for {$ids->count()} item(s).");

            return self::SUCCESS;
        }

        DownloadGedcomMediaJob::dispatch($ids->all());
        $this->info("Queued {$ids->count()} media item(s) for download. Ensure a queue worker is running.");

        return self::SUCCESS;
    }
}
