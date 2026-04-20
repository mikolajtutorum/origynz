<x-layouts::auth :title="__('Log in')">
    @php
        $socialProviders = collect(config('services.socialite.providers', []))
            ->filter(function (string $provider): bool {
                $config = config("services.{$provider}");

                return filled($config['client_id'] ?? null)
                    && filled($config['client_secret'] ?? null)
                    && filled($config['redirect'] ?? null);
            })
            ->values();
        $providerLabels = ['linkedin-openid' => 'LinkedIn'];
    @endphp

    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        @if ($errors->has('socialite'))
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('socialite') }}
            </div>
        @endif

        @if ($socialProviders->isNotEmpty())
            <div class="flex flex-col gap-3">
                @foreach ($socialProviders as $provider)
                    <a
                        href="{{ route('auth.social.redirect', ['provider' => $provider]) }}"
                        class="inline-flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50"
                    >
                        {{ __('Continue with :provider', ['provider' => $providerLabels[$provider] ?? ucfirst($provider)]) }}
                    </a>
                @endforeach
            </div>

            <div class="relative text-center text-xs uppercase tracking-[0.24em] text-zinc-400">
                <span class="bg-white px-3">{{ __('Or continue with email') }}</span>
                <div class="absolute inset-x-0 top-1/2 -z-10 h-px -translate-y-1/2 bg-zinc-200"></div>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf
            @honeypot

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
            </div>
        @endif
    </div>
</x-layouts::auth>
