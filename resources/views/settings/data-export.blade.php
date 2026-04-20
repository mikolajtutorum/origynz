<x-layouts::app :title="__('Data Export')">
    <div class="px-6 py-8">
        @include('partials.settings-heading')

        <div class="flex items-start max-md:flex-col">
            <div class="me-10 w-full pb-4 md:w-[220px]">
                <flux:navlist aria-label="{{ __('Settings') }}">
                    <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
                    <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
                    <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
                    <flux:navlist.item :href="route('settings.data-export')" wire:navigate>{{ __('Data Export') }}</flux:navlist.item>
                </flux:navlist>
            </div>

            <flux:separator class="md:hidden" />

            <div class="flex-1 self-stretch max-md:pt-6">
                <flux:heading>{{ __('Export Your Data') }}</flux:heading>
                <flux:subheading>{{ __('Download a copy of all personal data Origynz holds about you.') }}</flux:subheading>

                <div class="mt-5 w-full max-w-lg space-y-6">
                    <div class="rounded-xl border border-[#e3e8ee] bg-white p-5 text-sm text-[#474747] space-y-2">
                        <p class="font-medium">{{ __('What is included in the export:') }}</p>
                        <ul class="list-disc pl-5 space-y-1 text-zinc-600">
                            <li>{{ __('Your account profile (name, email, country, language preference)') }}</li>
                            <li>{{ __('All family trees you own, including all persons and relationships') }}</li>
                            <li>{{ __('Media item metadata (file names and descriptions)') }}</li>
                            <li>{{ __('Connected social account providers') }}</li>
                            <li>{{ __('Your activity history (up to 1,000 recent entries)') }}</li>
                        </ul>
                        <p class="text-zinc-500 text-xs pt-1">{{ __('Passwords, two-factor secrets, and recovery codes are never included.') }}</p>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ __('Your export will be downloaded as a JSON file. Keep it secure — it contains personal information.') }}
                    </div>

                    <form method="POST" action="{{ route('settings.data-export.store') }}">
                        @csrf
                        <flux:button type="submit" variant="primary">
                            {{ __('Download My Data') }}
                        </flux:button>
                    </form>

                    <p class="text-xs text-zinc-400">
                        {{ __('You can also export individual family trees in GEDCOM format from the tree settings. This export covers your account-level data (GDPR Article 20 — Right to Data Portability).') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>
