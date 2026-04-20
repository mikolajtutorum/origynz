<?php

namespace App\Models;

use App\Concerns\RecordsActivity;
use App\Enums\SiteRole;
use App\Enums\TreeAccessLevel;
use App\Enums\TreePermission;
use App\Support\Authorization\TreeAccessService;
use Database\Factories\FamilyTreeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $site_id
 * @property int|null $owner_person_id
 * @property string $name
 * @property string|null $description
 * @property string|null $home_region
 * @property string $privacy
 * @property string|null $gedcom_source_system
 * @property string|null $gedcom_source_version
 * @property string|null $gedcom_language
 * @property string|null $gedcom_destination
 * @property string|null $gedcom_exported_at_text
 * @property string|null $gedcom_file_label
 * @property string|null $gedcom_project_guid
 * @property string|null $gedcom_site_id
 * @property bool $global_tree_enabled
 * @property bool $show_birthdays_in_events
 * @property bool $show_wedding_anniversaries_in_events
 * @property bool $show_death_anniversaries_in_events
 * @property-read User $user
 * @property-read Site|null $site
 * @property-read Person|null $ownerPerson
 */
class FamilyTree extends Model
{
    /** @use HasFactory<FamilyTreeFactory> */
    use HasFactory, HasUuids, RecordsActivity;

    protected $fillable = [
        'user_id',
        'site_id',
        'owner_person_id',
        'name',
        'description',
        'home_region',
        'privacy',
        'global_tree_enabled',
        'show_birthdays_in_events',
        'show_wedding_anniversaries_in_events',
        'show_death_anniversaries_in_events',
        'gedcom_source_system',
        'gedcom_source_version',
        'gedcom_language',
        'gedcom_destination',
        'gedcom_exported_at_text',
        'gedcom_file_label',
        'gedcom_project_guid',
        'gedcom_site_id',
    ];

