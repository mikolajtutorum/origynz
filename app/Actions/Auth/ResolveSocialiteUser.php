<?php

namespace App\Actions\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use App\Support\Authorization\TreeAccessService;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class ResolveSocialiteUser
{
    public function __construct(
        private readonly TreeAccessService $treeAccess,
    ) {}

    public function resolve(string $provider, SocialiteUser $socialiteUser): User
    {
        $providerId = (string) $socialiteUser->getId();
        $email = $this->normalizeEmail($socialiteUser->getEmail());

        $socialAccount = SocialAccount::query()
            ->with('user')
            ->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if ($socialAccount) {
            $this->updateSocialAccount($socialAccount, $socialiteUser, $email);

            return $socialAccount->user;
        }

        if (! $email) {
            abort(422, 'Your social account did not provide an email address. Please use a provider that shares your email address.');
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            [$firstName, $middleName, $lastName] = $this->splitName($socialiteUser->getName() ?: $email);

            $user = User::create([
                'name' => $socialiteUser->getName() ?: $email,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'preferred_locale' => app()->getLocale(),
                'email' => $email,
                'email_verified_at' => now(),
                'password' => Str::password(32),
            ]);

            $this->treeAccess->assignDefaultRole($user);
        }

        $this->treeAccess->syncPendingAccessForUser($user);

        SocialAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'provider_id' => $providerId,
                'provider_email' => $email,
                'avatar_url' => $socialiteUser->getAvatar(),
                'token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken,
                'expires_in' => $socialiteUser->expiresIn,
            ],
        );

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }

    private function updateSocialAccount(SocialAccount $socialAccount, SocialiteUser $socialiteUser, ?string $email): void
    {
        $socialAccount->update([
            'provider_email' => $email,
            'avatar_url' => $socialiteUser->getAvatar(),
            'token' => $socialiteUser->token,
            'refresh_token' => $socialiteUser->refreshToken,
            'expires_in' => $socialiteUser->expiresIn,
        ]);
    }

    private function normalizeEmail(?string $email): ?string
    {
        $normalized = strtolower(trim((string) $email));

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @return array{0:string,1:?string,2:string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_values(array_filter($parts, fn (string $part) => $part !== ''));

        if ($parts === []) {
            return ['Account', null, 'Owner'];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, ''];
        }

        $firstName = array_shift($parts);
        $lastName = array_pop($parts);
        $middleName = $parts !== [] ? Str::of(implode(' ', $parts))->trim()->toString() : null;

        return [$firstName ?: 'Account', $middleName, $lastName ?: 'Owner'];
    }
}
