@php
    $currentStatus = old('status', $news?->is_published ? 'Published' : 'Draft');
@endphp
<form class="form-card" action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <fieldset class="form-section">
        <legend>Article</legend>
        <div class="field">
            <label for="title">Title</label>
            <input class="input @error('title') is-invalid @enderror" id="title" name="title" required maxlength="255"
                   value="{{ old('title', $news?->title) }}" aria-describedby="title-error">
            <x-admin.field-error name="title" />
        </div>
        <div class="field">
            <label for="excerpt">Short summary <span class="muted">(optional)</span></label>
            <textarea class="input @error('excerpt') is-invalid @enderror" id="excerpt" name="excerpt" rows="3" maxlength="300"
                      aria-describedby="excerpt-hint excerpt-error">{{ old('excerpt', $news?->excerpt) }}</textarea>
            <span class="hint" id="excerpt-hint">Shown on the News page. Leave blank to use the first part of the content.</span>
            <x-admin.field-error name="excerpt" />
        </div>
        <div class="field">
            <label for="body">Content</label>
            <textarea class="input @error('body') is-invalid @enderror" id="body" name="body" rows="12" required
                      aria-describedby="body-error">{{ old('body', $news?->body) }}</textarea>
            <x-admin.field-error name="body" />
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Image</legend>
        <div class="field">
            <label for="image">{{ $news?->image_path ? 'Replace image' : 'Image' }}</label>
            <input class="input @error('image') is-invalid @enderror" id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
                   aria-describedby="image-hint image-error">
            <span class="hint" id="image-hint">JPG, PNG, WebP or GIF, up to 5 MB.{{ $news?->image_path ? ' Leave empty to keep the current image.' : '' }}</span>
            <x-admin.field-error name="image" />
            @if ($news?->image_path)
                <img class="form-preview" src="{{ \App\Support\Media::url($news->image_path) }}" alt="Current image for {{ $news->title }}">
            @endif
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Publishing</legend>
        <div class="grid-2">
            <div class="field">
                <label for="status">Status</label>
                <select class="input @error('status') is-invalid @enderror" id="status" name="status" aria-describedby="status-error">
                    <option value="Draft" @selected($currentStatus === 'Draft')>Draft</option>
                    <option value="Published" @selected($currentStatus === 'Published')>Published</option>
                </select>
                <x-admin.field-error name="status" />
            </div>
            <div class="field">
                <label for="published_at">Publication date</label>
                <input class="input @error('published_at') is-invalid @enderror" id="published_at" type="date" name="published_at"
                       value="{{ old('published_at', $news?->published_at?->toDateString() ?? now()->toDateString()) }}"
                       aria-describedby="published_at-hint published_at-error">
                <span class="hint" id="published_at-hint">A future date schedules the post; it appears on the site on that day.</span>
                <x-admin.field-error name="published_at" />
            </div>
        </div>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-ok" type="submit"><x-admin.icon name="check" /> {{ $button }}</button>
        <a class="btn btn-secondary" href="{{ $news ? route('admin.operations.news.show', $news) : route('admin.operations.news.index') }}">Cancel</a>
    </div>
</form>
