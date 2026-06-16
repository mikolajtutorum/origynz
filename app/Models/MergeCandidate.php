<?php

namespace App\Models;

use App\Enums\MergeCandidateStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $person_a_id
 * @property string $person_b_id
 * @property int $similarity_score
 * @property MergeCandidateStatus $status
 * @property-read Person $personA
 * @property-read Person $personB
 */
class MergeCandidate extends Model
{
    use HasUuids;

    protected $fillable = [
        'person_a_id',
        'person_b_id',
        'similarity_score',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => MergeCandidateStatus::class,
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function personA(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_a_id');
    }

    /** @return BelongsTo<Person, $this> */
    public function personB(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_b_id');
    }
}
