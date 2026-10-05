@extends('admin.operations.layout')
@section('title', 'Edit user')
@section('heading', 'Edit user')
@section('back', route('admin.operations.users.index'))
@section('back_label', 'Users')
@section('content')
    @include('admin.operations.users.form', ['user' => $user, 'action' => route('admin.operations.users.update', $user), 'method' => 'PUT', 'button' => 'Save changes'])
@endsection