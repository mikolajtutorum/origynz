<x-layouts::auth :title="__('Register')">
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
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

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
                <span class="bg-white px-3">{{ __('Or create an account with email') }}</span>
                <div class="absolute inset-x-0 top-1/2 -z-10 h-px -translate-y-1/2 bg-zinc-200"></div>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            @honeypot
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex flex-col gap-3">
                <flux:checkbox name="terms" :checked="old('terms')" required>
                    <span>
                        {{ __('I agree to the') }}
                        <a href="{{ route('legal.terms') }}" target="_blank" class="underline text-blue-600 hover:text-blue-700">{{ __('Terms of Service') }}</a>
                        {{ __('and') }}
                        <a href="{{ route('legal.privacy') }}" target="_blank" class="underline text-blue-600 hover:text-blue-700">{{ __('Privacy Policy') }}</a>
                    </span>
                </flux:checkbox>
                @error('terms')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <flux:checkbox name="age_confirmation" :checked="old('age_confirmation')" required>
                    {{ __('I confirm I am 13 years of age or older') }}
                </flux:checkbox>
                @error('age_confirmation')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
