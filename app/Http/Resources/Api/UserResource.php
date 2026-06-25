<?php

namespace App\Http\Resources\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'preferred_locale' => $this->preferred_locale,
            'two_factor_enabled' => ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at),
            'is_super_admin' => $this->isSuperAdmin(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
