<x-layouts::app :title="__('Family events')" active-nav="home">
    <div class="genealogy-shell">
        <section class="family-events-shell">
            <div class="family-events-main">
                <div class="family-events-panel">
                    <div class="family-events-heading">
                        <div>
                            <h1>{{ __('Family events') }}</h1>
                            <p>{{ __('Track birthdays and anniversaries across your owned trees, or narrow the feed to one specific tree.', ['tree' => $selectedTree?->name]) }}</p>
                        </div>
                        @if ($selectedTree)
                            <a href="{{ route('trees.events.settings.edit', $selectedTree) }}" class="family-events-settings-link">
                                {{ __('Calendar settings') }}
                            </a>
                        @endif
                    </div>

                    <form method="GET" action="{{ route('family-events.index') }}" class="family-events-searchbar">
                        <input type="hidden" name="scope" value="{{ $scope }}">
                        <input type="hidden" name="type" value="{{ $typeFilter }}">
                        <input type="hidden" name="month" value="{{ $selectedMonth }}">
                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                        <input type="hidden" name="tree" value="{{ $selectedTreeValue }}">
                        <input
                            type="search"
                            name="search"
                            value="{{ $search }}"
                            class="family-events-search-input"
                            placeholder="{{ __('Find an event') }}"
                        />
                        <button type="submit" class="family-events-search-button" aria-label="{{ __('Search events') }}">➜</button>
                        <span class="family-events-advanced">{{ __('Show advanced search') }}</span>
                    </form>

                    <div class="family-events-toolbar">
                        <div class="family-events-tabs">
                            <a href="{{ route('family-events.index', ['scope' => 'upcoming', 'type' => $typeFilter, 'search' => $search, 'month' => $selectedMonth, 'year' => $selectedYear, 'tree' => $selectedTreeValue]) }}" class="family-events-tab {{ $scope === 'upcoming' ? 'is-active is-upcoming' : '' }}">
                                {{ __('Upcoming') }}
                            </a>
                            <span class="family-events-tab-separator">|</span>
                            <a href="{{ route('family-events.index', ['scope' => 'month', 'type' => $typeFilter, 'search' => $search, 'month' => $selectedMonth, 'year' => $selectedYear, 'tree' => $selectedTreeValue]) }}" class="family-events-tab {{ $scope === 'month' ? 'is-active' : '' }}">
                                {{ __('Month') }}
                            </a>
                            <span class="family-events-tab-separator">|</span>
                            <a href="{{ route('family-events.index', ['scope' => 'year', 'type' => $typeFilter, 'search' => $search, 'month' => $selectedMonth, 'year' => $selectedYear, 'tree' => $selectedTreeValue]) }}" class="family-events-tab {{ $scope === 'year' ? 'is-active' : '' }}">
                                {{ __('Year') }}
                            </a>
                        </div>

                        <div class="family-events-toolbar-actions">
                            <form method="GET" action="{{ route('family-events.index') }}" class="family-events-filter-form">
                                <input type="hidden" name="scope" value="{{ $scope }}">
                                <input type="hidden" name="search" value="{{ $search }}">
                                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <select name="tree" class="family-events-select" onchange="this.form.submit()">
                                    <option value="all" @selected($selectedTreeValue === 'all')>{{ __('All owned trees') }}</option>
                                    @foreach ($ownedTrees as $ownedTree)
                                        <option value="{{ $ownedTree->id }}" @selected($selectedTreeValue === (string) $ownedTree->id)>{{ $ownedTree->name }}</option>
                                    @endforeach
                                </select>
                                <select name="type" class="family-events-select" onchange="this.form.submit()">
                                    <option value="all" @selected($typeFilter === 'all')>{{ __('All events') }}</option>
                                    <option value="birthdays" @selected($typeFilter === 'birthdays')>{{ __('Birthdays') }}</option>
                                    <option value="anniversaries" @selected($typeFilter === 'anniversaries')>{{ __('Anniversaries') }}</option>
                                    <option value="death-anniversaries" @selected($typeFilter === 'death-anniversaries')>{{ __('Death anniversaries') }}</option>
                                </select>
                            </form>

                            @if ($selectedTree)
                                <a href="{{ route('trees.show', $selectedTree) }}" class="family-events-post-button">
                                    {{ __('+ Post an event') }}
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="family-events-month-heading">
                        {{ $scope === 'month' ? $monthDate->format('F Y') : __('Events for :year', ['year' => $selectedYear]) }}
                        @if ($scope === 'upcoming')
                            <span>{{ __('Next 12 months') }}</span>
                        @endif
                    </div>

                    @if ($totalEvents === 0)
                        <div class="family-events-empty">
                            {{ __('No events matched your current filters yet.') }}
                        </div>
                    @endif

                    @foreach ($groupedEvents as $groupLabel => $events)
                        @if ($scope !== 'month')
                            <h2 class="family-events-group-label">{{ $groupLabel }}</h2>
                        @endif

                        <div class="family-events-list">
                            @foreach ($events as $event)
                                <article class="family-events-card">
                                    <div class="family-events-datebox">
                                        <span class="family-events-datebox-dayname">{{ $event['occurs_on']->format('D') }}</span>
                                        <span class="family-events-datebox-day">{{ $event['occurs_on']->format('j') }}</span>
                                        <span class="family-events-datebox-month">{{ $event['occurs_on']->format('M') }}</span>
                                    </div>

                                    <div class="family-events-avatars">
                                        @if (!empty($event['avatar_url']))
                                            <img src="{{ $event['avatar_url'] }}" alt="" class="family-events-avatar-image" />
                                        @else
                                            <div class="family-events-avatar-fallback">{{ $event['initials'] }}</div>
                                        @endif

                                        @if (!empty($event['secondary_avatar_url']))
                                            <img src="{{ $event['secondary_avatar_url'] }}" alt="" class="family-events-avatar-image family-events-avatar-image-secondary" />
                                        @elseif (!empty($event['secondary_initials']))
                                            <div class="family-events-avatar-fallback family-events-avatar-fallback-secondary">{{ $event['secondary_initials'] }}</div>
                                        @endif
                                    </div>

                                    <div class="family-events-icon" aria-hidden="true">
                                        @if ($event['icon'] === 'rings')
                                            💍
                                        @elseif ($event['icon'] === 'memorial')
                                            ✝
                                        @else
                                            🎈
                                        @endif
                                    </div>

                                    <div class="family-events-copy">
                                        <a href="{{ $event['event_url'] ?? route('family-events.index') }}" class="family-events-title">{{ $event['title'] }}</a>
                                        <div class="family-events-meta-row">
                                            <span>{{ $event['meta'] }}</span>
                                            @if (!empty($event['tree_name']))
                                                <span>• {{ $event['tree_name'] }}</span>
                                            @endif
                                            @if (!empty($event['place']))
                                                <span>• {{ $event['place'] }}</span>
                                            @endif
                                        </div>
                                        @if (!empty($event['subtitle']))
                                            <p class="family-events-subtitle">{{ $event['subtitle'] }}</p>
                                        @endif
                                    </div>

                                    <button type="button" class="family-events-dismiss" aria-label="{{ __('Dismiss event') }}">×</button>
                                </article>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <aside class="family-events-sidebar">
                <div class="family-events-sidebar-card">
                    <div class="family-events-sidebar-heading">
                        <h2>{{ __('Missing family events') }}</h2>
                        <span>⌃</span>
                    </div>

                    <div class="family-events-missing-list">
                        @forelse ($missingEvents as $item)
                            <div class="family-events-missing-card">
                                @if ($item['avatar_url'])
                                    <img src="{{ $item['avatar_url'] }}" alt="" class="family-events-missing-avatar" />
                                @else
                                    <div class="family-events-missing-avatar family-events-missing-avatar-fallback">{{ $item['initials'] }}</div>
                                @endif

                                <div class="family-events-missing-copy">
                                    <p class="family-events-missing-title">{{ $item['title'] }}</p>
                                    <p class="family-events-missing-subtitle">{{ $item['subtitle'] }}</p>
                                    @if (!empty($item['tree_url']))
                                        <a href="{{ $item['tree_url'] }}" class="family-events-missing-link">{{ __('› Add') }}</a>
                                    @endif
                                </div>

                                <button type="button" class="family-events-missing-close" aria-label="{{ __('Dismiss suggestion') }}">×</button>
                            </div>
                        @empty
                            <p class="family-events-sidebar-empty">{{ __('Everyone in this tree already has a birthday or date recorded.') }}</p>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-layouts::app>
