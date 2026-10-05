@extends('admin.operations.layout')
@section('title', 'Add user')
@section('heading', 'Add user')
@section('subheading', 'Accounts created here are active immediately and need no approval.')
@section('back', route('admin.operations.users.index'))
@section('back_label', 'Users')
@section('content')
    @include('admin.operations.users.form', ['user' => null, 'action' => route('admin.operations.users.store'), 'method' => 'POST', 'button' => 'Create user'])
@endsection