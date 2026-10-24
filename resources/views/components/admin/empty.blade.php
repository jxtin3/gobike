{{--
    Empty state. First run, "nothing matches these filters" and "nothing waiting" all use this one shape:
    icon, a short title, one line of help, and (optionally) an action.

    row="5"    wraps it in a full-width table row (the number is the table's column count)
    clear=url  adds the standard "Clear filters" button
    <x-slot:action> holds any other button, e.g. the page's create button
--}}
@props(['icon' => 'inbox', 'title', 'row' => null, 'clear' => null])
@if ($row)<tr class="is-static"><td colspan="{{ $row }}">@endif
<div {{ $attributes->class(['empty']) }}>
    <x-admin.icon :name="$icon" />
    <b>{{ $title }}</b>
    @if (trim((string) $slot) !== '')
        <p>{{ $slot }}</p>
    @endif
    @if ($clear || isset($action))
        <div class="empty-actions">
            @if ($clear)<a class="btn btn-secondary btn-sm" href="{{ $clear }}">Clear filters</a>@endif
            @isset($action){{ $action }}@endisset
        </div>
    @endif
</div>
@if ($row)</td></tr>@endif
