@extends('admin.operations.layout')
@section('title', 'My profile')
@section('heading', 'My profile')
@section('subheading', 'Your admin account details.')
@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.profile.edit') }}" data-skeleton="form"><x-admin.icon name="edit" /> Edit profile</a>
@endsection

@section('content')
<article class="profile-page">
    <div class="profile-hero">
        <x-admin.avatar :name="$user->name" class="profile-hero-avatar" />
        <div>
            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }}</p>
            <span class="pill pill-brand">Admin</span>
        </div>
    </div>

    <dl class="detail-dl">
        <div><dt>Joined</dt><dd>{{ $user->created_at->format('F j, Y') }}</dd></div>
        <div><dt>Last updated</dt><dd>{{ $user->updated_at->format('F j, Y') }}</dd></div>
    </dl>
</article>
@endsection
