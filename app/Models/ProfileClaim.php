<?php

namespace App\Models;

use App\Enums\ProfileClaimStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string $person_id
 * @property ProfileClaimStatus $status
 * @property string|null $message
 * @property string|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property-read User $user
 * @property-read Person $person
 * @property-read User|null $reviewer
 */
class ProfileClaim extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'person_id',
        'status',
        'message',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProfileClaimStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
