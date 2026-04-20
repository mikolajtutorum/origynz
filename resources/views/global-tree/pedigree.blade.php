<!DOCTYPE html>
@php
    $direction = config('app.locales.'.app()->getLocale().'.direction', 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $direction }}" class="h-full bg-[#efefef]">
    <head>
        @include('partials.head', ['title' => __('Global Tree — Pedigree')])
    </head>
    <body class="tree-workspace-page h-full overflow-hidden bg-[#efefef] text-[#474747]">
        <div class="flex min-h-screen flex-col">

            <x-app-header active-nav="global-tree"></x-app-header>

            {{-- Sub-header: tabs + generation controls --}}
            <div class="border-b border-[#e7e7e7] bg-white">
                <div class="flex items-center justify-between px-8 py-4">
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-1 text-[15px]">
                            <a href="{{ route('global-tree.index') }}"
                               class="workspace-view-tab">{{ __('Directory') }}</a>
                            <a href="{{ route('global-tree.pedigree') }}"
                               class="workspace-view-tab is-active">{{ __('Pedigree Chart') }}</a>
                        </div>
                        @if ($rootPerson)
                            <div class="h-6 w-px bg-[#e3e3e3]"></div>
                            <div class="text-[14px] font-medium text-[#2f3b45]" data-toolbar-focus-name>{{ $rootPerson->display_name }}</div>
                        @endif
                    </div>

                    @if ($rootPerson)
                        <div class="flex items-center gap-5 text-[14px] text-[#64707b]">
                            <div class="flex items-center gap-2">
                                @foreach ([2, 3, 4, 5] as $gen)
                                    <a href="{{ route('global-tree.pedigree', ['generations' => $gen]) }}"
                                       class="rounded-full px-2 py-1 {{ $generations === $gen ? 'bg-[#ff6c2f] text-white' : 'hover:bg-[#f3f3f3]' }}">
                                        {{ $gen }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex min-h-0 flex-1">

                @if ($rootPerson)
                    {{-- Sidebar --}}
                    <aside id="pedigree-sidebar"
                           class="workspace-sidebar flex w-[300px] min-w-[300px] max-w-[300px] flex-col overflow-y-scroll {{ $direction === 'rtl' ? 'border-l' : 'border-r' }} border-[#dfdfdf] bg-white">
                        @include('global-tree.partials.pedigree-sidebar', $sidebarData)
                    </aside>
                @endif

                {{-- Main --}}
                <main class="flex min-w-0 flex-1 flex-col">

                    @if (! $hasEnabledTree)
                        {{-- ── LOCKED STATE ──────────────────────────────────────── --}}
                        <div class="flex flex-1 items-center justify-center bg-white p-12">
                            <div class="mx-auto max-w-lg text-center">
                                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border-2 border-[#e3e8ee] bg-white text-3xl shadow-sm">
                                    🔒
                                </div>
                                <h2 class="text-2xl font-semibold tracking-tight text-[#1f252b]">
                                    {{ __('Your pedigree chart is locked') }}
                                </h2>
                                <p class="mt-3 text-base leading-7 text-[#4f5963]">
                                    {{ __('To unlock the Global Pedigree Chart, at least one of your trees must be part of the Global Tree. You\'re currently keeping all your trees private — which is completely your choice.') }}
                                </p>
                                <div class="mt-6 rounded-xl border border-[#fde68a] bg-[#fffbeb] px-5 py-4 text-left text-sm leading-6 text-[#78350f]">
                                    <p><strong>{{ __('What you\'re missing:') }}</strong></p>
                                    <ul class="mt-2 list-inside list-disc space-y-1">
                                        <li>{{ __('Your ancestry chart rooted at your own profile') }}</li>
                                        <li>{{ __('Connections to other families in the Origynz community') }}</li>
                                        <li>{{ __('An interactive, pannable pedigree spanning multiple generations') }}</li>
                                    </ul>
                                </div>
                                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                                    <a href="{{ route('trees.manage') }}"
                                       class="inline-flex items-center gap-2 rounded-[8px] bg-[#2563eb] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#1d4ed8]">
                                        {{ __('Enable Global Tree on my trees') }}
                                    </a>
                                    <a href="{{ route('global-tree.index') }}"
                                       class="inline-flex items-center gap-2 rounded-[8px] border border-[#cdd7e1] bg-white px-5 py-2.5 text-sm font-medium text-[#334155] transition hover:bg-[#f7f9fb]">
                                        {{ __('Browse the directory instead') }}
                                    </a>
                                </div>
                            </div>
                        </div>

                    @elseif (! $rootPerson)
                        {{-- ── PARTIAL LOCK ──────────────────────────────────────── --}}
                        <div class="flex flex-1 items-center justify-center bg-white p-12">
                            <div class="mx-auto max-w-lg">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#fef3c7] text-2xl">
                                        ⚠️
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-semibold text-[#78350f]">{{ __('Profile not found in the Global Tree') }}</h2>
                                        <p class="mt-2 text-sm leading-6 text-[#92400e]">
                                            {{ __('Your tree is part of the Global Tree, but we couldn\'t find your personal profile within it. This usually means:') }}
                                        </p>
                                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-[#92400e]">
                                            <li>{{ __('No "account profile" person has been assigned to your tree') }}</li>
                                            <li>{{ __('Your profile person has been excluded from the Global Tree') }}</li>
                                        </ul>
                                        <div class="mt-4 flex flex-wrap gap-3">
                                            <a href="{{ route('trees.first') }}"
                                               class="inline-flex items-center gap-2 rounded-[8px] border border-[#d97706] bg-[#fef3c7] px-4 py-2 text-sm font-medium text-[#92400e] transition hover:bg-[#fde68a]">
                                                {{ __('Set my account profile in tree settings') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    @else
                        {{-- ── PEDIGREE CANVAS ───────────────────────────────────── --}}

                        {{-- Privacy notice bar --}}
                        <div class="border-b border-[#fde68a] bg-[#fffbeb] px-6 py-2 text-sm leading-6 text-[#78350f]">
                            <strong>{{ __('Privacy:') }}</strong>
                            {{ __('Living persons are shown as "Private Person" in compliance with GDPR and equivalent laws.') }}
                        </div>

                        <div class="relative min-h-0 flex-1 overflow-hidden bg-[#efefef]">
                            <div class="absolute inset-0 overflow-auto" data-canvas-scroll>
                                <div class="workspace-stage relative"
                                     style="width: {{ $chartMeta['width'] }}px; height: {{ $chartMeta['height'] }}px;"
                                     data-canvas-stage>
                                    <div class="workspace-canvas absolute left-0 top-0 bg-[#f2f2f2]"
                                         style="width: {{ $chartMeta['width'] }}px; min-height: {{ $chartMeta['height'] }}px;"
                                         data-canvas-surface>

                                        <svg class="pointer-events-none absolute inset-0 h-full w-full"
                                             viewBox="0 0 {{ $chartMeta['width'] }} {{ $chartMeta['height'] }}"
                                             preserveAspectRatio="none"
                                             aria-hidden="true">
                                            @foreach ($chartLines as $line)
                                                <path
                                                    d="{{ $line['path'] }}"
                                                    fill="none"
                                                    stroke="{{ $line['stroke'] }}"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="{{ $line['type'] === 'spouse' ? 2.6 : 2.2 }}"
                                                />
                                            @endforeach
                                        </svg>

                                        @foreach ($chartNodes as $node)
                                            <div
                                                class="workspace-tree-card absolute"
                                                data-role="{{ $node['role'] }}"
                                                data-person-card
                                                data-person-id="{{ $node['id'] }}"
                                                style="left: {{ $node['x'] }}px; top: {{ $node['y'] }}px;"
                                                @if ($node['is_focus']) data-focus-card @endif
                                            >
                                                <div class="h-[14px]"></div>

                                                <div class="workspace-tree-card-inner {{ $node['is_private'] ? 'opacity-60' : '' }}" data-person-card-open>
                                                    <div class="flex items-start gap-3">
                                                        <div class="workspace-node-avatar shrink-0 {{ $node['is_private'] ? 'bg-[#e5e7eb] text-[#9ca3af]' : '' }}">
                                                            @if ($node['is_private'])
                                                                ?
                                                            @else
                                                                {{ mb_strtoupper(mb_substr($node['name'], 0, 1)) }}
                                                            @endif
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <h3 class="text-[13px] font-semibold leading-4">{{ $node['name'] }}</h3>
                                                            <p class="mt-1 text-[11px] text-[#6f6f6f]">{{ $node['life_span'] }}</p>
                                                            @if ($node['is_private'])
                                                                <span class="mt-1.5 inline-flex rounded-full bg-[#f3f4f6] px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.12em] text-[#6b7280]">{{ __('Private') }}</span>
                                                            @elseif ($node['is_focus'])
                                                                <div class="mt-1.5 inline-flex rounded-full bg-[#ffcd6c] px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.12em] text-[#7d5d11]">{{ __('You') }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach

                                    </div>
                                </div>
                            </div>

                            {{-- Floating tools --}}
                            <div class="workspace-floating-tools">
                                <button type="button" class="workspace-float-btn" data-canvas-action="center"     title="{{ __('Center') }}">⌖</button>
                                <button type="button" class="workspace-float-btn" data-canvas-action="fit"        title="{{ __('Fit') }}">◎</button>
                                <button type="button" class="workspace-float-btn" data-canvas-action="fullscreen" title="{{ __('Fullscreen') }}">⤢</button>
                                <button type="button" class="workspace-float-btn" data-canvas-action="zoom-in"    title="{{ __('Zoom in') }}">＋</button>
                                <button type="button" class="workspace-float-btn" data-canvas-action="zoom-out"   title="{{ __('Zoom out') }}">－</button>
                            </div>
                        </div>
                    @endif

                </main>
            </div>
        </div>

        @fluxScripts

        @if ($rootPerson)
        <script>
        (function () {
            const scrollViewport = document.querySelector('[data-canvas-scroll]');
            const stage          = document.querySelector('[data-canvas-stage]');
            const surface        = document.querySelector('[data-canvas-surface]');
            const focusCard      = document.querySelector('[data-focus-card]');
            const sidebar        = document.getElementById('pedigree-sidebar');

            if (!scrollViewport || !stage || !surface) { return; }

            let scale      = 1;
            let baseWidth  = parseFloat(stage.style.width)  || 0;
            let baseHeight = parseFloat(stage.style.height) || 0;

            const getCardCenter = (card) => {
                if (!card) { return null; }
                return {
                    x: (parseFloat(card.style.left || '0') + card.offsetWidth  / 2) * scale,
                    y: (parseFloat(card.style.top  || '0') + card.offsetHeight / 2) * scale,
                };
            };

            const centerCard = (card, behavior = 'smooth') => {
                const c = getCardCenter(card);
                if (!c) { return; }
                scrollViewport.scrollTo({
                    left: Math.max(0, c.x - scrollViewport.clientWidth  / 2),
                    top:  Math.max(0, c.y - scrollViewport.clientHeight / 2),
                    behavior,
                });
            };

            const applyScale = (nextScale, anchor = focusCard) => {
                scale = Math.max(0.5, Math.min(2.4, nextScale));
                surface.style.transform = `scale(${scale})`;
                stage.style.width       = `${baseWidth  * scale}px`;
                stage.style.height      = `${baseHeight * scale}px`;
                if (anchor) {
                    window.requestAnimationFrame(() => centerCard(anchor, 'auto'));
                }
            };

            const fitCanvas = () => {
                const ws = scrollViewport.clientWidth  / (baseWidth  + 120);
                const hs = scrollViewport.clientHeight / (baseHeight + 120);
                applyScale(Math.min(1.15, Math.max(0.55, Math.min(ws, hs))));
            };

            // Floating buttons
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-canvas-action]');
                if (btn) {
                    switch (btn.dataset.canvasAction) {
                        case 'zoom-in':    applyScale(scale + 0.15); break;
                        case 'zoom-out':   applyScale(scale - 0.15); break;
                        case 'center':     centerCard(focusCard);    break;
                        case 'fit':        fitCanvas();              break;
                        case 'fullscreen':
                            if (!document.fullscreenElement) {
                                scrollViewport.closest('.relative')?.requestFullscreen?.();
                            } else {
                                document.exitFullscreen?.();
                            }
                            break;
                    }
                    return;
                }

                // Card click → load sidebar
                const cardOpen = e.target.closest('[data-person-card-open]');
                if (cardOpen) {
                    const card = cardOpen.closest('[data-person-card]');
                    const personId = card?.dataset.personId;
                    if (personId) { loadSidebar(personId); }
                    return;
                }

                // Sidebar family-member button
                const loadBtn = e.target.closest('[data-load-person]');
                if (loadBtn) {
                    loadSidebar(loadBtn.dataset.loadPerson);
                }
            });

            // Pointer drag
            let pointerId = null, lastX = 0, lastY = 0;

            scrollViewport.addEventListener('pointerdown', (e) => {
                const el = e.target;
                if (!(el instanceof HTMLElement)) { return; }
                if (el.closest('a, button, input')) { return; }
                pointerId = e.pointerId;
                lastX = e.clientX;
                lastY = e.clientY;
                scrollViewport.classList.add('is-dragging');
                scrollViewport.setPointerCapture(pointerId);
            });

            scrollViewport.addEventListener('pointermove', (e) => {
                if (pointerId !== e.pointerId) { return; }
                scrollViewport.scrollLeft -= e.clientX - lastX;
                scrollViewport.scrollTop  -= e.clientY - lastY;
                lastX = e.clientX;
                lastY = e.clientY;
            });

            const clearDrag = (e) => {
                if (pointerId !== e.pointerId) { return; }
                scrollViewport.classList.remove('is-dragging');
                scrollViewport.releasePointerCapture(pointerId);
                pointerId = null;
            };

            scrollViewport.addEventListener('pointerup',     clearDrag);
            scrollViewport.addEventListener('pointercancel', clearDrag);

            scrollViewport.addEventListener('wheel', (e) => {
                if (!(e.ctrlKey || e.metaKey)) { return; }
                e.preventDefault();
                applyScale(scale - e.deltaY * 0.0015);
            }, { passive: false });

            // Sidebar loader
            const loadSidebar = async (personId) => {
                if (!sidebar) { return; }
                try {
                    const res = await fetch(`/global-tree/pedigree/person/${personId}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!res.ok) { return; }
                    const data = await res.json();
                    if (data.sidebar_html) { sidebar.innerHTML = data.sidebar_html; }
                } catch { /* silent */ }
            };

            // Initial fit + center
            window.requestAnimationFrame(() => {
                fitCanvas();
                window.setTimeout(() => centerCard(focusCard, 'smooth'), 120);
            });
        })();
        </script>
        @endif
    </body>
</html>
