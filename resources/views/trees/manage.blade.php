<x-layouts::app :title="__('Manage Trees')" active-nav="family-tree">
    <div class="genealogy-shell space-y-8">
        <section class="tree-manage-panel rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 border-b border-[#eef2f6] pb-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#6f7b83]">{{ __('Tree management') }}</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-[#0f4f7a] sm:text-4xl">
                        {{ __('Manage family trees') }}
                    </h1>
                    <p class="max-w-3xl text-sm leading-6 text-[#5b6873] sm:text-base">
                        {{ __('Review every tree you can access, reopen workspaces, and manage exports from one place.') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-sm font-medium">
                    <a href="{{ route('trees.import.index') }}" class="tree-manage-toolbar-button tree-manage-toolbar-button-secondary">
                        {{ __('Import GEDCOM') }}
                    </a>
                    <a href="#create-tree" class="tree-manage-toolbar-button tree-manage-toolbar-button-primary">
                        {{ __('Add family tree') }}
                    </a>
                </div>
            </div>

            @if ($trees->isNotEmpty())
                <div class="mt-6 overflow-hidden rounded-[1.35rem] border border-[#dde7f0] bg-[linear-gradient(180deg,#ffffff_0%,#fbfdff_100%)] shadow-[0_18px_48px_rgba(15,95,147,0.08)]">
                    <div class="overflow-x-auto">
                        <table class="tree-manage-table min-w-full">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('#') }}</th>
                                    <th scope="col">{{ __('Family tree') }}</th>
                                    <th scope="col">{{ __('Source') }}</th>
                                    <th scope="col">{{ __('Languages') }}</th>
                                    <th scope="col">{{ __('Individuals') }}</th>
                                    <th scope="col">{{ __('Last update') }}</th>
                                    <th scope="col">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trees as $tree)
                                    <tr>
                                        <td data-label="{{ __('#') }}">
                                            <span class="tree-manage-row-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        </td>
                                        <td data-label="{{ __('Family tree') }}">
                                            <div class="space-y-2">
                                                <a href="{{ route('trees.show', $tree) }}" class="tree-manage-tree-link">
                                                    {{ $tree->name }}
                                                </a>
                                                <div class="flex flex-wrap items-center gap-2 text-xs text-[#6f7b83]">
                                                    <span class="tree-manage-chip tree-manage-chip-soft">
                                                        {{ $tree->home_region ?: __('Region not set') }}
                                                    </span>
                                                    <span class="tree-manage-chip tree-manage-chip-muted uppercase tracking-[0.16em]">
                                                        {{ __($tree->privacy) }}
                                                    </span>
                                                    <span class="tree-manage-chip tree-manage-chip-muted">
                                                        {{ $tree->access_level_label }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="{{ __('Source') }}">
                                            <span class="tree-manage-pill">
                                                {{ $tree->gedcom_source_system ?: 'WEB' }}
                                            </span>
                                        </td>
                                        <td data-label="{{ __('Languages') }}">
                                            <span class="tree-manage-meta">
                                                {{ $tree->gedcom_language ?: __('Default genealogy language') }}
                                            </span>
                                        </td>
                                        <td data-label="{{ __('Individuals') }}">
                                            <span class="tree-manage-meta">
                                                {{ number_format($tree->people_count) }}
                                            </span>
                                        </td>
                                        <td data-label="{{ __('Last update') }}">
                                            <span class="tree-manage-meta">
                                                {{ optional($tree->updated_at)->timezone(config('app.timezone'))->format('M j Y H:i') }}
                                            </span>
                                        </td>
                                        <td data-label="{{ __('Actions') }}">
                                            <div class="tree-manage-actions">
                                                <a href="{{ route('trees.show', $tree) }}" class="tree-manage-action-button tree-manage-action-button-primary">{{ __('View') }}</a>

                                                @if ($tree->can_manage_tree)
                                                    <a href="{{ route('trees.managers.show', $tree) }}" class="tree-manage-action-button">{{ __('Tree managers') }}</a>
                                                    <a href="{{ route('trees.gedcom.export', $tree) }}" class="tree-manage-action-button">{{ __('Export GEDCOM') }}</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="mt-6 rounded-xl border border-dashed border-[#c7d4df] bg-[#f7f9fb] p-6 text-sm leading-6 text-[#6f7b83]">
                    {{ __('No family trees yet. Create your first one here to start mapping your family.') }}
                </div>
            @endif
        </section>

        <section id="create-tree" class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm sm:p-8">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,.85fr)] lg:items-start">
                <div class="space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#6f7b83]">{{ __('Add family tree') }}</p>
                    <h2 class="text-2xl font-semibold tracking-tight text-[#1f252b] sm:text-3xl">
                        {{ __('Create a new branch, lineage, or research workspace') }}
                    </h2>
                    <p class="max-w-2xl text-sm leading-7 text-[#5b6873] sm:text-base">
                        {{ __('Use one tree per surname line, household branch, or collaboration project. You can start with a manual tree and import GEDCOM records later whenever you are ready.') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('trees.store') }}" class="space-y-4 rounded-2xl border border-[#e6edf3] bg-[#f8fafc] p-5 sm:p-6">
                    @csrf
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-[#4f5963]" for="name">{{ __('Tree name') }}</label>
                        <input id="name" name="name" required class="workspace-input" placeholder="{{ __('The Johnson-Moore Tree') }}" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-[#4f5963]" for="home_region">{{ __('Home region') }}</label>
                        <input id="home_region" name="home_region" class="workspace-input" placeholder="{{ __('Manchester, England') }}" />
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-[#4f5963]" for="privacy">{{ __('Visibility') }}</label>
                        <select id="privacy" name="privacy" class="workspace-input">
                            <option value="private">{{ __('Private') }}</option>
                            <option value="invited">{{ __('Invited relatives only') }}</option>
                            <option value="public">{{ __('Public') }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-[#4f5963]" for="description">{{ __('Description') }}</label>
                        <textarea id="description" name="description" rows="4" class="workspace-input resize-none" placeholder="{{ __('What branch does this tree cover? What research goal does it serve?') }}"></textarea>
                    </div>
                    <button type="submit" class="workspace-primary-button w-full justify-center sm:w-auto">
                        {{ __('Create family tree') }}
                    </button>
                </form>
            </div>
        </section>
    </div>
</x-layouts::app>
