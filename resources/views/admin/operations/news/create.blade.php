@extends('admin.operations.layout')
@section('title', 'Add news')
@section('heading', 'Add news')
@section('subheading', 'Write a post for the News page.')
@section('back', route('admin.operations.news.index'))
@section('back_label', 'Back to news')

@section('content')
    @include('admin.operations.news.form', ['news' => null, 'action' => route('admin.operations.news.store'), 'method' => 'POST', 'button' => 'Save post'])
@endsection
