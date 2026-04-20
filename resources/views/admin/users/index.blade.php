<x-layouts::app :title="__('Users — Admin')">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Admin Portal') }}</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#1f252b]">{{ __('User management') }}</h1>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                    ← {{ __('Admin dashboard') }}
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-[#bfdbfe] bg-[#eff6ff] px-4 py-3 text-sm text-[#1e40af]">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-xl border border-[#fecaca] bg-[#fef2f2] px-4 py-3 text-sm text-[#dc2626]">{{ session('error') }}</div>
        @endif

        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            <div class="border-b border-[#f0f4f8] px-6 py-4">
                <form method="GET" class="flex items-center gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('Search by name or email…') }}"
                        class="flex-1 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm text-[#1f252b] placeholder-[#9daab4] focus:border-[#93c5fd] focus:outline-none"
                    >
                    <button type="submit" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:bg-[#e8f0f7]">
                        {{ __('Search') }}
                    </button>
                    @if (request('search'))
                        <a href="{{ route('admin.users.index') }}" class="rounded-[6px] px-3 py-2 text-sm text-[#6f7b83] hover:text-[#2563eb]">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            <div class="divide-y divide-[#f0f4f8]">
                @forelse ($users as $user)
                    <div class="flex items-center justify-between gap-4 px-6 py-4">
                        <div class="flex items-center gap-4">
                            <span class="workspace-user-avatar !h-9 !w-9 !text-[12px]">{{ $user->initials() }}</span>
                            <div>
                                <a href="{{ route('admin.users.show', $user) }}" class="font-medium text-[#1f252b] hover:text-[#2563eb]">{{ $user->name }}</a>
                                <p class="text-sm text-[#6f7b83]">{{ $user->email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-[#6f7b83]">
                            <span>{{ $user->family_trees_count }} {{ __('trees') }}</span>
                            <span>{{ $user->people_count }} {{ __('profiles') }}</span>
                            @php $siteRole = $user->roles->where('pivot.family_tree_id', 0)->first(); @endphp
                            @if ($siteRole)
                                <span class="rounded-[6px] border border-[#fca5a5] bg-[#fef2f2] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.15em] text-[#dc2626]">
                                    {{ $siteRole->name }}
                                </span>
                            @else
                                <span class="rounded-[6px] border border-[#e3e8ee] bg-[#f7f9fb] px-2 py-0.5 text-[11px] uppercase tracking-[0.15em] text-[#9daab4]">member</span>
                            @endif
                            <span class="text-xs">{{ $user->created_at->format('M j, Y') }}</span>
                            <a href="{{ route('admin.users.show', $user) }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-3 py-1.5 text-xs font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">{{ __('View') }}</a>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-sm text-[#6f7b83]">{{ __('No users found.') }}</div>
                @endforelse
            </div>

            @if ($users->hasPages())
                <div class="border-t border-[#f0f4f8] px-6 py-4">
                    {{ $users->links() }}
                </div>
            @endif
        </section>

    </div>
</x-layouts::app>
