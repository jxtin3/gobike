{{-- Shown only when the page is printed or saved as PDF. --}}
<div class="print-head" aria-hidden="true">
    <div>
        <b>Go Bike · {{ $title }}</b>
        @isset($subtitle)<span>{{ $subtitle }}</span>@endisset
    </div>
    <small>Generated {{ now()->format('M j, Y g:i A') }} by {{ auth()->user()->name }}</small>
</div>
