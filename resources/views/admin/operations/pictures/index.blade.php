@extends('admin.operations.layout')
@section('title','Photos & Videos')
@section('heading','Photos & Videos')
@section('subheading', 'Directly upload and manage gallery photos and videos.')

@section('content')

<!-- Direct Upload Area -->
<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <input type="file" id="media-files" multiple accept="image/*,video/*" style="display: none;">

    <!-- Drop zone -->
    <div id="drop-zone" style="border: 2px dashed #cbd5e1; border-radius: 10px; padding: 2.5rem 1.5rem; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s ease;">
        <div style="margin-bottom: 0.75rem; color: #2563eb;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        </div>
        <h3 style="font-size: 1.1rem; font-weight: 700; color: #0f172a; margin: 0 0 0.4rem 0;">Click or drag &amp; drop to upload</h3>
        <p style="font-size: 0.875rem; color: #64748b; margin: 0;">JPG, PNG, WebP, GIF, MP4, WebM &bull; Up to 100 MB per file</p>
    </div>

    <!-- Upload panel: shown after files selected -->
    <div id="upload-panel" style="display: none; margin-top: 1.25rem;">

        <!-- File list -->
        <div id="file-list" style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; max-height: 240px; overflow-y: auto;"></div>

        <!-- Error banner -->
        <div id="upload-errors" style="display: none; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 0.75rem; font-size: 0.85rem; color: #dc2626;"></div>

        <!-- Status bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; padding: 0.85rem 1rem; background: #f1f5f9; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div>
                <span id="selected-summary" style="font-weight: 700; color: #0f172a; font-size: 0.95rem;"></span>
                <span id="upload-progress-text" style="display: block; font-size: 0.82rem; color: #64748b; margin-top: 2px;"></span>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button type="button" id="btn-clear" style="padding: 0.5rem 1.1rem; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem; cursor: pointer; color: #475569;">
                    Clear
                </button>
                <button type="button" id="btn-start-upload" style="padding: 0.5rem 1.4rem; background: #15803d; border: none; border-radius: 6px; font-size: 0.875rem; font-weight: 600; color: #fff; cursor: pointer;">
                    Upload All
                </button>
            </div>
        </div>

        <!-- Progress Bar -->
        <div id="progress-container" style="display: none; width: 100%; background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; margin-top: 0.75rem;">
            <div id="progress-bar" style="width: 0%; height: 100%; background: #16a34a; transition: width 0.25s ease;"></div>
        </div>
    </div>
</div>

<!-- Photos / Videos Grid -->
@if($pictures->isEmpty())
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 3rem 1.5rem; text-align: center; color: #64748b;">
        <p style="font-size: 1rem; margin: 0;">No photos or videos uploaded yet. Use the upload box above to add your first batch!</p>
    </div>
@else
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 1.25rem;">
        @foreach($pictures as $picture)
            @php
                $mediaSrc = \App\Support\Media::url($picture->image_path);
                $isVideo = $picture->isVideo();
            @endphp
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column;">
                <div style="position: relative; width: 100%; aspect-ratio: 4/3; background: #0f172a; overflow: hidden; cursor: pointer;"
                     onclick="openViewer('{{ $mediaSrc }}', {{ $isVideo ? 'true' : 'false' }})">
                    @if($isVideo)
                        <video src="{{ $mediaSrc }}" style="width: 100%; height: 100%; object-fit: cover;" preload="metadata" muted playsinline></video>
                        <span style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.75); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; text-transform: uppercase;">Video</span>
                        <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.15);">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.9); display: flex; align-items: center; justify-content: center; color: #0f172a;">
                                <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24" style="transform: translateX(1px);"><path d="M8 5v14l11-7z"/></svg>
                            </div>
                        </div>
                    @else
                        <img src="{{ $mediaSrc }}" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                    @endif
                </div>
                <div style="padding: 0.75rem; display: flex; justify-content: space-between; align-items: center; background: #ffffff; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openViewer('{{ $mediaSrc }}', {{ $isVideo ? 'true' : 'false' }})">View</button>
                    <form method="POST" action="{{ route('admin.operations.pictures.destroy', $picture) }}" onsubmit="return confirm('Remove this item?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-soft-danger btn-sm">Remove</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    @if($pictures->hasPages())
        <div style="margin-top: 2rem; display: flex; align-items: center; justify-content: center; gap: 0.35rem; flex-wrap: wrap;">

            {{-- Previous --}}
            @if($pictures->onFirstPage())
                <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:7px;border:1px solid #e2e8f0;color:#cbd5e1;cursor:default;font-size:1.1rem;background:#fafafa;">&#8249;</span>
            @else
                <a href="{{ $pictures->previousPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:7px;border:1px solid #e2e8f0;color:#334155;text-decoration:none;font-size:1.1rem;background:#fff;transition:background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#fff'">&#8249;</a>
            @endif

            {{-- Page numbers --}}
            @foreach($pictures->getUrlRange(1, $pictures->lastPage()) as $page => $url)
                @if($page == $pictures->currentPage())
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:7px;background:#0f172a;color:#fff;font-size:0.85rem;font-weight:700;">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:7px;border:1px solid #e2e8f0;color:#334155;text-decoration:none;font-size:0.85rem;background:#fff;transition:background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#fff'">{{ $page }}</a>
                @endif
            @endforeach

            {{-- Next --}}
            @if($pictures->hasMorePages())
                <a href="{{ $pictures->nextPageUrl() }}" style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:7px;border:1px solid #e2e8f0;color:#334155;text-decoration:none;font-size:1.1rem;background:#fff;transition:background 0.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#fff'">&#8250;</a>
            @else
                <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:7px;border:1px solid #e2e8f0;color:#cbd5e1;cursor:default;font-size:1.1rem;background:#fafafa;">&#8250;</span>
            @endif

        </div>
        <p style="text-align:center;margin-top:0.6rem;font-size:0.8rem;color:#94a3b8;">
            Showing {{ $pictures->firstItem() }}–{{ $pictures->lastItem() }} of {{ $pictures->total() }}
        </p>
    @endif
