<div class="workspace-stage relative" style="width: {{ $chartMeta['width'] }}px; height: {{ $chartMeta['height'] }}px;" data-canvas-stage>
    <div class="workspace-canvas absolute left-0 top-0 bg-[#f2f2f2]" style="width: {{ $chartMeta['width'] }}px; min-height: {{ $chartMeta['height'] }}px;" data-canvas-surface>
    <svg class="pointer-events-none absolute inset-0 h-full w-full" viewBox="0 0 {{ $chartMeta['width'] }} {{ $chartMeta['height'] }}" preserveAspectRatio="none" aria-hidden="true">
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
            data-person-name="{{ $node['name'] }}"
            data-person-life-span="{{ $node['life_span'] }}"
            data-focus-url="{{ $node['focus_url'] }}"
            data-person-surname="{{ $node['surname'] }}"
            data-has-father="{{ $node['has_father'] ? '1' : '0' }}"
            data-has-mother="{{ $node['has_mother'] ? '1' : '0' }}"
            @if ($node['is_focus']) data-focus-card @endif
            style="left: {{ $node['x'] }}px; top: {{ $node['y'] }}px;"
        >
            {{-- Small dot above the card — only shown when navigating to this person changes the view --}}
            @if (!$node['is_focus'])
            <div class="flex justify-center">
                <a href="{{ $node['focus_url'] }}" class="workspace-focus-dot" title="{{ __('View tree from :name', ['name' => $node['name']]) }}"></a>
            </div>
            @else
            <div class="h-[14px]"></div>
            @endif

            <div class="workspace-tree-card-inner {{ $node['is_focus'] ? 'is-focus' : '' }} {{ $node['is_owner'] ? 'is-owner' : '' }}" data-person-card-open>
                <div class="flex items-start gap-3">
                    @if (isset($personAvatarUrls[$node['id']]))
                    <img src="{{ $personAvatarUrls[$node['id']] }}" class="workspace-node-avatar object-cover" alt="{{ $node['name'] }}" />
                @else
                    <div class="workspace-node-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($node['name'], 0, 1)) }}</div>
                @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-[13px] font-semibold leading-4">{{ $node['name'] }}</h3>
                                <p class="mt-1 text-[11px] text-[#6f6f6f]">{{ $node['life_span'] }}</p>
                            </div>
                            <span class="text-[14px] text-[#888]">✎</span>
                        </div>
                        @if ($node['is_owner'])
                            <div class="mt-2 inline-flex rounded-full bg-[#ffcd6c] px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.12em] text-[#7d5d11]">You</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex justify-center">
                <button
                    type="button"
                    class="workspace-card-connector"
                    data-role-chooser-open
                    data-link-person-id="{{ $node['id'] }}"
                    data-person-name="{{ $node['name'] }}"
                    data-person-life-span="{{ $node['life_span'] }}"
                    data-focus-url="{{ $node['focus_url'] }}"
                    data-person-surname="{{ $node['surname'] }}"
                    data-has-father="{{ $node['has_father'] ? '1' : '0' }}"
                    data-has-mother="{{ $node['has_mother'] ? '1' : '0' }}"
                >
                    ＋
                </button>
            </div>

            @if ($node['can_expand'] && $node['branch_url'])
                <div class="mt-2 flex justify-center">
                    <a href="{{ $node['branch_url'] }}" class="workspace-branch-toggle">
                        {{ $node['is_collapsed'] ? __('Expand branch') : __('Collapse branch') }}
                    </a>
                </div>
            @endif
        </div>
    @endforeach
    </div>
</div>
