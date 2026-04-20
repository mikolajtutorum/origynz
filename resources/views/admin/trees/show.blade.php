<x-layouts::app :title="$tree->name . ' — Admin'">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Admin — Family tree') }}</p>
                    <h1 class="mt-0.5 text-3xl font-semibold tracking-tight text-[#1f252b]">{{ $tree->name }}</h1>
                    <p class="text-sm text-[#6f7b83]">
                        {{ __('Owner:') }} <a href="{{ route('admin.users.show', $tree->user) }}" class="text-[#2563eb] hover:underline">{{ $tree->user->name }}</a>
                        · {{ $tree->home_region ?: __('no region') }}
                        · {{ __('Created') }} {{ $tree->created_at->format('M j, Y') }}
                    </p>
                </div>
                <a href="{{ route('admin.trees.index') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                    ← {{ __('All trees') }}
                </a>
            </div>
        </section>

        @if (session('status'))
            <div class="rounded-xl border border-[#bfdbfe] bg-[#eff6ff] px-4 py-3 text-sm text-[#1e40af]">{{ session('status') }}</div>
        @endif

        <section class="grid grid-cols-2 gap-4 lg:grid-cols-5">
            @foreach ([
                ['label' => __('Profiles'), 'value' => $tree->people_count],
                ['label' => __('Links'), 'value' => $tree->relationships_count],
                ['label' => __('Media'), 'value' => $tree->media_items_count],
                ['label' => __('Sources'), 'value' => $tree->sources_count],
                ['label' => __('Invitations'), 'value' => $tree->invitations_count],
            ] as $stat)
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#6f7b83]">{{ $stat['label'] }}</p>
                    <p class="mt-2 text-3xl font-semibold text-[#1f252b]">{{ $stat['value'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            {{-- Details --}}
            <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-[#1f252b]">{{ __('Tree details') }}</h2>
                <dl class="mt-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <dt class="text-[#6f7b83]">{{ __('Privacy') }}</dt>
                        <dd class="font-medium text-[#1f252b]">
                            <span class="rounded-[6px] bg-[#1f252b] px-2 py-0.5 text-[10px] font-medium uppercase tracking-[0.18em] text-white">{{ $tree->privacy }}</span>
                        </dd>
                    </div>
                    @if ($tree->description)
                        <div class="text-sm">
                            <dt class="text-[#6f7b83]">{{ __('Description') }}</dt>
                            <dd class="mt-1 text-[#1f252b]">{{ $tree->description }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <dt class="text-[#6f7b83]">{{ __('Last updated') }}</dt>
                        <dd class="font-medium text-[#1f252b]">{{ $tree->updated_at->format('M j, Y H:i') }}</dd>
                    </div>
                </dl>
                <div class="mt-5">
                    <a href="{{ route('trees.show', $tree) }}" target="_blank" class="flex items-center justify-between rounded-xl border border-[#d8e0e7] bg-[#f7f9fb] px-4 py-3 text-sm text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                        <span>{{ __('Open workspace as admin') }}</span><span>↗</span>
                    </a>
                </div>
            </div>

            {{-- Danger zone --}}
            <div class="rounded-2xl border border-[#fecaca] bg-[#fef2f2] p-6">
                <h2 class="text-lg font-semibold text-[#dc2626]">{{ __('Danger zone') }}</h2>
                <p class="mt-1 text-sm text-[#b91c1c]">{{ __('Permanently deletes this tree and all associated profiles, media, and data. This cannot be undone.') }}</p>
                <form method="POST" action="{{ route('admin.trees.destroy', $tree) }}" class="mt-4" onsubmit="return confirm('Delete tree \'{{ addslashes($tree->name) }}\' and all its data? Cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-[6px] border border-[#fca5a5] bg-[#fee2e2] px-4 py-2 text-sm font-semibold text-[#dc2626] transition hover:bg-[#fca5a5]">
                        {{ __('Delete tree') }}
                    </button>
                </form>
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
                            <p class="text-xs text-[#6f7b83]">{{ $log->causer?->name ?? __('System') }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-[#6f7b83]">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="py-4 text-sm text-[#6f7b83]">{{ __('No activity logged for this tree.') }}</p>
                @endforelse
            </div>
        </section>

    </div>
</x-layouts::app>
