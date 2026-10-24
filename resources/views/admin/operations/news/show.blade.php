@extends('admin.operations.layout')
@section('title', $news->title)
@section('heading', $news->title)
@section('subheading', $news->status_label.($news->published_at ? ' · '.$news->published_at->format('F j, Y') : '').' · updated '.$news->updated_at->format('F j, Y'))
@section('back', route('admin.operations.news.index'))
@section('back_label', 'Back to news')
@section('actions')
    @if ($news->status_label === 'Published')
        <a class="btn btn-secondary" href="{{ route('news.show', $news->slug) }}" target="_blank" rel="noopener"><x-admin.icon name="external-link" /> View on site</a>
    @endif
    <a class="btn btn-primary" href="{{ route('admin.operations.news.edit', $news) }}"><x-admin.icon name="edit" /> Edit</a>
@endsection

@section('content')
<article class="detail-card detail-narrow">
    @if ($news->image_path)
        <img class="detail-image" decoding="async" src="{{ \App\Support\Media::url($news->image_path) }}" alt="Image for {{ $news->title }}">
    @endif
    <p class="detail-status"><span class="pill pill-{{ $news->status_pill }}">{{ $news->status_label }}</span></p>
    @if ($news->excerpt)
        <p class="detail-lead">{{ $news->excerpt }}</p>
    @endif
    <div class="detail-copy">{!! nl2br(e($news->body)) !!}</div>

    <div class="detail-danger">
        <form method="POST" action="{{ route('admin.operations.news.destroy', $news) }}"
              data-confirm-title="Delete this post?"
              data-confirm-text="“{{ Str::limit($news->title, 80) }}” and its image will be permanently deleted."
              data-confirm-ok="Delete post">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><x-admin.icon name="trash" /> Delete post</button>
        </form>
    </div>
</article>
@endsection
