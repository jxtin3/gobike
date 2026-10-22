@extends('admin.operations.layout')
@section('title', 'Edit user')
@section('heading', 'Edit user')
@section('subheading', $user->name.' · '.$user->email)
@section('back', route('admin.operations.users.show', $user))
@section('back_label', 'Back to '.$user->name)
@section('content')
    @include('admin.operations.users.form', ['user' => $user, 'action' => route('admin.operations.users.update', $user), 'method' => 'PUT', 'button' => 'Save changes'])
@endsection