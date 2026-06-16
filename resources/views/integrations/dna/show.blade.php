<x-layouts::app :title="__('DNA Kit')" active-nav="integrations">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <a href="{{ route('integrations.dna.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm text-[#6f7b83] hover:text-[#2563eb]">
                ← {{ __('DNA Kits') }}
            </a>
            <div class="flex items-end justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ $kit->provider->label() }}</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#1f252b]">
                        {{ $kit->kit_name ?? __('DNA Kit') }}
                    </h1>
                    <p class="mt-1 text-sm text-[#6f7b83]">{{ __('Uploaded :date', ['date' => $kit->created_at->format('j M Y')]) }}</p>
                </div>
                <form method="POST" action="{{ route('integrations.dna.destroy', $kit) }}">
                    @csrf @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('{{ __('Delete this DNA kit and its raw file?') }}')"
                            class="rounded-[6px] border border-[#fca5a5] px-4 py-2 text-sm font-medium text-[#991b1b] transition hover:bg-[#fef2f2]">
                        {{ __('Delete Kit') }}
                    </button>
                </form>
            </div>
        </section>

        {{-- Stats grid --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            @foreach ([
                [__('Provider'), $kit->provider->label()],
                [__('SNPs'), number_format($kit->snp_count)],
                [__('Y Haplogroup'), $kit->haplogroup_y ?? '—'],
                [__('mt Haplogroup'), $kit->haplogroup_mt ?? '—'],
            ] as [$label, $value])
                <div class="rounded-2xl border border-[#e3e8ee] bg-white px-5 py-4 shadow-sm">
                    <p class="text-2xl font-bold text-[#1f252b]">{{ $value }}</p>
                    <p class="mt-0.5 text-xs uppercase tracking-widest text-[#9daab4]">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        {{-- Linked person --}}
        @if ($kit->person)
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-5 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Linked Profile') }}</h2>
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#dbeafe] text-sm font-semibold text-[#2563eb]">
                        {{ mb_strtoupper(mb_substr($kit->person->display_name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-medium text-[#1f252b]">{{ $kit->person->display_name }}</p>
                        <p class="text-sm text-[#6f7b83]">{{ $kit->person->life_span }}</p>
                    </div>
                </div>
            </section>
        @endif

        {{-- Notes --}}
        @if ($kit->notes)
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-5 shadow-sm">
                <h2 class="mb-2 text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Notes') }}</h2>
                <p class="text-sm leading-6 text-[#4f5963]">{{ $kit->notes }}</p>
            </section>
        @endif

        {{-- Ancestry composition --}}
        @if ($kit->ancestry_composition)
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-5 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Ancestry Composition') }}</h2>
                <div class="space-y-2">
                    @foreach ($kit->ancestry_composition as $region => $pct)
                        <div class="flex items-center gap-3">
                            <span class="w-48 truncate text-sm text-[#4f5963]">{{ $region }}</span>
                            <div class="flex-1 rounded-full bg-[#e3e8ee] h-2">
                                <div class="h-2 rounded-full bg-[#2563eb]" style="width: {{ min(100, $pct) }}%"></div>
                            </div>
                            <span class="w-12 text-right text-sm font-medium text-[#1f252b]">{{ round($pct, 1) }}%</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

    </div>
</x-layouts::app>
