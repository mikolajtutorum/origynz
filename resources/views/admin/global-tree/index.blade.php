<x-layouts::app :title="__('Global Tree — Admin')">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Admin Portal') }}</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#1f252b]">{{ __('Global Tree management') }}</h1>
                    <p class="mt-1 text-sm text-[#6f7b83]">
                        {{ __('Control which family trees are included in the public Global Tree. You can force-enable or disable any tree regardless of the owner\'s setting.') }}
                    </p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                    ← {{ __('Admin dashboard') }}
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-[#bfdbfe] bg-[#eff6ff] px-4 py-3 text-sm text-[#1e40af]">{{ session('status') }}</div>
        @endif

        {{-- Stats --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#6f7b83]">{{ __('Enabled branches') }}</p>
                <p class="mt-2 text-3xl font-semibold text-[#1f252b]">{{ number_format($enabledCount) }}</p>
            </div>
            <div class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-[#6f7b83]">{{ __('Visible profiles') }}</p>
                <p class="mt-2 text-3xl font-semibold text-[#1f252b]">{{ number_format($totalPublicProfiles) }}</p>
                <p class="mt-0.5 text-xs text-[#9daab4]">{{ __('after per-person exclusions') }}</p>
            </div>
        </div>

        {{-- Quick link to the global tree --}}
        <div class="flex items-center justify-between rounded-xl border border-[#dbeafe] bg-[#eff6ff] px-5 py-3 text-sm text-[#1e40af]">
            <span>{{ __('View the live Global Tree as users see it:') }}</span>
            <a href="{{ route('global-tree.index') }}" class="font-medium underline hover:text-[#1d4ed8]">{{ __('Open Global Tree') }}</a>
        </div>

        {{-- Trees table --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            <div class="divide-y divide-[#f0f4f8]">
                @forelse ($trees as $tree)
                    <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.trees.show', $tree) }}" class="font-medium text-[#1f252b] hover:text-[#2563eb]">{{ $tree->name }}</a>
                                @if ($tree->global_tree_enabled)
                                    <span class="rounded-[4px] bg-[#dcfce7] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#166534]">{{ __('In Global Tree') }}</span>
                                @else
                                    <span class="rounded-[4px] bg-[#f3f4f6] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#6b7280]">{{ __('Not included') }}</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-sm text-[#6f7b83]">
                                {{ __('Owner:') }} {{ $tree->user->name }}
                                · {{ $tree->visible_people_count }} / {{ $tree->total_people_count }} {{ __('profiles visible') }}
                                · {{ $tree->created_at->format('M j, Y') }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('admin.global-tree.toggle', $tree) }}">
                            @csrf
                            @method('PATCH')
                            <button
                                type="submit"
                                class="{{ $tree->global_tree_enabled
                                    ? 'border-[#fca5a5] bg-[#fff1f2] text-[#b91c1c] hover:bg-[#fee2e2]'
                                    : 'border-[#cdd7e1] bg-[#f7f9fb] text-[#334155] hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]'
                                }} rounded-[6px] border px-4 py-1.5 text-sm font-medium transition"
                            >
                                {{ $tree->global_tree_enabled ? __('Remove from Global Tree') : __('Add to Global Tree') }}
                            </button>
                        </form>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-sm text-[#6f7b83]">{{ __('No trees found.') }}</div>
                @endforelse
            </div>

            @if ($trees->hasPages())
                <div class="border-t border-[#f0f4f8] px-6 py-4">
                    {{ $trees->links() }}
                </div>
            @endif
        </section>

    </div>
</x-layouts::app>
