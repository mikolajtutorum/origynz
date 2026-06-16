<x-layouts::app :title="__('WikiTree')" active-nav="integrations">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <a href="{{ route('integrations.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm text-[#6f7b83] hover:text-[#2563eb]">
                ← {{ __('Integrations') }}
            </a>
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f0f6ff] text-3xl font-bold text-[#2563eb]">WT</div>
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-[#1f252b]">WikiTree</h1>
                    <p class="text-sm text-[#6f7b83]">{{ __('Collaborative community-edited world family tree') }}</p>
                </div>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 text-sm text-[#166534]">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-[#fca5a5] bg-[#fef2f2] px-5 py-4 text-sm text-[#991b1b]">{{ $errors->first() }}</div>
        @endif

        @if ($integration)
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-[#1f252b]">{{ __('Connected as :name', ['name' => $integration->provider_username ?? $integration->provider_user_id]) }}</p>
                        <p class="mt-0.5 text-sm text-[#6f7b83]">{{ __('Connected :ago', ['ago' => $integration->created_at->diffForHumans()]) }}</p>
                    </div>
                    <form method="POST" action="{{ route('integrations.wikitree.disconnect') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-[6px] border border-[#fca5a5] px-4 py-2 text-sm font-medium text-[#991b1b] transition hover:bg-[#fef2f2]">
                            {{ __('Disconnect') }}
                        </button>
                    </form>
                </div>
            </section>
        @else
            <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
                <h2 class="mb-4 text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Connect with WikiTree credentials') }}</h2>
                <p class="mb-4 text-sm text-[#6f7b83]">{{ __('WikiTree uses a username/password session. Your credentials are sent directly to WikiTree and are never stored by Origynz.') }}</p>
                <form method="POST" action="{{ route('integrations.wikitree.connect') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-[#1f252b]">{{ __('WikiTree Email') }}</label>
                        <input type="email" name="email" required
                               class="mt-1 w-full rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm focus:border-[#93c5fd] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-[#1f252b]">{{ __('WikiTree Password') }}</label>
                        <input type="password" name="password" required
                               class="mt-1 w-full rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm focus:border-[#93c5fd] focus:outline-none">
                    </div>
                    <button type="submit" class="rounded-[8px] bg-[#2563eb] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]">
                        {{ __('Connect') }}
                    </button>
                </form>
            </section>
        @endif

    </div>
</x-layouts::app>
