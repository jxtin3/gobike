@extends('admin.operations.layout')
@section('title', 'Cookie Policy')
@section('heading', 'Cookie Policy')
@section('subheading', 'Edit the Cookie Policy directly in the live website view.')

@section('content')
    @include('admin.operations.legal._live_editor', [
        'document' => $document,
        'activeDoc' => 'cookies',
        'updateRoute' => route('admin.operations.legal.cookies.update'),
        'liveUrl' => route('cookie-policy'),
        'accentColor' => '#16A34A'
    ])
@endsection
