@extends('admin.operations.layout')
@section('title','Picture Details')
@section('heading','Picture Details')
@section('content')

<div class="picture-detail">
    <div class="picture-detail-media">
        <img src="{{ asset('storage/'.$picture->image_path) }}" alt="{{ $picture->title }}">
    </div>

    <div class="picture-detail-card">
        <div>
            <span class="picture-detail-label">Title</span>
            <p class="title">{{ $picture->title }}</p>
        </div>

        @if($picture->description)
        <div>
            <span class="picture-detail-label">Description</span>
            <p class="copy">{{ $picture->description }}</p>
        </div>
        @endif

        <div class="picture-detail-meta">
            <div>
                <span>Uploaded By</span>
                <p>{{ $picture->uploader?->name ?? 'Unknown' }}</p>
            </div>
            <div>
                <span>Upload Date</span>
                <p>{{ $picture->created_at->format('F d, Y') }}</p>
            </div>
        </div>

        <div class="picture-detail-actions">
            <a class="primary-button" href="{{ route('admin.operations.pictures.edit', $picture) }}">Edit Picture</a>
            <a class="map-button" href="{{ route('admin.operations.pictures.index') }}">← Back to Pictures</a>
        </div>
    </div>
</div>

@endsection
