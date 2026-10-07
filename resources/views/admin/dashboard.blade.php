@extends('admin.operations.layout')
@section('title', 'Dashboard')
@section('heading', 'Analytics dashboard')
@section('subheading', 'Vital signs activity and performance.')
@section('heading-actions')
    <form class="analytics-title-filter" method="GET" action="{{ route('admin.dashboard') }}">
        <label for="dashboard-month">Month</label>
        <input id="dashboard-month" class="input" type="month" name="month" value="{{ $selectedMonth }}" required>
        <button class="btn btn-secondary" type="submit">Apply</button>
    </form>
@endsection

@section('content')
    @include('admin.operations.reports._analytics')
@endsection
