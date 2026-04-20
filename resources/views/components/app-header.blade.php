@props([
    'activeNav'      => null,       // 'home' | 'family-tree' | 'photos' | 'settings' | null
    'familyTreeHref' => null,       // when set, Family tree nav renders as a clickable link
    'authenticated'  => true,       // false on public / auth pages (hides user info)
])

@php
    $currentLocale = app()->getLocale();
    $locales = config('app.locales', []);
    $direction = $locales[$currentLocale]['direction'] ?? 'ltr';
    $isRtl = $direction === 'rtl';
    $active = $locales[$currentLocale] ?? $locales['en'] ?? ['flag' => '🇬🇧', 'short' => 'EN'];
    $homeBaseHref = $authenticated && auth()->check() ? route('dashboard') : route('home');
    $firstFamilyTree = $authenticated && auth()->check()
        ? auth()->user()->familyTrees()->orderBy('id')->first()
        : null;
    $firstFamilyTreeHref = $firstFamilyTree ? route('trees.show', $firstFamilyTree) : route('trees.manage');
    $familyEventsHref = $authenticated && auth()->check() ? route('family-events.index') : $homeBaseHref.'#family-events';
    $familyStatisticsHref = $authenticated && auth()->check() ? route('family-statistics.index') : $homeBaseHref.'#family-statistics';
    $homeMenuItems = [
        ['label' => __('Family events'), 'href' => $familyEventsHref],
        ['label' => __('Family statistics'), 'href' => $familyStatisticsHref],
    ];
    $familyTreeMenuItems = [
        ['label' => __('My family tree'), 'href' => $firstFamilyTreeHref],
        ['label' => __('Import GEDCOM'), 'href' => route('trees.import.index')],
        ['label' => __('Manage trees'), 'href' => route('trees.manage')],
    ];
@endphp