@endif

<!-- Media Viewer Modal -->
<div id="admin-media-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.88); z-index: 9999; align-items: center; justify-content: center; padding: 1.5rem;" onclick="closeViewer()">
    <button type="button" onclick="closeViewer()" style="position: fixed; top: 1rem; right: 1.25rem; background: rgba(255,255,255,0.15); border: none; color: #fff; width: 38px; height: 38px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 10000;" title="Close">✕</button>
    <div style="position: relative; max-width: 92vw; max-height: 88vh;" onclick="event.stopPropagation()">
        <div id="modal-content" style="background: #000; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; max-height: 88vh;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone          = document.getElementById('drop-zone');
    const fileInput         = document.getElementById('media-files');
    const uploadPanel       = document.getElementById('upload-panel');
    const fileList          = document.getElementById('file-list');
    const summaryText       = document.getElementById('selected-summary');
    const progressText      = document.getElementById('upload-progress-text');
    const progressContainer = document.getElementById('progress-container');
    const progressBar       = document.getElementById('progress-bar');
    const btnStartUpload    = document.getElementById('btn-start-upload');
    const btnClear          = document.getElementById('btn-clear');
    const uploadErrors      = document.getElementById('upload-errors');

    let selectedFilesList = [];
    let isUploading = false;

    dropZone.addEventListener('click', function () {
        if (isUploading) return;
        fileInput.click();
    });

    fileInput.addEventListener('change', function () {
        if (this.files && this.files.length > 0) {
            handleSelectedFiles(Array.from(this.files));
        }
    });

    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (isUploading) return;
        dropZone.style.borderColor = '#2563eb';
        dropZone.style.background  = '#eff6ff';
    });

    ['dragleave', 'dragend'].forEach(type => {
        dropZone.addEventListener(type, function (e) {
            e.preventDefault();
            dropZone.style.borderColor = '#cbd5e1';
            dropZone.style.background  = '#f8fafc';
        });
    });

    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.style.borderColor = '#cbd5e1';
        dropZone.style.background  = '#f8fafc';
        if (isUploading) return;
        if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
            handleSelectedFiles(Array.from(e.dataTransfer.files));
        }
    });

    function formatSize(bytes) {
        if (bytes >= 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        return (bytes / 1024).toFixed(0) + ' KB';
    }

    function handleSelectedFiles(files) {
        selectedFilesList = files;
        uploadErrors.style.display = 'none';

        if (selectedFilesList.length === 0) {
            uploadPanel.style.display = 'none';
            dropZone.style.display = 'block';
            return;
        }

        // Build file rows
        fileList.innerHTML = '';
        selectedFilesList.forEach((f, idx) => {
            const isVid = f.type.startsWith('video');
            const row = document.createElement('div');
            row.id = 'file-row-' + idx;
            row.style.cssText = 'display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.85rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;';
            row.innerHTML = `
                <span style="font-size:1.4rem;">${isVid ? '🎬' : '🖼️'}</span>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.875rem;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${f.name}">${f.name}</div>
                    <div style="font-size:0.78rem;color:#64748b;">${formatSize(f.size)}</div>
                </div>
                <span id="status-${idx}" style="font-size:0.8rem;font-weight:600;color:#64748b;white-space:nowrap;">Pending</span>
            `;
            fileList.appendChild(row);
        });

        summaryText.textContent   = `${selectedFilesList.length} file(s) ready to upload`;
        progressText.textContent  = '';
        progressContainer.style.display = 'none';
        progressBar.style.width  = '0%';
        btnStartUpload.disabled  = false;
        btnStartUpload.textContent = 'Upload All';
        btnStartUpload.style.background = '#15803d';
        uploadPanel.style.display = 'block';
        dropZone.style.display   = 'none';
    }

    btnClear.addEventListener('click', function () {
        if (isUploading) return;
        selectedFilesList = [];
        fileInput.value   = '';
        fileList.innerHTML = '';
        uploadErrors.style.display = 'none';
        uploadPanel.style.display  = 'none';
        dropZone.style.display     = 'block';
    });

    btnStartUpload.addEventListener('click', async function () {
        if (isUploading || selectedFilesList.length === 0) return;
        isUploading = true;

        btnStartUpload.disabled  = true;
        btnClear.disabled        = true;
        uploadErrors.style.display = 'none';
        progressContainer.style.display = 'block';

        const total = selectedFilesList.length;
        let successCount = 0;
        const failedItems = [];

        // Always fetch a fresh CSRF token before starting
        const uploadUrl = '{{ route('admin.operations.pictures.store') }}';

        for (let i = 0; i < total; i++) {
            const file  = selectedFilesList[i];
            const statusEl = document.getElementById('status-' + i);

            statusEl.textContent = 'Uploading…';
            statusEl.style.color = '#2563eb';

            summaryText.textContent  = `Uploading ${i + 1} of ${total}…`;
            progressText.textContent = file.name;
            progressBar.style.width  = `${Math.round((i / total) * 100)}%`;

            // Get a fresh CSRF token from the meta tag each iteration
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', csrfToken);

            try {
                const response = await fetch(uploadUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: formData
                });

                if (response.ok) {
                    successCount++;
                    statusEl.textContent = '✓ Done';
                    statusEl.style.color = '#16a34a';

                    // Update CSRF token if Laravel sends a new one
                    const newToken = response.headers.get('X-CSRF-TOKEN');
                    if (newToken) {
                        const metaTag = document.querySelector('meta[name="csrf-token"]');
                        if (metaTag) metaTag.setAttribute('content', newToken);
                    }
                } else {
                    let errMsg = `HTTP ${response.status}`;
                    try {
                        const data = await response.json();
                        errMsg = data.message || JSON.stringify(data.errors || data) || errMsg;
                    } catch (_) {}
                    failedItems.push(`${file.name}: ${errMsg}`);
                    statusEl.textContent = '✗ Failed';
                    statusEl.style.color = '#dc2626';
                }
            } catch (err) {
                console.error('Upload error:', err);
                failedItems.push(`${file.name}: Network error`);
                statusEl.textContent = '✗ Error';
                statusEl.style.color = '#dc2626';
            }

            progressBar.style.width = `${Math.round(((i + 1) / total) * 100)}%`;
        }

        // Final state
        if (successCount > 0) {
            summaryText.textContent  = `✓ ${successCount} of ${total} file(s) uploaded`;
            progressText.textContent = failedItems.length > 0 ? `${failedItems.length} failed — see details above` : 'Refreshing gallery…';
        }

        if (failedItems.length > 0) {
            uploadErrors.innerHTML = '<strong>Some files failed:</strong><br>' + failedItems.map(e => `• ${e}`).join('<br>');
            uploadErrors.style.display = 'block';
            isUploading = false;
            btnClear.disabled = false;
        }

        if (successCount > 0) {
            setTimeout(() => window.location.reload(), failedItems.length > 0 ? 3000 : 800);
        } else {
            isUploading = false;
            btnStartUpload.disabled = false;
            btnClear.disabled = false;
            btnStartUpload.textContent = 'Retry';
            btnStartUpload.style.background = '#dc2626';
        }
    });
});

// Lightbox Viewer
function openViewer(src, isVideo) {
    const modal   = document.getElementById('admin-media-modal');
    const content = document.getElementById('modal-content');
    if (!modal || !content) return;
    content.innerHTML = isVideo
        ? `<video src="${src}" controls autoplay style="max-height:85vh;max-width:90vw;width:auto;height:auto;"></video>`
        : `<img src="${src}" style="max-height:85vh;max-width:90vw;object-fit:contain;">`;
    modal.style.display    = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeViewer() {
    const modal   = document.getElementById('admin-media-modal');
    const content = document.getElementById('modal-content');
    if (modal)   { modal.style.display = 'none'; }
    if (content) { content.innerHTML   = ''; }
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeViewer(); });
</script>
@endsection
