@extends('admin.operations.layout')
@section('title', 'Announcements')
@section('heading', 'Announcements')
@section('subheading', 'Messages for all GoBikers. They appear on the dashboard of the GoBiker app.')
@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.operations.announcements.create') }}"><x-admin.icon name="plus" /> Add announcement</a>
@endsection

@section('content')
@php($searching = filled(request('search')))
<div class="toolbar">
    <x-admin.filters :action="route('admin.operations.announcements.index')" placeholder="Search announcements" label="Search announcements" :filtered="$searching" />
    <p class="toolbar-note"><b>{{ number_format($liveCount) }}</b> live now</p>
</div>

<div data-list>
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Announcement</th>
                <th>Status</th>
                <th>Posted</th>
                <th>Ends</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($announcements as $announcement)
                <tr data-href="{{ route('admin.operations.announcements.edit', $announcement) }}">
                    <td>
                        <b><a class="cell-link" href="{{ route('admin.operations.announcements.edit', $announcement) }}" title="{{ $announcement->title }}">{{ Str::limit($announcement->title, 70) }}</a></b>
                        <small class="sub-line">{{ Str::limit($announcement->body, 110) }}</small>
                    </td>
                    <td><span class="pill pill-{{ $announcement->status_pill }}">{{ $announcement->status_label }}</span></td>
                    <td>
                        {{ $announcement->created_at->format('M j, Y') }}
                        <small class="sub-line">{{ $announcement->author?->name ?? 'Admin' }}</small>
                    </td>
                    <td>{{ $announcement->expires_at?->format('M j, Y g:i A') ?? 'No end date' }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ route('admin.operations.announcements.edit', $announcement) }}" title="Edit" aria-label="Edit {{ $announcement->title }}"><x-admin.icon name="edit" /></a>
                            <form method="POST" action="{{ route('admin.operations.announcements.destroy', $announcement) }}"
                                  data-confirm-title="Delete this announcement?"
                                  data-confirm-text="“{{ Str::limit($announcement->title, 80) }}” will disappear from the GoBiker app."
                                  data-confirm-ok="Delete announcement">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete {{ $announcement->title }}"><x-admin.icon name="trash" /></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                @if ($searching)
                    <x-admin.empty :row="5" icon="megaphone" title="No announcements match" :clear="route('admin.operations.announcements.index')">
                        Try a different word.
                    </x-admin.empty>
                @else
                    <x-admin.empty :row="5" icon="megaphone" title="No announcements yet">
                        Write one and every GoBiker will see it in the app, for example a schedule change.
                        <x-slot:action><a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.announcements.create') }}"><x-admin.icon name="plus" /> Add announcement</a></x-slot:action>
                    </x-admin.empty>
                @endif
            @endforelse
        </tbody>
    </table>
</div>

    <x-admin.pager :paginator="$announcements" noun="announcement" />
</div>
@endsection
