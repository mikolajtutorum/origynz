<?php

namespace App\Http\Resources\Api;

use App\Models\MediaItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin MediaItem
 */
class MediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isImage = str_starts_with((string) $this->mime_type, 'image/');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'is_image' => $isImage,
            'is_primary' => (bool) $this->is_primary,
            'person_id' => $this->person_id,
            'family_tree_id' => $this->family_tree_id,
            'tree_name' => $this->whenLoaded('familyTree', fn () => $this->familyTree->name),
            'preview_url' => $isImage ? $this->signedFileUrl('preview') : null,
            'download_url' => $this->signedFileUrl('download'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function signedFileUrl(string $mode): string
    {
        $url = URL::temporarySignedRoute('api.media.file', now()->addHours(6), [
            'mediaItem' => $this->id,
            'mode' => $mode,
        ]);

        // Host-relative so clients fetch it same-origin (works through the SPA
        // proxy, tunnels, and LAN). The signature stays valid because the proxy
        // pins the Host header to the URL's original host.
        return parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);
    }
}
