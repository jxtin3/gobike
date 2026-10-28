@extends('admin.operations.layout')
@section('title', 'Pictures')
@section('heading', 'Pictures')
@section('subheading', 'Photos and videos shown in the website gallery.')

@section('content')
<section class="upload-card" aria-labelledby="upload-title" data-upload-url="{{ route('admin.operations.pictures.store') }}">
    <h2 id="upload-title" class="sr-only">Upload photos and videos</h2>

    <label class="drop-zone" id="drop-zone">
        <input type="file" id="media-files" multiple
               accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov,.m4v,image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime">
        <x-admin.icon name="upload" />
        <b>Click or drag &amp; drop to upload</b>
        <span>JPG, PNG, WebP, GIF, MP4, WebM or MOV · up to 50 MB per file</span>
    </label>

    <div id="upload-panel" hidden>
        <ul class="upload-list" id="file-list" aria-label="Files to upload"></ul>

        <div class="alert error" id="upload-errors" role="alert" hidden>
            <x-admin.icon name="alert-circle" />
            <div id="upload-errors-text"></div>
        </div>

        <div class="upload-bar">
            <div role="status">
                <b id="selected-summary"></b>
                <small class="sub-line" id="upload-progress-text"></small>
            </div>
            <div class="upload-actions">
                <button type="button" class="btn btn-secondary" id="btn-clear">Clear</button>
                <button type="button" class="btn btn-ok" id="btn-start-upload"><x-admin.icon name="upload" /> <span>Upload all</span></button>
            </div>
        </div>

        <progress class="upload-progress" id="upload-progress" max="100" value="0" hidden></progress>
    </div>
</section>

<div data-list>
    @if ($pictures->isEmpty())
        <x-admin.empty icon="image" title="No pictures yet" class="empty-card">
            Use the upload box above to add the first photos and videos to the gallery.
        </x-admin.empty>
    @else
        <ul class="picture-grid" aria-label="Gallery pictures">
            @foreach ($pictures as $picture)
                @php
                    $mediaSrc = \App\Support\Media::url($picture->image_path);
                    $isVideo = $picture->isVideo();
                @endphp
                <li class="picture-card" data-href="{{ route('admin.operations.pictures.show', $picture) }}">
                    <button type="button" class="picture-card-media" data-lightbox="{{ $mediaSrc }}" data-video="{{ $isVideo ? '1' : '0' }}" data-title="{{ $picture->title }}"
                            aria-label="Open {{ $isVideo ? 'video' : 'photo' }} {{ $picture->title }}">
                        @if ($isVideo)
                            <video src="{{ $mediaSrc }}" preload="metadata" muted playsinline aria-hidden="true"></video>
                            <span class="picture-card-tag">Video</span>
                        @else
                            <img src="{{ $mediaSrc }}" alt="" loading="lazy" decoding="async">
                        @endif
                    </button>
                    <div class="picture-card-body">
                        <div>
                            <b title="{{ $picture->title }}">{{ $picture->title }}</b>
                            <small class="muted">{{ $picture->created_at->format('M j, Y') }}</small>
                        </div>
                        <div class="row-actions">
                            <a class="icon-btn" href="{{ route('admin.operations.pictures.show', $picture) }}" title="Details" aria-label="Details for {{ $picture->title }}"><x-admin.icon name="eye" /></a>
                            <form method="POST" action="{{ route('admin.operations.pictures.destroy', $picture) }}"
                                  data-confirm-title="Remove this {{ $isVideo ? 'video' : 'photo' }}?"
                                  data-confirm-text="“{{ Str::limit($picture->title, 60) }}” will be removed from the gallery and deleted."
                                  data-confirm-ok="Remove">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-btn danger" title="Remove" aria-label="Remove {{ $picture->title }}"><x-admin.icon name="trash" /></button>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <x-admin.pager :paginator="$pictures" noun="picture" />
    @endif
</div>

<dialog class="lightbox" id="lightbox" aria-label="Picture viewer">
    <button type="button" class="icon-btn lightbox-close" data-lightbox-close aria-label="Close viewer"><x-admin.icon name="x" /></button>
    <div class="lightbox-media" id="lightbox-media"></div>
