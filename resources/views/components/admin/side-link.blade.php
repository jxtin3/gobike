{{-- One sidebar link. `match` lists the route patterns that mean "you are here". The slot holds the badge. --}}
@props(['href', 'icon', 'label', 'match' => [], 'skeleton' => 'table'])
@php($here = $match !== [] && request()->routeIs(...$match))
<a class="side-link" href="{{ $href }}" aria-current="{{ $here ? 'page' : 'false' }}" data-tip="{{ $label }}" data-skeleton="{{ $skeleton }}">
    <x-admin.icon :name="$icon" /><span class="side-label">{{ $label }}</span>{{ $slot }}
</a>
