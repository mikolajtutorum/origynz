<x-layouts::app :title="__('DNA Kits')" active-nav="integrations">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <a href="{{ route('integrations.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm text-[#6f7b83] hover:text-[#2563eb]">
                ← {{ __('Integrations') }}
            </a>
            <div class="flex items-end justify-between">
                <div>
                    <h1 class="text-4xl font-semibold tracking-tight text-[#1f252b]">{{ __('DNA Kits') }}</h1>
                    <p class="mt-2 max-w-2xl text-base leading-7 text-[#4f5963]">
                        {{ __('Upload raw DNA data from 23andMe, AncestryDNA, FTDNA, MyHeritage, or Living DNA.') }}
                    </p>
                </div>
                <button onclick="document.getElementById('upload-modal').classList.remove('hidden')"
                        class="shrink-0 rounded-[8px] bg-[#2563eb] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]">
                    {{ __('Upload Kit') }}
                </button>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 text-sm text-[#166534]">{{ session('success') }}</div>
        @endif

        {{-- Upload modal --}}
        <div id="upload-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl">
                <h2 class="mb-4 text-xl font-semibold text-[#1f252b]">{{ __('Upload Raw DNA File') }}</h2>
                <form method="POST" action="{{ route('integrations.dna.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-[#1f252b]">{{ __('Raw DNA file') }}</label>
                        <input type="file" name="file" accept=".txt,.csv,.zip,.gz" required
                               class="mt-1 w-full text-sm text-[#4f5963]">
                        <p class="mt-1 text-xs text-[#9daab4]">{{ __('Accepted: .txt from 23andMe/AncestryDNA/FTDNA, .csv from MyHeritage. Max :mb MB.', ['mb' => config('integrations.dna.max_size_mb')]) }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#1f252b]">{{ __('Link to person (optional)') }}</label>
                        <input type="text" name="person_id" placeholder="{{ __('Person UUID') }}"
                               class="mt-1 w-full rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#1f252b]">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="2" maxlength="1000"
                                  class="mt-1 w-full resize-none rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm focus:outline-none"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="document.getElementById('upload-modal').classList.add('hidden')"
                                class="rounded-[6px] border border-[#cdd7e1] px-4 py-2 text-sm font-medium text-[#6f7b83]">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="rounded-[6px] bg-[#2563eb] px-5 py-2 text-sm font-semibold text-white hover:bg-[#1d4ed8]">
                            {{ __('Upload & Parse') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            @if ($kits->isEmpty())
                <div class="px-6 py-16 text-center text-sm text-[#6f7b83]">
                    {{ __('No DNA kits yet. Upload a raw data file to get started.') }}
                </div>
            @else
                <div class="divide-y divide-[#f0f4f8]">
                    @foreach ($kits as $kit)
                        <a href="{{ route('integrations.dna.show', $kit) }}"
                           class="flex items-center gap-4 px-6 py-4 transition hover:bg-[#f7f9fb]">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#fff7ed] text-lg">🧬</div>
                            <div class="flex-1">
                                <p class="font-medium text-[#1f252b]">{{ $kit->kit_name ?? $kit->provider->label() }}</p>
                                <p class="mt-0.5 text-sm text-[#6f7b83]">
                                    {{ $kit->provider->label() }}
                                    @if ($kit->snp_count)
                                        · {{ number_format($kit->snp_count) }} {{ __('SNPs') }}
                                    @endif
                                    @if ($kit->person)
                                        · {{ $kit->person->display_name }}
                                    @endif
                                </p>
                            </div>
                            <div class="shrink-0 text-xs text-[#9daab4]">
                                {{ $kit->created_at->format('j M Y') }}
                            </div>
                        </a>
                    @endforeach
                </div>
                @if ($kits->hasPages())
                    <div class="border-t border-[#f0f4f8] px-6 py-4">{{ $kits->links() }}</div>
                @endif
            @endif
        </section>

    </div>
</x-layouts::app>
