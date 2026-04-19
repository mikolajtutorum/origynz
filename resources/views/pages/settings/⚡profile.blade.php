<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;

    public string $first_name = '';
    public ?string $middle_name = '';
    public string $last_name = '';
    public ?string $birth_date = '';
    public ?string $country_of_residence = '';
    public string $preferred_locale = 'en';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->first_name = Auth::user()->first_name ?? '';
        $this->middle_name = Auth::user()->middle_name ?? '';
        $this->last_name = Auth::user()->last_name ?? '';
        $this->birth_date = Auth::user()->birth_date?->format('Y-m-d') ?? '';
        $this->country_of_residence = Auth::user()->country_of_residence ?? '';
        $this->preferred_locale = Auth::user()->preferred_locale ?? app()->getLocale();
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $this->birth_date = blank($this->birth_date) ? null : $this->birth_date;
        $this->middle_name = blank($this->middle_name) ? null : $this->middle_name;
        $this->country_of_residence = blank($this->country_of_residence) ? null : $this->country_of_residence;

        $validated = $this->validate($this->profileRules($user->id));

        $validated['name'] = collect([
            $validated['first_name'],
            $validated['middle_name'] ?: null,
            $validated['last_name'],
        ])->filter()->implode(' ');

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
        session(['locale' => $validated['preferred_locale']]);
        app()->setLocale($validated['preferred_locale']);

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('A new verification link has been sent to your email address.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your account details, locale, and profile information')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="first_name" :label="__('First name')" type="text" required autofocus autocomplete="given-name" />
                <flux:input wire:model="last_name" :label="__('Last name')" type="text" required autocomplete="family-name" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="middle_name" :label="__('Middle name')" type="text" autocomplete="additional-name" />
                <flux:input wire:model="birth_date" :label="__('Birth date')" type="date" />
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="country_of_residence" :label="__('Country of residence')" type="text" autocomplete="country-name" />
                <flux:select wire:model="preferred_locale" :label="__('Preferred locale')">
                    <option value="en">{{ __('English (UK)') }}</option>
                    <option value="pl">{{ __('Polski') }}</option>
                    <option value="ar">{{ __('Arabic') }}</option>
                </flux:select>
            </div>

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-profile-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
