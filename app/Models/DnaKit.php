<?php

namespace App\Models;

use App\Enums\DnaProvider;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $user_id
 * @property string|null $person_id
 * @property DnaProvider $provider
 * @property string|null $kit_name
 * @property string $file_path
 * @property int $snp_count
 * @property string|null $haplogroup_y
 * @property string|null $haplogroup_mt
 * @property array<string, mixed>|null $ancestry_composition
 * @property \Carbon\Carbon|null $sample_date
 * @property string|null $notes
 * @property-read User $user
 * @property-read Person|null $person
 */
class DnaKit extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'person_id',
        'provider',
        'kit_name',
        'file_path',
        'snp_count',
        'haplogroup_y',
        'haplogroup_mt',
        'ancestry_composition',
        'sample_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'provider'             => DnaProvider::class,
            'snp_count'            => 'integer',
            'ancestry_composition' => 'array',
            'sample_date'          => 'date',
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
}
