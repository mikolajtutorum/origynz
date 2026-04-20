<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonSourceCitation extends Model
{
    use HasUuids;
    protected $fillable = [
        'person_id',
        'source_id',
        'page',
        'quotation',
        'note',
        'quality',
        'event_name',
        'role',
        'entry_date_text',
        'entry_text',
    ];

    protected function casts(): array
    {
        return [
            'quality' => 'int',
        ];
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * @return BelongsTo<Source, $this>
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
