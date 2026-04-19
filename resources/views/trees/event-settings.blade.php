<x-layouts::app :title="__('Calendar settings')" active-nav="home">
    <div class="genealogy-shell space-y-8">
        <section class="event-settings-panel">
            <div class="event-settings-header">
                <div>
                    <p class="event-settings-eyebrow">{{ __('Family events') }}</p>
                    <h1>{{ __('Calendar settings') }}</h1>
                    <p class="event-settings-copy">
                        {{ __('Choose which milestones appear by default on the family events page for :tree.', ['tree' => $tree->name]) }}
                    </p>
                </div>

                <a href="{{ route('family-events.index', ['tree' => $tree->id]) }}" class="tree-manage-toolbar-button tree-manage-toolbar-button-secondary">
                    {{ __('Back to family events') }}
                </a>
            </div>

            @if (session('status'))
                <div class="workspace-notice mt-6">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('trees.events.settings.update', $tree) }}" class="event-settings-form">
                @csrf
                @method('PATCH')

                <label class="event-settings-option">
                    <input type="checkbox" name="show_birthdays_in_events" value="1" @checked($tree->show_birthdays_in_events) />
                    <span>
                        <strong>{{ __('Birthdays') }}</strong>
                        <small>{{ __('Enabled by default. Living relatives with known birthdays appear in the family events feed.') }}</small>
                    </span>
                </label>

                <label class="event-settings-option">
                    <input type="checkbox" name="show_wedding_anniversaries_in_events" value="1" @checked($tree->show_wedding_anniversaries_in_events) />
                    <span>
                        <strong>{{ __('Wedding anniversaries') }}</strong>
                        <small>{{ __('Enabled by default. Active spouse anniversaries appear when both partners are living.') }}</small>
                    </span>
                </label>

                <label class="event-settings-option">
                    <input type="checkbox" name="show_death_anniversaries_in_events" value="1" @checked($tree->show_death_anniversaries_in_events) />
                    <span>
                        <strong>{{ __('Death anniversaries') }}</strong>
                        <small>{{ __('Disabled by default. Turn this on if you want memorial dates to appear in family events.') }}</small>
                    </span>
                </label>

                <div class="event-settings-actions">
                    <button type="submit" class="tree-manage-toolbar-button tree-manage-toolbar-button-primary">
                        {{ __('Save calendar settings') }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-layouts::app>
