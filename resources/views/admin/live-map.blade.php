@extends('admin.operations.layout')
@section('title', 'Live map')
@section('heading', 'Live map')
@section('subheading', 'See GoBikers on ronda as they move through their barangays.')

@section('content')
    @include('admin.operations.reports._live-map')
@endsection
