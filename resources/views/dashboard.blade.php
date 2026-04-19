<x-layouts::app :title="__('Family Workspace')">
    <div class="genealogy-shell space-y-6">

        <section id="family-statistics" class="scroll-mt-28 overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <div class="grid gap-8 lg:grid-cols-[1.6fr_.9fr] lg:items-center">
                <div class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Family Storytelling Platform') }}</p>
                    <div class="space-y-3">
                        <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-[#1f252b] sm:text-5xl">
                            {{ __('Build living family trees, biographies, and relationship maps in one place.') }}
                        </h1>
                        <p class="max-w-2xl text-base leading-7 text-[#4f5963]">
                            {{ __('This first version focuses on the core genealogy engine: private trees, person profiles, parent and spouse links, and a workspace that can grow toward records, timelines, smart matches, and collaboration.') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 rounded-2xl border border-[#e3e8ee] bg-[#f7f9fb] p-4">
                    <div class="rounded-xl bg-[#1f252b] p-4 text-white">
                        <p class="text-xs uppercase tracking-[0.25em] text-white/60">{{ __('Trees') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $stats['trees'] }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e3e8ee] bg-white p-4 text-[#1f252b] shadow-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Profiles') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $stats['profiles'] }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e3e8ee] bg-white p-4 text-[#1f252b] shadow-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Living') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $stats['living'] }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e3e8ee] bg-white p-4 text-[#1f252b] shadow-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Links') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $stats['relationships'] }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
            <div class="space-y-4 rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Workspace overview') }}</h2>
                        <p class="text-sm text-[#6f7b83]">{{ __('Jump back into your family tree workspace, review imports, or head to tree management when you need to create and organize structures.') }}</p>
                    </div>
                    <a href="{{ route('trees.manage') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                        {{ __('Manage trees') }}
                    </a>
                </div>

                @if (session('status'))
                    <div class="rounded-xl border border-[#bfdbfe] bg-[#eff6ff] px-4 py-3 text-sm text-[#1e40af]">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="grid gap-4 lg:grid-cols-2">
                    @forelse ($trees->take(4) as $tree)
                        <a href="{{ route('trees.show', $tree) }}" class="group rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] p-5 transition hover:-translate-y-0.5 hover:border-[#c7d4df] hover:bg-white hover:shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-base font-semibold text-[#1f252b] group-hover:text-[#2563eb]">{{ $tree->name }}</h3>
                                    <p class="mt-1 text-sm text-[#6f7b83]">{{ $tree->home_region ?: __('Region not set yet') }}</p>
                                </div>
                                <span class="rounded-[6px] bg-[#1f252b] px-3 py-1 text-xs font-medium uppercase tracking-[0.22em] text-white">
                                    {{ __($tree->privacy) }}
                                </span>
                            </div>
                            <p class="mt-4 line-clamp-3 text-sm leading-6 text-[#4f5963]">
                                {{ $tree->description ?: __('Start with direct relatives, then grow toward branches, stories, and documentary sources.') }}
                            </p>
                            <div class="mt-5 flex items-center justify-between text-sm text-[#6f7b83]">
                                <span>{{ __(':count profiles', ['count' => $tree->people_count]) }}</span>
                                <span class="font-medium text-[#2563eb]">{{ __('Open workspace ›') }}</span>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-[#c7d4df] bg-[#f7f9fb] p-6 text-sm leading-6 text-[#6f7b83] lg:col-span-2">
                            {{ __('No family trees yet. Head to Manage trees to create your first tree and start mapping ancestors, descendants, and spouse connections.') }}
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <div id="tree-managers" class="scroll-mt-28 rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Tree managers') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('Create, organize, and review every tree from one place. GEDCOM imports now have their own page so the dashboard can stay focused on activity and stats.') }}</p>
                    <div class="mt-5 space-y-3">
                        <a href="{{ route('trees.manage') }}" class="flex items-center justify-between rounded-xl border border-[#d8e0e7] bg-[#f7f9fb] px-4 py-3 text-sm text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                            <span>{{ __('Open tree management') }}</span>
                            <span>{{ __('›') }}</span>
                        </a>
                        <a href="{{ route('trees.import.index') }}" class="flex items-center justify-between rounded-xl border border-[#d8e0e7] bg-[#f7f9fb] px-4 py-3 text-sm text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                            <span>{{ __('Go to GEDCOM import') }}</span>
                            <span>{{ __('›') }}</span>
                        </a>
                    </div>
                </div>

                <div id="family-events" class="scroll-mt-28 rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Family events') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('Recent additions across your family trees appear here so you can pick up where work last changed.') }}</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentPeople as $person)
                            <div class="workspace-list-card">
                                <div class="flex items-center justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-[#1f252b]">{{ $person->display_name }}</p>
                                        <p class="text-sm text-[#6f7b83]">{{ $person->familyTree->name }}</p>
                                    </div>
                                    <span class="text-xs uppercase tracking-[0.22em] text-[#6f7b83]">{{ $person->life_span }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-[#6f7b83]">{{ __('Profiles you add to any tree will start appearing here.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

    </div>
</x-layouts::app>
