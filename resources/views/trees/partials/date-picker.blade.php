{{--
    Date picker partial.
    Variables:
      $ns         - namespace prefix for data attributes, e.g. "edit-birth" → data-edit-birth-date-mode
      $components - ['mode', 'month', 'day', 'year', 'month2', 'day2', 'year2', 'free_text']
      $qualifiers - associative array [value => label]
--}}
@php
    $months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
    $isFree    = ($components['mode'] ?? 'exact') === 'free';
    $isDual    = in_array($components['mode'] ?? 'exact', ['between', 'from-to'], true);
@endphp

<div class="space-y-1" data-date-picker="{{ $ns }}">
    {{-- Mode + first date row --}}
    <div class="flex flex-wrap gap-1">
        <select class="workspace-input workspace-modal-input min-w-0 flex-shrink-0" data-{{ $ns }}-date-mode>
            @foreach ($qualifiers as $val => $label)
                <option value="{{ $val }}" @selected(($components['mode'] ?? 'exact') === $val)>{{ $label }}</option>
            @endforeach
        </select>

        <div class="flex flex-1 gap-1" data-date-picker-fields data-{{ $ns }}-fields @class(['hidden' => $isFree])>
            <select class="workspace-input workspace-modal-input flex-1" data-{{ $ns }}-date-month>
                <option value="">{{ __('Month') }}</option>
                @foreach ($months as $m)
                    <option value="{{ $m }}" @selected(($components['month'] ?? '') === $m)>{{ $m }}</option>
                @endforeach
            </select>
            <input class="workspace-input workspace-modal-input w-12 min-w-0 flex-shrink-0" placeholder="{{ __('Day') }}" data-{{ $ns }}-date-day value="{{ $components['day'] ?? '' }}" />
            <input class="workspace-input workspace-modal-input w-16 min-w-0 flex-shrink-0" placeholder="{{ __('Year') }}" data-{{ $ns }}-date-year value="{{ $components['year'] ?? '' }}" />
        </div>

        <input class="workspace-input workspace-modal-input flex-1" placeholder="{{ __('Enter date...') }}" data-{{ $ns }}-date-free value="{{ $components['free_text'] ?? '' }}" @class(['hidden' => !$isFree]) />
    </div>

    {{-- Second date row for between / from-to --}}
    <div class="flex flex-1 gap-1 pl-1" data-date-picker-second @class(['hidden' => !$isDual])>
        <span class="flex items-center pr-1 text-[12px] text-[#7b8791]" data-date-picker-second-label>
            {{ in_array($components['mode'] ?? '', ['between']) ? __('and') : __('to') }}
        </span>
        <select class="workspace-input workspace-modal-input flex-1" data-{{ $ns }}-date-month2>
            <option value="">{{ __('Month') }}</option>
            @foreach ($months as $m)
                <option value="{{ $m }}" @selected(($components['month2'] ?? '') === $m)>{{ $m }}</option>
            @endforeach
        </select>
        <input class="workspace-input workspace-modal-input w-12 min-w-0 flex-shrink-0" placeholder="{{ __('Day') }}" data-{{ $ns }}-date-day2 value="{{ $components['day2'] ?? '' }}" />
        <input class="workspace-input workspace-modal-input w-16 min-w-0 flex-shrink-0" placeholder="{{ __('Year') }}" data-{{ $ns }}-date-year2 value="{{ $components['year2'] ?? '' }}" />
    </div>
</div>
