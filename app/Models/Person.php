<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use Carbon\CarbonInterface;
use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $family_tree_id
 * @property string $created_by
 * @property string $given_name
 * @property string|null $middle_name
 * @property string|null $alternative_name
 * @property string $surname
 * @property string|null $birth_surname
 * @property string|null $prefix
 * @property string|null $suffix
 * @property string|null $nickname
 * @property string $sex
 * @property CarbonInterface|null $birth_date
 * @property string|null $birth_date_text
 * @property CarbonInterface|null $death_date
 * @property string|null $death_date_text
 * @property string|null $birth_place
 * @property string|null $death_place
 * @property string|null $cause_of_death
 * @property string|null $burial_place
 * @property bool $is_living
 * @property bool $exclude_from_global_tree
 * @property string|null $headline
 * @property string|null $notes
 * @property string|null $physical_description
 * @property string|null $gedcom_rin
 * @property string|null $gedcom_uid
 * @property string|null $gedcom_updated_at_text
 * @property-read FamilyTree $familyTree
 * @property-read User $creator
 * @property-read string $display_name
 * @property-read string $life_span
 * @property-read string $readable_life_span
 * @property-read string|null $readable_birth_date
 * @property-read string|null $readable_death_date
 */
class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory, HasUuids, RecordsActivity;

    protected $fillable = [
        'family_tree_id',
        'created_by',
        'given_name',
        'middle_name',
        'alternative_name',
        'surname',
        'birth_surname',
        'prefix',
        'suffix',
        'nickname',
        'sex',
        'birth_date',
        'birth_date_text',
        'death_date',
        'death_date_text',
        'birth_place',
        'death_place',
        'cause_of_death',
        'burial_place',
        'is_living',
        'exclude_from_global_tree',
        'trust_score',
        'merged_into_id',
        'findagrave_memorial_id',
        'billiongraves_id',
        'familysearch_person_id',
        'wikitree_id',
        'geni_profile_id',
        'headline',
        'notes',
        'physical_description',
        'gedcom_rin',
        'gedcom_uid',
        'gedcom_updated_at_text',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'death_date' => 'date',
            'is_living' => 'bool',
            'exclude_from_global_tree' => 'bool',
            'trust_score' => 'integer',
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
     * @return HasMany<PersonRelationship, $this>
     */
    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(PersonRelationship::class);
    }

    /**
     * @return HasMany<PersonRelationship, $this>
     */
    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(PersonRelationship::class, 'related_person_id');
    }

    /**
     * @return HasMany<MediaItem, $this>
     */
    public function mediaItems(): HasMany
    {
        return $this->hasMany(MediaItem::class);
    }

    /**
     * @return HasMany<PersonSourceCitation, $this>
     */
    public function sourceCitations(): HasMany
    {
        return $this->hasMany(PersonSourceCitation::class);
    }

    /**
     * @return HasMany<PersonEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(PersonEvent::class)->orderBy('sort_order')->orderBy('event_date')->orderBy('id');
    }

    /**
     * @return HasMany<ProfileDiscussion, $this>
     */
    public function discussions(): HasMany
    {
        return $this->hasMany(ProfileDiscussion::class)
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->orderByDesc('created_at');
    }

    /**
     * @return HasMany<ProfileWatch, $this>
     */
    public function watches(): HasMany
    {
        return $this->hasMany(ProfileWatch::class);
    }

    /**
     * @return HasMany<ProfileClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(ProfileClaim::class);
    }

    /**
     * @return HasMany<MergeCandidate, $this>
     */
    public function mergeCandidatesAsA(): HasMany
    {
        return $this->hasMany(MergeCandidate::class, 'person_a_id');
    }

    /**
     * @return HasMany<MergeCandidate, $this>
     */
    public function mergeCandidatesAsB(): HasMany
    {
        return $this->hasMany(MergeCandidate::class, 'person_b_id');
    }

    /**
     * @return HasMany<PhotoRequest, $this>
     */
    public function photoRequests(): HasMany
    {
        return $this->hasMany(PhotoRequest::class);
    }

    /**
     * @return HasMany<DnaKit, $this>
     */
    public function dnaKits(): HasMany
    {
        return $this->hasMany(DnaKit::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return collect([$this->given_name, $this->middle_name, $this->surname])
            ->filter()
            ->implode(' ');
    }

    public function getLifeSpanAttribute(): string
    {
        $birth = $this->birth_date?->format('Y') ?: $this->extractYear($this->birth_date_text);
        $death = $this->death_date?->format('Y') ?: $this->extractYear($this->death_date_text);

        if (! $birth && ! $death) {
            return $this->is_living ? '' : (string) __('Dates unknown');
        }

        if ($this->is_living && ! $death) {
            return $birth;
        }

        return trim(($birth ?: '?').' - '.($death ?: '?'));
    }

    public function getReadableLifeSpanAttribute(): string
    {
        $birth = $this->formatReadableDate($this->birth_date, $this->birth_date_text);
        $death = $this->formatReadableDate($this->death_date, $this->death_date_text);

        if (! $birth && ! $death) {
            return $this->is_living ? '' : (string) __('Dates unknown');
        }

        if ($this->is_living && ! $death) {
            return $birth;
        }

        return trim(($birth ?: '?').' - '.($death ?: '?'));
    }

    public function getReadableBirthDateAttribute(): ?string
    {
        return $this->formatReadableDate($this->birth_date, $this->birth_date_text);
    }

    public function getReadableDeathDateAttribute(): ?string
    {
        return $this->formatReadableDate($this->death_date, $this->death_date_text);
    }

    private function extractYear(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (preg_match('/(\d{1,4})\s*(B\.?C\.?E?\.?)/i', $value, $matches)) {
            return $matches[1].' BCE';
        }

        preg_match('/\b(\d{4})\b/', $value, $matches);

        return $matches[1] ?? null;
    }

    private function formatReadableDate(?CarbonInterface $date, ?string $text): ?string
    {
        if ($date) {
            return $date->format('j M Y');
        }

        if (! $text) {
            return null;
        }

        return trim($text);
    }
}
