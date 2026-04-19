<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $family_tree_id
 * @property int $person_id
 * @property int $related_person_id
 * @property string $type
 * @property string|null $start_date_text
 * @property string|null $end_date_text
 * @property string|null $place
 * @property string|null $subtype
 * @property string|null $description
 * @property-read FamilyTree $familyTree
 * @property-read Person $person
 * @property-read Person $relatedPerson
 */
class PersonRelationship extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'family_tree_id',
        'person_id',
        'related_person_id',
        'type',
        'start_date',
        'start_date_text',
        'end_date',
        'end_date_text',
        'place',
        'subtype',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<FamilyTree, $this>
     */
    public function familyTree(): BelongsTo
    {
        return $this->belongsTo(FamilyTree::class);
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function relatedPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'related_person_id');
    }
}
