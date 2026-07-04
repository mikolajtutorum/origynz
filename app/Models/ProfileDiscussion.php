<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $person_id
 * @property string $user_id
 * @property string|null $parent_id
 * @property string $body
 * @property bool $is_deleted
 * @property-read User $user
 * @property-read Person $person
 * @property-read ProfileDiscussion|null $parent
 * @property-read Collection<int, ProfileDiscussion> $replies
 */
class ProfileDiscussion extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_id',
        'user_id',
        'parent_id',
        'body',
        'is_deleted',
    ];

    protected function casts(): array
    {
        return [
            'is_deleted' => 'bool',
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

    /** @return BelongsTo<ProfileDiscussion, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<ProfileDiscussion, $this> */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->with('user')->orderBy('created_at');
    }
}
