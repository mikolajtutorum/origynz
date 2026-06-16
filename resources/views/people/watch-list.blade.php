<x-layouts::app :title="__('Watch List')" active-nav="global-tree">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Global Tree') }}</p>
            <h1 class="mt-1 text-4xl font-semibold tracking-tight text-[#1f252b]">{{ __('My Watch List') }}</h1>
            <p class="mt-2 max-w-2xl text-base leading-7 text-[#4f5963]">
                {{ __('Profiles you are following. You will be notified when their data changes.') }}
            </p>
        </section>

        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            @if ($watches->isEmpty())
                <div class="px-6 py-16 text-center">
                    <p class="text-[#6f7b83]">{{ __('You are not watching any profiles yet.') }}</p>
                    <a href="{{ route('global-tree.index') }}" class="mt-4 inline-block text-sm text-[#2563eb] hover:underline">
                        {{ __('Browse the Global Tree') }}
                    </a>
                </div>
            @else
                <div class="divide-y divide-[#f0f4f8]">
                    @foreach ($watches as $watch)
                        <div class="flex items-center gap-4 px-6 py-4"
                             x-data="{ watching: true }"
                             x-show="watching">

                            {{-- Avatar --}}
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#dbeafe] text-sm font-semibold text-[#2563eb]">
                                {{ mb_strtoupper(mb_substr($watch->person?->display_name ?? '?', 0, 1)) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-[#1f252b]">{{ $watch->person?->display_name }}</p>
                                <p class="mt-0.5 text-sm text-[#6f7b83]">
                                    {{ $watch->person?->life_span }}
                                    @if ($watch->person?->birth_place)
                                        <span class="mx-1">·</span>{{ $watch->person->birth_place }}
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs text-[#9daab4]">{{ $watch->person?->familyTree?->name }}</p>
                            </div>

                            <div class="shrink-0 text-xs text-[#9daab4]">
                                {{ __('Watching since') }} {{ $watch->created_at->format('j M Y') }}
                            </div>

                            <button
                                x-on:click.prevent="
                                    fetch('{{ route('people.watch.toggle', $watch->person_id) }}', {
                                        method: 'POST',
                                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                                    }).then(r => r.json()).then(d => { watching = d.watching; })
                                "
                                class="shrink-0 rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-3 py-1.5 text-xs font-medium text-[#6f7b83] transition hover:border-[#f87171] hover:text-[#dc2626]">
                                {{ __('Unwatch') }}
                            </button>
                        </div>
                    @endforeach
                </div>

                @if ($watches->hasPages())
                    <div class="border-t border-[#f0f4f8] px-6 py-4">
                        {{ $watches->links() }}
                    </div>
                @endif
            @endif
        </section>

    </div>
</x-layouts::app>
