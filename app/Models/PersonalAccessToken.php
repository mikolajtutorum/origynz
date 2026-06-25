<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * UUID-keyed Sanctum token. The personal_access_tokens table uses a uuid primary
 * key (see migration), so the model must generate ordered UUIDs on create, just
 * like every other model in this app.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;
}
