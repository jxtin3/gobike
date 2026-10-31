{{-- Someone's profile photo, or their initials when there is none. --}}
@props(['name' => '', 'src' => null])
@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
@if ($src)
    {{-- A real photo carries meaning, so it is named rather than hidden from screen readers. --}}
    <img {{ $attributes->merge(['class' => 'avatar avatar-photo']) }}
         src="{{ $src }}" alt="{{ $name !== '' ? $name : 'Profile photo' }}" loading="lazy">
@else
    <span {{ $attributes->merge(['class' => 'avatar', 'aria-hidden' => 'true']) }}>{{ $initials !== '' ? $initials : '?' }}</span>
@endif
