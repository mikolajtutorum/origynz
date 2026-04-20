<x-layouts::app :title="$user->name . ' — Admin'">
    <div class="genealogy-shell space-y-6">

        {{-- Header --}}
        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <span class="workspace-user-avatar !h-12 !w-12 !text-[16px]">{{ $user->initials() }}</span>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Admin — User profile') }}</p>
                        <h1 class="mt-0.5 text-3xl font-semibold tracking-tight text-[#1f252b]">{{ $user->name }}</h1>
                        <p class="text-sm text-[#6f7b83]">{{ $user->email }} · {{ __('Joined') }} {{ $user->created_at->format('M j, Y') }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.users.index') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                    ← {{ __('All users') }}
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-[#bfdbfe] bg-[#eff6ff] px-4 py-3 text-sm text-[#1e40af]">{{ session('status') }}</div>
        @endif

        <section class="grid gap-6 lg:grid-cols-3">

            {{-- Stats --}}
            <div class="grid grid-cols-2 gap-4 lg:col-span-1 lg:grid-cols-1">
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Family trees') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-[#1f252b]">{{ $user->family_trees_count }}</p>
                </div>
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('People added') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-[#1f252b]">{{ $user->people_count }}</p>
                </div>
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('Social accounts') }}</p>
                    <p class="mt-2 text-3xl font-semibold text-[#1f252b]">{{ $user->social_accounts_count }}</p>
                </div>
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#6f7b83]">{{ __('2FA') }}</p>
                    <p class="mt-2 text-lg font-semibold text-[#1f252b]">
                        {{ $user->two_factor_confirmed_at ? __('Enabled') : __('Disabled') }}
                    </p>
                </div>
            </div>

            <div class="space-y-6 lg:col-span-2">

                {{-- Role Management --}}
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-[#1f252b]">{{ __('Site role') }}</h2>
                    <p class="mt-1 text-sm text-[#6f7b83]">{{ __('Controls platform-level access. Super admins bypass all tree permission checks.') }}</p>
                    @php $siteRole = $user->roles->where('pivot.family_tree_id', 0)->first(); @endphp
                    <form method="POST" action="{{ route('admin.users.update-role', $user) }}" class="mt-4 flex items-end gap-3">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1">
                            <label class="mb-1 block text-xs font-medium text-[#6f7b83]">{{ __('Assign role') }}</label>
                            <select name="role" class="w-full rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-3 py-2 text-sm text-[#1f252b] focus:border-[#93c5fd] focus:outline-none">
                                <option value="member" @selected(! $siteRole || $siteRole->name === 'member')>Member</option>
                                <option value="admin" @selected($siteRole?->name === 'admin')>Admin</option>
                                <option value="super admin" @selected($siteRole?->name === 'super admin')>Super Admin</option>
                            </select>
                        </div>
                        <button type="submit" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                            {{ __('Update role') }}
                        </button>
                    </form>
                </div>

                {{-- Trees --}}
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-[#1f252b]">{{ __('Family trees') }}</h2>
                    <div class="mt-4 space-y-3">
                        @forelse ($user->familyTrees as $tree)
                            <div class="flex items-center justify-between gap-4 rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-4 py-3">
                                <div>
                                    <p class="font-medium text-[#1f252b]">{{ $tree->name }}</p>
                                    <p class="text-xs text-[#6f7b83]">{{ $tree->people_count }} {{ __('profiles') }} · {{ $tree->home_region ?: __('no region') }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-[6px] bg-[#1f252b] px-2 py-0.5 text-[10px] font-medium uppercase tracking-[0.18em] text-white">{{ $tree->privacy }}</span>
                                    <a href="{{ route('admin.trees.show', $tree) }}" class="rounded-[6px] border border-[#cdd7e1] bg-white px-3 py-1 text-xs font-medium text-[#334155] hover:text-[#2563eb]">{{ __('View') }}</a>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-[#6f7b83]">{{ __('No family trees.') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- Danger zone --}}
                @if ($user->id !== auth()->id())
                    <div class="rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-6">
                        <h2 class="text-lg font-semibold text-[#dc2626]">{{ __('Danger zone') }}</h2>
                        <p class="mt-1 text-sm text-[#b91c1c]">{{ __('Deleting a user permanently removes their account, trees, and all associated data. This cannot be undone.') }}</p>
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-4" onsubmit="return confirm('Delete {{ addslashes($user->name) }} and all their data? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-[6px] border border-[#fca5a5] bg-[#fee2e2] px-4 py-2 text-sm font-semibold text-[#dc2626] transition hover:bg-[#fca5a5]">
                                {{ __('Delete user') }}
                            </button>
                        </form>
                    </div>
                @endif

            </div>
        </section>

        {{-- Activity --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Recent activity') }}</h2>
            <div class="mt-5 divide-y divide-[#f0f4f8]">
                @forelse ($recentActivity as $log)
                    <div class="flex items-start justify-between gap-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-[#1f252b]">{{ $log->description }}</p>
                            @if ($log->subject_type)
                                <p class="text-xs text-[#6f7b83]">{{ class_basename($log->subject_type) }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 text-xs text-[#6f7b83]">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="py-4 text-sm text-[#6f7b83]">{{ __('No activity logged.') }}</p>
                @endforelse
            </div>
        </section>

    </div>
</x-layouts::app>
