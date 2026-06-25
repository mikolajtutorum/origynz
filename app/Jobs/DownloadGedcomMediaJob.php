<?php

namespace App\Jobs;

use App\Models\MediaItem;
use App\Support\Gedcom\GedcomMediaDownloader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Downloads remote media referenced by a GEDCOM import, outside of the import
 * transaction so large/slow media sets can never time out the import itself.
 * Processes a bounded chunk per run and re-dispatches the remainder.
 */
class DownloadGedcomMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum media files downloaded per job run before re-dispatching the rest. */
    private const CHUNK = 25;

    public int $timeout = 600;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    /**
     * @param  list<string>  $mediaItemIds
     */
    public function __construct(private readonly array $mediaItemIds) {}

    public function handle(GedcomMediaDownloader $downloader): void
    {
        $batch = array_slice($this->mediaItemIds, 0, self::CHUNK);
        $remaining = array_slice($this->mediaItemIds, self::CHUNK);

        MediaItem::query()
            ->whereIn('id', $batch)
            ->whereNull('file_path')
            ->get()
            ->each(fn (MediaItem $media) => $downloader->download($media));

        if ($remaining !== []) {
            self::dispatch($remaining);
        }
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['gedcom-media-download'];
    }
}
