@extends('admin.operations.layout')
@section('title','View Media')
@section('heading','Media Viewer')
@section('back', route('admin.operations.pictures.index'))
@section('back_label', 'Back to Gallery')

@section('content')
<div style="max-width: 900px; margin: 0 auto; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
    <div style="background: #000; text-align: center; max-height: 70vh; display: flex; align-items: center; justify-content: center; min-height: 300px;">
        @if($picture->isVideo())
            <video src="{{ asset('storage/'.$picture->image_path) }}" controls autoplay style="max-height: 70vh; max-width: 100%; width: auto;"></video>
        @else
            <img src="{{ asset('storage/'.$picture->image_path) }}" alt="Gallery media" style="max-height: 70vh; max-width: 100%; object-contain: fit;">
        @endif
    </div>

    <div style="padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0;">
        <a class="btn btn-secondary" href="{{ route('admin.operations.pictures.index') }}">← Back to Pictures</a>
        <form method="POST" action="{{ route('admin.operations.pictures.destroy', $picture) }}">
            @csrf @method('DELETE')
            <button class="btn btn-soft-danger" type="submit" onclick="return confirm('Remove this item?')">Remove</button>
        </form>
    </div>
</div>
@endsection
