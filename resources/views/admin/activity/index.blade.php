<x-layouts::app :title="__('Activity Log — Admin')">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Admin Portal') }}</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#1f252b]">{{ __('Activity log') }}</h1>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                    ← {{ __('Admin dashboard') }}
                </a>
            </div>
        </section>

        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            <div class="border-b border-[#f0f4f8] px-6 py-4">
                <form method="GET" class="flex items-center gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('Search log descriptions…') }}"
                        class="flex-1 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm text-[#1f252b] placeholder-[#9daab4] focus:border-[#93c5fd] focus:outline-none"
                    >
                    <button type="submit" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:bg-[#e8f0f7]">
                        {{ __('Search') }}
                    </button>
                    @if (request('search'))
                        <a href="{{ route('admin.activity.index') }}" class="rounded-[6px] px-3 py-2 text-sm text-[#6f7b83] hover:text-[#2563eb]">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>

            <div class="divide-y divide-[#f0f4f8]">
                @forelse ($logs as $log)
                    <div class="flex items-start justify-between gap-4 px-6 py-4">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-[#1f252b]">{{ $log->description }}</p>
                            <p class="mt-0.5 text-xs text-[#6f7b83]">
                                @if ($log->causer)
                                    <a href="{{ route('admin.users.show', $log->causer) }}" class="hover:text-[#2563eb]">{{ $log->causer->name }}</a>
                                @else
                                    {{ __('System') }}
                                @endif
                                @if ($log->subject_type)
                                    · {{ class_basename($log->subject_type) }}
                                    @if ($log->subject_id) #{{ $log->subject_id }} @endif
                                @endif
                                @if ($log->event)
                                    · <span class="font-medium">{{ $log->event }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-[#6f7b83]">{{ $log->created_at->format('M j, Y H:i') }}</span>
                    </div>
                @empty
                    <div class="px-6 py-10 text-center text-sm text-[#6f7b83]">{{ __('No activity logged yet.') }}</div>
                @endforelse
            </div>

            @if ($logs->hasPages())
                <div class="border-t border-[#f0f4f8] px-6 py-4">
                    {{ $logs->links() }}
                </div>
            @endif
        </section>

    </div>
</x-layouts::app>
