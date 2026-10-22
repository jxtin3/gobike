{{-- Week / Month switch with previous and next arrows. Needs $period, $route, optional $params (route) and $extra (query). --}}
@php
    $params ??= [];
    $extra ??= [];
    $to = fn ($p) => route($route, [...$params, ...$p->query($extra)]);
    $next = $period->next();
@endphp
<div class="period-bar">
    <div class="segmented" role="group" aria-label="Report period">
        @foreach (\App\Support\ReportPeriod::TYPES as $type => $label)
            <a href="{{ $to($period->switchTo($type)) }}" aria-current="{{ $period->type === $type ? 'page' : 'false' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="period-nav">
        <a class="icon-btn" href="{{ $to($period->previous()) }}" aria-label="Previous {{ $period->type }}" title="Previous {{ $period->type }}"><x-admin.icon name="chevron-left" /></a>
        <b class="period-label">{{ $period->label() }}</b>
        @if ($next)
            <a class="icon-btn" href="{{ $to($next) }}" aria-label="Next {{ $period->type }}" title="Next {{ $period->type }}"><x-admin.icon name="chevron-right" /></a>
        @else
            <span class="icon-btn is-disabled" aria-hidden="true"><x-admin.icon name="chevron-right" /></span>
        @endif
    </div>
    @unless ($period->isCurrent())
        <a class="btn btn-secondary btn-sm" href="{{ $to(\App\Support\ReportPeriod::for($period->type, now()->toImmutable())) }}">This {{ $period->type }}</a>
    @endunless
</div>
