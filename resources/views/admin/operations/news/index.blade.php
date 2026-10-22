@extends('admin.operations.layout')
@section('title', 'News')
@section('heading', 'News')
@section('subheading', 'Posts shown on the News page of the website.')
@section('actions')
    <a class="btn btn-primary" href="{{ route('admin.operations.news.create') }}"><x-admin.icon name="plus" /> Add news</a>
@endsection

@section('content')
<div class="toolbar">
    <form class="search" role="search" method="GET" action="{{ route('admin.operations.news.index') }}">
        <x-admin.icon name="search" />
        <input class="input" type="search" name="search" value="{{ request('search') }}" placeholder="Search by title" aria-label="Search news">
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Publication date</th>
                <th>Updated</th>
                <th><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($news as $item)
                <tr>
                    <td>
                        <b><a class="cell-link" href="{{ route('admin.operations.news.show', $item) }}" title="{{ $item->title }}">{{ Str::limit($item->title, 70) }}</a></b>
                        <small class="sub-line">{{ Str::limit($item->summary, 90) }}</small>
                    </td>
                    <td><span class="pill pill-{{ $item->status_pill }}">{{ $item->status_label }}</span></td>
                    <td>{{ $item->published_at?->format('M j, Y') ?? '—' }}</td>
                    <td>{{ $item->updated_at->format('M j, Y') }}</td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ route('admin.operations.news.show', $item) }}" title="View" aria-label="View {{ $item->title }}"><x-admin.icon name="eye" /></a>
                            <a class="icon-btn" href="{{ route('admin.operations.news.edit', $item) }}" title="Edit" aria-label="Edit {{ $item->title }}"><x-admin.icon name="edit" /></a>
                            <form method="POST" action="{{ route('admin.operations.news.destroy', $item) }}"
                                  data-confirm-title="Delete this post?"
                                  data-confirm-text="“{{ Str::limit($item->title, 80) }}” and its image will be permanently deleted."
                                  data-confirm-ok="Delete post">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-btn danger" title="Delete" aria-label="Delete {{ $item->title }}"><x-admin.icon name="trash" /></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr class="is-static">
                    <td colspan="5">
                        <div class="empty">
                            <x-admin.icon name="file-text" />
                            @if (filled(request('search')))
                                <b>No posts match your search</b>
                                <p>Try a different title.</p>
                                <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.news.index') }}">Clear search</a>
                            @else
                                <b>No news yet</b>
                                <p>Write the first post for the News page.</p>
                                <a class="btn btn-primary btn-sm" href="{{ route('admin.operations.news.create') }}"><x-admin.icon name="plus" /> Add news</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $news->links('admin.partials.pagination') }}
@endsection
