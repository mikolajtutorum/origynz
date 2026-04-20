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
                                <div class="flex items-center gap-4 px-6 py-4 {{ $data['is_private'] ? 'opacity-60' : '' }}">

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
