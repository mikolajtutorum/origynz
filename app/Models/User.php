<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Onboard\Concerns\GetsOnboarded;
use Spatie\Onboard\Concerns\Onboardable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'first_name', 'middle_name', 'last_name', 'birth_date', 'country_of_residence', 'preferred_locale', 'ccpa_do_not_sell', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements Onboardable
{
    /** @use HasFactory<UserFactory> */
    use GetsOnboarded, HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, TwoFactorAuthenticatable;

    protected static function boot(): void
    {
        parent::boot();
        static::deleting(function (User $user) {
            \Spatie\Activitylog\Models\Activity::where('causer_id', $user->id)
                ->where('causer_type', self::class)
                ->delete();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function fullName(): string
    {
        $fullName = collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->implode(' ');

        return $fullName !== '' ? $fullName : $this->name;
    }

    /**
     * @return HasOne<Site, $this>
     */
    public function ownedSite(): HasOne
    {
        return $this->hasOne(Site::class);
    }

    /**
     * @return HasMany<FamilyTree, $this>
     */
    public function familyTrees(): HasMany
    {
        return $this->hasMany(FamilyTree::class);
    }

    /**
     * @return HasMany<Person, $this>
     */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class, 'created_by');
    }

    /**
     * @return HasMany<SocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function isSuperAdmin(): bool
    {
        $tables   = config('permission.table_names');
        $columns  = config('permission.column_names');
        $teamKey  = $columns['team_foreign_key'] ?? 'family_tree_id';
        $morphKey = $columns['model_morph_key'] ?? 'model_id';

        return \Illuminate\Support\Facades\DB::table($tables['model_has_roles'].' as model_roles')
            ->join($tables['roles'].' as roles', 'roles.id', '=', 'model_roles.role_id')
            ->where('model_roles.'.$teamKey, \App\Support\Authorization\TreeAccessService::SITE_TEAM_ID)
            ->where('model_roles.model_type', self::class)
            ->where('model_roles.'.$morphKey, $this->getKey())
            ->where('roles.name', \App\Enums\SiteRole::SuperAdmin->value)
            ->exists();
    }
}
