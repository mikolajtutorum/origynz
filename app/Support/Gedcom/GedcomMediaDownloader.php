<?php

namespace App\Support\Gedcom;

use App\Models\MediaItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GedcomMediaDownloader
{
    public function isRemoteReference(?string $reference): bool
    {
        return $reference !== null && $reference !== '' && filter_var($reference, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Resolve a stored file name for a media reference without performing any network I/O.
     */
    public function resolveFileName(string $fileReference, ?string $fallbackTitle = null, ?string $mimeType = null): string
    {
        $path = parse_url($fileReference, PHP_URL_PATH) ?: $fileReference;
        $candidate = basename((string) $path);

        if ($candidate === '' || $candidate === '.' || $candidate === '/' || ! str_contains($candidate, '.')) {
            $base = Str::slug((string) ($fallbackTitle ?: 'imported-media'));
            $extension = $this->extensionFromMimeType($mimeType);

            return trim(($base !== '' ? $base : 'imported-media').($extension ? '.'.$extension : ''), '.');
        }

        return $candidate;
    }

    /**
     * Download a remote media item's file and persist it locally, updating the model.
     * Returns true when the file was downloaded; false when it could not be fetched
     * (the external_reference is kept so it can be retried or shown to the user).
     */
    public function download(MediaItem $media): bool
    {
        $reference = (string) $media->external_reference;

        if ($media->file_path !== null || ! $this->isRemoteReference($reference)) {
            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'KinfolkAtlasGedcomImporter/1.0',
                ])
                ->get($reference);
        } catch (\Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $body = $response->body();

        if ($body === '') {
            return false;
        }

        $mimeType = $response->header('Content-Type');
        $fileName = $this->resolveFileName($reference, $media->title, $mimeType);
        $path = 'media-items/imported/'.Str::uuid()->toString().'-'.$fileName;

        Storage::disk('local')->put($path, $body);

        $media->update([
            'file_path' => $path,
            'file_name' => $fileName,
            'mime_type' => $mimeType ?: $media->mime_type,
            'file_size' => strlen($body),
        ]);

        return true;
    }

    private function extensionFromMimeType(?string $mimeType): ?string
    {
        if (! $mimeType) {
            return null;
        }

        $normalized = strtolower(trim(explode(';', $mimeType)[0]));

        return match ($normalized) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tif',
            'application/pdf' => 'pdf',
            default => null,
        };
    }
}
