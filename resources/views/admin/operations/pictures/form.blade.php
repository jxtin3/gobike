<div class="pic-form-card">
    <form class="pic-form" action="{{ $action }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div class="field">
            <label class="field-label" for="pic-title">Title</label>
            <input id="pic-title" type="text" name="title" required maxlength="30"
                   value="{{ old('title', $picture?->title) }}"
                   placeholder="Short descriptive title..."
                   oninput="updateCounter(this, 'title-count', 30)">
            <div class="counter" id="title-count">
                {{ strlen(old('title', $picture?->title ?? '')) }}/30
            </div>
        </div>

        <div class="field">
            <label class="field-label" for="pic-desc">
                Description <span class="field-hint">(optional)</span>
            </label>
            <textarea id="pic-desc" name="description" maxlength="50" rows="3"
                      placeholder="Brief caption for this photo..."
                      oninput="updateCounter(this, 'desc-count', 50)">{{ old('description', $picture?->description) }}</textarea>
            <div class="counter" id="desc-count">
                {{ strlen(old('description', $picture?->description ?? '')) }}/50
            </div>
        </div>

        <div class="field">
            <label class="field-label" for="pic-image">
                {{ $picture ? 'Replace Image' : 'Upload Image' }}
                <span class="field-hint">(JPG, PNG, WebP · max 5 MB)</span>
            </label>
            @if($picture)
                <img class="form-preview" src="{{ asset('storage/'.$picture->image_path) }}" alt="{{ $picture->title }}">
            @endif
            <input id="pic-image" type="file" name="image" accept="image/jpeg,image/png,image/webp" {{ $picture ? '' : 'required' }}>
        </div>

        <div class="form-actions">
            <button class="primary-button" type="submit">{{ $button }}</button>
            <a class="map-button" href="{{ route('admin.operations.pictures.index') }}">Cancel</a>
        </div>
    </form>
</div>

<script>
function updateCounter(el, counterId, max) {
    const count = el.value.length;
    const counter = document.getElementById(counterId);
    if (!counter) return;
    counter.textContent = count + '/' + max;
    counter.style.color = count >= max ? '#ef4444' : count >= max * 0.85 ? '#f97316' : '#9aa7b8';
}
</script>