</dialog>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const card = document.querySelector('[data-upload-url]');
    const uploadUrl = card.dataset.uploadUrl;
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('media-files');
    const panel = document.getElementById('upload-panel');
    const list = document.getElementById('file-list');
    const summary = document.getElementById('selected-summary');
    const progressText = document.getElementById('upload-progress-text');
    const progress = document.getElementById('upload-progress');
    const startButton = document.getElementById('btn-start-upload');
    const startLabel = startButton.querySelector('span');
    const clearButton = document.getElementById('btn-clear');
    const errors = document.getElementById('upload-errors');
    const errorsText = document.getElementById('upload-errors-text');
    const MAX_BYTES = 50 * 1024 * 1024;

    let files = [];
    let uploading = false;

    const formatSize = (bytes) => bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.ceil(bytes / 1024) + ' KB';
    const plural = (n, word) => `${n} ${word}${n === 1 ? '' : 's'}`;

    function showErrors(messages) {
        errorsText.replaceChildren(...messages.map((message) => {
            const line = document.createElement('div');
            line.textContent = message;
            return line;
        }));
        errors.hidden = messages.length === 0;
    }

    function setState(index, state, label) {
        const el = list.querySelector(`[data-index="${index}"] .upload-item-state`);
        el.dataset.state = state;
        el.textContent = label;
    }

    function selectFiles(selected) {
        if (uploading) return;
        files = selected;
        showErrors([]);
        list.replaceChildren();

        const tooBig = files.filter((file) => file.size > MAX_BYTES).map((file) => `${file.name} is larger than 50 MB and will be skipped.`);
        showErrors(tooBig);

        files.forEach((file, index) => {
            const item = document.createElement('li');
            item.className = 'upload-item';
            item.dataset.index = index;
            const name = document.createElement('div');
            name.className = 'upload-item-name';
            const title = document.createElement('b');
            title.textContent = file.name;
            title.title = file.name;
            const size = document.createElement('small');
            size.className = 'muted';
            size.textContent = formatSize(file.size);
            name.append(title, size);
            const state = document.createElement('span');
            state.className = 'upload-item-state';
            state.textContent = file.size > MAX_BYTES ? 'Too large' : 'Ready';
            state.dataset.state = file.size > MAX_BYTES ? 'failed' : 'ready';
            item.append(name, state);
            list.append(item);
        });

        summary.textContent = `${plural(files.length, 'file')} selected`;
        progressText.textContent = '';
        progress.hidden = true;
        progress.value = 0;
        startButton.disabled = false;
        startLabel.textContent = 'Upload all';
        panel.hidden = files.length === 0;
        dropZone.hidden = files.length > 0;
    }

    fileInput.addEventListener('change', () => selectFiles([...fileInput.files]));

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        if (!uploading) dropZone.classList.add('is-dragging');
    });
    ['dragleave', 'dragend'].forEach((type) => dropZone.addEventListener(type, () => dropZone.classList.remove('is-dragging')));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('is-dragging');
        if (e.dataTransfer?.files?.length) selectFiles([...e.dataTransfer.files]);
    });

    clearButton.addEventListener('click', () => {
        if (uploading) return;
        fileInput.value = '';
        selectFiles([]);
    });

    startButton.addEventListener('click', async () => {
        if (uploading || files.length === 0) return;
        uploading = true;
        startButton.disabled = true;
        clearButton.disabled = true;
        progress.hidden = false;

        const failed = [];
        let uploaded = 0;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            progress.value = Math.round((i / files.length) * 100);

            if (file.size > MAX_BYTES) {
                failed.push(`${file.name}: larger than 50 MB.`);
                continue;
            }

            summary.textContent = `Uploading ${i + 1} of ${files.length}…`;
            progressText.textContent = file.name;
            setState(i, 'uploading', 'Uploading…');

            const body = new FormData();
            body.append('file', file);

            try {
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const message = data.errors ? Object.values(data.errors).flat()[0] : data.message;
                    throw new Error(message || `The server answered ${response.status}.`);
                }
                uploaded++;
                setState(i, 'done', 'Uploaded');
            } catch (error) {
                failed.push(`${file.name}: ${error.message === 'Failed to fetch' ? 'network error.' : error.message}`);
                setState(i, 'failed', 'Failed');
            }
        }

        progress.value = 100;
        summary.textContent = `${uploaded} of ${plural(files.length, 'file')} uploaded`;
        showErrors(failed);

        if (uploaded > 0) {
            progressText.textContent = failed.length ? 'Refreshing the gallery shortly…' : 'Refreshing the gallery…';
            setTimeout(() => window.location.reload(), failed.length ? 4000 : 800);
            return;
        }

        uploading = false;
        startButton.disabled = false;
        clearButton.disabled = false;
        startLabel.textContent = 'Try again';
        progressText.textContent = '';
    });

    // Lightbox
    const lightbox = document.getElementById('lightbox');
    const lightboxMedia = document.getElementById('lightbox-media');

    // A double-click on the dark area closes the viewer on the first click; the second click would
    // land on the card underneath and open it again, so ignore clicks for a moment after closing.
    let closedAt = 0;

    document.querySelectorAll('[data-lightbox]').forEach((button) => {
        button.addEventListener('click', () => {
            if (Date.now() - closedAt < 400) return;
            const media = document.createElement(button.dataset.video === '1' ? 'video' : 'img');
            media.src = button.dataset.lightbox;
            if (media instanceof HTMLVideoElement) {
                media.controls = true;
                media.autoplay = true;
            } else {
                media.alt = button.dataset.title || '';
            }
            lightboxMedia.replaceChildren(media);
            lightbox.showModal();
        });
    });

    // Anything that is not the photo or video itself (dark area, close button) closes the viewer
    lightbox.addEventListener('click', (e) => {
        if (e.target.closest('[data-lightbox-close]') || !e.target.closest('img, video')) {
            closedAt = Date.now(); // set now: the "close" event below fires a moment later
            lightbox.close();
        }
    });
    lightbox.addEventListener('close', () => {
        closedAt = Date.now();
        lightboxMedia.replaceChildren();
    });
});
</script>
@endpush
@endsection
