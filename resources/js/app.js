const workspace = document.querySelector('[data-tree-workspace]');

if (workspace) {
    // ─── Static DOM refs (never replaced by AJAX) ───────────────────────────
    const scrollViewport   = workspace.querySelector('[data-canvas-scroll]');
    const roleChooser      = workspace.querySelector('[data-role-chooser]');
    const roleChooserName  = workspace.querySelector('[data-role-anchor-name]');
    const roleChooserLife  = workspace.querySelector('[data-role-anchor-life]');
    const roleChooserAvatar = workspace.querySelector('[data-role-anchor-avatar]');
    const roleChoiceButtons = Array.from(workspace.querySelectorAll('[data-role-choice]'));
    const personModal       = workspace.querySelector('[data-person-modal]');
    const personModalForm   = workspace.querySelector('[data-person-modal-form]');
    const personModalTitle  = workspace.querySelector('[data-person-modal-title]');
    const personModalAnchorId = workspace.querySelector('[data-person-modal-anchor-id]');
    const personModalRole   = workspace.querySelector('[data-person-modal-role]');
    const personModalReturnTo = workspace.querySelector('[data-person-modal-return-to]');
    const personModalSurname  = workspace.querySelector('[data-person-modal-surname]');
    const birthDateHidden     = workspace.querySelector('[data-birth-date-hidden]');
    const birthDateTextHidden = workspace.querySelector('[data-birth-date-text-hidden]');
    const deathDateHidden     = workspace.querySelector('[data-death-date-hidden]');
    const deathDateTextHidden = workspace.querySelector('[data-death-date-text-hidden]');
    const searchInput         = workspace.querySelector('[data-person-search]');
    const searchSubmit        = workspace.querySelector('[data-person-search-submit]');
    const ownerPersonSearch   = workspace.querySelector('[data-owner-person-search]');
    const ownerPersonResults  = workspace.querySelector('[data-owner-person-results]');
    const ownerPersonEmpty    = workspace.querySelector('[data-owner-person-empty]');
    const ownerPersonCount    = workspace.querySelector('[data-owner-person-count]');

    // ─── Dynamic state ───────────────────────────────────────────────────────
    let stage     = null;
    let surface   = null;
    let focusCard = null;
    let scale     = 1;
    let baseWidth  = 0;
    let baseHeight = 0;
    let navigating = false;

    const treePath   = window.location.pathname;
    const searchIndex = JSON.parse(document.getElementById('tree-search-index')?.textContent ?? '[]');

    let relationshipAnchor = {
        id: '',
        name: 'Selected person',
        lifeSpan: 'Dates unknown',
        surname: '',
        focusUrl: window.location.href,
        hasFather: false,
        hasMother: false,
    };

    // ─── Canvas ref init ─────────────────────────────────────────────────────
    function initCanvasRefs() {
        stage     = workspace.querySelector('[data-canvas-stage]');
        surface   = workspace.querySelector('[data-canvas-surface]');
        focusCard = workspace.querySelector('[data-focus-card]');

        if (stage) {
            baseWidth  = Number.parseFloat(stage.style.width)  || 0;
            baseHeight = Number.parseFloat(stage.style.height) || 0;
        }
    }

    initCanvasRefs();

    // ─── Panel system (sidebar) ──────────────────────────────────────────────
    function getPanels() {
        return Array.from(workspace.querySelectorAll('[data-panel]'));
    }

    function getPanelButtons() {
        return Array.from(workspace.querySelectorAll('[data-panel-target]'));
    }

    function showPanel(panelId) {
        if (!panelId) { return; }

        const panels = getPanels();
        const panelButtons = getPanelButtons();

        panels.forEach((panel) => {
            if (!panel.id) { return; }
            panel.classList.toggle('is-hidden', panel.id !== panelId);
        });

        panelButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.panelTarget === panelId);
        });

        const activePanel = workspace.querySelector(`#${CSS.escape(panelId)}`);
        activePanel?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }

    // ─── Role chooser ────────────────────────────────────────────────────────
    const roleCopy = {
        father:   { title: 'Add father of',   sex: 'male'    },
        mother:   { title: 'Add mother of',   sex: 'female'  },
        brother:  { title: 'Add brother of',  sex: 'male'    },
        sister:   { title: 'Add sister of',   sex: 'female'  },
        son:      { title: 'Add son of',      sex: 'male'    },
        daughter: { title: 'Add daughter of', sex: 'female'  },
        partner:  { title: 'Add partner of',  sex: 'unknown' },
    };

    const setSex = (value) => {
        workspace.querySelectorAll('[data-person-sex]').forEach((input) => {
            input.checked = input.value === value;
        });
    };

    const updateRoleChooser = () => {
        if (!roleChooser) { return; }

        roleChooserName.textContent   = relationshipAnchor.name;
        roleChooserLife.textContent   = relationshipAnchor.lifeSpan;
        roleChooserAvatar.textContent = relationshipAnchor.name.slice(0, 1).toUpperCase();

        roleChoiceButtons.forEach((button) => {
            const slot = button.dataset.roleSlot;
            const shouldHide = (slot === 'father' && relationshipAnchor.hasFather)
                || (slot === 'mother' && relationshipAnchor.hasMother);
            button.classList.toggle('is-hidden', shouldHide);
        });
    };

    const openRoleChooser = (button) => {
        relationshipAnchor = {
            id:       button.dataset.linkPersonId ?? button.closest('[data-person-card]')?.dataset.personId ?? '',
            name:     button.dataset.personName ?? button.closest('[data-person-card]')?.dataset.personName ?? 'Selected person',
            lifeSpan: button.dataset.personLifeSpan ?? button.closest('[data-person-card]')?.dataset.personLifeSpan ?? 'Dates unknown',
            surname:  button.dataset.personSurname ?? button.closest('[data-person-card]')?.dataset.personSurname ?? '',
            focusUrl: button.dataset.focusUrl ?? button.closest('[data-person-card]')?.dataset.focusUrl ?? window.location.href,
            hasFather: (button.dataset.hasFather ?? button.closest('[data-person-card]')?.dataset.hasFather ?? '0') === '1',
            hasMother: (button.dataset.hasMother ?? button.closest('[data-person-card]')?.dataset.hasMother ?? '0') === '1',
        };

        updateRoleChooser();
        roleChooser?.classList.remove('is-hidden');
    };

    const closeRoleChooser = () => {
        roleChooser?.classList.add('is-hidden');
    };

    // ─── Add-relative modal ──────────────────────────────────────────────────
    const resetDateInputs = () => {
        ['birth', 'death'].forEach((prefix) => {
            workspace.querySelector(`[data-${prefix}-date-mode]`).value  = 'exact';
            workspace.querySelector(`[data-${prefix}-date-month]`).value = '';
            workspace.querySelector(`[data-${prefix}-date-day]`).value   = '';
            workspace.querySelector(`[data-${prefix}-date-year]`).value  = '';
        });
    };

    const openPersonModal = (role) => {
        const config = roleCopy[role] ?? roleCopy.partner;

        personModalForm?.reset();
        resetDateInputs();
        personModalAnchorId.value  = relationshipAnchor.id;
        personModalRole.value      = role;
        personModalReturnTo.value  = relationshipAnchor.focusUrl;
        personModalSurname.value   = relationshipAnchor.surname;
        personModalTitle.textContent = `${config.title} ${relationshipAnchor.name}`;
        setSex(config.sex);
        closeRoleChooser();
        personModal?.classList.remove('is-hidden');
    };

    const closePersonModal = () => {
        personModal?.classList.add('is-hidden');
    };

    // ─── Edit profile modal ──────────────────────────────────────────────────
    const getEditModal = () => workspace.querySelector('[data-edit-profile-modal]');

    const syncMaidenNameField = () => {
        const editModal    = getEditModal();
        const sexRadio     = editModal?.querySelector('[data-edit-sex]:checked');
        const maidenField  = editModal?.querySelector('[data-maiden-name-field]');
        const maidenInput  = editModal?.querySelector('[data-maiden-name-input]');

        if (!maidenField || !maidenInput) { return; }

        const isFemale = sexRadio?.value === 'female';
        maidenField.classList.toggle('hidden', !isFemale);
        maidenInput.disabled = !isFemale;

        if (!isFemale) {
            maidenInput.value = '';
        }
    };

    const openEditProfileModal = () => {
        syncMaidenNameField();
        const modal = getEditModal();
        if (modal) {
            modal.querySelectorAll('[data-date-picker]').forEach((picker) => {
                const ns = picker.dataset.datePicker;
                const modeSelect = picker.querySelector(`[data-${ns}-date-mode]`);
                if (modeSelect) { syncDatePickerUI(modeSelect); }
            });
        }
        modal?.classList.remove('is-hidden');
    };

    const closeEditProfileModal = () => {
        getEditModal()?.classList.add('is-hidden');
    };

    // ─── Date builder for GEDCOM ──────────────────────────────────────────────
    const MONTH_NUMS = { JAN:1,FEB:2,MAR:3,APR:4,MAY:5,JUN:6,JUL:7,AUG:8,SEP:9,OCT:10,NOV:11,DEC:12 };

    const gedcomFragment = (month, day, year) => {
        const d = String(day).padStart(2, '0');
        return [d !== '00' && d !== '' ? d : '', month, year].filter(Boolean).join(' ');
    };

    const exactIso = (month, day, year) => {
        if (!month || !year) { return ''; }
        const d = String(day).padStart(2, '0');
        if (d === '00') { return ''; }
        return `${year}-${String(MONTH_NUMS[month] ?? 1).padStart(2, '0')}-${d}`;
    };

    const buildGedcomDate = (ns) => {
        const scope = workspace;
        const mode  = scope.querySelector(`[data-${ns}-date-mode]`)?.value  ?? 'exact';
        const month = scope.querySelector(`[data-${ns}-date-month]`)?.value ?? '';
        const day   = (scope.querySelector(`[data-${ns}-date-day]`)?.value  ?? '').trim();
        const year  = (scope.querySelector(`[data-${ns}-date-year]`)?.value ?? '').trim();

        if (mode === 'free') {
            const txt = (scope.querySelector(`[data-${ns}-date-free]`)?.value ?? '').trim();
            return { date: '', text: txt };
        }

        if (year === '') { return { date: '', text: '' }; }

        const core = gedcomFragment(month, day, year);

        if (mode === 'exact')   { return { date: exactIso(month, day, year), text: core }; }
        if (mode === 'before')  { return { date: '', text: `BEF ${core}` }; }
        if (mode === 'after')   { return { date: '', text: `AFT ${core}` }; }
        if (mode === 'circa')   { return { date: '', text: `ABT ${core}` }; }
        if (mode === 'unsure')  { return { date: '', text: `EST ${core}` }; }
        if (mode === 'from')    { return { date: '', text: `FROM ${core}` }; }
        if (mode === 'to')      { return { date: '', text: `TO ${core}` }; }

        const month2 = scope.querySelector(`[data-${ns}-date-month2]`)?.value ?? '';
        const day2   = (scope.querySelector(`[data-${ns}-date-day2]`)?.value  ?? '').trim();
        const year2  = (scope.querySelector(`[data-${ns}-date-year2]`)?.value ?? '').trim();
        const core2  = gedcomFragment(month2, day2, year2);

        if (mode === 'between') { return { date: '', text: core2 ? `BET ${core} AND ${core2}` : `BET ${core}` }; }
        if (mode === 'from-to') { return { date: '', text: core2 ? `FROM ${core} TO ${core2}` : `FROM ${core}` }; }

        return { date: exactIso(month, day, year), text: core };
    };

    // Show/hide second date row and free-text field when mode changes
    const syncDatePickerUI = (modeSelect) => {
        const picker = modeSelect.closest('[data-date-picker]');
        if (!picker) { return; }
        const mode     = modeSelect.value;
        const isDual   = mode === 'between' || mode === 'from-to';
        const isFree   = mode === 'free';
        const fields   = picker.querySelector('[data-date-picker-fields]');
        const second   = picker.querySelector('[data-date-picker-second]');
        const freeInput = picker.querySelector('[data-date-picker-free]') ?? picker.querySelector('[class*="date-free"]');
        const secondLabel = picker.querySelector('[data-date-picker-second-label]');

        if (fields)    { fields.classList.toggle('hidden', isFree); }
        if (second)    { second.classList.toggle('hidden', !isDual || isFree); }
        if (secondLabel && isDual) { secondLabel.textContent = mode === 'between' ? 'and' : 'to'; }

        // Find the free input by its data attribute (dynamic ns)
        const ns = picker.dataset.datePicker;
        const freeEl = picker.querySelector(`[data-${ns}-date-free]`);
        if (freeEl)    { freeEl.classList.toggle('hidden', !isFree); }
    };

    // ─── Canvas / zoom helpers ────────────────────────────────────────────────
    const getCardCenter = (card) => {
        if (!card) { return null; }

        const left = Number.parseFloat(card.style.left || '0');
        const top  = Number.parseFloat(card.style.top  || '0');

        return {
            x: (left + (card.offsetWidth  / 2)) * scale,
            y: (top  + (card.offsetHeight / 2)) * scale,
        };
    };

    const centerCard = (card, behavior = 'smooth') => {
        if (!scrollViewport || !card) { return; }

        const center = getCardCenter(card);
        if (!center) { return; }

        scrollViewport.scrollTo({
            left: Math.max(0, center.x - (scrollViewport.clientWidth  / 2)),
            top:  Math.max(0, center.y - (scrollViewport.clientHeight / 2)),
            behavior,
        });
    };

    const applyScale = (nextScale, anchorCard = focusCard) => {
        if (!surface || !stage || !baseWidth || !baseHeight) { return; }

        scale = Math.max(0.5, Math.min(2.4, nextScale));
        surface.style.transform = `scale(${scale})`;
        stage.style.width  = `${baseWidth  * scale}px`;
        stage.style.height = `${baseHeight * scale}px`;

        if (anchorCard) {
            window.requestAnimationFrame(() => centerCard(anchorCard, 'auto'));
        }
    };

    const fitCanvas = () => {
        if (!scrollViewport || !baseWidth || !baseHeight) { return; }

        const widthScale  = scrollViewport.clientWidth  / (baseWidth  + 120);
        const heightScale = scrollViewport.clientHeight / (baseHeight + 120);
        applyScale(Math.min(1.15, Math.max(0.55, Math.min(widthScale, heightScale))));
    };

    // ─── Search ───────────────────────────────────────────────────────────────
    const performSearch = () => {
        if (!searchInput) { return; }

        const query = searchInput.value.trim().toLowerCase();
        if (query === '') { return; }

        const match = searchIndex.find((p) => p.name.toLowerCase() === query)
            ?? searchIndex.find((p) => p.name.toLowerCase().includes(query));

        if (match) {
            const url = new URL(window.location.href);
            url.searchParams.set('focus', match.id);
            navigateTo(url.toString());
        }
    };

    // ─── Owner candidate filter ───────────────────────────────────────────────
    const normalizeFilterText = (value) => value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const filterOwnerCandidates = () => {
        if (!ownerPersonSearch || !ownerPersonResults) { return; }

        const query      = normalizeFilterText(ownerPersonSearch.value);
        const queryParts = query === '' ? [] : query.split(' ').filter(Boolean);
        const options    = Array.from(ownerPersonResults.querySelectorAll('[data-owner-person-option]'));
        const scoredOptions = [];
        let visibleCount = 0;

        options.forEach((option) => {
            const haystack  = normalizeFilterText(option.dataset.search ?? option.textContent ?? '');
            const isVisible = query === '' || haystack.includes(query);

            option.classList.toggle('hidden', !isVisible);
            option.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                visibleCount += 1;
                let dynamicScore = Number.parseInt(option.dataset.score ?? '0', 10) || 0;

                if (query !== '') {
                    if (haystack === query)            { dynamicScore += 120; }
                    if (haystack.startsWith(query))    { dynamicScore += 60;  }

                    queryParts.forEach((part) => {
                        if (haystack.includes(` ${part} `) || haystack.startsWith(`${part} `) || haystack.endsWith(` ${part}`) || haystack === part) {
                            dynamicScore += 24;
                        } else if (haystack.includes(part)) {
                            dynamicScore += 10;
                        }
                    });
                }

                scoredOptions.push({ option, dynamicScore });
            }
        });

        scoredOptions
            .sort((left, right) => right.dynamicScore - left.dynamicScore)
            .forEach(({ option }) => { ownerPersonResults.appendChild(option); });

        const selectedVisibleOption = scoredOptions.find(({ option }) => {
            const radio = option.querySelector('input[type="radio"]');
            return radio?.checked;
        });

        if (!selectedVisibleOption && scoredOptions[0]) {
            const radio = scoredOptions[0].option.querySelector('input[type="radio"]');
            if (radio) { radio.checked = true; }
        }

        if (ownerPersonEmpty) {
            ownerPersonEmpty.classList.toggle('hidden', visibleCount !== 0);
        }

        if (ownerPersonCount) {
            ownerPersonCount.textContent = `Showing ${visibleCount} of ${options.length} people`;
        }
    };

    // ─── Toolbar update ───────────────────────────────────────────────────────
    const updateToolbar = (toolbar, navigatedUrl) => {
        const base = new URL(navigatedUrl);

        // Focus person name
        const focusNameEl = workspace.querySelector('[data-toolbar-focus-name]');
        if (focusNameEl) {
            focusNameEl.textContent = toolbar.focus_name ?? 'No focus person';
        }

        // Position counter
        const posEl = workspace.querySelector('[data-toolbar-position]');
        if (posEl) {
            const total = workspace.dataset.peopleCount ?? '?';
            posEl.textContent = `${toolbar.focus_position} of ${total} people`;
        }

        // Mode tabs: update href + active class
        workspace.querySelectorAll('[data-mode-tab]').forEach((tab) => {
            const mode   = tab.dataset.modeTab;
            const tabUrl = new URL(base);
            tabUrl.searchParams.set('mode', mode);
            tab.href = tabUrl.toString();
            tab.classList.toggle('is-active', mode === toolbar.chart_mode);
        });

        // Generation links: update href + active style
        workspace.querySelectorAll('[data-gen-link]').forEach((link) => {
            const gen      = Number.parseInt(link.dataset.genLink, 10);
            const isActive = gen === toolbar.chart_generations;
            const genUrl   = new URL(base);
            genUrl.searchParams.set('generations', gen);
            link.href = genUrl.toString();
            link.classList.toggle('bg-[#ff6c2f]', isActive);
            link.classList.toggle('text-white',    isActive);
            link.classList.toggle('hover:bg-[#f3f3f3]', !isActive);
        });
    };

    // ─── Sidebar-only person load ─────────────────────────────────────────────
    const loadPersonSidebar = async (focusUrl) => {
        try {
            const url = new URL(focusUrl, window.location.href);
            url.searchParams.set('partial', '1');
            url.searchParams.set('sidebar_only', '1');

            const response = await fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (! response.ok) { return; }

            const data = await response.json();

            const sidebarEl = workspace.querySelector('aside');
            if (sidebarEl && data.sidebar_html) {
                sidebarEl.innerHTML = data.sidebar_html;
            }

            swapEditModal(data.edit_modal_html);
        } catch { /* silent fail */ }
    };

    // ─── AJAX navigation ──────────────────────────────────────────────────────
    const isTreeNavLink = (href) => {
        try {
            const url = new URL(href, window.location.href);
            return url.origin === window.location.origin && url.pathname === treePath;
        } catch {
            return false;
        }
    };

    const swapEditModal = (html) => {
        const existing = workspace.querySelector('[data-edit-profile-modal]');
        if (!existing || !html) { return; }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        const next = wrapper.firstElementChild;

        if (next) {
            existing.replaceWith(next);
        }
    };

    const navigateTo = async (url) => {
        if (navigating) { return; }

        navigating = true;
        workspace.querySelector('[data-canvas-scroll]')?.classList.add('is-loading');

        try {
            const partialUrl = new URL(url, window.location.href);
            partialUrl.searchParams.set('partial', '1');

            const response = await fetch(partialUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                window.location.href = url;
                return;
            }

            const data = await response.json();

            // Swap canvas (the stage inside the scroll viewport)
            const oldStage = workspace.querySelector('[data-canvas-stage]');
            if (oldStage && data.canvas_html) {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = data.canvas_html;
                const next = wrapper.firstElementChild;
                if (next) { oldStage.replaceWith(next); }
            }

            // Swap sidebar inner content
            const sidebarEl = workspace.querySelector('aside');
            if (sidebarEl && data.sidebar_html) {
                sidebarEl.innerHTML = data.sidebar_html;
            }

            // Swap edit profile modal
            swapEditModal(data.edit_modal_html);

            // Update toolbar text + tab/gen links
            updateToolbar(data.toolbar, url);

            // Push browser history
            history.pushState({ url }, '', url);

            // Re-init canvas refs and fit/center
            initCanvasRefs();
            scale = 1;
            window.requestAnimationFrame(() => {
                fitCanvas();
                window.setTimeout(() => centerCard(focusCard, 'smooth'), 120);
            });

            // Re-wire the sex select in the new edit modal
            syncMaidenNameField();

        } catch {
            window.location.href = url;
        } finally {
            navigating = false;
            workspace.querySelector('[data-canvas-scroll]')?.classList.remove('is-loading');
        }
    };

    // ─── Event delegation ─────────────────────────────────────────────────────
    workspace.addEventListener('click', (event) => {
        const target = event.target;

        // Role chooser close
        if (target.closest('[data-role-chooser-close]')) {
            closeRoleChooser();
            return;
        }

        // Role chooser backdrop
        if (target === roleChooser) {
            closeRoleChooser();
            return;
        }

        // Open role chooser
        const rcOpen = target.closest('[data-role-chooser-open]');
        if (rcOpen) {
            openRoleChooser(rcOpen);
            return;
        }

        // Role choice (father / mother / son / etc.)
        const rcChoice = target.closest('[data-role-choice]');
        if (rcChoice) {
            openPersonModal(rcChoice.dataset.roleChoice ?? 'partner');
            return;
        }

        // Close add-relative modal
        if (target.closest('[data-person-modal-close]') || (target === personModal)) {
            closePersonModal();
            return;
        }

        // Open edit profile
        if (target.closest('[data-edit-profile-open]')) {
            openEditProfileModal();
            return;
        }

        // Close edit profile
        if (target.closest('[data-edit-profile-close]') || target === getEditModal()) {
            closeEditProfileModal();
            return;
        }

        // Sidebar panel buttons
        const panelBtn = target.closest('[data-panel-target]');
        if (panelBtn) {
            const panelId = panelBtn.dataset.panelTarget;

            if (panelId) {
                showPanel(panelId);
            }

            const linkPersonId = panelBtn.dataset.linkPersonId;
            const linkPersonSelect = workspace.querySelector('[data-link-person]');

            if (linkPersonId && linkPersonSelect) {
                linkPersonSelect.value = linkPersonId;
            }

            const linkRelatedPersonSelect = workspace.querySelector('[data-link-related-person]');
            const currentFocusCard = workspace.querySelector('[data-focus-card]');

            if (linkRelatedPersonSelect && currentFocusCard?.dataset.personId && panelId === 'link-relationship') {
                linkRelatedPersonSelect.value = currentFocusCard.dataset.personId;
            }

            return;
        }

        // More menu toggle
        if (target.closest('[data-more-menu-open]')) {
            const menu = workspace.querySelector('[data-more-menu]');
            menu?.classList.toggle('is-hidden');
            return;
        }

        // More menu actions (open a panel then close menu)
        const moreAction = target.closest('[data-more-menu-action]');
        if (moreAction) {
            const panelId = moreAction.dataset.moreMenuAction;
            workspace.querySelector('[data-more-menu]')?.classList.add('is-hidden');
            if (panelId) { showPanel(panelId); }
            return;
        }

        // Close more menu when clicking outside
        if (!target.closest('[data-more-menu]') && !target.closest('[data-more-menu-open]')) {
            workspace.querySelector('[data-more-menu]')?.classList.add('is-hidden');
        }

        // Canvas action buttons (zoom / fit / fullscreen)
        const canvasBtn = target.closest('[data-canvas-action]');
        if (canvasBtn) {
            switch (canvasBtn.dataset.canvasAction) {
                case 'zoom-in':   applyScale(scale + 0.15); break;
                case 'zoom-out':  applyScale(scale - 0.15); break;
                case 'home':
                case 'center':    centerCard(focusCard); break;
                case 'fit':       fitCanvas(); break;
                case 'fullscreen':
                    if (!document.fullscreenElement) {
                        workspace.requestFullscreen?.();
                    } else {
                        document.exitFullscreen?.();
                    }
                    break;
                default: break;
            }
            return;
        }

        // Card body click → load that person's info into the sidebar
        const cardOpen = target.closest('[data-person-card-open]');
        if (cardOpen) {
            const card = cardOpen.closest('[data-person-card]');
            if (card?.dataset.focusUrl) {
                loadPersonSidebar(card.dataset.focusUrl);
            }
            return;
        }

        // AJAX navigation: intercept tree nav links
        const link = target.closest('a[href]');
        if (link && isTreeNavLink(link.href)) {
            event.preventDefault();
            navigateTo(link.href);
        }
    });

    // Sex radio change and living/deceased toggle inside the edit modal (delegated)
    workspace.addEventListener('change', (event) => {
        const editModal = workspace.querySelector('[data-edit-profile-modal]');
        if (!editModal) { return; }

        if (event.target.matches('[data-edit-sex]')) {
            syncMaidenNameField();
        }

        if (event.target.matches('[data-edit-is-living]')) {
            const isLiving = event.target.value === '1';
            editModal.querySelectorAll('[data-edit-death-section]').forEach((el) => {
                el.classList.toggle('hidden', isLiving);
            });
        }

        // Date picker mode change: show/hide dual-date row and free-text input
        if (event.target.matches('select[data-date-picker-mode], select[class]') && event.target.closest('[data-date-picker]')) {
            syncDatePickerUI(event.target);
        }
    });

    // Catch all mode selects (they don't share a single data attribute; match by closest picker)
    workspace.addEventListener('change', (event) => {
        const picker = event.target.closest('[data-date-picker]');
        if (picker && event.target.tagName === 'SELECT') {
            const ns = picker.dataset.datePicker;
            if (event.target === picker.querySelector(`[data-${ns}-date-mode]`)) {
                syncDatePickerUI(event.target);
            }
        }
    });

    // Build GEDCOM date fields before form submit (add-relative modal)
    personModalForm?.addEventListener('submit', () => {
        const birth = buildGedcomDate('birth');
        const death = buildGedcomDate('death');
        birthDateHidden.value     = birth.date;
        birthDateTextHidden.value = birth.text;
        deathDateHidden.value     = death.date;
        deathDateTextHidden.value = death.text;
    });

    // Build GEDCOM date fields before form submit (edit profile modal)
    workspace.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form.closest('[data-edit-profile-modal]')) { return; }

        const birthHidden     = form.querySelector('[data-edit-birth-date-hidden]');
        const birthTextHidden = form.querySelector('[data-edit-birth-date-text-hidden]');
        const deathHidden     = form.querySelector('[data-edit-death-date-hidden]');
        const deathTextHidden = form.querySelector('[data-edit-death-date-text-hidden]');

        if (birthHidden) {
            const b = buildGedcomDate('edit-birth');
            birthHidden.value     = b.date;
            birthTextHidden.value = b.text;
        }
        if (deathHidden) {
            const d = buildGedcomDate('edit-death');
            deathHidden.value     = d.date;
            deathTextHidden.value = d.text;
        }

        // Build marriage dates for relationship subforms
        form.querySelectorAll('[data-rel-id]').forEach((hiddenId) => {
            const relId = hiddenId.value;
            const ns    = `edit-rel-${relId}`;
            const sd    = form.querySelector(`[data-edit-rel-start-date-hidden][data-rel="${relId}"]`);
            const sdt   = form.querySelector(`[data-edit-rel-start-date-text-hidden][data-rel="${relId}"]`);
            if (sd) {
                const m = buildGedcomDate(ns);
                sd.value  = m.date;
                sdt.value = m.text;
            }
        });
    });

    // Search input
    searchSubmit?.addEventListener('click', performSearch);
    searchInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            performSearch();
        }
    });

    // Owner candidate filter
    ownerPersonSearch?.addEventListener('input', filterOwnerCandidates);
    filterOwnerCandidates();

    // Canvas drag-to-scroll
    if (scrollViewport) {
        let pointerId = null;
        let lastX = 0;
        let lastY = 0;

        scrollViewport.addEventListener('pointerdown', (event) => {
            const el = event.target;
            if (!(el instanceof HTMLElement)) { return; }
            if (el.closest('a, button, input, textarea, select, option, label, [data-person-card-open]')) { return; }

            pointerId = event.pointerId;
            lastX = event.clientX;
            lastY = event.clientY;
            scrollViewport.classList.add('is-dragging');
            scrollViewport.setPointerCapture(pointerId);
        });

        scrollViewport.addEventListener('pointermove', (event) => {
            if (pointerId !== event.pointerId) { return; }

            scrollViewport.scrollLeft -= event.clientX - lastX;
            scrollViewport.scrollTop  -= event.clientY - lastY;
            lastX = event.clientX;
            lastY = event.clientY;
        });

        const clearDrag = (event) => {
            if (pointerId !== event.pointerId) { return; }
            scrollViewport.classList.remove('is-dragging');
            scrollViewport.releasePointerCapture(pointerId);
            pointerId = null;
        };

        scrollViewport.addEventListener('pointerup',     clearDrag);
        scrollViewport.addEventListener('pointercancel', clearDrag);

        scrollViewport.addEventListener('wheel', (event) => {
            if (!(event.ctrlKey || event.metaKey)) { return; }
            event.preventDefault();
            applyScale(scale - (event.deltaY * 0.0015));
        }, { passive: false });
    }

    // Browser back / forward
    window.addEventListener('popstate', () => {
        navigateTo(window.location.href);
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeRoleChooser();
            closePersonModal();
            closeEditProfileModal();
        }
    });

    // Hash-based panel open on load
    if (window.location.hash) {
        const hashTarget = window.location.hash.replace('#', '');
        const targetPanel = workspace.querySelector(`#${CSS.escape(hashTarget)}`);
        if (targetPanel?.hasAttribute('data-panel') && targetPanel.id) {
            showPanel(targetPanel.id);
        }
    }

    // Record the initial page state so Back works from the very first load
    history.replaceState({ url: window.location.href }, '', window.location.href);

    // Initial fit + center
    window.requestAnimationFrame(() => {
        fitCanvas();
        window.setTimeout(() => centerCard(focusCard, 'smooth'), 120);
    });
}
