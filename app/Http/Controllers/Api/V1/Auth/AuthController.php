<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\Person;
use App\Models\PersonRelationship;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

/**
 * Token (bearer) authentication for the React SPA. Reuses Fortify's backend
 * actions/providers, but issues Sanctum personal access tokens instead of
 * starting a web session.
 */
class AuthController extends Controller
{
    /**
     * Register a new user and return an access token.
     */
    public function register(Request $request, CreatesNewUsers $creator): JsonResponse
    {
        // CreateNewUser validates name/email/password/terms/age_confirmation and
        // provisions the default tree + role.
        $user = $creator->create($request->all());

        event(new Registered($user));

        return $this->tokenResponse($user, $this->deviceName($request), 201);
    }

    /**
     * Exchange credentials for an access token (or signal that 2FA is required).
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($this->hasTwoFactorEnabled($user)) {
            return response()->json(['two_factor_required' => true]);
        }

        return $this->tokenResponse($user, $this->deviceName($request));
    }

    /**
     * Complete a two-factor challenge and return an access token.
     */
    public function twoFactorChallenge(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password) || ! $this->hasTwoFactorEnabled($user)) {
            throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
        }

        $valid = false;

        if ($code = $request->input('recovery_code')) {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?? [];
            if (in_array($code, $codes, true)) {
                $user->forceFill([
                    'two_factor_recovery_codes' => encrypt(json_encode(array_values(array_diff($codes, [$code])))),
                ])->save();
                $valid = true;
            }
        } elseif ($code = $request->input('code')) {
            $valid = $provider->verify(decrypt($user->two_factor_secret), $code);
        }

        if (! $valid) {
            throw ValidationException::withMessages(['code' => [__('The provided two factor authentication code was invalid.')]]);
        }

        return $this->tokenResponse($user, $this->deviceName($request));
    }

    /**
     * Revoke the access token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('Logged out.')]);
    }

    /**
     * The authenticated user.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Dashboard counts across the user's own trees.
     */
    public function stats(Request $request): JsonResponse
    {
        $treeIds = $request->user()->familyTrees()->pluck('id');

        return response()->json([
            'trees' => $treeIds->count(),
            'profiles' => Person::whereIn('family_tree_id', $treeIds)->count(),
            'living' => Person::whereIn('family_tree_id', $treeIds)->where('is_living', true)->count(),
            'relationships' => PersonRelationship::whereIn('family_tree_id', $treeIds)->count(),
        ]);
    }

    /**
     * Onboarding checklist (Spatie laravel-onboard) for the dashboard.
     */
    public function onboarding(Request $request): JsonResponse
    {
        $onboarding = $request->user()->onboarding();

        return response()->json([
            'in_progress' => $onboarding->inProgress(),
            'steps' => collect($onboarding->steps())->map(fn ($step) => [
                'title' => $step->title,
                'complete' => $step->complete(),
                'cta' => $step->cta,
                'link' => $step->link,
            ])->values(),
        ]);
    }

    private function hasTwoFactorEnabled(User $user): bool
    {
        return ! is_null($user->two_factor_secret) && ! is_null($user->two_factor_confirmed_at);
    }

    private function deviceName(Request $request): string
    {
        return $request->string('device_name')->toString() ?: 'spa';
    }

    private function tokenResponse(User $user, string $device, int $status = 200): JsonResponse
    {
        $token = $user->createToken($device)->plainTextToken;

        /** @var JsonResponse $response */
        $response = (new UserResource($user))
            ->additional(['token' => $token])
            ->response()
            ->setStatusCode($status);

        return $response;
    }
}
