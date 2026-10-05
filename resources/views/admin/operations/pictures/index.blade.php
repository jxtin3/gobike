@extends('admin.operations.layout')
@section('title','Pictures')
@section('heading','Picture Management')
@section('content')

<div class="page-actions">
    <form class="search-form" method="GET" action="{{ route('admin.operations.pictures.index') }}">
        <input name="search" value="{{ request('search') }}" placeholder="Search pictures by title...">
        <button class="map-button" type="submit">Search</button>
        @if(request('search'))
            <a href="{{ route('admin.operations.pictures.index') }}" class="map-button">Clear</a>
        @endif
    </form>
    <a class="primary-button" href="{{ route('admin.operations.pictures.create') }}">+ Add Picture</a>
</div>

@if($pictures->isEmpty())
    <div class="picture-empty">
        <x-admin.icon name="image" />
        <p>No pictures uploaded yet. <a href="{{ route('admin.operations.pictures.create') }}">Upload the first one →</a></p>
    </div>
@else
    <div class="picture-grid">
        @foreach($pictures as $picture)
            <article class="picture-card">
                <div class="picture-card-media">
                    <img src="{{ asset('storage/'.$picture->image_path) }}" alt="{{ $picture->title }}">
                    <span class="picture-card-date">{{ $picture->created_at->format('M d, Y') }}</span>
                </div>

                <div class="picture-card-body">
                    <h2 title="{{ $picture->title }}">{{ $picture->title }}</h2>
                    @if($picture->description)
                        <p>{{ $picture->description }}</p>
                    @else
                        <p class="is-empty">No description</p>
                    @endif

                    <div class="picture-card-actions">
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.pictures.show', $picture) }}">View</a>
                        <a class="btn btn-secondary btn-sm" href="{{ route('admin.operations.pictures.edit', $picture) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.operations.pictures.destroy', $picture) }}"
                              data-confirm-title="Delete this picture?"
                              data-confirm-text="This cannot be undone."
                              data-confirm-ok="Delete">
                            @csrf @method('DELETE')
                            <button class="btn btn-soft-danger btn-sm" type="submit">Delete</button>
                        </form>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    <div style="margin-top:1.5rem;">
        {{ $pictures->links() }}
    </div>
@endif

@endsection
