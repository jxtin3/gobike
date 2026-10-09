@extends('admin.operations.layout')
@section('title', 'Homepage news')
@section('heading', 'Homepage news')
@section('subheading', 'Choose and order the three published stories featured on the homepage.')

@section('content')
<div class="page-actions">
    <a class="map-button" href="{{ route('admin.operations.news.index') }}">Back to news management</a>
</div>

@if ($publishedNews->count() < 3)
    <div class="alert error" role="alert">
        Publish at least three news stories before choosing the homepage stories. There are currently {{ $publishedNews->count() }} published.
    </div>
@endif

<form class="form-panel" method="POST" action="{{ route('admin.operations.news.homepage.update') }}">
    @csrf
    @method('PUT')

    @foreach (range(0, 2) as $index)
        <label>
            <span>Homepage story {{ $index + 1 }}</span>
            <select class="input" name="news[{{ $index }}]" required>
                <option value="">Choose a published story</option>
                @foreach ($publishedNews as $item)
                    <option value="{{ $item->id }}" @selected((string) old("news.$index", $selectedNews[$index] ?? '') === (string) $item->id)>
                        {{ $item->title }} ({{ $item->published_at->format('M d, Y') }})
                    </option>
                @endforeach
            </select>
        </label>
    @endforeach

    <div class="form-actions">
        <button class="primary-button" type="submit" @disabled($publishedNews->count() < 3)>Save homepage stories</button>
        <a class="map-button" href="{{ route('admin.operations.news.index') }}">Cancel</a>
    </div>
</form>
@endsection
