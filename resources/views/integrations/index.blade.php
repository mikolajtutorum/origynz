<x-layouts::app :title="__('Integrations')" active-nav="integrations">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Origynz') }}</p>
            <h1 class="mt-1 text-4xl font-semibold tracking-tight text-[#1f252b]">{{ __('Integrations') }}</h1>
            <p class="mt-2 max-w-2xl text-base leading-7 text-[#4f5963]">
                {{ __('Connect Origynz to external genealogy platforms, import DNA data, and manage your API access.') }}
            </p>
        </section>

        {{-- Platform integrations --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            <div class="border-b border-[#f0f4f8] px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Genealogy Platforms') }}</h2>
            </div>
            <div class="divide-y divide-[#f0f4f8]">

                {{-- FamilySearch --}}
                <div class="flex items-center gap-5 px-6 py-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#f0f9f4] text-2xl font-bold text-[#16a34a]">FS</div>
                    <div class="flex-1">
                        <p class="font-semibold text-[#1f252b]">FamilySearch</p>
                        <p class="mt-0.5 text-sm text-[#6f7b83]">{{ __('Sync profiles with the world\'s largest free genealogy database.') }}</p>
                    </div>
                    @if ($connected->has('familysearch'))
                        <span class="rounded-full bg-[#dcfce7] px-3 py-1 text-xs font-semibold text-[#166534]">{{ __('Connected') }}</span>
                    @endif
                    <a href="{{ route('integrations.familysearch') }}" class="shrink-0 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:bg-[#eff6ff] hover:text-[#2563eb]">
                        {{ $connected->has('familysearch') ? __('Manage') : __('Connect') }}
                    </a>
                </div>

                {{-- WikiTree --}}
                <div class="flex items-center gap-5 px-6 py-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#f0f6ff] text-2xl font-bold text-[#2563eb]">WT</div>
                    <div class="flex-1">
                        <p class="font-semibold text-[#1f252b]">WikiTree</p>
                        <p class="mt-0.5 text-sm text-[#6f7b83]">{{ __('Connect to the collaborative, community-edited world family tree.') }}</p>
                    </div>
                    @if ($connected->has('wikitree'))
                        <span class="rounded-full bg-[#dcfce7] px-3 py-1 text-xs font-semibold text-[#166534]">{{ __('Connected') }}</span>
                    @endif
                    <a href="{{ route('integrations.wikitree') }}" class="shrink-0 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:bg-[#eff6ff] hover:text-[#2563eb]">
                        {{ $connected->has('wikitree') ? __('Manage') : __('Connect') }}
                    </a>
                </div>

                {{-- Geni --}}
                <div class="flex items-center gap-5 px-6 py-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#fdf4ff] text-2xl font-bold text-[#9333ea]">G</div>
                    <div class="flex-1">
                        <p class="font-semibold text-[#1f252b]">Geni</p>
                        <p class="mt-0.5 text-sm text-[#6f7b83]">{{ __('Sync with the global shared family tree and connect with distant relatives.') }}</p>
                    </div>
                    @if ($connected->has('geni'))
                        <span class="rounded-full bg-[#dcfce7] px-3 py-1 text-xs font-semibold text-[#166534]">{{ __('Connected') }}</span>
                    @endif
                    <a href="{{ route('integrations.geni') }}" class="shrink-0 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:bg-[#eff6ff] hover:text-[#2563eb]">
                        {{ $connected->has('geni') ? __('Manage') : __('Connect') }}
                    </a>
                </div>
            </div>
        </section>

        {{-- DNA --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            <div class="border-b border-[#f0f4f8] px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('DNA') }}</h2>
            </div>
            <div class="flex items-center gap-5 px-6 py-5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#fff7ed] text-2xl font-bold text-[#ea580c]">🧬</div>
                <div class="flex-1">
                    <p class="font-semibold text-[#1f252b]">{{ __('Raw DNA Import') }}</p>
                    <p class="mt-0.5 text-sm text-[#6f7b83]">{{ __('Upload raw data from 23andMe, AncestryDNA, FTDNA, MyHeritage, and Living DNA.') }}</p>
                </div>
                <a href="{{ route('integrations.dna.index') }}" class="shrink-0 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:bg-[#eff6ff] hover:text-[#2563eb]">
                    {{ __('Manage DNA Kits') }}
                </a>
            </div>
        </section>

        {{-- Developer API --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            <div class="border-b border-[#f0f4f8] px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Developer API') }}</h2>
            </div>
            <div class="flex items-center gap-5 px-6 py-5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#f1f5f9] text-2xl font-bold text-[#475569]">&lt;/&gt;</div>
                <div class="flex-1">
                    <p class="font-semibold text-[#1f252b]">{{ __('REST API & Tokens') }}</p>
                    <p class="mt-0.5 text-sm text-[#6f7b83]">{{ __('Access your family tree data programmatically. Also used for Gramps sync.') }}</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <a href="{{ route('api.docs') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:bg-[#eff6ff] hover:text-[#2563eb]">
                        {{ __('API Docs') }}
                    </a>
                    <a href="{{ route('settings.api-tokens') }}" class="rounded-[6px] bg-[#2563eb] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#1d4ed8]">
                        {{ __('Manage Tokens') }}
                    </a>
                </div>
            </div>
        </section>

    </div>
</x-layouts::app>
