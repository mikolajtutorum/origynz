<div class="workspace-person-hero border-b border-[#ececec] px-6 py-7">
    <div class="flex items-start gap-4">
        <div class="workspace-profile-avatar {{ $display_data['is_private'] ? 'bg-[#e5e7eb] text-[#9ca3af]' : '' }}">
            @if ($display_data['is_private'])
                ?
            @else
                {{ mb_strtoupper(mb_substr($display_data['display_name'], 0, 1)) }}
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <h2 class="workspace-person-hero-name">{{ $display_data['display_name'] }}</h2>

            @if ($display_data['is_private'])
                <p class="mt-1 text-xs text-[#9ca3af]">{{ __('Details withheld to protect privacy.') }}</p>
            @else
                <div class="workspace-person-vitals mt-2">
                    @if ($display_data['life_span'])
                        <div class="workspace-person-vital">
                            <span class="workspace-person-vital-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="workspace-inline-icon">
                                    <path d="M12 3v18M3 12h18M6.5 6.5l11 11M17.5 6.5l-11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <div class="workspace-person-vital-date">{{ $display_data['life_span'] }}</div>
                                @if ($display_data['birth_place'])
                                    <div class="workspace-person-vital-place">{{ $display_data['birth_place'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-3 inline-flex items-center gap-1 rounded-full border border-[#e3e8ee] bg-[#f7f9fb] px-2.5 py-0.5 text-[11px] text-[#6f7b83]">
                {{ $tree_name }}
            </div>
        </div>
    </div>
</div>

<div class="flex-1">
    @if (! $display_data['is_private'])

        @if (! empty($parents))
            <section class="workspace-panel-section">
                <div class="workspace-section-title">{{ __('Parents') }}</div>
                <div class="mt-3 space-y-1.5">
                    @foreach ($parents as $member)
                        <button type="button"
                                class="flex w-full items-center gap-3 rounded-xl border border-[#ececec] bg-[#fafafa] px-3 py-2 text-left text-[13px] text-[#566472] transition hover:bg-white"
                                data-load-person="{{ $member['id'] }}">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#dbeafe] text-[10px] font-semibold text-[#2563eb]">
                                {{ $member['data']['is_private'] ? '?' : mb_strtoupper(mb_substr($member['data']['display_name'], 0, 1)) }}
                            </div>
                            <span class="truncate">{{ $member['data']['display_name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        @if (! empty($spouses))
            <section class="workspace-panel-section">
                <div class="workspace-section-title">{{ __('Spouse / Partner') }}</div>
                <div class="mt-3 space-y-1.5">
                    @foreach ($spouses as $member)
                        <button type="button"
                                class="flex w-full items-center gap-3 rounded-xl border border-[#ececec] bg-[#fafafa] px-3 py-2 text-left text-[13px] text-[#566472] transition hover:bg-white"
                                data-load-person="{{ $member['id'] }}">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#fce7f3] text-[10px] font-semibold text-[#db2777]">
                                {{ $member['data']['is_private'] ? '?' : mb_strtoupper(mb_substr($member['data']['display_name'], 0, 1)) }}
                            </div>
                            <span class="truncate">{{ $member['data']['display_name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        @if (! empty($children))
            <section class="workspace-panel-section">
                <div class="workspace-section-title">{{ __('Children') }}</div>
                <div class="mt-3 space-y-1.5">
                    @foreach ($children as $member)
                        <button type="button"
                                class="flex w-full items-center gap-3 rounded-xl border border-[#ececec] bg-[#fafafa] px-3 py-2 text-left text-[13px] text-[#566472] transition hover:bg-white"
                                data-load-person="{{ $member['id'] }}">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#dcfce7] text-[10px] font-semibold text-[#16a34a]">
                                {{ $member['data']['is_private'] ? '?' : mb_strtoupper(mb_substr($member['data']['display_name'], 0, 1)) }}
                            </div>
                            <span class="truncate">{{ $member['data']['display_name'] }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        @if (empty($parents) && empty($spouses) && empty($children))
            <section class="workspace-panel-section">
                <p class="text-[13px] text-[#8b97a0]">{{ __('No linked family members visible in the Global Tree.') }}</p>
            </section>
        @endif

    @else
        <section class="workspace-panel-section">
            <p class="text-[13px] leading-6 text-[#8b97a0]">
                {{ __('This person\'s details are hidden to comply with GDPR and data-protection law.') }}
            </p>
        </section>
    @endif

    <section class="workspace-panel-section border-t border-[#ececec]">
        <div class="workspace-section-title">{{ __('Privacy notice') }}</div>
        <p class="mt-2 text-[12px] leading-5 text-[#8b97a0]">
            {{ __('Any person born within the last 100 years with no recorded death date is shown as "Private Person".') }}
        </p>
    </section>
</div>
