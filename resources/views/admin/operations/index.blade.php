@extends('admin.operations.layout')
@section('title', 'Operations')
@section('heading', 'Operations')
@section('subheading', 'Manage website content, accounts and inquiries in one place.')

@section('content')
@if ($pendingCount > 0)
    <a class="callout" href="{{ route('admin.operations.users.index', ['filter' => 'pending']) }}">
        <x-admin.icon name="user-check" />
        <div>
            <b>{{ $pendingCount }} GoBiker {{ \Illuminate\Support\Str::plural('sign-up', $pendingCount) }} waiting for approval</b>
            <span>Review them so they can start their ronda in the mobile app.</span>
        </div>
        <span class="btn btn-secondary btn-sm">Review</span>
    </a>
@endif

<div class="link-grid">
<!-- News -->
    <a class="module" href="{{ route('admin.operations.news.index') }}">
        <div class="module-top"><span class="stat-icon"><x-admin.icon name="file-text" /></span><h2>News</h2><x-admin.icon class="i chev" name="chevron-right" /></div>
        <div class="module-num">{{ $newsCount }}<small>{{ $publishedNewsCount }} published</small></div>
        <p>Write and publish updates for the public website.</p>
    </a>

<!-- Pictures -->
    <a class="module" href="{{ route('admin.operations.pictures.index') }}">
        <div class="module-top"><span class="stat-icon"><x-admin.icon name="image" /></span><h2>Pictures</h2><x-admin.icon class="i chev" name="chevron-right" /></div>
        <div class="module-num">{{ $pictureCount }}<small>in the gallery</small></div>
        <p>Upload and organize photos shown in the public gallery.</p>
    </a>

    <a class="module" href="{{ route('admin.operations.homepage-data.index') }}">
        <div class="module-top"><span class="stat-icon"><x-admin.icon name="edit" /></span><h2>Homepage content</h2><x-admin.icon class="i chev" name="chevron-right" /></div>
        <p>Manage homepage partnerships and impact statistics.</p>
    </a>

<!-- Users -->
    <a class="module" href="{{ route('admin.operations.users.index') }}">
        <div class="module-top"><span class="stat-icon"><x-admin.icon name="users" /></span><h2>Users</h2><x-admin.icon class="i chev" name="chevron-right" /></div>
        <div class="module-num">{{ $userCount }}<small>{{ $pendingCount }} pending</small></div>
        <p>Manage accounts and approve new GoBikers.</p>
    </a>

<!-- Messages -->
    <a class="module" href="{{ route('admin.operations.messages.index') }}">
        <div class="module-top"><span class="stat-icon"><x-admin.icon name="mail" /></span><h2>Messages</h2><x-admin.icon class="i chev" name="chevron-right" /></div>
        <div class="module-num">{{ $messageCount }}<small>{{ $unreadCount }} unread</small></div>
        <p>Read and reply to messages from the contact form.</p>
    </a>

<!-- Patients -->
    <a class="module" href="{{ route('admin.operations.patients.index') }}">
        <div class="module-top"><span class="stat-icon"><x-admin.icon name="activity" /></span><h2>Patients</h2><x-admin.icon class="i chev" name="chevron-right" /></div>
        <div class="module-num">{{ $patientCount }}<small>check-up records</small></div>
        <p>Review check-up records collected by GoBikers on their rondas.</p>
    </a>
</div>
@endsection