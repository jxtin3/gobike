@extends('admin.operations.layout')
@section('title', 'Edit news')
@section('heading', 'Edit news')
@section('subheading', $news->title)
@section('back', route('admin.operations.news.show', $news))
@section('back_label', 'Back to post')

@section('content')
    @include('admin.operations.news.form', ['news' => $news, 'action' => route('admin.operations.news.update', $news), 'method' => 'PUT', 'button' => 'Save changes'])
@endsection
