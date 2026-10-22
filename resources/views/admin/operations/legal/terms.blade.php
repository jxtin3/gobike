@extends('admin.operations.layout')
@section('title', 'Terms & Conditions')
@section('heading', 'Terms & Conditions')
@section('subheading', 'Edit Terms & Conditions directly in the live website view.')

@section('content')
    @include('admin.operations.legal._live_editor', [
        'document' => $document,
        'activeDoc' => 'terms',
        'updateRoute' => route('admin.operations.legal.terms.update'),
        'liveUrl' => route('terms'),
        'accentColor' => '#F97316'
    ])
@endsection
