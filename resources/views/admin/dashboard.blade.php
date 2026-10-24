@extends('admin.operations.layout')
@section('title', 'Dashboard')
@section('heading', 'Analytics dashboard')
@section('subheading', 'Patient check-ups and vital sign readings recorded by GoBikers.')
@section('actions')
    <form class="analytics-title-filter" method="GET" action="{{ route('admin.dashboard') }}" data-skeleton="dashboard">
        <label for="dashboard-month">Month</label>
        <select id="dashboard-month" class="input" name="month" data-autosubmit>
            <option value="">All months</option>
            @foreach (range(1, 12) as $number)
                <option value="{{ $number }}" @selected($number === $month)>{{ date('F', mktime(0, 0, 0, $number, 1)) }}</option>
            @endforeach
        </select>
        <label for="dashboard-year">Year</label>
        <select id="dashboard-year" class="input" name="year" data-autosubmit>
            @foreach ($years as $option)
                <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
            @endforeach
        </select>
        <noscript><button class="btn btn-secondary" type="submit">Apply</button></noscript>
    </form>
@endsection

@section('content')
    @include('admin.operations.reports._analytics')
@endsection
