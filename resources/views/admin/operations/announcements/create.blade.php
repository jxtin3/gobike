@extends('admin.operations.layout')
@section('title', 'New announcement')
@section('heading', 'New announcement')
@section('subheading', 'Every GoBiker will see it on the dashboard of the app.')
@section('back', route('admin.operations.announcements.index'))
@section('back_label', 'Back to announcements')

@section('content')
    @include('admin.operations.announcements.form', ['announcement' => null, 'action' => route('admin.operations.announcements.store'), 'method' => 'POST', 'button' => 'Post announcement'])
@endsection
