<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string|null $description
 * @property-read User $user
 */
class Site extends Model
{
    protected $fillable = ['user_id', 'name', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function familyTrees(): HasMany
    {
        return $this->hasMany(FamilyTree::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SiteMembership::class);
    }

    /**
     * Get or lazily create the personal site for a user.
     */
    public static function forUser(User $user): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id],
            ['name'    => self::defaultNameFor($user)],
        );
    }

    /**
     * Scope: sites owned by or accepted-membership for the given user.
     *
     * @param  Builder<self>  $query
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id)
            ->orWhereHas('memberships', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)->where('status', 'accepted');
            });
    }

    private static function defaultNameFor(User $user): string
    {
        $full = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return ($full !== '' ? $full : $user->name)."'s site";
    }
}
