<x-layouts::app :title="__('Review Merge')" active-nav="global-tree">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Global Tree · Merge Review') }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#1f252b]">{{ __('Resolve Merge Conflict') }}</h1>
            <p class="mt-2 text-sm leading-6 text-[#4f5963]">
                {{ __('Choose which value to keep for each field, then select which profile survives.') }}
            </p>
        </section>

        <form method="POST" action="{{ route('global-tree.merge.execute', $candidate) }}">
            @csrf

            {{-- Surviving person choice --}}
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-5 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Surviving Profile') }}</h2>
                <div class="flex gap-4">
                    <label class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl border-2 border-[#e3e8ee] p-4 transition has-[:checked]:border-[#2563eb] has-[:checked]:bg-[#eff6ff]">
                        <input type="radio" name="surviving" value="a" checked class="accent-[#2563eb]">
                        <div>
                            <p class="font-semibold text-[#1f252b]">{{ $candidate->personA->display_name }}</p>
                            <p class="text-xs text-[#9daab4]">{{ $candidate->personA->familyTree?->name }}</p>
                        </div>
                    </label>
                    <label class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl border-2 border-[#e3e8ee] p-4 transition has-[:checked]:border-[#2563eb] has-[:checked]:bg-[#eff6ff]">
                        <input type="radio" name="surviving" value="b" class="accent-[#2563eb]">
                        <div>
                            <p class="font-semibold text-[#1f252b]">{{ $candidate->personB->display_name }}</p>
                            <p class="text-xs text-[#9daab4]">{{ $candidate->personB->familyTree?->name }}</p>
                        </div>
                    </label>
                </div>
            </section>

            {{-- Field-by-field decisions --}}
            <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
                <div class="border-b border-[#f0f4f8] px-6 py-4">
                    <h2 class="text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Field Decisions') }}</h2>
                    <p class="mt-1 text-xs text-[#9daab4]">{{ __('Select which value to keep for each field. Defaults to Profile A.') }}</p>
                </div>

                <div class="divide-y divide-[#f0f4f8]">
                    @foreach ($fields as $field)
                        @php
                            $valA = $candidate->personA->{$field};
                            $valB = $candidate->personB->{$field};
                            $same = $valA == $valB;
                        @endphp
                        <div class="grid grid-cols-[140px_1fr_40px_1fr] items-center gap-4 px-6 py-3">
                            <span class="text-xs font-medium uppercase tracking-wide text-[#6f7b83]">
                                {{ str_replace('_', ' ', $field) }}
                            </span>

                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[#e3e8ee] px-3 py-2 transition has-[:checked]:border-[#2563eb] has-[:checked]:bg-[#eff6ff] {{ $same ? 'opacity-40' : '' }}">
                                <input type="radio" name="decisions[{{ $field }}]" value="a" checked class="accent-[#2563eb]">
                                <span class="text-sm text-[#1f252b]">
                                    @if ($valA instanceof \Carbon\Carbon)
                                        {{ $valA->format('j M Y') }}
                                    @elseif (is_bool($valA))
                                        {{ $valA ? __('Yes') : __('No') }}
                                    @else
                                        {{ $valA ?? '—' }}
                                    @endif
                                </span>
                            </label>

                            <span class="text-center text-xs text-[#9daab4]">{{ $same ? '=' : 'vs' }}</span>

                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-[#e3e8ee] px-3 py-2 transition has-[:checked]:border-[#2563eb] has-[:checked]:bg-[#eff6ff] {{ $same ? 'opacity-40' : '' }}">
                                <input type="radio" name="decisions[{{ $field }}]" value="b" {{ $same ? 'disabled' : '' }} class="accent-[#2563eb]">
                                <span class="text-sm text-[#1f252b]">
                                    @if ($valB instanceof \Carbon\Carbon)
                                        {{ $valB->format('j M Y') }}
                                    @elseif (is_bool($valB))
                                        {{ $valB ? __('Yes') : __('No') }}
                                    @else
                                        {{ $valB ?? '—' }}
                                    @endif
                                </span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Related record counts --}}
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-5 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Related Records (will all transfer to surviving profile)') }}</h2>
                <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                    @foreach (['events', 'sourceCitations', 'mediaItems'] as $rel)
                        <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] p-4 text-center">
                            <p class="text-2xl font-bold text-[#1f252b]">
                                {{ $candidate->personA->{$rel}->count() + $candidate->personB->{$rel}->count() }}
                            </p>
                            <p class="mt-0.5 text-xs uppercase tracking-widest text-[#9daab4]">{{ str_replace('C', ' C', ucfirst($rel)) }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Submit --}}
            <div class="flex justify-end gap-4">
                <a href="{{ route('global-tree.merge.index') }}"
                   class="rounded-[8px] border border-[#cdd7e1] bg-[#f7f9fb] px-5 py-2.5 text-sm font-medium text-[#334155] transition hover:bg-[#e8f0f7]">
                    {{ __('Cancel') }}
                </a>
                <button type="submit"
                        class="rounded-[8px] bg-[#dc2626] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b91c1c]"
                        onclick="return confirm('{{ __('This action cannot be undone. Continue?') }}')">
                    {{ __('Merge Profiles') }}
                </button>
            </div>
        </form>

    </div>
</x-layouts::app>
