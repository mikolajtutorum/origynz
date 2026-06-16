<x-layouts::app :title="__('API Tokens')" active-nav="settings">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Settings') }}</p>
            <h1 class="mt-1 text-4xl font-semibold tracking-tight text-[#1f252b]">{{ __('API Tokens') }}</h1>
            <p class="mt-2 max-w-2xl text-base leading-7 text-[#4f5963]">
                {{ __('Personal access tokens let third-party apps and Gramps sync tools access your family tree data via the REST API.') }}
                <a href="{{ route('api.docs') }}" class="text-[#2563eb] hover:underline">{{ __('View API documentation') }}</a>
            </p>
        </section>

        @if (session('new_token'))
            <div class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 shadow-sm">
                <p class="mb-2 text-sm font-semibold text-[#166534]">{{ __('Token created — copy it now, it won\'t be shown again.') }}</p>
                <code class="block break-all rounded-lg bg-[#dcfce7] px-4 py-3 text-xs font-mono text-[#166534]">
                    {{ session('new_token') }}
                </code>
            </div>
        @endif

        @if (session('success') && ! session('new_token'))
            <div class="rounded-xl border border-[#bbf7d0] bg-[#f0fdf4] px-5 py-4 text-sm text-[#166534]">{{ session('success') }}</div>
        @endif

        {{-- Create form --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">{{ __('Create New Token') }}</h2>
            <form method="POST" action="{{ route('settings.api-tokens.store') }}" class="flex flex-wrap gap-4">
                @csrf
                <input type="text" name="name" required placeholder="{{ __('Token name (e.g. Gramps, My App)') }}"
                       class="flex-1 min-w-[200px] rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm focus:border-[#93c5fd] focus:outline-none">
                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 text-sm text-[#334155]">
                        <input type="checkbox" name="abilities[]" value="read" checked class="accent-[#2563eb]">
                        {{ __('Read') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-[#334155]">
                        <input type="checkbox" name="abilities[]" value="write" class="accent-[#2563eb]">
                        {{ __('Write') }}
                    </label>
                </div>
                <button type="submit" class="rounded-[6px] bg-[#2563eb] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#1d4ed8]">
                    {{ __('Create Token') }}
                </button>
            </form>
        </section>

        {{-- Token list --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            @if ($tokens->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-[#6f7b83]">
                    {{ __('No tokens yet. Create one to get started.') }}
                </div>
            @else
                <div class="divide-y divide-[#f0f4f8]">
                    @foreach ($tokens as $token)
                        <div class="flex items-center gap-4 px-6 py-4">
                            <div class="flex-1">
                                <p class="font-medium text-[#1f252b]">{{ $token->name }}</p>
                                <p class="mt-0.5 text-xs text-[#9daab4]">
                                    {{ implode(', ', $token->abilities) }}
                                    · {{ __('Created :date', ['date' => $token->created_at->format('j M Y')]) }}
                                    @if ($token->last_used_at)
                                        · {{ __('Last used :ago', ['ago' => $token->last_used_at->diffForHumans()]) }}
                                    @else
                                        · {{ __('Never used') }}
                                    @endif
                                    @if ($token->expires_at)
                                        · {{ $token->expires_at->isPast() ? __('Expired') : __('Expires :date', ['date' => $token->expires_at->format('j M Y')]) }}
                                    @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('settings.api-tokens.destroy', $token->id) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-[#9daab4] transition hover:text-[#dc2626]">
                                    {{ __('Revoke') }}
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
</x-layouts::app>
