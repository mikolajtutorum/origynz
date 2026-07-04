<?php

namespace App\Models;

use App\Enums\IntegrationProvider;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property IntegrationProvider $provider
 * @property string $access_token
 * @property string|null $refresh_token
 * @property Carbon|null $token_expires_at
 * @property string|null $provider_user_id
 * @property string|null $provider_username
 * @property array<string, mixed>|null $provider_meta
 * @property-read User $user
 * @property-read bool $token_is_expired
 */
class UserIntegration extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'provider',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'provider_user_id',
        'provider_username',
        'provider_meta',
    ];

    protected function casts(): array
    {
        return [
            'provider' => IntegrationProvider::class,
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'provider_meta' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTokenIsExpiredAttribute(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }
}
