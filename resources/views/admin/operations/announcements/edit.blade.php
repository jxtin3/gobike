@extends('admin.operations.layout')
@section('title', 'Edit announcement')
@section('heading', 'Edit announcement')
@section('subheading', $announcement->status_label.' · posted '.$announcement->created_at->format('M j, Y'))
@section('back', route('admin.operations.announcements.index'))
@section('back_label', 'Back to announcements')

@section('content')
    @include('admin.operations.announcements.form', ['announcement' => $announcement, 'action' => route('admin.operations.announcements.update', $announcement), 'method' => 'PUT', 'button' => 'Save changes'])
@endsection
