@extends('admin.operations.layout')
@section('title', 'My profile')
@section('heading', 'My profile')
@section('subheading', 'Your admin account details.')
@section('actions')
    <a class="btn btn-secondary" href="{{ route('admin.profile.edit') }}"><x-admin.icon name="edit" /> Edit profile</a>
@endsection

@section('content')
@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp

<article class="profile-page">
    <div class="profile-hero">
        <span class="avatar profile-hero-avatar" aria-hidden="true">{{ $initials }}</span>
        <div>
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }}</p>
            <span class="pill pill-brand">Admin</span>
        </div>
    </div>

    <dl class="user-details">
        <dt>Full name</dt><dd>{{ $user->name }}</dd>
        <dt>Email</dt><dd>{{ $user->email }}</dd>
        <dt>Role</dt><dd>Admin</dd>
        <dt>Joined</dt><dd>{{ $user->created_at->format('F j, Y') }}</dd>
        <dt>Last updated</dt><dd>{{ $user->updated_at->format('F j, Y') }}</dd>
    </dl>
</article>
@endsection
