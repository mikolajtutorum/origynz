<x-layouts::app :title="__('Geni')" active-nav="integrations">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <a href="{{ route('integrations.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm text-[#6f7b83] hover:text-[#2563eb]">
                ← {{ __('Integrations') }}
            </a>
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#fdf4ff] text-3xl font-bold text-[#9333ea]">G</div>
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-[#1f252b]">Geni</h1>
                    <p class="text-sm text-[#6f7b83]">{{ __('Global shared family tree') }}</p>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 text-sm text-[#166534]">{{ session('success') }}</div>
        @endif

        @if ($integration)
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-[#1f252b]">{{ __('Connected') }}</p>
                        <p class="mt-0.5 text-sm text-[#6f7b83]">
                            {{ $integration->provider_username ?? __('Geni user') }}
                            · {{ __('Connected :ago', ['ago' => $integration->created_at->diffForHumans()]) }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('integrations.geni.disconnect') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-[6px] border border-[#fca5a5] px-4 py-2 text-sm font-medium text-[#991b1b] transition hover:bg-[#fef2f2]">
                            {{ __('Disconnect') }}
                        </button>
                    </form>
                </div>
            </section>
        @else
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-8 text-center shadow-sm">
                <p class="text-[#6f7b83]">{{ __('Connect your Geni account to sync profiles with the global shared family tree.') }}</p>
                @if (config('integrations.geni.client_id'))
                    <a href="{{ route('integrations.geni.redirect') }}"
                       class="mt-4 inline-block rounded-[8px] bg-[#9333ea] px-6 py-3 text-sm font-semibold text-white transition hover:bg-[#7e22ce]">
                        {{ __('Connect to Geni') }}
                    </a>
                @else
                    <p class="mt-3 rounded-xl border border-[#fde68a] bg-[#fffbeb] px-4 py-3 text-sm text-[#78350f]">
                        {{ __('Geni integration requires GENI_CLIENT_ID and GENI_CLIENT_SECRET to be set in your .env file.') }}
                    </p>
                @endif
            </section>
        @endif

    </div>
</x-layouts::app>
