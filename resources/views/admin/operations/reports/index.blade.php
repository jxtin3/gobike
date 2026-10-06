@extends('admin.operations.layout')
@section('title', 'Reports')
@section('heading', 'Reports & analytics')
@section('subheading', 'Explore monthly activity across the OneGoBike platform.')

@section('content')
    @include('admin.operations.reports._analytics')
@endsection
