<x-layouts::app :title="__('Media Library')" active-nav="photos">
    @php
        $baseLibraryUrl = $isGlobalLibrary ? route('media.index') : route('trees.media.index', $tree);
        $queryBase = array_filter([
            'q' => $filters['q'],
            'kind' => $filters['kind'] !== 'all' ? $filters['kind'] : null,
            'tree' => $filters['tree_id'],
        ], fn ($value) => $value !== null && $value !== '');

        $allHref = $baseLibraryUrl.(count($queryBase) ? '?'.http_build_query($queryBase) : '');
        $peopleHref = $baseLibraryUrl.'?'.http_build_query(array_merge($queryBase, ['linked' => 'linked']));
        $albumsHref = $baseLibraryUrl.'?'.http_build_query(array_merge($queryBase, ['linked' => 'unlinked']));
        $activeSection = $filters['linked'] === 'linked' ? 'people' : ($filters['linked'] === 'unlinked' ? 'albums' : 'all');
        $uploadHref = ! $isGlobalLibrary && $tree ? route('trees.show', $tree).'#media-panel' : route('trees.manage');
    @endphp

    <div class="media-browser-frame">
        <div class="media-browser-shell">
            <aside class="media-browser-sidebar">
                <nav class="media-browser-side-nav">
                    <a href="{{ $allHref }}" class="media-browser-side-link {{ $activeSection === 'all' ? 'is-active' : '' }}">
                        <span class="media-browser-side-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" class="h-full w-full">
                                <rect x="4" y="4" width="6" height="6" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                <rect x="14" y="4" width="6" height="6" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                <rect x="4" y="14" width="6" height="6" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                <rect x="14" y="14" width="6" height="6" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </span>
                        <span>{{ __('All media items') }}</span>
                    </a>

                    <a href="{{ $peopleHref }}" class="media-browser-side-link {{ $activeSection === 'people' ? 'is-active' : '' }}">
                        <span class="media-browser-side-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" class="h-full w-full">
                                <circle cx="8" cy="9" r="3" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="16.5" cy="10.5" r="2.5" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M3.5 19c1.1-2.7 3.2-4 5.9-4 2.8 0 4.8 1.3 6 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M14 18c.7-1.7 2-2.6 3.8-2.6 1.1 0 2.1.3 2.9 1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>{{ __('People') }}</span>
                    </a>

                    <a href="{{ $albumsHref }}" class="media-browser-side-link {{ $activeSection === 'albums' ? 'is-active' : '' }}">
                        <span class="media-browser-side-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" class="h-full w-full">
                                <rect x="3.5" y="5" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M7 19.5h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <span>{{ __('Albums') }}</span>
                    </a>
                </nav>
            </aside>

            <main class="media-browser-content">
                <div class="media-browser-topbar">
                    <div class="media-browser-summary">
                        {{ __('Showing :count media items', ['count' => number_format($mediaItems->total())]) }}
                    </div>

                    <div class="media-browser-tools">
                        <form method="GET" action="{{ $baseLibraryUrl }}" class="media-browser-searchbar">
                            @if ($filters['tree_id'])
                                <input type="hidden" name="tree" value="{{ $filters['tree_id'] }}" />
                            @endif
                            @if ($filters['kind'] !== 'all')
                                <input type="hidden" name="kind" value="{{ $filters['kind'] }}" />
                            @endif
                            @if ($filters['linked'] !== 'all')
                                <input type="hidden" name="linked" value="{{ $filters['linked'] }}" />
                            @endif
                            <input
                                type="search"
                                name="q"
                                value="{{ $filters['q'] }}"
                                placeholder="{{ __('Search') }}"
                            />
                            <span class="media-browser-searchbar-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="h-full w-full">
                                    <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/>
                                    <path d="M16 16l4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </span>
                        </form>

                        <div class="media-browser-view-icons">
                            <span class="is-active" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="h-7 w-7">
                                    <rect x="4" y="4" width="7" height="7" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                    <rect x="13" y="4" width="7" height="7" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                    <rect x="4" y="13" width="7" height="7" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                    <rect x="13" y="13" width="7" height="7" rx="1.3" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <span aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="h-7 w-7">
                                    <rect x="4" y="4" width="16" height="4.5" rx="1.25" stroke="currentColor" stroke-width="1.8"/>
                                    <rect x="4" y="10" width="16" height="4.5" rx="1.25" stroke="currentColor" stroke-width="1.8"/>
                                    <rect x="4" y="16" width="16" height="4.5" rx="1.25" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                        </div>

                        <a href="{{ $uploadHref }}" class="media-browser-upload">
                            <span class="media-browser-upload-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                                    <path d="M12 15V5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    <path d="M8.5 8.5 12 5l3.5 3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    <rect x="4" y="11" width="16" height="9" rx="2" stroke="currentColor" stroke-width="1.8"/>
                                </svg>
                            </span>
                            <span>{{ __('Upload') }}</span>
                        </a>
                    </div>
                </div>

                @if ($mediaItems->isNotEmpty())
                    <section class="media-browser-masonry">
                        @foreach ($mediaItems as $item)
                            <a href="{{ route('media.show', $item) }}" class="media-browser-masonry-item group">
                                @if ($item->file_path && str_starts_with((string) $item->mime_type, 'image/'))
                                    <img
                                        src="{{ route('media.preview', $item) }}"
                                        alt="{{ $item->title }}"
                                        class="media-browser-masonry-image transition duration-200 group-hover:brightness-75"
                                    />
                                @else
                                    <div class="media-browser-masonry-fallback">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->title, 0, 1)) }}
                                    </div>
                                @endif

                                <div class="media-browser-masonry-overlay">
                                    <div class="media-browser-masonry-title">{{ $item->title }}</div>
                                    <div class="media-browser-masonry-meta">{{ $item->person?->display_name ?? $item->familyTree->name }}</div>
                                </div>
                            </a>
                        @endforeach
                    </section>

                    <div class="media-browser-page-links">
                        {{ $mediaItems->links() }}
                    </div>
                @else
                    <div class="media-browser-no-results">
                        {{ __('No media items match these filters yet.') }}
                    </div>
                @endif
            </main>
        </div>
    </div>
</x-layouts::app>
