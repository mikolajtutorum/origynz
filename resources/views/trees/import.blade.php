<x-layouts::app :title="__('Import GEDCOM')" active-nav="family-tree">
    <div class="genealogy-shell space-y-6">
        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <div class="grid gap-8 lg:grid-cols-[1.15fr_.85fr] lg:items-start">
                <div class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('GEDCOM import') }}</p>
                    <div class="space-y-3">
                        <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-[#1f252b] sm:text-5xl">
                            {{ __('Import a GEDCOM into an existing tree or let the upload create a new one for you.') }}
                        </h1>
                        <p class="max-w-2xl text-base leading-7 text-[#4f5963]">
                            {{ __('Choose a destination tree if you already have one, or leave it blank and we will create a new private tree from the uploaded GEDCOM file.') }}
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-[#e3e8ee] bg-[#f7f9fb] p-6">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Import file') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('Pick an existing destination or leave it empty to create a new private tree automatically.') }}</p>

                    {{-- Import form (hidden while importing) --}}
                    <form id="gedcom-import-form" method="POST" action="{{ route('trees.import.store') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                        @csrf
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-[#4f5963]" for="tree_id">{{ __('Destination tree') }}</label>
                            <select id="tree_id" name="tree_id" class="workspace-input">
                                <option value="">{{ __('Create a new tree from this file') }}</option>
                                @foreach ($trees as $tree)
                                    <option value="{{ $tree->id }}">{{ $tree->name }}{{ $tree->home_region ? ' - '.$tree->home_region : '' }}</option>
                                @endforeach
                            </select>
                            <p id="error-tree_id" class="hidden text-sm text-[#b91c1c]"></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-[#4f5963]" for="tree_name">{{ __('New tree name') }}</label>
                            <input
                                id="tree_name"
                                type="text"
                                name="tree_name"
                                value="{{ old('tree_name') }}"
                                class="workspace-input"
                                placeholder="{{ __('Optional: otherwise we use the file name') }}"
                            />
                            <p class="text-xs leading-5 text-[#6f7b83]">{{ __('Only used when no destination tree is selected.') }}</p>
                            <p id="error-tree_name" class="hidden text-sm text-[#b91c1c]"></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-[#4f5963]" for="gedcom_file">{{ __('GEDCOM file') }}</label>
                            <input id="gedcom_file" type="file" name="gedcom_file" accept=".ged,.gedcom,text/plain" class="workspace-input" required />
                            <p id="error-gedcom_file" class="hidden text-sm text-[#b91c1c]"></p>
                        </div>
                        <p id="error-general" class="hidden text-sm text-[#b91c1c]"></p>
                        <button type="submit" id="gedcom-submit-btn" class="workspace-primary-button">
                            {{ __('Import GEDCOM') }}
                        </button>
                    </form>

                    {{-- Progress panel (shown while importing) --}}
                    <div id="gedcom-progress-panel" class="mt-5 hidden space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span id="gedcom-progress-message" class="font-medium text-[#1f252b]">Starting import...</span>
                                <span id="gedcom-progress-pct" class="tabular-nums text-[#6f7b83]">0%</span>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-[#e3e8ee]">
                                <div
                                    id="gedcom-progress-bar"
                                    class="h-full rounded-full bg-[#2563eb] transition-all duration-300"
                                    style="width: 0%"
                                ></div>
                            </div>
                        </div>
                        <p class="text-xs text-[#6f7b83]">{{ __('Large files may take a few minutes. Please keep this page open.') }}</p>
                        <div id="gedcom-progress-error" class="hidden rounded-xl border border-[#fca5a5] bg-[#fef2f2] p-4 text-sm text-[#b91c1c]"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Available destination trees') }}</h2>
                    <p class="text-sm text-[#6f7b83]">{{ __('These are the trees you can import into right now.') }}</p>
                </div>
                <a href="{{ route('trees.manage') }}" class="rounded-[6px] border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-2 text-sm font-medium text-[#334155] transition hover:border-[#93c5fd] hover:bg-[#eff6ff] hover:text-[#2563eb]">
                    {{ __('Manage trees') }}
                </a>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                @forelse ($trees as $tree)
                    <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] p-5">
                        <h3 class="text-base font-semibold text-[#1f252b]">{{ $tree->name }}</h3>
                        <p class="mt-1 text-sm text-[#6f7b83]">{{ $tree->home_region ?: __('Region not set yet') }}</p>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-[#c7d4df] bg-[#f7f9fb] p-6 text-sm leading-6 text-[#6f7b83] lg:col-span-2">
                        {{ __('No trees yet. Upload a GEDCOM file above and we will create the first tree for you automatically.') }}
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <script>
    (function () {
        const form = document.getElementById('gedcom-import-form');
        const submitBtn = document.getElementById('gedcom-submit-btn');
        const progressPanel = document.getElementById('gedcom-progress-panel');
        const progressBar = document.getElementById('gedcom-progress-bar');
        const progressPct = document.getElementById('gedcom-progress-pct');
        const progressMessage = document.getElementById('gedcom-progress-message');
        const progressError = document.getElementById('gedcom-progress-error');

        const errorFields = ['tree_id', 'tree_name', 'gedcom_file', 'general'];

        function clearErrors() {
            errorFields.forEach(function (field) {
                const el = document.getElementById('error-' + field);
                if (el) { el.textContent = ''; el.classList.add('hidden'); }
            });
        }

        function showFieldError(field, message) {
            const el = document.getElementById('error-' + field);
            if (el) { el.textContent = message; el.classList.remove('hidden'); }
        }

        function setProgress(pct, message) {
            progressBar.style.width = pct + '%';
            progressPct.textContent = pct + '%';
            progressMessage.textContent = message;
        }

        function showError(message) {
            progressError.textContent = message;
            progressError.classList.remove('hidden');
            // Re-show form so user can retry
            form.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = '{{ __('Try again') }}';
            progressPanel.classList.add('hidden');
        }

        let pollTimer = null;

        function pollProgress(importId) {
            fetch('/gedcom/import/' + importId + '/progress', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                setProgress(data.progress || 0, data.message || '');

                if (data.status === 'done') {
                    setProgress(100, data.message);
                    window.location.href = '/gedcom/import/' + importId + '/complete';
                    return;
                }

                if (data.status === 'failed') {
                    showError(data.message || 'Import failed. Please try again.');
                    return;
                }

                pollTimer = setTimeout(function () { pollProgress(importId); }, 800);
            })
            .catch(function () {
                pollTimer = setTimeout(function () { pollProgress(importId); }, 2000);
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            clearErrors();

            submitBtn.disabled = true;
            submitBtn.textContent = '{{ __('Uploading...') }}';

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            })
            .then(function (res) {
                if (res.status === 422) {
                    return res.json().then(function (data) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = '{{ __('Import GEDCOM') }}';

                        const errors = data.errors || {};
                        Object.keys(errors).forEach(function (field) {
                            const messages = errors[field];
                            showFieldError(field, Array.isArray(messages) ? messages[0] : messages);
                        });

                        if (!Object.keys(errors).length && data.message) {
                            showFieldError('general', data.message);
                        }
                    });
                }

                if (!res.ok) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = '{{ __('Import GEDCOM') }}';
                    showFieldError('general', '{{ __('An unexpected error occurred. Please try again.') }}');
                    return;
                }

                return res.json().then(function (data) {
                    // Show progress panel, hide form
                    form.classList.add('hidden');
                    progressPanel.classList.remove('hidden');
                    setProgress(0, '{{ __('Queued for processing...') }}');

                    pollProgress(data.import_id);
                });
            })
            .catch(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = '{{ __('Import GEDCOM') }}';
                showFieldError('general', '{{ __('A network error occurred. Please check your connection and try again.') }}');
            });
        });
    })();
    </script>
</x-layouts::app>
