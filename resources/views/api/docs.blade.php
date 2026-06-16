<x-layouts::app :title="__('API Documentation')" active-nav="integrations">
    <div class="genealogy-shell space-y-6">

        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Developer') }}</p>
            <h1 class="mt-1 text-4xl font-semibold tracking-tight text-[#1f252b]">{{ __('REST API') }}</h1>
            <p class="mt-2 max-w-2xl text-base leading-7 text-[#4f5963]">
                {{ __('Read and write your family tree data programmatically. Authenticate with a Bearer token created on the') }}
                <a href="{{ route('settings.api-tokens') }}" class="text-[#2563eb] hover:underline">{{ __('API Tokens') }}</a>
                {{ __('page.') }}
            </p>
            <div class="mt-4 flex gap-4 text-sm">
                <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-4 py-2">
                    <span class="font-mono text-[#1f252b]">{{ $baseUrl }}</span>
                </div>
                <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-4 py-2 text-[#6f7b83]">
                    {{ __(':n req/min', ['n' => $rateLimit]) }}
                </div>
            </div>
        </section>

        {{-- Authentication --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
            <h2 class="text-lg font-semibold text-[#1f252b]">{{ __('Authentication') }}</h2>
            <p class="mt-2 text-sm text-[#4f5963]">{{ __('Pass your token in the Authorization header:') }}</p>
            <pre class="mt-3 overflow-x-auto rounded-xl bg-[#1e2a3a] px-5 py-4 text-sm text-[#93c5fd]"><code>Authorization: Bearer &lt;your-token&gt;
Accept: application/json</code></pre>
        </section>

        {{-- Endpoints --}}
        @php
            $endpoints = [
                ['GET',  '/me',                            __('Authenticated user info')],
                ['GET',  '/trees',                         __('List accessible trees (paginated)')],
                ['GET',  '/trees/{id}',                    __('Get a single tree')],
                ['GET',  '/trees/{id}/people',             __('List all people in a tree (paginated, 100/page)')],
                ['GET',  '/people/search?q=name',          __('Search people across all accessible trees')],
                ['GET',  '/people/{id}',                   __('Get a single person')],
                ['GET',  '/people/{id}/relationships',     __('Get all relationships for a person')],
                ['GET',  '/sync/gedcom/{tree_id}',         __('Export tree as GEDCOM 5.5 (Gramps sync)')],
                ['POST', '/sync/gedcom/{tree_id}',         __('Import/merge a GEDCOM file into a tree (Gramps sync, requires write token)')],
            ];
        @endphp
        <section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm">
            <div class="border-b border-[#f0f4f8] px-6 py-4">
                <h2 class="text-lg font-semibold text-[#1f252b]">{{ __('Endpoints') }}</h2>
            </div>
            <div class="divide-y divide-[#f0f4f8]">
                @foreach ($endpoints as [$method, $path, $desc])
                    <div class="flex flex-wrap items-center gap-4 px-6 py-3">
                        <span @class([
                            'shrink-0 rounded-[4px] px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider',
                            'bg-[#dbeafe] text-[#1d4ed8]' => $method === 'GET',
                            'bg-[#dcfce7] text-[#166534]' => $method === 'POST',
                            'bg-[#fef9c3] text-[#854d0e]' => $method === 'PATCH',
                            'bg-[#fee2e2] text-[#991b1b]' => $method === 'DELETE',
                        ])>{{ $method }}</span>
                        <code class="font-mono text-sm text-[#1f252b]">{{ $baseUrl }}{{ $path }}</code>
                        <span class="text-sm text-[#6f7b83]">{{ $desc }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Gramps sync --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-6 shadow-sm">
            <h2 class="text-lg font-semibold text-[#1f252b]">{{ __('Gramps / Desktop Software Sync') }}</h2>
            <p class="mt-2 text-sm text-[#4f5963]">
                {{ __('Use the GEDCOM sync endpoints with any desktop genealogy app that supports remote GEDCOM. Create a token with write access, then configure your software:') }}
            </p>
            <div class="mt-4 space-y-3">
                <div class="rounded-xl bg-[#f7f9fb] px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#6f7b83]">{{ __('Export (pull from Origynz)') }}</p>
                    <code class="mt-1 block text-sm text-[#1f252b]">GET {{ $baseUrl }}/sync/gedcom/{tree_id}</code>
                    <p class="mt-1 text-xs text-[#9daab4]">{{ __('Returns GEDCOM 5.5. Works with Gramps, MacFamilyTree, Legacy Family Tree, etc.') }}</p>
                </div>
                <div class="rounded-xl bg-[#f7f9fb] px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-[#6f7b83]">{{ __('Import (push to Origynz)') }}</p>
                    <code class="mt-1 block text-sm text-[#1f252b]">POST {{ $baseUrl }}/sync/gedcom/{tree_id}</code>
                    <p class="mt-1 text-xs text-[#9daab4]">{{ __('Multipart form-data with field "gedcom_file". Merges data without deleting existing records.') }}</p>
                </div>
            </div>
        </section>

        {{-- Rate limiting --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white px-6 py-5 shadow-sm">
            <h2 class="text-lg font-semibold text-[#1f252b]">{{ __('Rate Limiting') }}</h2>
            <p class="mt-2 text-sm text-[#4f5963]">
                {{ __('The API allows :n requests per minute per token. Exceeded requests receive a 429 response with a Retry-After header.', ['n' => $rateLimit]) }}
            </p>
        </section>

    </div>
</x-layouts::app>
