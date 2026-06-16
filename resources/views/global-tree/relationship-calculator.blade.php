<x-layouts::app :title="__('Relationship Calculator')" active-nav="global-tree">
    <div class="genealogy-shell space-y-6"
         x-data="{
             personA: null, personB: null,
             labelA: '', labelB: '',
             searchA: '', searchB: '',
             resultsA: [], resultsB: [],
             loading: false, result: null,
             async searchPeople(side) {
                 const q = side === 'a' ? this.searchA : this.searchB;
                 if (q.length < 2) { if (side === 'a') this.resultsA = []; else this.resultsB = []; return; }
                 const r = await fetch('{{ route('global-tree.pedigree.search') }}?q=' + encodeURIComponent(q));
                 const data = await r.json();
                 if (side === 'a') this.resultsA = data; else this.resultsB = data;
             },
             selectPerson(side, p) {
                 if (side === 'a') { this.personA = p.id; this.labelA = p.name + (p.life_span ? ' (' + p.life_span + ')' : ''); this.searchA = ''; this.resultsA = []; }
                 else              { this.personB = p.id; this.labelB = p.name + (p.life_span ? ' (' + p.life_span + ')' : ''); this.searchB = ''; this.resultsB = []; }
             },
             async calculate() {
                 if (!this.personA || !this.personB) return;
                 this.loading = true; this.result = null;
                 const r = await fetch('{{ route('global-tree.relationship-calculator.calculate') }}', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                     body: JSON.stringify({ person_a_id: this.personA, person_b_id: this.personB })
                 });
                 this.result = await r.json();
                 this.loading = false;
             }
         }">

        {{-- Header --}}
        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Global Tree') }}</p>
                <h1 class="text-4xl font-semibold tracking-tight text-[#1f252b]">{{ __('Relationship Calculator') }}</h1>
                <p class="max-w-2xl text-base leading-7 text-[#4f5963]">
                    {{ __('Find out how any two people in the Global Tree are related.') }}
                </p>
            </div>

            {{-- Tab navigation --}}
            <div class="mt-6 flex gap-1 border-b border-[#e3e8ee]">
                <a href="{{ route('global-tree.index') }}"
                   class="rounded-t-[6px] px-4 py-2 text-sm font-medium text-[#6f7b83] transition hover:text-[#1f252b]">
                    {{ __('Directory') }}
                </a>
                <a href="{{ route('global-tree.pedigree') }}"
                   class="rounded-t-[6px] px-4 py-2 text-sm font-medium text-[#6f7b83] transition hover:text-[#1f252b]">
                    {{ __('Pedigree Chart') }}
                </a>
                <a href="{{ route('global-tree.relationship-calculator') }}"
                   class="rounded-t-[6px] border-b-2 border-[#2563eb] px-4 py-2 text-sm font-medium text-[#2563eb]">
                    {{ __('Relationship Calculator') }}
                </a>
            </div>
        </section>

        {{-- Person pickers --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
            <div class="grid gap-6 md:grid-cols-2">

                {{-- Person A --}}
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-[#1f252b]">{{ __('Person A') }}</label>
                    <div x-show="labelA" class="flex items-center gap-2 rounded-xl border border-[#93c5fd] bg-[#eff6ff] px-4 py-3">
                        <span class="flex-1 text-sm font-medium text-[#1f252b]" x-text="labelA"></span>
                        <button @click="personA=null;labelA=''" class="text-[#9daab4] hover:text-[#dc2626]">✕</button>
                    </div>
                    <div x-show="!labelA" class="relative">
                        <input type="text" x-model="searchA" @input.debounce.300ms="searchPeople('a')"
                               placeholder="{{ __('Search by name…') }}"
                               class="w-full rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm focus:border-[#93c5fd] focus:outline-none">
                        <div x-show="resultsA.length > 0" class="absolute z-10 mt-1 w-full rounded-xl border border-[#e3e8ee] bg-white shadow-lg">
                            <template x-for="p in resultsA" :key="p.id">
                                <button @click="selectPerson('a', p)"
                                        class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm hover:bg-[#f7f9fb]">
                                    <span class="font-medium text-[#1f252b]" x-text="p.name"></span>
                                    <span class="text-[#9daab4]" x-text="p.life_span"></span>
                                    <span class="ml-auto text-xs text-[#9daab4]" x-text="p.tree"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Person B --}}
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-[#1f252b]">{{ __('Person B') }}</label>
                    <div x-show="labelB" class="flex items-center gap-2 rounded-xl border border-[#93c5fd] bg-[#eff6ff] px-4 py-3">
                        <span class="flex-1 text-sm font-medium text-[#1f252b]" x-text="labelB"></span>
                        <button @click="personB=null;labelB=''" class="text-[#9daab4] hover:text-[#dc2626]">✕</button>
                    </div>
                    <div x-show="!labelB" class="relative">
                        <input type="text" x-model="searchB" @input.debounce.300ms="searchPeople('b')"
                               placeholder="{{ __('Search by name…') }}"
                               class="w-full rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm focus:border-[#93c5fd] focus:outline-none">
                        <div x-show="resultsB.length > 0" class="absolute z-10 mt-1 w-full rounded-xl border border-[#e3e8ee] bg-white shadow-lg">
                            <template x-for="p in resultsB" :key="p.id">
                                <button @click="selectPerson('b', p)"
                                        class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm hover:bg-[#f7f9fb]">
                                    <span class="font-medium text-[#1f252b]" x-text="p.name"></span>
                                    <span class="text-[#9daab4]" x-text="p.life_span"></span>
                                    <span class="ml-auto text-xs text-[#9daab4]" x-text="p.tree"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-center">
                <button @click="calculate()"
                        :disabled="!personA || !personB || loading"
                        class="rounded-[8px] bg-[#2563eb] px-8 py-3 text-sm font-semibold text-white transition hover:bg-[#1d4ed8] disabled:opacity-40">
                    <span x-show="!loading">{{ __('Calculate Relationship') }}</span>
                    <span x-show="loading">{{ __('Calculating…') }}</span>
                </button>
            </div>
        </section>

        {{-- Result --}}
        <section x-show="result" class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
            <div x-show="result && !result.connected" class="py-8 text-center text-[#6f7b83]">
                {{ __('No relationship path found between these two people.') }}
            </div>

            <div x-show="result && result.connected">
                <h2 class="mb-6 text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Relationship Path') }}</h2>
                <div class="flex flex-wrap items-center gap-2">
                    <template x-for="(step, i) in (result ? result.path : [])" :key="i">
                        <div class="flex items-center gap-2">
                            <div x-show="step.via" class="flex flex-col items-center">
                                <div class="h-4 w-px bg-[#e3e8ee]"></div>
                                <span class="rounded-full bg-[#f0f4f8] px-3 py-0.5 text-[11px] uppercase tracking-wider text-[#6f7b83]" x-text="step.via"></span>
                                <div class="h-4 w-px bg-[#e3e8ee]"></div>
                            </div>
                            <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-4 py-3 text-center">
                                <p class="text-sm font-semibold text-[#1f252b]" x-text="step.name"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

    </div>
</x-layouts::app>
