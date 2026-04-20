<x-layouts::app :title="__('Admin Portal')">
    <div class="genealogy-shell space-y-6">

        {{-- Header --}}
        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <div class="flex items-center justify-between gap-6">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Super Admin') }}</p>
                    <h1 class="text-4xl font-semibold tracking-tight text-[#1f252b]">{{ __('Admin Portal') }}</h1>
                    <p class="max-w-xl text-base leading-7 text-[#4f5963]">{{ __('Full platform overview and control. Manage users, family trees, and monitor all activity.') }}</p>
                </div>
                <div class="flex flex-col gap-2 text-right">
                    <span class="inline-flex items-center gap-2 rounded-[6px] border border-[#fecaca] bg-[#fef2f2] px-3 py-1.5 text-xs font-semibold text-[#dc2626]">
                        ● {{ __('Super Admin Access') }}
                    </span>
                </div>
            </div>
        </section>

        {{-- Stats --}}
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <a href="{{ route('admin.users.index') }}" class="group rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Total Users') }}</p>
                <p class="mt-3 text-4xl font-semibold text-[#1f252b]">{{ $stats['users'] }}</p>
                <p class="mt-2 text-sm text-[#2563eb] group-hover:underline">{{ __('Manage users →') }}</p>
            </a>
            <a href="{{ route('admin.trees.index') }}" class="group rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Family Trees') }}</p>
                <p class="mt-3 text-4xl font-semibold text-[#1f252b]">{{ $stats['trees'] }}</p>
                <p class="mt-2 text-sm text-[#2563eb] group-hover:underline">{{ __('Manage trees →') }}</p>
            </a>
            <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Total Profiles') }}</p>
                <p class="mt-3 text-4xl font-semibold text-[#1f252b]">{{ $stats['people'] }}</p>
            </div>
            <a href="{{ route('admin.activity.index') }}" class="group rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Activity Logs') }}</p>
                <p class="mt-3 text-4xl font-semibold text-[#1f252b]">{{ $stats['recent_logs']->count() }}</p>
                <p class="mt-2 text-sm text-[#2563eb] group-hover:underline">{{ __('View all logs →') }}</p>
            </a>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">

            {{-- Recent Users --}}
            <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Recent users') }}</h2>
                    <a href="{{ route('admin.users.index') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                        {{ __('View all') }}
                    </a>
                </div>
                <div class="mt-5 space-y-3">
                    @foreach ($stats['recent_users'] as $user)
                        <a href="{{ route('admin.users.show', $user) }}" class="workspace-list-card group flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <span class="workspace-user-avatar !h-8 !w-8 !text-[11px]">{{ $user->initials() }}</span>
                                <div>
                                    <p class="font-medium text-[#1f252b] group-hover:text-[#2563eb]">{{ $user->name }}</p>
                                    <p class="text-sm text-[#6f7b83]">{{ $user->email }}</p>
                                </div>
                            </div>
                            <span class="text-xs text-[#6f7b83]">{{ $user->created_at->diffForHumans() }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="space-y-6">
                {{-- Recent Trees --}}
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Recent trees') }}</h2>
                        <a href="{{ route('admin.trees.index') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                            {{ __('View all') }}
                        </a>
                    </div>
                    <div class="mt-5 space-y-3">
                        @foreach ($stats['recent_trees'] as $tree)
                            <a href="{{ route('admin.trees.show', $tree) }}" class="workspace-list-card group flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-medium text-[#1f252b] group-hover:text-[#2563eb]">{{ $tree->name }}</p>
                                    <p class="text-sm text-[#6f7b83]">{{ $tree->user->name }}</p>
                                </div>
                                <span class="rounded-[6px] bg-[#1f252b] px-2 py-0.5 text-[10px] font-medium uppercase tracking-[0.18em] text-white">{{ $tree->privacy }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Quick nav --}}
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Admin tools') }}</h2>
                    <div class="mt-4 space-y-3">
                        <a href="{{ route('admin.users.index') }}" class="flex items-center justify-between rounded-xl border border-[#d8e0e7] bg-[#f7f9fb] px-4 py-3 text-sm text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                            <span>{{ __('User management') }}</span><span>›</span>
                        </a>
                        <a href="{{ route('admin.trees.index') }}" class="flex items-center justify-between rounded-xl border border-[#d8e0e7] bg-[#f7f9fb] px-4 py-3 text-sm text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                            <span>{{ __('Family tree management') }}</span><span>›</span>
                        </a>
                        <a href="{{ route('admin.activity.index') }}" class="flex items-center justify-between rounded-xl border border-[#d8e0e7] bg-[#f7f9fb] px-4 py-3 text-sm text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                            <span>{{ __('Activity log') }}</span><span>›</span>
                        </a>
                    </div>
                </div>
            </div>

        </section>

        {{-- Recent Activity --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Recent activity') }}</h2>
                <a href="{{ route('admin.activity.index') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                    {{ __('View all logs') }}
                </a>
            </div>
            <div class="mt-5 divide-y divide-[#f0f4f8]">
                @forelse ($stats['recent_logs'] as $log)
                    <div class="flex items-start justify-between gap-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-[#1f252b]">{{ $log->description }}</p>
                            <p class="text-xs text-[#6f7b83]">
                                {{ $log->causer?->name ?? __('System') }}
                                @if ($log->subject_type)
                                    · {{ class_basename($log->subject_type) }}
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-[#6f7b83]">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="py-4 text-sm text-[#6f7b83]">{{ __('No activity logged yet.') }}</p>
                @endforelse
            </div>
        </section>

    </div>
</x-layouts::app>
