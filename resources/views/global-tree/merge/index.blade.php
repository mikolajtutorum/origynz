<x-layouts::app :title="__('Merge Candidates')" active-nav="global-tree">
    <div class="genealogy-shell space-y-6">

        {{-- Header --}}
        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Global Tree · Curator Tools') }}</p>
                    <h1 class="text-4xl font-semibold tracking-tight text-[#1f252b]">{{ __('Merge Candidates') }}</h1>
                    <p class="max-w-2xl text-base leading-7 text-[#4f5963]">
                        {{ __('Profiles from different branches that may represent the same person. Review each pair and merge or dismiss.') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('global-tree.merge.scan') }}">
                    @csrf
                    <button type="submit" class="rounded-[8px] bg-[#2563eb] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]">
                        {{ __('Scan for Duplicates') }}
                    </button>
                </form>
            </div>

            {{-- Tab navigation --}}
            <div class="mt-6 flex gap-1 border-b border-[#e3e8ee]">
                <a href="{{ route('global-tree.index') }}"
                   class="rounded-t-[6px] px-4 py-2 text-sm font-medium text-[#6f7b83] transition hover:text-[#1f252b]">
                    {{ __('Directory') }}
                </a>
                <a href="{{ route('global-tree.pedigree') }}"
                   class="rounded-t-[6px] px-4 py-2 text-sm font-medium text-[#6f7b83] transition hover:text-[#1f252b]">
                    {{ __('Pedigree Chart') }}
                </a>
                <a href="{{ route('global-tree.relationship-calculator') }}"
                   class="rounded-t-[6px] px-4 py-2 text-sm font-medium text-[#6f7b83] transition hover:text-[#1f252b]">
                    {{ __('Relationship Calculator') }}
                </a>
                <a href="{{ route('global-tree.merge.index') }}"
                   class="rounded-t-[6px] border-b-2 border-[#2563eb] px-4 py-2 text-sm font-medium text-[#2563eb]">
                    {{ __('Merge Candidates') }}
                </a>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 text-sm text-[#166534]">
                {{ session('success') }}
            </div>
        @endif

        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            @if ($candidates->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-[#6f7b83]">{{ __('No pending merge candidates. Run a scan to detect duplicates.') }}</p>
                </div>
            @else
                <div class="divide-y divide-[#f0f4f8]">
                    @foreach ($candidates as $candidate)
                        <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center">

                            {{-- Score badge --}}
                            <div class="flex w-16 shrink-0 flex-col items-center">
                                <span class="text-2xl font-bold text-[#1f252b]">{{ $candidate->similarity_score }}</span>
                                <span class="text-[10px] uppercase tracking-widest text-[#9daab4]">{{ __('match') }}</span>
                            </div>

                            {{-- Person A --}}
                            <div class="flex-1 rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-4 py-3">
                                <p class="font-semibold text-[#1f252b]">{{ $candidate->personA?->display_name }}</p>
                                <p class="mt-0.5 text-sm text-[#6f7b83]">{{ $candidate->personA?->life_span }}</p>
                                <p class="mt-0.5 text-xs text-[#9daab4]">{{ $candidate->personA?->familyTree?->name }}</p>
                            </div>

                            <span class="shrink-0 text-[#9daab4]">≈</span>

                            {{-- Person B --}}
                            <div class="flex-1 rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-4 py-3">
                                <p class="font-semibold text-[#1f252b]">{{ $candidate->personB?->display_name }}</p>
                                <p class="mt-0.5 text-sm text-[#6f7b83]">{{ $candidate->personB?->life_span }}</p>
                                <p class="mt-0.5 text-xs text-[#9daab4]">{{ $candidate->personB?->familyTree?->name }}</p>
                            </div>

                            {{-- Actions --}}
                            <div class="flex shrink-0 gap-2">
                                <a href="{{ route('global-tree.merge.review', $candidate) }}"
                                   class="rounded-[6px] border border-[#2563eb] px-4 py-2 text-sm font-medium text-[#2563eb] transition hover:bg-[#eff6ff]">
                                    {{ __('Review') }}
                                </a>
                                <form method="POST" action="{{ route('global-tree.merge.dismiss', $candidate) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-[6px] border border-[#cdd7e1] px-4 py-2 text-sm font-medium text-[#6f7b83] transition hover:border-[#f87171] hover:text-[#dc2626]">
                                        {{ __('Dismiss') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($candidates->hasPages())
                    <div class="border-t border-[#f0f4f8] px-6 py-4">
                        {{ $candidates->links() }}
                    </div>
                @endif
            @endif
        </section>

    </div>
</x-layouts::app>
