<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request): UserResource
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'birth_date' => ['nullable', 'date'],
            'country_of_residence' => ['nullable', 'string', 'max:120'],
            'preferred_locale' => ['nullable', 'string', 'max:10'],
        ]);

        // Re-verification on email change (only matters if the model enforces it).
        if ($data['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        $user->fill($data)->save();

        return new UserResource($user->fresh());
    }

    /**
     * Change the password (requires the current password).
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if (! Hash::check($request->string('current_password'), $user->password)) {
            throw ValidationException::withMessages(['current_password' => [__('The provided password is incorrect.')]]);
        }

        $user->forceFill(['password' => $request->string('password')])->save();

        return response()->json(['message' => __('Password updated.')]);
    }

    /**
     * Delete the account (requires the current password).
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => [__('The provided password is incorrect.')]]);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => __('Account deleted.')]);
    }
}
