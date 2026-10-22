{{-- Ronda history only exists from the day ronda logging shipped. Needs $trackingSince and $period. --}}
@if ($trackingSince === null || $trackingSince->startOfDay()->gt($period->from))
    <p class="report-note">
        <x-admin.icon name="clock" />
        @if ($trackingSince === null)
            <span>Rondas are counted from the next ronda a GoBiker starts in the app. Check-ups are counted for every date.</span>
        @else
            <span>Rondas are counted from {{ $trackingSince->format('F j, Y') }}. Earlier rondas were not recorded; check-ups are counted for every date.</span>
        @endif
    </p>
@endif
