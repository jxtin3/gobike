@props([
    'href'    => '#',
    'active'  => false,
    'mobile'  => false,
])

@php
    $isActive = $active || (request()->url() === url($href));
@endphp

@if ($mobile)
    <!-- Mobile menu link -->
    <a
        href="{{ $href }}"
        {{ $attributes->merge([
            'class' => 'block px-4 py-3 text-base font-medium rounded-lg transition-colors duration-200 '
                     . ($isActive
                         ? 'bg-[#2FA7FF]/10 text-[#2FA7FF]'
                         : 'text-white/90 hover:bg-white/10 hover:text-white')
        ]) }}
    >
        {{ $slot }}
    </a>
@else
    <!-- Desktop nav link -->
    <a
        href="{{ $href }}"
        {{ $attributes->merge([
            'class' => 'nav-underline text-sm md:text-[15px] font-medium tracking-wide transition-colors duration-200 '
                     . ($isActive
                         ? 'text-white'
                         : 'text-white/90 hover:text-white')
        ]) }}
    >
        {{ $slot }}
    </a>
@endif
