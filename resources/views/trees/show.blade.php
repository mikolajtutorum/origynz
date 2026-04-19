<!DOCTYPE html>
@php
    $direction = config('app.locale_meta.'.app()->getLocale().'.direction', 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $direction }}" class="h-full bg-[#efefef]">
    <head>
        @include('partials.head', ['title' => $tree->name])
    </head>
    <body class="tree-workspace-page h-full overflow-hidden bg-[#efefef] text-[#474747]">
        <div class="flex min-h-screen flex-col" data-tree-workspace data-people-count="{{ $peopleCount }}">
            <x-app-header
                active-nav="family-tree"
                :family-tree-href="route('trees.show', ['tree' => $tree, 'focus' => $focusPerson?->id, 'mode' => $chartMode, 'generations' => $chartGenerations, 'collapsed' => $toolbarCollapsed])"
            >
                <x-slot:topbar-left>
                    <div id="site-switcher" class="relative">
                        <button id="site-switcher-btn" aria-expanded="false" class="flex items-center gap-2 text-[16px] font-medium hover:text-white/80">
                            {{ $currentSite->name }}
                            <span class="text-white/70 text-[13px]">▾</span>
                        </button>
                        <div id="site-switcher-dropdown" class="absolute top-full left-0 z-50 mt-1 hidden min-w-[240px] overflow-hidden rounded-[6px] border border-white/20 bg-[#4a4846] py-1 shadow-xl" role="menu">
                            @foreach ($accessibleSites as $accessibleSite)
                                <a href="{{ route('sites.open', $accessibleSite) }}"
                                   role="menuitem"
                                   class="flex items-center gap-2 px-4 py-2.5 text-[13px] text-white/90 hover:bg-white/10 {{ $accessibleSite->id === $currentSite->id ? 'font-semibold' : '' }}">
                                    @if ($accessibleSite->id === $currentSite->id)
                                        <span class="text-white/60">✓</span>
                                    @else
                                        <span class="w-[1em] inline-block"></span>
                                    @endif
                                    {{ $accessibleSite->name }}
                                    @if ($accessibleSite->user_id !== auth()->id())
                                        <span class="ml-auto text-[11px] text-white/50">{{ __('shared') }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-white/85">
                        <span class="workspace-top-icon">↻</span>
                        <span class="workspace-top-icon relative">☰<span class="workspace-badge">99+</span></span>
                        <span class="workspace-top-icon relative">⌬<span class="workspace-dot"></span></span>
                    </div>
                </x-slot:topbar-left>
                <x-slot:topbar-right-extra>
                    <a href="{{ route('dashboard') }}" class="rounded bg-[#2563eb] px-4 py-1.5 font-medium text-white hover:bg-[#1d4ed8]">{{ __('Go Workspace') }}</a>
                </x-slot:topbar-right-extra>
            </x-app-header>

            <div class="border-b border-[#e7e7e7] bg-white">
                <div class="flex items-center justify-between px-8 py-4">
                    <div class="flex items-center gap-6 text-[14px] text-[#2f3b45]">
                        <div id="tree-switcher" class="relative">
                            <button id="tree-switcher-btn" aria-expanded="false" class="flex items-center gap-1.5 text-[18px] font-semibold hover:text-[#2563eb]">
                                {{ $tree->name }}
                                <span class="text-[#666] text-[14px]">▾</span>
                            </button>
                            <div id="tree-switcher-dropdown" class="absolute top-full left-0 z-50 mt-1 hidden min-w-[220px] overflow-hidden rounded-[6px] border border-[#dde1e6] bg-white py-1 shadow-xl" role="menu">
                                @foreach ($userTrees as $userTree)
                                    <a href="{{ route('trees.show', $userTree) }}"
                                       role="menuitem"
                                       class="flex items-center gap-2 px-4 py-2 text-[14px] text-[#2f3b45] hover:bg-[#f5f7fa] {{ $userTree->id === $tree->id ? 'font-semibold text-[#2563eb]' : '' }}">
                                        @if ($userTree->id === $tree->id)
                                            <span class="text-[#2563eb]">✓</span>
                                        @else
                                            <span class="w-[1em] inline-block"></span>
                                        @endif
                                        {{ $userTree->name }}
                                    </a>
                                @endforeach
                                <div class="my-1 border-t border-[#e7e7e7]"></div>
                                <a href="{{ route('trees.manage') }}" role="menuitem" class="flex items-center gap-2 px-4 py-2 text-[13px] text-[#5d6872] hover:bg-[#f5f7fa]">
                                    {{ __('Manage trees') }}
                                </a>
                            </div>
                        </div>
                        <div class="h-8 w-px bg-[#e3e3e3]"></div>
                        <div class="font-medium" data-toolbar-focus-name>{{ $focusPerson?->display_name ?? __('No focus person') }}</div>
                    </div>

                    <div class="flex items-center gap-6 text-[15px]">
                        <a href="{{ route('trees.show', ['tree' => $tree, 'focus' => $focusPerson?->id, 'mode' => 'pedigree', 'generations' => $chartGenerations, 'collapsed' => $toolbarCollapsed]) }}"
                           data-mode-tab="pedigree"
                           class="workspace-view-tab {{ $chartMode === 'pedigree' ? 'is-active' : '' }}">
                            {{ __('Family view') }}
                        </a>
                        <a href="{{ route('trees.show', ['tree' => $tree, 'focus' => $focusPerson?->id, 'mode' => 'descendants', 'generations' => $chartGenerations, 'collapsed' => $toolbarCollapsed]) }}"
                           data-mode-tab="descendants"
                           class="workspace-view-tab {{ $chartMode === 'descendants' ? 'is-active' : '' }}">
                            {{ __('Descendants') }}
                        </a>
                        <a href="{{ route('trees.gedcom.export', $tree) }}" class="rounded-full border border-[#d7d7d7] bg-white px-3 py-1.5 text-[13px] font-medium text-[#5d6872]">
                            {{ __('Export GEDCOM') }}
                        </a>
                        <form method="POST" action="{{ route('trees.gedcom.import', $tree) }}" enctype="multipart/form-data" class="flex items-center gap-2">
                            @csrf
                            <input type="file" name="gedcom_file" accept=".ged,.gedcom,text/plain" class="max-w-[190px] text-[12px] text-[#64707b]" required />
                            <button type="submit" class="rounded-full bg-[#2563eb] px-3 py-1.5 text-[13px] font-medium text-white hover:bg-[#1d4ed8]">
                                {{ __('Import GEDCOM') }}
                            </button>
                        </form>
                        <span class="workspace-icon-pill">⌘</span>
                        <span class="workspace-icon-pill">⊞</span>
                    </div>
                </div>
            </div>

            <div class="flex min-h-0 flex-1">
                <aside class="workspace-sidebar flex w-[300px] min-w-[300px] max-w-[300px] flex-col overflow-y-scroll {{ $direction === 'rtl' ? 'border-l' : 'border-r' }} border-[#dfdfdf] bg-white">
                    @include('trees.partials.sidebar-inner')
                </aside>

                <main class="flex min-w-0 flex-1 flex-col">
                    <div class="border-b border-[#e2e2e2] bg-white px-8 py-4">
                        <div class="flex items-center justify-between gap-6">
                            <div class="text-[15px] text-[#68727b]" data-toolbar-position>
                                {{ __(':position of :count people', ['position' => $focusPersonPosition, 'count' => $peopleCount]) }}
                            </div>

                            <div class="flex items-center gap-5 text-[14px] text-[#64707b]">
                                <div class="flex items-center gap-2"><span>⚒</span><span>{{ __('Tools') }}</span></div>
                                <div class="flex items-center gap-2">
                                    @foreach ([2, 3, 4, 5] as $generationCount)
                                        <a href="{{ route('trees.show', ['tree' => $tree, 'focus' => $focusPerson?->id, 'mode' => $chartMode, 'generations' => $generationCount, 'collapsed' => $toolbarCollapsed]) }}"
                                           data-gen-link="{{ $generationCount }}"
                                           class="rounded-full px-2 py-1 {{ $chartGenerations === $generationCount ? 'bg-[#ff6c2f] text-white' : 'hover:bg-[#f3f3f3]' }}">
                                            {{ $generationCount }}
                                        </a>
                                    @endforeach
                                </div>
                                <div class="workspace-search">
                                    <span class="text-[#9ca4aa]">⌕</span>
                                    <input
                                        type="search"
                                        class="min-w-0 flex-1 bg-transparent text-[14px] text-[#4f5a63] outline-none"
                                        placeholder="{{ __('Find a person...') }}"
                                        list="tree-person-search"
                                        data-person-search
                                    />
                                    <button type="button" class="text-[#ff6c2f]" data-person-search-submit>{{ __('Go') }}</button>
                                    <datalist id="tree-person-search">
                                        @foreach ($peopleSearchIndex as $person)
                                            <option
                                                value="{{ $person['name'] }}"
                                                data-person-id="{{ $person['id'] }}"
                                                label="{{ $person['life_span'] }} · {{ $person['birth_place'] }}"
                                            ></option>
                                        @endforeach
                                    </datalist>
                                </div>
                                <button type="button" class="workspace-round-icon" data-canvas-action="fit">⌂</button>
                                <button type="button" class="workspace-round-icon" data-panel-target="edit-profile">✎</button>
                            </div>
                        </div>
                    </div>

                    <div class="relative min-h-0 flex-1 overflow-hidden bg-[#efefef]">
                        <div class="absolute inset-0 overflow-auto" data-canvas-scroll>
                            @include('trees.partials.canvas')
                        </div>

                        <div class="workspace-floating-tools">
                            <button type="button" class="workspace-float-btn" data-canvas-action="center">⌖</button>
                            <button type="button" class="workspace-float-btn" data-canvas-action="fit">◎</button>
                            <button type="button" class="workspace-float-btn" data-canvas-action="fullscreen">⤢</button>
                            <button type="button" class="workspace-float-btn" data-canvas-action="home">⌂</button>
                            <button type="button" class="workspace-float-btn" data-canvas-action="zoom-in">＋</button>
                            <button type="button" class="workspace-float-btn" data-canvas-action="zoom-out">－</button>
                        </div>

                        <div class="workspace-role-chooser is-hidden" data-role-chooser>
                            <div class="workspace-role-close-row">
                                <button type="button" class="workspace-role-close" data-role-chooser-close>{{ __('Close ✕') }}</button>
                            </div>
                            <div class="workspace-role-chooser-shell">

                                {{-- Left column: siblings --}}
                                <div class="wrc-side-col">
                                    <button type="button" class="wrc-add-btn wrc-add-btn--male" data-role-choice="brother" data-role-slot="brother">
                                        <span class="wrc-avatar-icon"></span>
                                        <span>{{ __('Add brother') }}</span>
                                    </button>
                                    <button type="button" class="wrc-add-btn wrc-add-btn--female" data-role-choice="sister" data-role-slot="sister">
                                        <span class="wrc-avatar-icon"></span>
                                        <span>{{ __('Add sister') }}</span>
                                    </button>
                                </div>

                                {{-- Center column: parents / person / children --}}
                                <div class="wrc-center-col">
                                    <div class="wrc-dyad-row">
                                        <button type="button" class="wrc-add-btn wrc-add-btn--male" data-role-choice="father" data-role-slot="father">
                                            <span class="wrc-avatar-icon"></span>
                                            <span>{{ __('Add father') }}</span>
                                        </button>
                                        <button type="button" class="wrc-add-btn wrc-add-btn--female" data-role-choice="mother" data-role-slot="mother">
                                            <span class="wrc-avatar-icon"></span>
                                            <span>{{ __('Add mother') }}</span>
                                        </button>
                                    </div>

                                    <div class="wrc-line wrc-line--top"></div>

                                    <div class="workspace-role-center-card">
                                        <div class="workspace-role-avatar" data-role-anchor-avatar>?</div>
                                        <div>
                                            <div class="text-[15px] font-semibold text-[#2b3944]" data-role-anchor-name>{{ __('Selected person') }}</div>
                                            <div class="mt-1 text-[12px] text-[#6c7882]" data-role-anchor-life>{{ __('Dates unknown') }}</div>
                                        </div>
                                    </div>

                                    <div class="wrc-line wrc-line--bottom"></div>

                                    <div class="wrc-dyad-row">
                                        <button type="button" class="wrc-add-btn wrc-add-btn--male" data-role-choice="son" data-role-slot="son">
                                            <span class="wrc-avatar-icon"></span>
                                            <span>{{ __('Add son') }}</span>
                                        </button>
                                        <button type="button" class="wrc-add-btn wrc-add-btn--female" data-role-choice="daughter" data-role-slot="daughter">
                                            <span class="wrc-avatar-icon"></span>
                                            <span>{{ __('Add daughter') }}</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Right column: partner --}}
                                <div class="wrc-side-col wrc-side-col--right">
                                    <button type="button" class="wrc-add-btn wrc-add-btn--female wrc-add-btn--partner" data-role-choice="partner" data-role-slot="partner">
                                        <span class="wrc-avatar-icon"></span>
                                        <div>
                                            <div>{{ __('Add another partner') }}</div>
                                            <div class="wrc-partner-sub">{{ __('Wife, ex-wife, partner...') }}</div>
                                        </div>
                                    </button>
                                </div>

                            </div>
                        </div>

                        <div class="workspace-person-modal is-hidden" data-person-modal>
                            <div class="workspace-person-modal-card">
                                <div class="flex items-start justify-between gap-4">
                                    <h3 class="text-[22px] font-semibold text-[#24313b]" data-person-modal-title>{{ __('Add relative') }}</h3>
                                    <button type="button" class="workspace-person-modal-close" data-person-modal-close>✕</button>
                                </div>

                                <form method="POST" action="{{ route('trees.people.store-relative', $tree) }}" class="mt-8 space-y-5" data-person-modal-form>
                                    @csrf
                                    <input type="hidden" name="anchor_person_id" value="{{ $focusPerson?->id }}" data-person-modal-anchor-id />
                                    <input type="hidden" name="relation_role" value="father" data-person-modal-role />
                                    <input type="hidden" name="return_to" value="{{ $currentUrl }}" data-person-modal-return-to />
                                    <input type="hidden" name="birth_date" value="" data-birth-date-hidden />
                                    <input type="hidden" name="birth_date_text" value="" data-birth-date-text-hidden />
                                    <input type="hidden" name="death_date" value="" data-death-date-hidden />
                                    <input type="hidden" name="death_date_text" value="" data-death-date-text-hidden />

                                    <div class="flex flex-wrap gap-6 text-[14px] text-[#62707b]">
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="sex" value="male" data-person-sex />
                                            <span>{{ __('Male') }}</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="sex" value="female" data-person-sex />
                                            <span>{{ __('Female') }}</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="sex" value="unknown" data-person-sex checked />
                                            <span>{{ __('Unknown') }}</span>
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="workspace-modal-label">{{ __('First (and middle) name:') }}</label>
                                            <input name="given_name" required class="workspace-input workspace-modal-input" />
                                        </div>
                                        <div>
                                            <label class="workspace-modal-label">{{ __('Last name:') }}</label>
                                            <input name="surname" required class="workspace-input workspace-modal-input" data-person-modal-surname />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="workspace-modal-label">{{ __('Birth date:') }}</label>
                                            <div class="grid grid-cols-4 gap-2">
                                                <select class="workspace-input workspace-modal-input" data-birth-date-mode>
                                                    <option value="exact">{{ __('Exactly') }}</option>
                                                    <option value="about">{{ __('About') }}</option>
                                                    <option value="before">{{ __('Before') }}</option>
                                                    <option value="after">{{ __('After') }}</option>
                                                    <option value="year">{{ __('Year only') }}</option>
                                                </select>
                                                <select class="workspace-input workspace-modal-input" data-birth-date-month>
                                                    <option value="">{{ __('Month') }}</option>
                                                    @foreach (['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'] as $month)
                                                        <option value="{{ $month }}">{{ $month }}</option>
                                                    @endforeach
                                                </select>
                                                <input class="workspace-input workspace-modal-input" placeholder="{{ __('Day') }}" data-birth-date-day />
                                                <input class="workspace-input workspace-modal-input" placeholder="{{ __('Year') }}" data-birth-date-year />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="workspace-modal-label">{{ __('Birth place:') }}</label>
                                            <input name="birth_place" class="workspace-input workspace-modal-input" />
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-6 border-t border-[#ececec] pt-4 text-[14px] text-[#62707b]">
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="is_living" value="1" checked />
                                            <span>{{ __('Living') }}</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="is_living" value="0" />
                                            <span>{{ __('Deceased') }}</span>
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="workspace-modal-label">{{ __('Death date:') }}</label>
                                            <div class="grid grid-cols-4 gap-2">
                                                <select class="workspace-input workspace-modal-input" data-death-date-mode>
                                                    <option value="exact">{{ __('Exactly') }}</option>
                                                    <option value="about">{{ __('About') }}</option>
                                                    <option value="before">{{ __('Before') }}</option>
                                                    <option value="after">{{ __('After') }}</option>
                                                    <option value="year">{{ __('Year only') }}</option>
                                                </select>
                                                <select class="workspace-input workspace-modal-input" data-death-date-month>
                                                    <option value="">{{ __('Month') }}</option>
                                                    @foreach (['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'] as $month)
                                                        <option value="{{ $month }}">{{ $month }}</option>
                                                    @endforeach
                                                </select>
                                                <input class="workspace-input workspace-modal-input" placeholder="{{ __('Day') }}" data-death-date-day />
                                                <input class="workspace-input workspace-modal-input" placeholder="{{ __('Year') }}" data-death-date-year />
                                            </div>
                                        </div>
                                        <div>
                                            <label class="workspace-modal-label">{{ __('Death place:') }}</label>
                                            <input name="death_place" class="workspace-input workspace-modal-input" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="workspace-modal-label">{{ __('Biography note:') }}</label>
                                        <textarea name="notes" rows="4" class="workspace-input workspace-modal-input" placeholder="{{ __('Add notes, facts, and context') }}"></textarea>
                                    </div>

                                    <div class="flex items-center justify-between pt-2">
                                        <button type="button" class="text-[14px] font-medium text-[#ff6c2f]">{{ __('Edit more (bio, more facts...)') }}</button>
                                        <div class="flex gap-3">
                                            <button type="button" class="workspace-modal-secondary" data-person-modal-close>{{ __('Cancel') }}</button>
                                            <button type="submit" class="workspace-modal-primary">{{ __('OK') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @include('trees.partials.edit-modal')

                        @if ($showOwnerChooser)
                            <div class="workspace-person-modal" data-owner-person-modal>
                                <div class="workspace-person-modal-card max-h-[85vh] max-w-[58rem] overflow-hidden p-0">
                                    <div class="border-b border-[#e7edf3] px-8 py-6">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <h3 class="text-[22px] font-semibold text-[#24313b]">{{ __('Choose Your Imported Profile') }}</h3>
                                                <p class="mt-2 text-sm leading-6 text-[#62707b]">{{ __('Pick the person in this GEDCOM who should represent your account. The list below is scrollable and the save button stays in view.') }}</p>
                                            </div>
                                            <a href="{{ route('trees.show', ['tree' => $tree, 'focus' => $focusPerson?->id, 'mode' => $chartMode, 'generations' => $chartGenerations, 'collapsed' => $toolbarCollapsed]) }}" class="workspace-person-modal-close">✕</a>
                                        </div>

                                        <div class="mt-5 space-y-3">
                                            <input
                                                type="search"
                                                value=""
                                                class="workspace-input"
                                                placeholder="{{ __('Type to filter by name, year, or place') }}"
                                                data-owner-person-search
                                            />
                                            <p class="text-xs leading-5 text-[#6f7b83]" data-owner-person-count>
                                                {{ __('Showing :count people', ['count' => count($ownerCandidateOptions)]) }}
                                            </p>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('trees.owner-person', $tree) }}" class="flex max-h-[calc(85vh-10.5rem)] flex-col">
                                        @csrf
                                        <input type="hidden" name="return_to" value="{{ $currentUrl }}" />
                                        @php
                                            $defaultOwnerCandidateId = old('person_id', $ownerCandidateOptions[0]['id'] ?? null);
                                        @endphp

                                        <div class="min-h-0 flex-1 overflow-y-auto px-8 py-4" data-owner-person-results>
                                            <div class="space-y-3">
                                                @forelse ($ownerCandidateOptions as $person)
                                                    <label
                                                        class="flex cursor-pointer items-start justify-between gap-4 rounded-2xl border border-[#e7edf3] bg-white px-5 py-4 {{ $direction === 'rtl' ? 'text-right' : 'text-left' }} transition hover:border-[#93c5fd] hover:bg-[#f8fbff]"
                                                        data-owner-person-option
                                                        data-search="{{ \Illuminate\Support\Str::of($person['name'].' '.$person['life_span'].' '.$person['birth_place'])->lower()->ascii()->replaceMatches('/[^a-z0-9\\s]/', ' ')->squish() }}"
                                                        data-score="{{ $person['score'] }}"
                                                    >
                                                        <span class="flex min-w-0 items-start gap-4">
                                                            <input
                                                                type="radio"
                                                                name="person_id"
                                                                value="{{ $person['id'] }}"
                                                                class="mt-1 h-4 w-4"
                                                                {{ (string) $defaultOwnerCandidateId === (string) $person['id'] ? 'checked' : '' }}
                                                            />
                                                            <span class="min-w-0">
                                                                <span class="block font-medium text-[#1f252b]">{{ $person['name'] }}</span>
                                                                <span class="mt-1 block text-sm text-[#6f7b83]">
                                                                    {{ $person['life_span'] ?: __('Dates unknown') }}
                                                                    @if ($person['birth_place'])
                                                                        {{ ' · '.$person['birth_place'] }}
                                                                    @endif
                                                                </span>
                                                            </span>
                                                        </span>
                                                        @if ($person['match_label'] !== '')
                                                            <span class="shrink-0 rounded-full bg-[#eff6ff] px-2.5 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-[#1d4ed8]">
                                                                {{ __($person['match_label']) }}
                                                            </span>
                                                        @endif
                                                    </label>
                                                @empty
                                                    <div class="rounded-2xl border border-dashed border-[#c7d4df] bg-[#f7f9fb] px-5 py-4 text-sm leading-6 text-[#6f7b83]">
                                                        {{ __('No matches for that search yet. Try another name, year, or place.') }}
                                                    </div>
                                                @endforelse
                                                <div class="hidden rounded-2xl border border-dashed border-[#c7d4df] bg-[#f7f9fb] px-5 py-4 text-sm leading-6 text-[#6f7b83]" data-owner-person-empty>
                                                    {{ __('No matches for that search yet. Try another name, year, or place.') }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border-t border-[#e7edf3] bg-white px-8 py-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <p class="text-xs leading-5 text-[#6f7b83]">
                                                    {{ __('Showing the top ranked matches first. Start typing to re-rank and auto-select the best visible match.') }}
                                                </p>
                                                <button type="submit" class="workspace-modal-primary">{{ __('Use as my profile') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </main>
            </div>
        </div>

        <script type="application/json" id="tree-search-index">@json($peopleSearchIndex)</script>

        <script>
        (function () {
            function setupClickDropdown(rootId, btnId, dropdownId) {
                var root = document.getElementById(rootId);
                var btn = document.getElementById(btnId);
                var dropdown = document.getElementById(dropdownId);
                if (!root || !btn || !dropdown) return;
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var hidden = dropdown.classList.contains('hidden');
                    dropdown.classList.toggle('hidden');
                    btn.setAttribute('aria-expanded', String(hidden));
                });
                document.addEventListener('click', function (e) {
                    if (!root.contains(e.target)) {
                        dropdown.classList.add('hidden');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            }
            setupClickDropdown('site-switcher', 'site-switcher-btn', 'site-switcher-dropdown');
            setupClickDropdown('tree-switcher', 'tree-switcher-btn', 'tree-switcher-dropdown');
        })();
        </script>

        @fluxScripts
    </body>
</html>