{{-- ── Topbar ──────────────────────────────────────────────────── --}}
<header class="workspace-topbar flex h-11 shrink-0 items-center justify-between bg-[#666462] px-6 text-white">

    <div class="flex items-center gap-6">
        @if (!isset($topbarLeft) || $topbarLeft->isEmpty())
            <div class="flex items-center gap-3 text-[16px] font-medium">
                <span>Origynz</span>
            </div>
        @else
            {{ $topbarLeft }}
        @endif
    </div>

    <div class="flex items-center gap-4 text-[14px]">
        @unless (!isset($topbarRightExtra) || $topbarRightExtra->isEmpty())
            {{ $topbarRightExtra }}
        @endunless

        {{-- Language switcher --}}
        <div class="relative" id="locale-switcher">
            <button
                type="button"
                id="locale-btn"
                class="workspace-topbar-btn flex items-center gap-1.5"
                aria-haspopup="true"
                aria-expanded="false"
            >
                <span>{{ $active['flag'] }}</span>
                <span class="text-[11px] font-semibold tracking-wide">{{ $active['short'] }}</span>
                <span class="text-[9px] opacity-70">▾</span>
            </button>

            <div
                id="locale-dropdown"
                class="absolute top-full z-50 mt-1 hidden min-w-[160px] overflow-hidden rounded-lg border border-white/20 bg-[#4a4846] shadow-xl {{ $isRtl ? 'left-0' : 'right-0' }}"
                role="menu"
            >
                @foreach ($locales as $code => $meta)
                    @php $option = ['locale' => $code, 'flag' => $meta['flag'], 'label' => $meta['label']]; @endphp
                    <form method="POST" action="{{ route('locale.store') }}">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $option['locale'] }}">
                        <button
                            type="submit"
                            role="menuitem"
                            class="flex w-full items-center gap-3 px-4 py-2.5 {{ $isRtl ? 'text-right' : 'text-left' }} text-[13px] text-white transition-colors hover:bg-white/10 {{ $currentLocale === $option['locale'] ? 'bg-white/15 font-semibold' : '' }}"
                        >
                            <span class="text-[16px]">{{ $option['flag'] }}</span>
                            <span>{{ $option['label'] }}</span>
                            @if ($currentLocale === $option['locale'])
                                <span class="{{ $isRtl ? 'mr-auto' : 'ml-auto' }} text-[10px] text-white/60">✓</span>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>

        @if ($authenticated && auth()->check())
            <span class="workspace-topbar-icon-btn">✉</span>
            <div class="workspace-topbar-user">
                <span class="workspace-user-avatar !h-7 !w-7 !text-[10px]">{{ auth()->user()->initials() }}</span>
                <span class="text-[13px] font-medium leading-none">{{ auth()->user()->name }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="workspace-topbar-btn">{{ __('Log out') }}</button>
            </form>
            <span class="workspace-topbar-btn cursor-default">{{ __('Help') }}</span>
        @else
            <a href="{{ route('home') }}" class="workspace-topbar-btn">{{ __('Home') }}</a>
        @endif
    </div>

</header>

{{-- ── Nav header ──────────────────────────────────────────────── --}}
<div class="flex h-[72px] shrink-0 items-center justify-center border-b border-[#ececec] bg-white">
    <div class="flex w-full max-w-[1200px] items-center justify-between px-8">

        <a href="{{ route('home') }}" class="text-[28px] font-semibold tracking-tight text-[#5d5d5d] transition-colors duration-150 hover:text-[#2563eb]">
            Origynz
        </a>

        <nav class="flex items-center gap-2 text-[#5f6a74]">
            <div class="relative" id="home-nav-switcher">
                <button
                    type="button"
                    id="home-nav-btn"
                    class="workspace-nav-link flex items-center gap-2 rounded-[6px] {{ $activeNav === 'home' ? 'is-active' : '' }}"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <span>{{ __('Home') }}</span>
                    <span class="text-[9px] opacity-70">▾</span>
                </button>

                <div
                    id="home-nav-dropdown"
                    class="absolute top-full z-50 mt-1 hidden min-w-[220px] overflow-hidden rounded-[6px] border border-[#dde1e6] bg-white py-1 shadow-xl {{ $isRtl ? 'right-0' : 'left-0' }}"
                    role="menu"
                >
                    <a
                        href="{{ $homeBaseHref }}"
                        role="menuitem"
                        class="block px-4 py-2.5 text-sm font-medium text-[#1f252b] transition-colors hover:bg-[#f3f7fb] hover:text-[#2563eb]"
                    >
                        {{ __('Home overview') }}
                    </a>
                    @foreach ($homeMenuItems as $item)
                        <a
                            href="{{ $item['href'] }}"
                            role="menuitem"
                            class="block px-4 py-2.5 text-sm text-[#4f5963] transition-colors hover:bg-[#f3f7fb] hover:text-[#2563eb]"
                        >
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($authenticated && auth()->check())
                <div class="relative" id="family-tree-nav-switcher">
                    <button
                        type="button"
                        id="family-tree-nav-btn"
                        class="workspace-nav-link flex items-center gap-2 rounded-[6px] {{ $activeNav === 'family-tree' ? 'is-active' : '' }}"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <span>{{ __('Family tree') }}</span>
                        <span class="text-[9px] opacity-70">▾</span>
                    </button>

                    <div
                        id="family-tree-nav-dropdown"
                        class="absolute top-full z-50 mt-1 hidden min-w-[240px] overflow-hidden rounded-[6px] border border-[#dde1e6] bg-white py-1 shadow-xl {{ $isRtl ? 'right-0' : 'left-0' }}"
                        role="menu"
                    >
                        @foreach ($familyTreeMenuItems as $item)
                            <a
                                href="{{ $item['href'] }}"
                                role="menuitem"
                                class="block px-4 py-2.5 text-sm text-[#4f5963] transition-colors hover:bg-[#f3f7fb] hover:text-[#2563eb]"
                            >
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @elseif ($familyTreeHref)
                <a href="{{ $familyTreeHref }}" class="workspace-nav-link {{ $activeNav === 'family-tree' ? 'is-active' : '' }}">{{ __('Family tree') }}</a>
            @else
                <span class="workspace-nav-link {{ $activeNav === 'family-tree' ? 'is-active' : '' }} {{ $activeNav !== 'family-tree' ? 'cursor-default' : '' }}">{{ __('Family tree') }}</span>
            @endif

            @if ($authenticated && auth()->check())
                <a href="{{ route('global-tree.index') }}" class="workspace-nav-link {{ $activeNav === 'global-tree' ? 'is-active' : '' }}">{{ __('Global Tree') }}</a>
            @else
                <span class="workspace-nav-link cursor-default">{{ __('Global Tree') }}</span>
            @endif
            @if ($authenticated && auth()->check())
                <a href="{{ route('media.index') }}" class="workspace-nav-link {{ $activeNav === 'photos' ? 'is-active' : '' }}">{{ __('Photos') }}</a>
            @else
                <span class="workspace-nav-link cursor-default">{{ __('Photos') }}</span>
            @endif
            <span class="workspace-nav-link cursor-default">{{ __('DNA') }}</span>
            <span class="workspace-nav-link cursor-default">{{ __('Research') }}</span>

            @if ($authenticated && auth()->check())
                <a href="{{ route('profile.edit') }}" class="workspace-nav-link {{ $activeNav === 'settings' ? 'is-active' : '' }}">{{ __('Settings') }}</a>
            @endif

            @if ($authenticated && auth()->check() && auth()->user()->isSuperAdmin())
                <div class="relative" id="admin-nav-switcher">
                    <button
                        type="button"
                        id="admin-nav-btn"
                        class="workspace-nav-link flex items-center gap-2 rounded-[6px] !text-[#dc2626] font-semibold {{ $activeNav === 'admin' ? 'is-active' : '' }}"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >
                        <span>{{ __('Admin') }}</span>
                        <span class="text-[9px] opacity-70">▾</span>
                    </button>
                    <div
                        id="admin-nav-dropdown"
                        class="absolute top-full z-50 mt-1 hidden min-w-[220px] overflow-hidden rounded-[6px] border border-[#dde1e6] bg-white py-1 shadow-xl {{ $isRtl ? 'right-0' : 'left-0' }}"
                        role="menu"
                    >
                        <a href="{{ route('admin.dashboard') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-[#4f5963] transition hover:bg-[#f3f7fb] hover:text-[#2563eb]">{{ __('Dashboard') }}</a>
                        <a href="{{ route('admin.users.index') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-[#4f5963] transition hover:bg-[#f3f7fb] hover:text-[#2563eb]">{{ __('Users') }}</a>
                        <a href="{{ route('admin.trees.index') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-[#4f5963] transition hover:bg-[#f3f7fb] hover:text-[#2563eb]">{{ __('Trees') }}</a>
                        <a href="{{ route('admin.global-tree.index') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-[#4f5963] transition hover:bg-[#f3f7fb] hover:text-[#2563eb]">{{ __('Global Tree') }}</a>
                        <a href="{{ route('admin.activity.index') }}" role="menuitem" class="block px-4 py-2.5 text-sm text-[#4f5963] transition hover:bg-[#f3f7fb] hover:text-[#2563eb]">{{ __('Activity log') }}</a>
                    </div>
                </div>
            @endif
        </nav>

        <div class="w-10 {{ $isRtl ? 'text-left' : 'text-right' }} text-[#ababab]">✣</div>
    </div>
</div>

<script>
(function () {
    function setupClickDropdown(rootId, buttonId, dropdownId) {
        var root = document.getElementById(rootId);
        var button = document.getElementById(buttonId);
        var dropdown = document.getElementById(dropdownId);

        if (!root || !button || !dropdown) {
            return;
        }

        button.addEventListener('click', function (e) {
            e.stopPropagation();
            var isHidden = dropdown.classList.contains('hidden');

            dropdown.classList.toggle('hidden');
            button.setAttribute('aria-expanded', String(isHidden));
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                dropdown.classList.add('hidden');
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function setupHoverDropdown(rootId, buttonId, dropdownId) {
        var root = document.getElementById(rootId);
        var button = document.getElementById(buttonId);
        var dropdown = document.getElementById(dropdownId);

        if (!root || !button || !dropdown) {
            return;
        }

        function openDropdown() {
            dropdown.classList.remove('hidden');
            button.setAttribute('aria-expanded', 'true');
        }

        function closeDropdown() {
            dropdown.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        }

        root.addEventListener('mouseenter', openDropdown);
        root.addEventListener('mouseleave', closeDropdown);
        root.addEventListener('focusin', openDropdown);
        root.addEventListener('focusout', function (e) {
            if (!root.contains(e.relatedTarget)) {
                closeDropdown();
            }
        });

        button.addEventListener('click', function () {
            if (dropdown.classList.contains('hidden')) {
                openDropdown();
            } else {
                closeDropdown();
            }
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                closeDropdown();
            }
        });
    }

    setupClickDropdown('locale-switcher', 'locale-btn', 'locale-dropdown');
    setupHoverDropdown('home-nav-switcher', 'home-nav-btn', 'home-nav-dropdown');
    setupHoverDropdown('family-tree-nav-switcher', 'family-tree-nav-btn', 'family-tree-nav-dropdown');
    setupHoverDropdown('admin-nav-switcher', 'admin-nav-btn', 'admin-nav-dropdown');
})();
</script>
