{{--
    The one search toolbar for list pages: search box, any extra filters (selects that auto-submit) in the slot,
    a Search button, and a Clear link once a search or filter is active.

    hidden   values to carry along, e.g. the active tab: ['status' => 'unread']
    filtered true when a search or filter (not a tab) is active
--}}
@props(['action', 'placeholder' => 'Search', 'label' => 'Search', 'hidden' => [], 'filtered' => false])
@php
    $carry = array_filter($hidden, fn ($value) => filled($value));
    $clearUrl = $carry ? $action.'?'.http_build_query($carry) : $action;
@endphp
<form class="filters" role="search" method="GET" action="{{ $action }}" data-skeleton="rows">
    @foreach ($carry as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <div class="search">
        <x-admin.icon name="search" />
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="{{ $placeholder }}" aria-label="{{ $label }}">
    </div>
    {{ $slot }}
    <button type="submit" class="btn btn-secondary">Search</button>
    @if ($filtered)
        <a class="btn btn-ghost" href="{{ $clearUrl }}" data-skeleton="rows">Clear</a>
    @endif
</form>
