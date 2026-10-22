{{-- Sidebar counter. Unread items use the brand colour, items waiting for a decision use amber. --}}
@props(['count' => 0, 'tone' => 'brand', 'label', 'name'])
@if ($count > 0)
    <span class="badge badge-{{ $tone }}" data-nav-badge="{{ $name }}"><span class="count-num">{{ number_format($count) }}</span><span class="sr-only"> {{ $label }}</span></span>
@endif
