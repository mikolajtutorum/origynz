<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $family_tree_id
 * @property int $person_id
 * @property int $created_by
 * @property string $type
 * @property string|null $label
 * @property string|null $category
 * @property CarbonInterface|null $event_date
 * @property string|null $event_date_text
 * @property string|null $place
 * @property string|null $value
 * @property string|null $age
 * @property string|null $cause
 * @property string|null $email
 * @property string|null $address_line1
 * @property string|null $city
 * @property string|null $country
 * @property string|null $description
 * @property int $sort_order
 */
class PersonEvent extends Model
{
    use HasUuids;

    protected $fillable = [
        'family_tree_id',
        'person_id',
        'created_by',
        'type',
        'label',
        'category',
        'event_date',
        'event_date_text',
        'place',
        'value',
        'age',
        'cause',
        'email',
        'address_line1',
        'city',
        'country',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'sort_order' => 'int',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
