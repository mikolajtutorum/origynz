<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasUuids, RecordsActivity;

    protected $fillable = [
        'family_tree_id',
        'created_by',
        'title',
        'author',
        'publication_facts',
        'repository',
        'call_number',
        'url',
        'text',
        'quality',
        'gedcom_rin',
        'gedcom_updated_at_text',
        'source_type',
        'source_medium',
    ];

    protected function casts(): array
    {
        return [
            'quality' => 'int',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PersonSourceCitation, $this>
     */
    public function citations(): HasMany
    {
        return $this->hasMany(PersonSourceCitation::class);
    }
}
