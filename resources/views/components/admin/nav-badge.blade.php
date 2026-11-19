{{--
    Sidebar counter. Unread items use the brand colour, items waiting for a decision use amber.
    Always rendered (hidden at zero) so resources/js/admin/live-updates.js can show it when the first one comes in.
--}}
@props(['count' => 0, 'tone' => 'brand', 'label', 'name'])
<span class="badge badge-{{ $tone }}" data-nav-badge="{{ $name }}"@if ($count <= 0) hidden @endif><span class="count-num">{{ number_format(max(0, $count)) }}</span><span class="sr-only"> {{ $label }}</span></span>
