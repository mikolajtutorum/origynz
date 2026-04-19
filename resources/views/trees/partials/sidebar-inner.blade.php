@if ($focusPerson)
    <div class="workspace-person-hero border-b border-[#ececec] px-6 py-7">
        <div class="flex items-start gap-5">
            @php $focusAvatarUrl = $personAvatarUrls[$focusPerson->id] ?? null; @endphp
            @if ($focusAvatarUrl)
                <img src="{{ $focusAvatarUrl }}" class="workspace-profile-avatar object-cover" alt="{{ $focusPerson->display_name }}" />
            @else
                <div class="workspace-profile-avatar">{{ $focusPerson->display_name[0] ?? '?' }}</div>
            @endif
            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <h1 class="workspace-person-hero-name">{{ $focusPerson->display_name }}</h1>
                    @if ($tree->owner_person_id === $focusPerson->id)
                        <span class="workspace-person-badge">{{ __('You') }}</span>
                    @endif
                </div>

                <div class="workspace-person-hero-meta">
                    <span class="workspace-person-hero-meta-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" class="workspace-inline-icon">
                            <path d="M12 3c-2.8 3.7-4.2 6.5-4.2 8.5a4.2 4.2 0 1 0 8.4 0C16.2 9.5 14.8 6.7 12 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5 14.5c0 3.4 2.8 6.2 6.2 6.2 5.2 0 8.8-4 8.8-8.8 0-2.4-1.1-4.8-3.3-7.2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="workspace-person-hero-meta-text">
                        {{ $ownerRelationshipLabel }}
                    </span>
                </div>

                <div class="workspace-person-vitals">
                    <div class="workspace-person-vital">
                        <span class="workspace-person-vital-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" class="workspace-inline-icon">
                                <path d="M12 3v18M3 12h18M6.5 6.5l11 11M17.5 6.5l-11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <div class="workspace-person-vital-date">{{ $focusPerson->readable_birth_date ?: __('Birth date unknown') }}</div>
                            @if ($focusPerson->birth_place)
                                <div class="workspace-person-vital-place">{{ $focusPerson->birth_place }}</div>
                            @endif
                        </div>
                    </div>

                    @if (! $focusPerson->is_living && ($focusPerson->readable_death_date || $focusPerson->death_place))
                        <div class="workspace-person-vital">
                            <span class="workspace-person-vital-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="workspace-inline-icon">
                                    <rect x="6" y="3.5" width="12" height="17" rx="2.5" stroke="currentColor" stroke-width="2"/>
                                    <path d="M9 8.5h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                @if ($focusPerson->readable_death_date)
                                    <div class="workspace-person-vital-date">{{ $focusPerson->readable_death_date }}</div>
                                @endif
                                @if ($focusPerson->death_place)
                                    <div class="workspace-person-vital-place">{{ $focusPerson->death_place }}</div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <a href="{{ route('trees.show', ['tree' => $tree, 'focus' => $focusPerson->id, 'mode' => $chartMode, 'generations' => $chartGenerations]) }}" class="workspace-person-hero-link">
                    {{ __('Research this person ›') }}
                </a>
            </div>
        </div>

        <div class="workspace-action-dock">
            <button type="button" class="workspace-action-chip is-active" data-panel-target="profile-card">
                <span class="workspace-action-chip-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="workspace-inline-icon">
                        <rect x="3" y="5" width="18" height="14" rx="2.5" stroke="currentColor" stroke-width="1.8"/>
                        <circle cx="9" cy="11" r="2.3" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M5.5 17c.8-2 2.4-3 4.5-3s3.7 1 4.5 3M14.5 10h4M14.5 13h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>{{ __('Profile') }}</span>
            </button>
            <button type="button" class="workspace-action-chip" data-edit-profile-open>
                <span class="workspace-action-chip-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="workspace-inline-icon">
                        <path d="M4 20h4l9.8-9.8a2.1 2.1 0 0 0-3-3L5 17v3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="m13.5 6.5 3 3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>{{ __('Edit') }}</span>
            </button>
            <button
                type="button"
                class="workspace-action-chip"
                data-role-chooser-open
                data-link-person-id="{{ $focusPerson->id }}"
                data-person-name="{{ $focusPerson->display_name }}"
                data-person-life-span="{{ $focusPerson->life_span }}"
                data-focus-url="{{ route('trees.show', ['tree' => $tree, 'focus' => $focusPerson->id, 'mode' => $chartMode, 'generations' => $chartGenerations]) }}"
                data-person-surname="{{ $focusPerson->surname }}"
                data-has-father="{{ count($focusFamily['parents']) > 0 && collect($focusFamily['parents'])->contains(fn ($person) => $person->sex === 'male') ? '1' : '0' }}"
                data-has-mother="{{ count($focusFamily['parents']) > 0 && collect($focusFamily['parents'])->contains(fn ($person) => $person->sex === 'female') ? '1' : '0' }}"
            >
                <span class="workspace-action-chip-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="workspace-inline-icon">
                        <circle cx="10" cy="9" r="3" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M4.5 18c1.2-2.8 3.1-4.2 5.5-4.2s4.3 1.4 5.5 4.2M19 8v6M16 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>{{ __('Add') }}</span>
            </button>
            <button type="button" class="workspace-action-chip" data-more-menu-open>
                <span class="workspace-action-chip-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="workspace-inline-icon">
                        <circle cx="5" cy="12" r="1.5" fill="currentColor"/>
                        <circle cx="12" cy="12" r="1.5" fill="currentColor"/>
                        <circle cx="19" cy="12" r="1.5" fill="currentColor"/>
                    </svg>
                </span>
                <span>{{ __('More') }}</span>
            </button>
        </div>

        <div class="workspace-more-menu is-hidden" data-more-menu>
            <a href="{{ route('trees.show', ['tree' => $tree, 'focus' => $focusPerson->id, 'mode' => $chartMode, 'generations' => $chartGenerations]) }}" class="workspace-more-menu-item">
                <svg viewBox="0 0 24 24" fill="none" class="workspace-more-menu-icon">
                    <circle cx="12" cy="5" r="2" stroke="currentColor" stroke-width="1.8"/>
                    <circle cx="5" cy="19" r="2" stroke="currentColor" stroke-width="1.8"/>
                    <circle cx="19" cy="19" r="2" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M12 7v4M12 11l-5 6M12 11l5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ __('View his tree') }}
            </a>
            <button type="button" class="workspace-more-menu-item" data-more-menu-action="media-panel">
                <svg viewBox="0 0 24 24" fill="none" class="workspace-more-menu-icon">
                    <circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M3 9a2 2 0 0 1 2-2h1.5l1.5-2h6l1.5 2H19a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9Z" stroke="currentColor" stroke-width="1.8"/>
                </svg>
                {{ __('Edit photo') }}
            </button>
            <button type="button" class="workspace-more-menu-item" data-more-menu-action="link-relationship">
                <svg viewBox="0 0 24 24" fill="none" class="workspace-more-menu-icon">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ __('Connect to existing person') }}
            </button>
            <button type="button" class="workspace-more-menu-item" data-more-menu-action="link-relationship">
                <svg viewBox="0 0 24 24" fill="none" class="workspace-more-menu-icon">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <line x1="4" y1="4" x2="20" y2="20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                {{ __('Remove connection') }}
            </button>
            <button type="button" class="workspace-more-menu-item" data-more-menu-action="link-relationship">
                <svg viewBox="0 0 24 24" fill="none" class="workspace-more-menu-icon">
                    <circle cx="9" cy="7" r="3" stroke="currentColor" stroke-width="1.8"/>
                    <circle cx="17" cy="7" r="2" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M3 19c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M17 10c1.7 0 4 1 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                {{ __('Manage parents') }}
            </button>
            <button type="button" class="workspace-more-menu-item workspace-more-menu-item--danger">
                <svg viewBox="0 0 24 24" fill="none" class="workspace-more-menu-icon">
                    <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                {{ __('Delete this person') }}
            </button>
        </div>
    </div>
