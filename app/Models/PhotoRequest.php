<?php

namespace App\Models;

use App\Enums\PhotoRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $person_id
 * @property string $requested_by
 * @property string|null $findagrave_memorial_id
 * @property PhotoRequestStatus $status
 * @property string|null $notes
 * @property-read Person $person
 * @property-read User $requester
 */
class PhotoRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_id',
        'requested_by',
        'findagrave_memorial_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PhotoRequestStatus::class,
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
