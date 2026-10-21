@php
    $currentStatus = old('status', $news?->is_published ? 'Published' : 'Draft');
@endphp
<form class="form-panel" action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    @if($errors->any())
        <ul class="form-errors" role="alert" style="color:#b91c1c;margin:0 0 1rem;padding-left:1.25rem;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <label>Title
        <input name="title" required maxlength="255" value="{{ old('title', $news?->title) }}">
    </label>

    <label>Short summary (optional)
        <textarea name="excerpt" rows="3" maxlength="300" placeholder="Shown on the News page. Leave blank to use the first part of the content.">{{ old('excerpt', $news?->excerpt) }}</textarea>
    </label>

    <label>Content
        <textarea name="body" rows="10" required>{{ old('body', $news?->body) }}</textarea>
    </label>

    <label>Image
        <input type="file" name="image" accept="image/*">
        @if($news?->image_path)
            <img class="form-preview" src="{{ \App\Support\Media::url($news->image_path) }}" alt="Current image" style="max-width:220px;margin-top:.5rem;">
            <small>Upload a new file to replace it.</small>
        @endif
    </label>

    <label>Publication date
        <input type="date" name="published_at" value="{{ old('published_at', $news?->published_at?->toDateString() ?? now()->toDateString()) }}">
    </label>

    <label>Status
        <select name="status">
            <option value="Draft" {{ $currentStatus === 'Draft' ? 'selected' : '' }}>Draft</option>
            <option value="Published" {{ $currentStatus === 'Published' ? 'selected' : '' }}>Published</option>
        </select>
    </label>

    <div class="form-actions">
        <button class="primary-button">{{ $button }}</button>
        <a class="map-button" href="{{ route('admin.operations.news.index') }}">Cancel</a>
    </div>
</form>
