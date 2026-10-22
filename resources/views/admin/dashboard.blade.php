@extends('admin.operations.layout')
@section('title', 'Dashboard')
@section('heading', 'Analytics dashboard')
@section('subheading', 'Patient check-ups and vital sign readings recorded by GoBikers.')
@section('actions')
    <form class="analytics-title-filter" method="GET" action="{{ route('admin.dashboard') }}">
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
