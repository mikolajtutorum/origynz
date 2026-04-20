<x-layouts::app :title="__('Trees — Admin')">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Admin Portal') }}</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#1f252b]">{{ __('Family tree management') }}</h1>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                    ← {{ __('Admin dashboard') }}
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-[#bfdbfe] bg-[#eff6ff] px-4 py-3 text-sm text-[#1e40af]">{{ session('status') }}</div>
        @endif

        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            <div class="border-b border-[#f0f4f8] px-6 py-4">
                <form method="GET" class="flex items-center gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('Search by name or region…') }}"
                        class="flex-1 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm text-[#1f252b] placeholder-[#9daab4] focus:border-[#93c5fd] focus:outline-none"
                    >
                    <button type="submit" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:bg-[#e8f0f7]">
                        {{ __('Search') }}
                    </button>
                    @if (request('search'))
                        <a href="{{ route('admin.trees.index') }}" class="rounded-[6px] px-3 py-2 text-sm text-[#6f7b83] hover:text-[#2563eb]">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            <div class="divide-y divide-[#f0f4f8]">
                @forelse ($trees as $tree)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <div>
                            <a href="{{ route('admin.trees.show', $tree) }}" class="font-medium text-[#1f252b] hover:text-[#2563eb]">{{ $tree->name }}</a>
                            <p class="text-sm text-[#6f7b83]">
                                {{ __('Owner:') }} {{ $tree->user->name }} ·
                                {{ $tree->people_count }} {{ __('profiles') }} ·
                                {{ $tree->relationships_count }} {{ __('links') }} ·
                                {{ $tree->media_items_count }} {{ __('media') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <span class="rounded-[6px] bg-[#1f252b] px-2 py-0.5 text-[10px] font-medium uppercase tracking-[0.18em] text-white">{{ $tree->privacy }}</span>
                            <span class="text-xs text-[#6f7b83]">{{ $tree->created_at->format('M j, Y') }}</span>
                            <a href="{{ route('admin.trees.show', $tree) }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-3 py-1.5 text-xs font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">{{ __('View') }}</a>
                        </div>
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
