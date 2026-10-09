@extends('admin.operations.layout')
@section('title', 'News')
@section('heading', 'News management')
@section('content')
<div class="page-actions">
    <form class="search-form">
        <input name="search" value="{{ request('search') }}" placeholder="Search news...">
        <button class="map-button">Search</button>
    </form>
    <a class="map-button" href="{{ route('admin.operations.news.homepage') }}">Manage homepage news</a>
    <a class="primary-button" href="{{ route('admin.operations.news.create') }}">+ Add news</a>
</div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Title</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($news as $item)
                <tr>
                    <td>{{ $item->title }}</td>
                    <td><span class="status-pill {{ $item->is_published ? 'published' : 'draft' }}">{{ $item->is_published ? 'Published' : 'Draft' }}</span></td>
                    <td>{{ $item->published_at?->format('M d, Y') ?? '—' }}</td>
                    <td class="actions">
                        <a href="{{ route('admin.operations.news.show', $item) }}">View</a>
                        <a href="{{ route('admin.operations.news.edit', $item) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.operations.news.destroy', $item) }}" onsubmit="return confirm('Are you sure you want to delete this news?')">
                            @csrf
                            @method('DELETE')
                            <button>Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-state">No news found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $news->links() }}
@endsection
