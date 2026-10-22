<div class="legal-live-editor-root" style="--legal-accent: {{ $accentColor }}">
    <div class="legal-live-wrap">
        <div class="legal-top-bar">
            <nav class="legal-tabs" aria-label="Legal pages">
                <a href="{{ route('admin.operations.legal.terms') }}" class="legal-tab" aria-current="{{ $activeDoc === 'terms' ? 'page' : 'false' }}">
                    <x-admin.icon name="file-text" /> Terms &amp; Conditions
                </a>
                <a href="{{ route('admin.operations.legal.privacy') }}" class="legal-tab" aria-current="{{ $activeDoc === 'privacy' ? 'page' : 'false' }}">
                    <x-admin.icon name="shield" /> Privacy Policy
                </a>
                <a href="{{ route('admin.operations.legal.cookies') }}" class="legal-tab" aria-current="{{ $activeDoc === 'cookies' ? 'page' : 'false' }}">
                    <x-admin.icon name="eye" /> Cookie Policy
                </a>
            </nav>

            <div class="legal-actions">
                <div class="legal-status" id="save-status" role="status">
                    <span class="legal-status-dot" aria-hidden="true"></span>
                    <span id="status-text">All changes saved</span>
                </div>

                <button type="button" class="btn-live-save" id="btn-save" title="Save document (Ctrl + S)">
                    <x-admin.icon name="check" />
                    <span id="save-btn-label">Save changes</span>
                </button>
            </div>
        </div>

        <div class="alert error legal-save-error" id="save-error" role="alert" hidden>
            <x-admin.icon name="alert-circle" />
            <div id="save-error-text"></div>
        </div>

        <form id="legal-form" method="POST" action="{{ $updateRoute }}">
            @csrf
            @method('PUT')

            <div class="live-page-canvas">
                <div class="live-hero">
                    <input type="text"
                           name="title"
                           id="doc-title"
                           class="live-title-input"
                           value="{{ $document->title ?? ['terms' => 'Terms & Conditions', 'privacy' => 'Privacy Policy', 'cookies' => 'Cookie Policy'][$activeDoc] }}"
                           placeholder="Document title"
                           aria-label="Document title"
                           required
                           maxlength="150"
                           autocomplete="off">

                    <p class="legal-format-hint">
                        Last updated <strong id="doc-last-updated">{{ $document->last_updated ?: 'not saved yet' }}</strong>.
                        The date changes each time you save.
                    </p>
                    <p class="legal-format-hint">
                        <strong>Formatting:</strong>
                        leave a blank line between paragraphs ·
                        start a line with <code>- </code> for a bullet point ·
                        <code>**bold text**</code> ·
                        <code>[link text](/contact)</code>.
                        Website and email addresses become links automatically.
                        <a href="{{ $liveUrl }}" target="_blank" rel="noopener">View live page ↗</a>
                    </p>
                </div>

                <div class="live-sections" id="sections-container">
                    @php
                        $sections = $document->sections ?: [['title' => '1. Overview', 'content' => '']];
                    @endphp
                    @foreach ($sections as $index => $section)
                        @include('admin.operations.legal._section', ['index' => $index, 'section' => $section])
                    @endforeach
                </div>

                <div class="legal-add-row">
                    <button type="button" class="btn-add-section" id="btn-add-section">
                        <x-admin.icon name="plus" /> Add another section
                    </button>
                </div>
            </div>
        </form>
    </div>

    <template id="section-template">
        @include('admin.operations.legal._section', ['index' => '__INDEX__', 'section' => ['title' => '', 'content' => '']])
    </template>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('legal-form');
    const container = document.getElementById('sections-container');
    const template = document.getElementById('section-template');
    const btnAdd = document.getElementById('btn-add-section');
    const btnSave = document.getElementById('btn-save');
    const saveBtnLabel = document.getElementById('save-btn-label');
    const statusWrap = document.getElementById('save-status');
    const statusText = document.getElementById('status-text');
    const lastUpdated = document.getElementById('doc-last-updated');
    const errorBox = document.getElementById('save-error');
    const errorText = document.getElementById('save-error-text');

    let hasUnsavedChanges = false;
    let isSaving = false;

    function markChanged() {
        if (hasUnsavedChanges) return;
        hasUnsavedChanges = true;
        statusWrap.className = 'legal-status has-changes';
        statusText.textContent = 'Unsaved changes';
        btnSave.classList.remove('is-saved');
    }

    function markClean() {
        hasUnsavedChanges = false;
        statusWrap.className = 'legal-status saved';
        statusText.textContent = 'All changes saved';
    }

    function showError(message) {
        errorText.textContent = message;
        errorBox.hidden = !message;
    }

    // Warn before leaving the page (tabs, sidebar, closing the tab) with unsaved edits.
    window.addEventListener('beforeunload', function (e) {
        if (!hasUnsavedChanges) return;
        e.preventDefault();
        e.returnValue = '';
    });

    function resizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.max(60, textarea.scrollHeight + 4) + 'px';
    }

    function reindexSections() {
        container.querySelectorAll('.live-section-card').forEach((card, idx) => {
            card.dataset.sectionIndex = idx;
            card.querySelector('.sec-num').textContent = idx + 1;
            const title = card.querySelector('.section-title-input');
            const content = card.querySelector('.section-content-textarea');
            title.name = `sections[${idx}][title]`;
            title.setAttribute('aria-label', `Section ${idx + 1} heading`);
            content.name = `sections[${idx}][content]`;
            content.setAttribute('aria-label', `Section ${idx + 1} content`);
        });
    }

    form.addEventListener('input', function (e) {
        if (e.target.matches('.section-content-textarea')) resizeTextarea(e.target);
        markChanged();
    });

    container.addEventListener('click', async function (e) {
        const button = e.target.closest('.btn-delete-section');
        if (!button) return;

        const card = button.closest('.live-section-card');
        const title = card.querySelector('.section-title-input');
        const content = card.querySelector('.section-content-textarea');
        const hasText = title.value.trim() !== '' || content.value.trim() !== '';

        if (hasText) {
            const ok = await window.adminConfirm({
                title: 'Remove this section?',
                text: (title.value.trim() || 'This section') + ' will be removed when you save.',
                ok: 'Remove section',
            });
            if (!ok) return;
        }

        if (container.querySelectorAll('.live-section-card').length <= 1) {
            title.value = '';
            content.value = '';
            resizeTextarea(content);
        } else {
            card.remove();
            reindexSections();
        }
        markChanged();
    });

    btnAdd.addEventListener('click', function () {
        const count = container.querySelectorAll('.live-section-card').length;
        const fragment = template.content.cloneNode(true);
        const card = fragment.querySelector('.live-section-card');
        container.appendChild(fragment);

        const title = card.querySelector('.section-title-input');
        title.value = (count + 1) + '. ';
        reindexSections();
        markChanged();

        title.focus();
        title.setSelectionRange(title.value.length, title.value.length);
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    async function performSave() {
        if (isSaving) return;
        if (!form.reportValidity()) return;
        isSaving = true;

        btnSave.classList.add('is-saving');
        saveBtnLabel.textContent = 'Saving…';
        statusText.textContent = 'Saving changes…';
        showError('');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: new FormData(form)
            });

            const result = await response.json().catch(() => ({}));

            if (!response.ok) {
                const firstError = result.errors ? Object.values(result.errors).flat()[0] : null;
                throw new Error(firstError || result.message || 'The page could not be saved. Please try again.');
            }

            markClean();
            btnSave.classList.remove('is-saving');
            btnSave.classList.add('is-saved');
            saveBtnLabel.textContent = 'Saved';

            if (result.last_updated) lastUpdated.textContent = result.last_updated;

            setTimeout(() => {
                btnSave.classList.remove('is-saved');
                saveBtnLabel.textContent = 'Save changes';
            }, 2500);
        } catch (err) {
            btnSave.classList.remove('is-saving');
            saveBtnLabel.textContent = 'Save changes';
            statusText.textContent = 'Not saved';
            showError(err.message);
        } finally {
            isSaving = false;
        }
    }

    btnSave.addEventListener('click', function (e) {
        e.preventDefault();
        performSave();
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        performSave();
    });

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            performSave();
        }
    });

    reindexSections();
    container.querySelectorAll('.section-content-textarea').forEach(resizeTextarea);
});
</script>
