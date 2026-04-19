<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ResolveSocialiteUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialiteController extends Controller
{
    /**
     * @return list<string>
     */
    private function configuredProviders(): array
    {
        return collect(config('services.socialite.providers', []))
            ->filter(function (string $provider): bool {
                $config = config("services.{$provider}");

                return filled($config['client_id'] ?? null)
                    && filled($config['client_secret'] ?? null)
                    && filled($config['redirect'] ?? null);
            })
            ->values()
            ->all();
    }

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, ResolveSocialiteUser $resolver): RedirectResponse
    {
        $this->ensureProviderIsSupported($provider);

        try {
            $socialiteUser = Socialite::driver($provider)->user();
            $user = $resolver->resolve($provider, $socialiteUser);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->withErrors([
                    'socialite' => __('We could not sign you in with :provider.', ['provider' => ucfirst($provider)]),
                ]);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('status', ucfirst($provider).' login successful.');
    }

    private function ensureProviderIsSupported(string $provider): void
    {
        abort_unless(in_array($provider, $this->configuredProviders(), true), 404);
    }
}