    protected static function booted(): void
    {
        static::created(function (self $tree): void {
            if ($tree->relationLoaded('user')) {
                $owner = $tree->user;
            } else {
                $owner = $tree->user()->first();
            }

            if ($owner) {
                app(TreeAccessService::class)->grantTreeAccess($owner, $tree, TreeAccessLevel::Owner);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'global_tree_enabled' => 'bool',
            'show_birthdays_in_events' => 'bool',
            'show_wedding_anniversaries_in_events' => 'bool',
            'show_death_anniversaries_in_events' => 'bool',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function ownerPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'owner_person_id');
    }

    /**
     * @return HasMany<Person, $this>
     */
    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    /**
     * @return HasMany<PersonRelationship, $this>
     */
    public function relationships(): HasMany
    {
        return $this->hasMany(PersonRelationship::class);
    }

    /**
     * @return HasMany<MediaItem, $this>
     */
    public function mediaItems(): HasMany
    {
        return $this->hasMany(MediaItem::class);
    }

    /**
     * @return HasMany<Source, $this>
     */
    public function sources(): HasMany
    {
        return $this->hasMany(Source::class);
    }

    /**
     * @return HasMany<PersonEvent, $this>
     */
    public function personEvents(): HasMany
    {
        return $this->hasMany(PersonEvent::class);
    }

    /**
     * @return HasMany<FamilyTreeInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(FamilyTreeInvitation::class);
    }

    /**
     * @return HasMany<FamilyTreeMembershipRequest, $this>
     */
    public function membershipRequests(): HasMany
    {
        return $this->hasMany(FamilyTreeMembershipRequest::class);
    }

    /**
     * @return Builder<User>
     */
    public function authorizedUsers(): Builder
    {
        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $teamKey = $columns['team_foreign_key'] ?? 'family_tree_id';
        $morphKey = $columns['model_morph_key'] ?? 'model_id';

        return User::query()
            ->whereExists(function (QueryBuilder $query) use ($tables, $teamKey, $morphKey): void {
                $query->selectRaw('1')
                    ->from($tables['model_has_permissions'].' as model_permissions')
                    ->join($tables['permissions'].' as permissions', 'permissions.id', '=', 'model_permissions.permission_id')
                    ->whereColumn('model_permissions.'.$morphKey, 'users.id')
                    ->where('model_permissions.model_type', User::class)
                    ->where('model_permissions.'.$teamKey, $this->id)
                    ->whereIn('permissions.name', TreePermission::visibilityValues())
                    ->where('permissions.guard_name', 'web');
            });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($this->userHasSiteRole($user, [SiteRole::SuperAdmin->value, SiteRole::Admin->value])) {
            return $query;
        }

        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $teamKey = $columns['team_foreign_key'] ?? 'family_tree_id';
        $morphKey = $columns['model_morph_key'] ?? 'model_id';

        return $query->where(function (Builder $q) use ($user, $tables, $teamKey, $morphKey): void {
            $q->whereExists(function (QueryBuilder $subquery) use ($user, $tables, $teamKey, $morphKey): void {
                $subquery->selectRaw('1')
                    ->from($tables['model_has_permissions'].' as model_permissions')
                    ->join($tables['permissions'].' as permissions', 'permissions.id', '=', 'model_permissions.permission_id')
                    ->whereColumn('model_permissions.'.$teamKey, 'family_trees.id')
                    ->where('model_permissions.model_type', User::class)
                    ->where('model_permissions.'.$morphKey, $user->getKey())
                    ->whereIn('permissions.name', TreePermission::visibilityValues())
                    ->where('permissions.guard_name', 'web');
            })->orWhereExists(function (QueryBuilder $subquery) use ($user): void {
                $subquery->selectRaw('1')
                    ->from('site_memberships')
                    ->whereColumn('site_memberships.site_id', 'family_trees.site_id')
                    ->where('site_memberships.user_id', $user->getKey())
                    ->where('site_memberships.status', 'accepted')
                    ->where('site_memberships.trees_access', 'all');
            });
        });
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($this->userHasSiteRole($user, [SiteRole::SuperAdmin->value, SiteRole::Admin->value])) {
            return $query;
        }

        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $teamKey = $columns['team_foreign_key'] ?? 'family_tree_id';
        $morphKey = $columns['model_morph_key'] ?? 'model_id';

        return $query->whereExists(function (QueryBuilder $subquery) use ($user, $tables, $teamKey, $morphKey): void {
            $subquery->selectRaw('1')
                ->from($tables['model_has_permissions'].' as model_permissions')
                ->join($tables['permissions'].' as permissions', 'permissions.id', '=', 'model_permissions.permission_id')
                ->whereColumn('model_permissions.'.$teamKey, 'family_trees.id')
                ->where('model_permissions.model_type', User::class)
                ->where('model_permissions.'.$morphKey, $user->getKey())
                ->whereIn('permissions.name', [
                    TreePermission::Owner->value,
                    TreePermission::Manage->value,
                ])
                ->where('permissions.guard_name', 'web');
        });
    }

    /**
     * @param  list<string>  $roles
     */
    private function userHasSiteRole(User $user, array $roles): bool
    {
        $tables = config('permission.table_names');
        $columns = config('permission.column_names');
        $teamKey = $columns['team_foreign_key'] ?? 'family_tree_id';
        $morphKey = $columns['model_morph_key'] ?? 'model_id';

        return DB::table($tables['model_has_roles'].' as model_roles')
            ->join($tables['roles'].' as roles', 'roles.id', '=', 'model_roles.role_id')
            ->where('model_roles.'.$teamKey, \App\Support\Authorization\TreeAccessService::SITE_TEAM_ID)
            ->where('model_roles.model_type', User::class)
            ->where('model_roles.'.$morphKey, $user->getKey())
            ->whereIn('roles.name', $roles)
            ->exists();
    }
}
