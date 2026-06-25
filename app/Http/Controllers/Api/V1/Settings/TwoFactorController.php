<?php

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

/**
 * Headless two-factor management. Wraps Fortify's action classes (which the web
 * UI also uses) and returns the QR/secret/recovery codes as JSON.
 */
class TwoFactorController extends Controller
{
    /**
     * Current 2FA status + (when set up) QR/secret/recovery codes.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $enabled = ! is_null($user->two_factor_secret);
        $confirmed = ! is_null($user->two_factor_confirmed_at);

        return response()->json([
            'enabled' => $enabled,
            'confirmed' => $confirmed,
            'qr_svg' => $enabled ? $user->twoFactorQrCodeSvg() : null,
            'secret' => $enabled ? decrypt($user->two_factor_secret) : null,
            'recovery_codes' => $enabled && $confirmed ? $user->recoveryCodes() : [],
        ]);
    }

    /**
     * Begin enrollment: generates the secret + recovery codes (not yet confirmed).
     */
    public function store(Request $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $this->confirmPassword($request);

        $enable($request->user());

        return $this->show($request);
    }

    /**
     * Confirm enrollment with a TOTP code.
     */
    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $confirm($request->user(), $request->string('code')->toString());

        return $this->show($request);
    }

    /**
     * Regenerate recovery codes.
     */
    public function recoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {
        $this->confirmPassword($request);

        $generate($request->user());

        return response()->json(['recovery_codes' => $request->user()->fresh()->recoveryCodes()]);
    }

    /**
     * Disable 2FA entirely.
     */
    public function destroy(Request $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $this->confirmPassword($request);

        $disable($request->user());

        return response()->json(['enabled' => false, 'confirmed' => false]);
    }

    private function confirmPassword(Request $request): void
    {
        $request->validate(['current_password' => ['required', 'string']]);

        if (! Hash::check($request->string('current_password'), $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => [__('The provided password is incorrect.')]]);
        }
    }
}
