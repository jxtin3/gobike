@extends('admin.operations.layout')
@section('title', 'Privacy Policy')
@section('heading', 'Privacy Policy')
@section('subheading', 'Edit Privacy Policy directly in the live website view.')

@section('content')
    @include('admin.operations.legal._live_editor', [
        'document' => $document,
        'activeDoc' => 'privacy',
        'updateRoute' => route('admin.operations.legal.privacy.update'),
        'liveUrl' => route('privacy-policy'),
        'accentColor' => '#2FA7FF'
    ])
@endsection
