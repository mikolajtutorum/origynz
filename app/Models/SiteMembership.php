<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $site_id
 * @property int $user_id
 * @property int $invited_by
 * @property string $trees_access  'all' | 'specific'
 * @property string $status        'pending' | 'accepted' | 'revoked'
 * @property \Illuminate\Support\Carbon|null $accepted_at
 */
class SiteMembership extends Model
{
    protected $fillable = [
        'site_id',
        'user_id',
        'invited_by',
        'trees_access',
        'status',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return ['accepted_at' => 'datetime'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
