<x-layouts::app :title="__('Global Tree')" active-nav="global-tree">
    <div class="genealogy-shell space-y-6">

        {{-- Header --}}
        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Origynz') }}</p>
                    <h1 class="text-4xl font-semibold tracking-tight text-[#1f252b] sm:text-5xl">{{ __('Global Tree') }}</h1>
                    <p class="max-w-2xl text-base leading-7 text-[#4f5963]">
                        {{ __('An aggregated view of family trees shared by the Origynz community. Living persons are shown anonymously to comply with data-protection law (GDPR and equivalent).') }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-4 text-sm text-[#6f7b83]">
                    <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-5 py-3 text-center">
                        <p class="text-2xl font-semibold text-[#1f252b]">{{ number_format($branches->count()) }}</p>
                        <p class="mt-0.5 text-xs uppercase tracking-[0.2em]">{{ __('Branches') }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-5 py-3 text-center">
                        <p class="text-2xl font-semibold text-[#1f252b]">{{ number_format($totalProfiles) }}</p>
                        <p class="mt-0.5 text-xs uppercase tracking-[0.2em]">{{ __('Profiles') }}</p>
                    </div>
                </div>
            </div>

            {{-- Tab navigation --}}
            <div class="mt-6 flex gap-1 border-b border-[#e3e8ee]">
                <a href="{{ route('global-tree.index') }}"
                   class="rounded-t-[6px] border-b-2 border-[#2563eb] px-4 py-2 text-sm font-medium text-[#2563eb]">
                    {{ __('Directory') }}
                </a>
                <a href="{{ route('global-tree.pedigree') }}"
                   class="rounded-t-[6px] px-4 py-2 text-sm font-medium text-[#6f7b83] transition hover:text-[#1f252b]">
                    {{ __('Pedigree Chart') }}
                </a>
                <a href="{{ route('global-tree.relationship-calculator') }}"
                   class="rounded-t-[6px] px-4 py-2 text-sm font-medium text-[#6f7b83] transition hover:text-[#1f252b]">
                    {{ __('Relationship Calculator') }}
                </a>
                @if (auth()->user()?->hasAnyRole(['super admin', 'admin', 'curator']))
                    <a href="{{ route('global-tree.merge.index') }}"
                       class="rounded-t-[6px] px-4 py-2 text-sm font-medium text-[#6f7b83] transition hover:text-[#1f252b]">
                        {{ __('Merge Candidates') }}
                    </a>
                @endif
            </div>
        </section>

        {{-- Privacy notice banner --}}
        <div class="rounded-xl border border-[#fde68a] bg-[#fffbeb] px-5 py-4 text-sm leading-6 text-[#78350f]">
            <strong>{{ __('Privacy notice:') }}</strong>
            {{ __('Any person born within the last 100 years with no recorded death date is displayed as "Private Person" in compliance with GDPR (EU) 2016/679, UK GDPR, and equivalent laws. No names, dates, places, or photos are shown for living individuals.') }}
        </div>

        <div class="grid gap-6 xl:grid-cols-[280px_1fr]">

            {{-- Sidebar: branches + filters --}}
            <aside class="space-y-4">
                <section class="rounded-2xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.28em] text-[#6f7b83]">{{ __('Filter by branch') }}</h2>

                    <div class="mt-4 space-y-1">
                        <a
                            href="{{ route('global-tree.index', array_filter(['search' => $search])) }}"
                            class="flex items-center justify-between rounded-[6px] px-3 py-2 text-sm transition hover:bg-[#f3f7fb] {{ $treeFilter === 0 ? 'bg-[#eff6ff] font-semibold text-[#2563eb]' : 'text-[#334155]' }}"
                        >
                            <span>{{ __('All branches') }}</span>
                            <span class="text-xs text-[#9daab4]">{{ number_format($totalProfiles) }}</span>
                        </a>

                        @foreach ($branches as $branch)
                            <a
                                href="{{ route('global-tree.index', array_filter(['tree' => $branch->id, 'search' => $search])) }}"
                                class="flex items-center justify-between rounded-[6px] px-3 py-2 text-sm transition hover:bg-[#f3f7fb] {{ $treeFilter === $branch->id ? 'bg-[#eff6ff] font-semibold text-[#2563eb]' : 'text-[#334155]' }}"
                            >
                                <span class="truncate">{{ $branch->name }}</span>
                                <span class="ml-2 shrink-0 text-xs text-[#9daab4]">{{ number_format($branch->visible_people_count) }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.28em] text-[#6f7b83]">{{ __('Add your tree') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-[#6f7b83]">
                        {{ __('Tree managers can opt their tree into the Global Tree from the Tree Managers page.') }}
                    </p>
                    <a href="{{ route('trees.manage') }}" class="mt-3 inline-block rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                        {{ __('Manage my trees') }}
                    </a>
                </section>

                {{-- Quick links --}}
                <section class="rounded-2xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.28em] text-[#6f7b83]">{{ __('Tools') }}</h2>
                    <div class="mt-3 space-y-1">
                        <a href="{{ route('global-tree.relationship-calculator') }}"
                           class="flex items-center gap-2 rounded-[6px] px-3 py-2 text-sm text-[#334155] transition hover:bg-[#f3f7fb]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#9daab4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            {{ __('Relationship Calculator') }}
                        </a>
                        <a href="{{ route('people.watch-list') }}"
                           class="flex items-center gap-2 rounded-[6px] px-3 py-2 text-sm text-[#334155] transition hover:bg-[#f3f7fb]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#9daab4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            {{ __('My Watch List') }}
                        </a>
                        <a href="{{ route('people.claims.index') }}"
                           class="flex items-center gap-2 rounded-[6px] px-3 py-2 text-sm text-[#334155] transition hover:bg-[#f3f7fb]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#9daab4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ __('Profile Claims') }}
                        </a>
                    </div>
                </section>

                {{-- Suggested connections --}}
                @if (isset($suggestedConnections) && $suggestedConnections->isNotEmpty())
                    <section class="rounded-2xl border border-[#fde68a] bg-[#fffbeb] p-5 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.28em] text-[#92400e]">{{ __('Suggested Connections') }}</h2>
                        <p class="mt-1 text-xs text-[#78350f]">{{ __('These profiles in your trees may match people in other branches.') }}</p>
                        <div class="mt-3 space-y-2">
                            @foreach ($suggestedConnections->take(3) as $sc)
                                <a href="{{ route('global-tree.merge.review', $sc) }}"
                                   class="block rounded-lg border border-[#fde68a] bg-white px-3 py-2 text-xs hover:border-[#f59e0b]">
                                    <p class="font-medium text-[#1f252b]">{{ $sc->personA?->display_name }} ≈ {{ $sc->personB?->display_name }}</p>
                                    <p class="mt-0.5 text-[#78350f]">{{ $sc->similarity_score }}% {{ __('match') }}</p>
                                </a>
                            @endforeach
                        </div>
                        @if ($suggestedConnections->count() > 3)
                            <a href="{{ route('global-tree.merge.index') }}" class="mt-3 block text-center text-xs text-[#2563eb] hover:underline">
                                {{ __('View all :n', ['n' => $suggestedConnections->count()]) }}
                            </a>
                        @endif
                    </section>
                @endif
            </aside>

            {{-- Main: search + people list --}}
            <div class="space-y-4">
                <section class="rounded-2xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                    <form method="GET" action="{{ route('global-tree.index') }}" class="flex gap-3">
                        @if ($treeFilter > 0)
                            <input type="hidden" name="tree" value="{{ $treeFilter }}">
                        @endif
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="{{ __('Search by name…') }}"
                            class="flex-1 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm text-[#1f252b] placeholder-[#9daab4] focus:border-[#93c5fd] focus:outline-none"
                        >
                        <button type="submit" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:bg-[#e8f0f7]">
                            {{ __('Search') }}
                        </button>
                        @if ($search)
                            <a href="{{ route('global-tree.index', $treeFilter > 0 ? ['tree' => $treeFilter] : []) }}" class="rounded-[6px] px-3 py-2 text-sm text-[#6f7b83] hover:text-[#2563eb]">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                </section>

                <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
                    @if ($people->isEmpty())
                        <div class="px-6 py-12 text-center text-sm text-[#6f7b83]">
                            {{ $search ? __('No profiles matched your search.') : __('No profiles in the Global Tree yet.') }}
                        </div>
                    @else
                        <div class="divide-y divide-[#f0f4f8]">
                            @foreach ($displayData as $i => $data)
                                <div class="flex items-center gap-4 px-6 py-4 {{ $data['is_private'] ? 'opacity-60' : '' }}"
                                     x-data="{ watching: {{ isset($watchedIds) && in_array($data['id'], $watchedIds) ? 'true' : 'false' }} }">

                                    {{-- Avatar placeholder --}}
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full
                                        {{ $data['is_private'] ? 'bg-[#e5e7eb] text-[#9ca3af]' : 'bg-[#dbeafe] text-[#2563eb]' }}
                                        text-sm font-semibold">
                                        @if ($data['is_private'])
                                            ?
                                        @else
                                            {{ mb_strtoupper(mb_substr($data['display_name'], 0, 1)) }}
                                        @endif
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium text-[#1f252b]">{{ $data['display_name'] }}</span>
                                            @if ($data['is_private'])
                                                <span class="rounded-[4px] bg-[#f3f4f6] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#6b7280]">
                                                    {{ __('Private') }}
                                                </span>
                                            @endif
                                            @if (! $data['is_private'] && isset($data['trust_score']))
                                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $data['trust_colour'] }}">
                                                    {{ $data['trust_label'] }}
                                                </span>
                                            @endif
                                        </div>

                                        <p class="mt-0.5 text-sm text-[#6f7b83]">
                                            @if (! $data['is_private'])
                                                @if ($data['life_span'])
                                                    <span>{{ $data['life_span'] }}</span>
                                                @endif
                                                @if ($data['birth_place'])
                                                    <span class="mx-1">·</span>
                                                    <span>{{ $data['birth_place'] }}</span>
                                                @endif
                                            @else
                                                {{ __('Details withheld to protect privacy.') }}
                                            @endif
                                        </p>
                                    </div>

                                    {{-- Branch tag --}}
                                    <div class="shrink-0 text-right text-xs text-[#9daab4]">
                                        {{ $data['family_tree'] }}
                                    </div>

                                    {{-- Watch toggle (non-private only) --}}
                                    @if (! $data['is_private'])
                                        <button
                                            x-on:click.prevent="
                                                fetch('{{ route('people.watch.toggle', $data['id']) }}', {
                                                    method: 'POST',
                                                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                                }).then(r => r.json()).then(d => { watching = d.watching; })
                                            "
                                            :title="watching ? '{{ __('Unwatch') }}' : '{{ __('Watch') }}'"
                                            class="shrink-0 rounded-full p-1.5 transition hover:bg-[#f0f4f8]"
                                            :class="watching ? 'text-[#2563eb]' : 'text-[#cdd7e1] hover:text-[#6f7b83]'"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="h-4 w-4">
                                                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8a3 3 0 1 0 0 6 3 3 0 0 0 0-6z"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($people->hasPages())
                            <div class="border-t border-[#f0f4f8] px-6 py-4">
                                {{ $people->links() }}
                            </div>
                        @endif
                    @endif
                </section>
            </div>
        </div>

    </div>
</x-layouts::app>
