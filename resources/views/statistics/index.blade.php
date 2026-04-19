<x-layouts::app :title="__('Family statistics')" active-nav="home">
    <div class="genealogy-shell space-y-8">
        <section class="rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <div class="grid gap-8 lg:grid-cols-[1.45fr_.95fr] lg:items-center">
                <div class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Family analytics') }}</p>
                    <div class="space-y-3">
                        <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-[#1f252b] sm:text-5xl">
                            {{ __('See how your family archive is growing.') }}
                        </h1>
                        <p class="max-w-2xl text-base leading-7 text-[#4f5963]">
                            {{ __('Review totals across all your trees, compare branch sizes, and spot the places that appear most often in the records you have already added.') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 rounded-2xl border border-[#e3e8ee] bg-[#f7f9fb] p-4">
                    <div class="rounded-xl bg-[#1f252b] p-4 text-white">
                        <p class="text-xs uppercase tracking-[0.25em] text-white/60">{{ __('Trees') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $summary['trees'] }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e3e8ee] bg-white p-4 text-[#1f252b] shadow-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Profiles') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $summary['profiles'] }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e3e8ee] bg-white p-4 text-[#1f252b] shadow-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Living') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $summary['living'] }}</p>
                    </div>
                    <div class="rounded-xl border border-[#e3e8ee] bg-white p-4 text-[#1f252b] shadow-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Links') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $summary['relationships'] }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.15fr_.85fr]">
            <div class="space-y-6">
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Tree breakdown') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('Compare how many profiles and relationships each owned tree currently holds.') }}</p>
                        </div>
                        <a href="{{ route('trees.manage') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                            {{ __('Manage trees') }}
                        </a>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-[1.35rem] border border-[#dde7f0] bg-[linear-gradient(180deg,#ffffff_0%,#fbfdff_100%)] shadow-[0_18px_48px_rgba(15,95,147,0.08)]">
                        <div class="overflow-x-auto">
                            <table class="tree-manage-table min-w-full">
                                <thead>
                                    <tr>
                                        <th scope="col">{{ __('Family tree') }}</th>
                                        <th scope="col">{{ __('Profiles') }}</th>
                                        <th scope="col">{{ __('Relationships') }}</th>
                                        <th scope="col">{{ __('Region') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($trees as $tree)
                                        <tr>
                                            <td>
                                                <a href="{{ route('trees.show', $tree) }}" class="tree-manage-tree-link">{{ $tree->name }}</a>
                                            </td>
                                            <td><span class="tree-manage-meta">{{ number_format($tree->people_count) }}</span></td>
                                            <td><span class="tree-manage-meta">{{ number_format($tree->relationships_count) }}</span></td>
                                            <td><span class="tree-manage-meta">{{ $tree->home_region ?: __('Region not set') }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-sm text-[#6f7b83]">{{ __('No family trees yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('At a glance') }}</h2>
                    <div class="mt-5 grid gap-3">
                        <div class="workspace-list-card flex items-center justify-between gap-4">
                            <span class="text-sm text-[#6f7b83]">{{ __('Deceased profiles') }}</span>
                            <span class="text-lg font-semibold text-[#1f252b]">{{ $summary['deceased'] }}</span>
                        </div>
                        <div class="workspace-list-card flex items-center justify-between gap-4">
                            <span class="text-sm text-[#6f7b83]">{{ __('Average tree size') }}</span>
                            <span class="text-lg font-semibold text-[#1f252b]">{{ $summary['average_tree_size'] }}</span>
                        </div>
                        <div class="workspace-list-card flex items-center justify-between gap-4">
                            <span class="text-sm text-[#6f7b83]">{{ __('Largest tree') }}</span>
                            <span class="text-right text-sm font-semibold text-[#1f252b]">
                                {{ $largestTree?->name ?: __('None yet') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Top birth places') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($topBirthPlaces as $place => $count)
                            <div class="workspace-list-card flex items-center justify-between gap-4">
                                <span class="text-sm text-[#1f252b]">{{ $place }}</span>
                                <span class="text-sm font-semibold text-[#2563eb]">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-[#6f7b83]">{{ __('Birth places will appear here as you add more profile details.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Top death places') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($topDeathPlaces as $place => $count)
                            <div class="workspace-list-card flex items-center justify-between gap-4">
                                <span class="text-sm text-[#1f252b]">{{ $place }}</span>
                                <span class="text-sm font-semibold text-[#2563eb]">{{ $count }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-[#6f7b83]">{{ __('Death places will appear here as you record later-life details.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-layouts::app>
