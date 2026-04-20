<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaItem extends Model
{
    use HasUuids, RecordsActivity;

    protected $fillable = [
        'family_tree_id',
        'person_id',
        'uploaded_by',
        'title',
        'file_name',
        'file_path',
        'external_reference',
        'mime_type',
        'file_size',
        'description',
        'is_primary',
        'gedcom_rin',
        'gedcom_updated_at_text',
        'gedcom_external_id',
        'gedcom_parent_external_id',
        'crop_position',
        'is_cutout',
        'is_personal_photo',
        'is_parent_photo',
        'is_primary_cutout',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'int',
            'is_primary' => 'bool',
            'is_cutout' => 'bool',
            'is_personal_photo' => 'bool',
            'is_parent_photo' => 'bool',
            'is_primary_cutout' => 'bool',
        ];
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcept(): array
    {
        return [
            'created_at',
            'updated_at',
            'file_path',
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
