<x-layouts::app :title="__('Profile Claims')" active-nav="trees">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Trees') }}</p>
            <h1 class="mt-1 text-4xl font-semibold tracking-tight text-[#1f252b]">{{ __('Profile Claims') }}</h1>
            <p class="mt-2 max-w-2xl text-base leading-7 text-[#4f5963]">
                {{ __('Living relatives may claim a profile to link it to their account. Review and approve or reject each request.') }}
            </p>
        </section>

        @if (session('success'))
            <div class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 text-sm text-[#166534]">{{ session('success') }}</div>
        @endif

        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            @if ($claims->isEmpty())
                <div class="px-6 py-16 text-center text-sm text-[#6f7b83]">
                    {{ __('No profile claims to review.') }}
                </div>
            @else
                <div class="divide-y divide-[#f0f4f8]">
                    @foreach ($claims as $claim)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-start">

                            <div class="flex-1 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-[#1f252b]">{{ $claim->person?->display_name }}</span>
                                    <span class="text-xs text-[#9daab4]">{{ $claim->person?->familyTree?->name }}</span>

                                    @php
                                        $statusClasses = match($claim->status->value) {
                                            'pending'  => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                            default    => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider {{ $statusClasses }}">
                                        {{ $claim->status->value }}
                                    </span>
                                </div>

                                <p class="text-sm text-[#4f5963]">
                                    {{ __('Claimed by') }} <span class="font-medium">{{ $claim->user?->name }}</span>
                                    · {{ $claim->created_at->diffForHumans() }}
                                </p>

                                @if ($claim->message)
                                    <p class="mt-1 rounded-lg bg-[#f7f9fb] px-3 py-2 text-sm italic text-[#6f7b83]">
                                        "{{ $claim->message }}"
                                    </p>
                                @endif
                            </div>

                            @if ($claim->status->value === 'pending')
                                <div class="flex shrink-0 gap-2">
                                    <form method="POST" action="{{ route('people.claims.review', $claim) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="approved">
                                        <button type="submit" class="rounded-[6px] border border-[#86efac] bg-[#f0fdf4] px-4 py-2 text-sm font-medium text-[#166534] transition hover:bg-[#dcfce7]">
                                            {{ __('Approve') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('people.claims.review', $claim) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="decision" value="rejected">
                                        <button type="submit" class="rounded-[6px] border border-[#fca5a5] bg-[#fef2f2] px-4 py-2 text-sm font-medium text-[#991b1b] transition hover:bg-[#fee2e2]">
                                            {{ __('Reject') }}
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($claims->hasPages())
                    <div class="border-t border-[#f0f4f8] px-6 py-4">
                        {{ $claims->links() }}
                    </div>
                @endif
            @endif
        </section>

    </div>
</x-layouts::app>
