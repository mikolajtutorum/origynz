<x-layouts::app :title="__('FamilySearch')" active-nav="integrations">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <a href="{{ route('integrations.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm text-[#6f7b83] hover:text-[#2563eb]">
                ← {{ __('Integrations') }}
            </a>
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0f9f4] text-3xl font-bold text-[#16a34a]">FS</div>
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-[#1f252b]">FamilySearch</h1>
                    <p class="text-sm text-[#6f7b83]">{{ __('World\'s largest free genealogy database') }}</p>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 text-sm text-[#166534]">{{ session('success') }}</div>
        @endif

        @if ($integration)
            {{-- Connected state --}}
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-[#1f252b]">{{ __('Connected') }}</p>
                        <p class="mt-0.5 text-sm text-[#6f7b83]">
                            @if ($integration->provider_username)
                                {{ __('Signed in as :name', ['name' => $integration->provider_username]) }}
                            @endif
                            · {{ __('Connected :ago', ['ago' => $integration->created_at->diffForHumans()]) }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('integrations.familysearch.disconnect') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-[6px] border border-[#fca5a5] px-4 py-2 text-sm font-medium text-[#991b1b] transition hover:bg-[#fef2f2]">
                            {{ __('Disconnect') }}
                        </button>
                    </form>
                </div>
            </section>

            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
                <h2 class="mb-2 text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('How to Sync a Person') }}</h2>
                <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-[#4f5963]">
                    <li>{{ __('Open a person\'s profile in any of your trees.') }}</li>
                    <li>{{ __('Scroll to the "External Links" section and click "Search FamilySearch".') }}</li>
                    <li>{{ __('Select the matching person from the results and click "Import".') }}</li>
                    <li>{{ __('Missing fields are filled from FamilySearch. Existing data is never overwritten.') }}</li>
                </ol>
            </section>
        @else
            {{-- Disconnected state --}}
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-8 text-center shadow-sm">
                <p class="text-[#6f7b83]">{{ __('Connect your FamilySearch account to import person data and sync records.') }}</p>
                @if (config('integrations.familysearch.client_id'))
                    <a href="{{ route('integrations.familysearch.redirect') }}"
                       class="mt-4 inline-block rounded-[8px] bg-[#16a34a] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#15803d]">
                        {{ __('Connect to FamilySearch') }}
                    </a>
                @else
                    <p class="mt-3 rounded-xl border border-[#fde68a] bg-[#fffbeb] px-4 py-3 text-sm text-[#78350f]">
                        {{ __('FamilySearch integration requires FAMILYSEARCH_CLIENT_ID and FAMILYSEARCH_CLIENT_SECRET to be set in your .env file.') }}
                    </p>
                @endif
            </section>
        @endif

    </div>
</x-layouts::app>
