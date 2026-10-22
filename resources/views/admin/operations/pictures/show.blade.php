@extends('admin.operations.layout')
@section('title', $picture->title)
@section('heading', $picture->title)
@section('subheading', ($picture->isVideo() ? 'Video' : 'Photo').' uploaded '.$picture->created_at->format('F j, Y'))
@section('back', route('admin.operations.pictures.index'))
@section('back_label', 'Back to pictures')
@section('actions')
    <form method="POST" action="{{ route('admin.operations.pictures.destroy', $picture) }}"
          data-confirm-title="Remove this {{ $picture->isVideo() ? 'video' : 'photo' }}?"
          data-confirm-text="“{{ Str::limit($picture->title, 60) }}” will be removed from the gallery and deleted."
          data-confirm-ok="Remove">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger"><x-admin.icon name="trash" /> Remove</button>
    </form>
@endsection

@section('content')
@php $src = \App\Support\Media::url($picture->image_path); @endphp
<article class="picture-detail">
    <div class="picture-detail-media">
        @if ($picture->isVideo())
            <video src="{{ $src }}" controls></video>
        @else
            <img src="{{ $src }}" alt="{{ $picture->title }}">
        @endif
    </div>
    <dl class="picture-detail-meta">
        <div><dt>Type</dt><dd>{{ $picture->isVideo() ? 'Video' : 'Photo' }}</dd></div>
        <div><dt>Uploaded</dt><dd>{{ $picture->created_at->format('F j, Y g:i A') }}</dd></div>
        <div><dt>File</dt><dd><a href="{{ $src }}" target="_blank" rel="noopener">Open original <x-admin.icon name="external-link" /></a></dd></div>
    </dl>
</article>
@endsection
