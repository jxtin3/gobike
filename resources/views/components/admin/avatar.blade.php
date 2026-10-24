{{-- Round initials badge. Replaces the copy of the initials snippet that lived in eight views. --}}
@props(['name' => ''])
@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
<span {{ $attributes->merge(['class' => 'avatar', 'aria-hidden' => 'true']) }}>{{ $initials !== '' ? $initials : '?' }}</span>