@endif

<div class="flex-1">
    @if (session('status'))
        <section class="workspace-panel-section">
            <div class="workspace-notice">{{ session('status') }}</div>
        </section>
    @endif

    @if ($errors->any())
        <section class="workspace-panel-section">
            <div class="workspace-error-list">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="workspace-panel-section" data-panel>
            <div class="workspace-section-title">{{ __('Discoveries') }}</div>
        <div class="space-y-3 text-[13px] text-[#5f6b76]">
            <div class="flex items-center gap-3"><span class="text-[#55bf6b]">⟳</span><span>{{ __(':count suggested family links', ['count' => count($focusFamily['parents']) + count($focusFamily['children'])]) }}</span></div>
            <div class="flex items-center gap-3"><span class="text-[#c37a35]">◔</span><span>{{ __(':count linked relationships in this tree', ['count' => $relationshipCount]) }}</span></div>
        </div>
    </section>

    <section class="workspace-panel-section" data-panel>
        <div class="flex items-center justify-between">
            <div class="workspace-section-title">{{ __('Photos & media') }} ({{ $focusImageMedia->count() }})</div>
            <div class="flex items-center gap-3">
                <a href="{{ route('trees.media.index', $tree) }}" class="text-[13px] font-medium text-[#64707b] hover:text-[#1f252b]">{{ __('Library') }}</a>
                <button type="button" class="text-[13px] font-medium text-[#ff6c2f]" data-panel-target="media-panel">{{ __('+ Add') }}</button>
            </div>
        </div>
        @if ($focusImageMedia->isNotEmpty())
            <div class="mt-4 grid grid-cols-4 gap-2">
                @foreach ($focusImageMedia->take(4) as $item)
                    <a href="{{ route('media.show', $item) }}" class="workspace-media-thumb group">
                        <img src="{{ route('media.preview', $item) }}" class="workspace-photo-tile workspace-photo-tile--gallery object-cover transition duration-200 group-hover:brightness-75" alt="{{ $item->title }}" />
                    </a>
                @endforeach
            </div>
        @else
            <p class="mt-4 text-[13px] text-[#8b97a0]">{{ __('No media attached to this profile yet.') }}</p>
        @endif
    </section>

    <section id="profile-card" class="workspace-panel-section" data-panel>
        <div class="flex items-center justify-between">
            <div class="workspace-section-title">{{ __('Biography') }}</div>
            <span class="text-[13px] font-medium text-[#ff6c2f]">{{ __('+ Add') }}</span>
        </div>
        <p class="mt-4 text-[13px] leading-6 text-[#606d78]">
            {{ $focusPerson?->notes ?: __('Add life events, occupations, education, migration details, and personal stories to make this profile richer.') }}
        </p>
    </section>

    <section class="workspace-panel-section" data-panel>
        <div class="flex items-center justify-between">
            <div class="workspace-section-title">{{ __('Timeline & facts') }} ({{ $focusEvents->count() }})</div>
        </div>
        @if ($focusEvents->isNotEmpty())
            <div class="mt-4 space-y-3">
                @foreach ($focusEvents->take(10) as $event)
                    <div class="workspace-list-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-medium text-[#40505c]">{{ $event->label }}</div>
                            @if ($event->event_date || $event->event_date_text)
                                <div class="text-[11px] uppercase tracking-[0.14em] text-[#7b8791]">
                                    {{ $event->event_date?->format('j M Y') ?: $event->event_date_text }}
                                </div>
                            @endif
                        </div>
                        @if ($event->value)
                            <div class="mt-1 text-[12px] text-[#5d6974]">{{ $event->value }}</div>
                        @endif
                        @if ($event->place || $event->address_line1 || $event->city || $event->country)
                            <div class="mt-1 text-[11px] text-[#7b8791]">
                                {{ collect([$event->place, $event->address_line1, $event->city, $event->country])->filter()->implode(' · ') }}
                            </div>
                        @endif
                        @if ($event->age || $event->cause || $event->email)
                            <div class="mt-1 text-[11px] text-[#7b8791]">
                                {{ collect([
                                    $event->age ? __('Age: :value', ['value' => $event->age]) : null,
                                    $event->cause ? __('Cause: :value', ['value' => $event->cause]) : null,
                                    $event->email,
                                ])->filter()->implode(' · ') }}
                            </div>
                        @endif
                        @if ($event->description)
                            <p class="mt-2 text-[12px] leading-5 text-[#5d6974]">{{ $event->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-4 text-[13px] text-[#8b97a0]">{{ __('No imported timeline facts are attached to this person yet.') }}</p>
        @endif
    </section>

    @if ($focusRelationshipFacts->isNotEmpty())
        <section class="workspace-panel-section" data-panel>
            <div class="workspace-section-title">{{ __('Relationship facts') }}</div>
            <div class="mt-4 space-y-3">
                @foreach ($focusRelationshipFacts as $fact)
                    <div class="workspace-list-card">
                        <div class="font-medium text-[#40505c]">
                            {{ __('Relationship with :person', ['person' => $fact['person']->display_name]) }}
                        </div>
                        <div class="mt-1 text-[11px] text-[#7b8791]">
                            {{ collect([
                                $fact['subtype'],
                                $fact['start_date_readable'] ? __('From: :value', ['value' => $fact['start_date_readable']]) : null,
                                $fact['end_date_text'] ? __('Until: :value', ['value' => $fact['end_date_text']]) : null,
                                $fact['place'],
                            ])->filter()->implode(' · ') }}
                        </div>
                        @if ($fact['description'])
                            <p class="mt-2 text-[12px] leading-5 text-[#5d6974]">{{ $fact['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="workspace-panel-section" data-panel>
        <div class="workspace-section-title">{{ __('Imported record') }}</div>
        <div class="mt-4 space-y-2 text-[12px] text-[#5d6974]">
            @if ($focusPerson?->alternative_name)
                <div>{{ __('Alternative name: :value', ['value' => $focusPerson->alternative_name]) }}</div>
            @endif
            @if ($focusPerson?->gedcom_updated_at_text)
                <div>{{ __('Imported source update: :value', ['value' => $focusPerson->gedcom_updated_at_text]) }}</div>
            @endif
            @if ($focusPerson?->gedcom_rin)
                <div>{{ __('GEDCOM RIN: :value', ['value' => $focusPerson->gedcom_rin]) }}</div>
            @endif
            @if ($focusPerson?->gedcom_uid)
                <div>{{ __('GEDCOM UID: :value', ['value' => $focusPerson->gedcom_uid]) }}</div>
            @endif
            @if ($tree->gedcom_source_system || $tree->gedcom_language)
                <div>
                    {{ collect([
                        $tree->gedcom_source_system,
                        $tree->gedcom_source_version,
                        $tree->gedcom_language,
                    ])->filter()->implode(' · ') }}
                </div>
            @endif
            @if ($tree->gedcom_exported_at_text)
                <div>{{ __('GEDCOM export date: :value', ['value' => $tree->gedcom_exported_at_text]) }}</div>
            @endif
        </div>
    </section>

    <section class="workspace-panel-section" data-panel>
        <div class="flex items-center justify-between">
            <div class="workspace-section-title">{{ __('Immediate family') }}</div>
            <span class="text-[#4b4b4b]">▾</span>
        </div>
        <div class="mt-4 space-y-2">
            @forelse ($immediateFamily as $familyMember)
                <a href="{{ route('trees.show', ['tree' => $tree, 'focus' => $familyMember->id, 'mode' => $chartMode, 'generations' => $chartGenerations, 'collapsed' => $toolbarCollapsed]) }}"
                   class="block rounded-xl border border-[#ececec] bg-[#fafafa] px-3 py-2 text-[13px] text-[#566472] hover:bg-white">
                    {{ $familyMember->display_name }}
                </a>
            @empty
                <p class="text-[13px] text-[#8b97a0]">{{ __('No immediate family linked yet.') }}</p>
            @endforelse
        </div>
    </section>

    <section id="add-person" class="workspace-panel-section is-hidden" data-panel>
        <div class="workspace-section-title">{{ __('Quick add person') }}</div>
        <form method="POST" action="{{ route('trees.people.store', $tree) }}" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="return_to" value="{{ $currentUrl }}" />
            <input name="given_name" required class="workspace-input" placeholder="{{ __('Given name') }}" />
            <input name="surname" required class="workspace-input" placeholder="{{ __('Surname') }}" />
            <div class="grid grid-cols-2 gap-3">
                <select name="sex" class="workspace-input">
                    <option value="female">{{ __('Female') }}</option>
                    <option value="male">{{ __('Male') }}</option>
                    <option value="unknown" selected>{{ __('Unknown') }}</option>
                </select>
                <input name="birth_place" class="workspace-input" placeholder="{{ __('Birth place') }}" />
            </div>
            <button type="submit" class="workspace-primary-button">{{ __('Add profile') }}</button>
        </form>
    </section>

    <section id="media-panel" class="workspace-panel-section is-hidden" data-panel>
        <div class="workspace-section-title">{{ __('Attach media') }}</div>
        @if ($focusMedia->isNotEmpty())
            <div class="mt-4 space-y-2">
                @foreach ($focusMedia as $item)
                    <div class="workspace-list-card">
                        <a href="{{ route('media.show', $item) }}" class="font-medium text-[#40505c] hover:text-[#2563eb]">{{ $item->title }}</a>
                        @if ($item->description)
                            <p class="mt-2 text-[12px] leading-5 text-[#5d6974]">{{ $item->description }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
        <form method="POST" action="{{ route('trees.media.store', $tree) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="return_to" value="{{ $currentUrl }}" />
            <input type="hidden" name="person_id" value="{{ $focusPerson?->id }}" />
            <input name="title" required class="workspace-input" placeholder="{{ __('Media title') }}" />
            <input type="file" name="media_file" class="workspace-input" required />
            <textarea name="description" rows="3" class="workspace-input" placeholder="{{ __('What does this item show or prove?') }}"></textarea>
            <label class="flex items-center gap-2 text-[13px] text-[#57636d]">
                <input type="checkbox" name="is_primary" value="1" />
                <span>{{ __('Mark as primary media') }}</span>
            </label>
            <button type="submit" class="workspace-primary-button">{{ __('Upload media') }}</button>
        </form>
    </section>

    <section id="sources-panel" class="workspace-panel-section is-hidden" data-panel>
        <div class="workspace-section-title">{{ __('Sources & citations') }}</div>
        @if ($focusCitations->isNotEmpty())
            <div class="mt-4 space-y-3">
                @foreach ($focusCitations as $citation)
                    <div class="workspace-list-card">
                        <div class="font-medium text-[#40505c]">{{ $citation->source->title }}</div>
                        @if ($citation->page)
                            <div class="mt-1 text-[11px] uppercase tracking-[0.14em] text-[#7b8791]">{{ __('Page') }} {{ $citation->page }}</div>
                        @endif
                        @if ($citation->event_name || $citation->role || $citation->entry_date_text)
                            <div class="mt-1 text-[11px] text-[#7b8791]">
                                {{ collect([$citation->event_name, $citation->role, $citation->entry_date_text])->filter()->implode(' · ') }}
                            </div>
                        @endif
                        @if ($citation->quotation)
                            <p class="mt-2 text-[12px] leading-5 text-[#5d6974]">{{ $citation->quotation }}</p>
                        @endif
                        @if ($citation->entry_text)
                            <p class="mt-2 text-[12px] leading-5 text-[#5d6974]">{{ $citation->entry_text }}</p>
                        @endif
                        @if ($citation->source->author || $citation->source->source_type || $citation->source->source_medium)
                            <div class="mt-2 text-[11px] text-[#7b8791]">
                                {{ collect([$citation->source->author, $citation->source->source_type, $citation->source->source_medium])->filter()->implode(' · ') }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-4 text-[13px] text-[#8b97a0]">{{ __('No source citations linked to this person yet.') }}</p>
        @endif
        <form method="POST" action="{{ route('trees.sources.store', $tree) }}" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="return_to" value="{{ $currentUrl }}" />
            <input type="hidden" name="person_id" value="{{ $focusPerson?->id }}" />
            <input name="title" required class="workspace-input" placeholder="{{ __('Source title') }}" />
            <div class="grid grid-cols-2 gap-3">
                <input name="author" class="workspace-input" placeholder="{{ __('Author or creator') }}" />
                <input name="publication_facts" class="workspace-input" placeholder="{{ __('Publication facts') }}" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <input name="repository" class="workspace-input" placeholder="{{ __('Repository') }}" />
                <input name="call_number" class="workspace-input" placeholder="{{ __('Call number / film no.') }}" />
            </div>
            <input name="url" class="workspace-input" placeholder="{{ __('URL') }}" />
            <input name="page" class="workspace-input" placeholder="{{ __('Page / frame / item reference') }}" />
            <textarea name="quotation" rows="3" class="workspace-input" placeholder="{{ __('Quoted evidence') }}"></textarea>
            <textarea name="citation_note" rows="3" class="workspace-input" placeholder="{{ __('Citation note') }}"></textarea>
            <div class="grid grid-cols-2 gap-3">
                <select name="source_quality" class="workspace-input">
                    <option value="">{{ __('Source quality') }}</option>
                    <option value="0">{{ __('0 - Unreliable') }}</option>
                    <option value="1">{{ __('1 - Questionable') }}</option>
                    <option value="2">{{ __('2 - Secondary') }}</option>
                    <option value="3">{{ __('3 - Primary') }}</option>
                </select>
                <select name="citation_quality" class="workspace-input">
                    <option value="">{{ __('Citation confidence') }}</option>
                    <option value="0">{{ __('0 - Unreliable') }}</option>
                    <option value="1">{{ __('1 - Questionable') }}</option>
                    <option value="2">{{ __('2 - Secondary') }}</option>
                    <option value="3">{{ __('3 - Primary') }}</option>
                </select>
            </div>
            <textarea name="text" rows="3" class="workspace-input" placeholder="{{ __('Source transcript or abstract') }}"></textarea>
            <button type="submit" class="workspace-primary-button">{{ __('Save source citation') }}</button>
        </form>
    </section>

    <section id="link-relationship" class="workspace-panel-section is-hidden" data-panel>
        <div class="workspace-section-title">{{ __('Link relationship') }}</div>
        <form method="POST" action="{{ route('trees.relationships.store', $tree) }}" class="mt-4 space-y-3">
            @csrf
            <input type="hidden" name="return_to" value="{{ $currentUrl }}" />
            <select name="person_id" class="workspace-input" data-link-person>
                <option value="">{{ __('Choose person') }}</option>
                @foreach ($peopleOptions as $person)
                    <option value="{{ $person['id'] }}" @selected($focusPerson && $focusPerson->id === $person['id'])>{{ $person['name'] }}</option>
                @endforeach
            </select>
            <select name="type" class="workspace-input">
                <option value="parent">{{ __('is parent of') }}</option>
                <option value="child">{{ __('is child of') }}</option>
                <option value="spouse">{{ __('is spouse of') }}</option>
            </select>
            <select name="subtype" class="workspace-input">
                <option value="">{{ __('Relationship label (optional)') }}</option>
                <option value="birth">{{ __('Biological / birth') }}</option>
                <option value="adoptive">{{ __('Adoptive') }}</option>
                <option value="foster">{{ __('Foster') }}</option>
                <option value="guardian">{{ __('Guardian') }}</option>
                <option value="step">{{ __('Step') }}</option>
            </select>
            <select name="related_person_id" class="workspace-input" data-link-related-person>
                <option value="">{{ __('Choose related person') }}</option>
                @foreach ($peopleOptions as $person)
                    <option value="{{ $person['id'] }}">{{ $person['name'] }}</option>
                @endforeach
            </select>
            <button type="submit" class="workspace-primary-button">{{ __('Save link') }}</button>
        </form>
    </section>
</div>
