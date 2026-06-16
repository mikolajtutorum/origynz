<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $surviving_person_id
 * @property string $absorbed_person_id
 * @property string $merged_by_user_id
 * @property array<string, string>|null $field_decisions
 * @property-read Person $survivingPerson
 * @property-read User $mergedBy
 */
class PersonMerge extends Model
{
    use HasUuids;

    protected $fillable = [
        'surviving_person_id',
        'absorbed_person_id',
        'merged_by_user_id',
        'field_decisions',
    ];

    protected function casts(): array
    {
        return [
            'field_decisions' => 'array',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function survivingPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'surviving_person_id');
    }

    /** @return BelongsTo<User, $this> */
    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by_user_id');
    }
}
