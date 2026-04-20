<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    use HasUuids;
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'provider_email',
        'avatar_url',
        'token',
        'refresh_token',
        'expires_in',
    ];

    protected function casts(): array
    {
        return [
            'expires_in' => 'int',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
