<x-layouts::app :title="$mediaItem->title" active-nav="photos">
    <div class="media-detail-page">
        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Media detail') }}</p>
                    <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-[#1f252b] sm:text-5xl">
                        {{ $mediaItem->title }}
                    </h1>
                    <p class="max-w-2xl text-base leading-7 text-[#4f5963]">
                        {{ __('Review this media item, its linked person, and the source details saved with it.') }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('media.index') }}" class="rounded-[6px] border border-[#cdd7e1] bg-white px-4 py-2 text-sm font-medium text-[#475569] transition hover:border-[#93c5fd] hover:text-[#2563eb]">
                        {{ __('All photos') }}
                    </a>
                    <a href="{{ route('trees.media.index', $tree) }}" class="rounded-[6px] border border-[#cdd7e1] bg-white px-4 py-2 text-sm font-medium text-[#475569] transition hover:border-[#93c5fd] hover:text-[#2563eb]">
                        {{ __('This tree') }}
                    </a>
                    <a href="{{ route('trees.show', $tree) }}" class="rounded-[6px] bg-[#2563eb] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#1d4ed8]">
                        {{ __('Back to workspace') }}
                    </a>
                </div>
            </div>
        </section>

        <section class="media-detail-layout">
            <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                @if ($mediaItem->file_path && str_starts_with((string) $mediaItem->mime_type, 'image/'))
                    <img
                        src="{{ route('media.preview', $mediaItem) }}"
                        alt="{{ $mediaItem->title }}"
                        class="media-detail-image"
                    />
                @else
                    <div class="media-detail-fallback">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($mediaItem->title, 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="media-detail-sidebar">
                <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Details') }}</h2>
                    <div class="mt-5 space-y-4 text-sm text-[#4f5963]">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#7b8794]">{{ __('Tree') }}</p>
                            <p class="mt-1">{{ $tree->name }}</p>
                        </div>
                        @if ($mediaItem->person)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#7b8794]">{{ __('Linked person') }}</p>
                                <p class="mt-1">{{ $mediaItem->person->display_name }}</p>
                                <a href="{{ route('trees.show', ['tree' => $tree, 'focus' => $mediaItem->person->id]) }}" class="mt-2 inline-flex text-sm font-medium text-[#2563eb] hover:text-[#1d4ed8]">
                                    {{ __('Open this person in the tree') }}
                                </a>
                            </div>
                        @endif
                        @if ($mediaItem->description)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#7b8794]">{{ __('Description') }}</p>
                                <p class="mt-1 leading-6">{{ $mediaItem->description }}</p>
                            </div>
                        @endif
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#7b8794]">{{ __('File') }}</p>
                            <p class="mt-1">{{ $mediaItem->file_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#7b8794]">{{ __('Type') }}</p>
                            <p class="mt-1">{{ $mediaItem->mime_type ?: __('Unknown') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#7b8794]">{{ __('Size') }}</p>
                            <p class="mt-1">{{ number_format($mediaItem->file_size / 1024, 1) }} KB</p>
                        </div>
                        @if ($mediaItem->uploader)
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#7b8794]">{{ __('Uploaded by') }}</p>
                                <p class="mt-1">{{ $mediaItem->uploader->name }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($relatedMedia->isNotEmpty())
                    <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('More from this tree') }}</h2>
                        <div class="media-detail-related-grid">
                            @foreach ($relatedMedia as $item)
                                <a href="{{ route('media.show', $item) }}" class="media-detail-related-item group">
                                    @if ($item->file_path && str_starts_with((string) $item->mime_type, 'image/'))
                                        <img
                                            src="{{ route('media.preview', $item) }}"
                                            alt="{{ $item->title }}"
                                            class="media-detail-related-image"
                                        />
                                    @else
                                        <div class="media-detail-related-fallback">
                                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->title, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-layouts::app>
