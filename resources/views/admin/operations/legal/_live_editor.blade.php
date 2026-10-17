<div class="legal-live-editor-root">
    <style>
        .legal-live-wrap {
            max-width: 960px;
            margin: 0 auto;
            font-family: inherit;
        }

        /* Top control bar */
        .legal-top-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
            background: #ffffff;
            padding: 0.85rem 1.25rem;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 1rem;
            z-index: 40;
        }

        .legal-tabs {
            display: inline-flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
            gap: 4px;
        }

        .legal-tab {
            padding: 6px 14px;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            color: #64748b;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .legal-tab:hover {
            color: #0f172a;
        }

        .legal-tab.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .legal-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .legal-status {
            font-size: 0.85rem;
            font-weight: 500;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s ease;
        }

        .legal-status.has-changes {
            color: #d97706;
        }

        .legal-status.saved {
            color: #16a34a;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #94a3b8;
            display: inline-block;
        }

        .legal-status.has-changes .status-dot {
            background: #f59e0b;
            animation: pulse-dot 1.5s infinite;
        }

        .legal-status.saved .status-dot {
            background: #22c55e;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }

        .btn-live-save {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.6rem 1.4rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .btn-live-save:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            transform: translateY(-1px);
        }

        .btn-live-save:active {
            transform: translateY(0);
            background: #f1f5f9;
        }

        .btn-live-save.is-saving {
            opacity: 0.7;
            cursor: wait;
            background: #f8fafc;
        }

        .btn-live-save.is-saved {
            background: #f0fdf4 !important;
            color: #15803d !important;
            border-color: #86efac !important;
        }

        .btn-live-view {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 0.55rem 0.9rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-live-view:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        /* Clean White Document Canvas */
        .live-page-canvas {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            padding: 2.5rem;
            position: relative;
            border: 1px solid #e2e8f0;
            color: #0f172a;
        }

        /* Hero Header Inside White Canvas: Just the title */
        .live-hero {
            position: relative;
            text-align: center;
            max-width: 800px;
            margin: 0 auto 2rem auto;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .live-title-input {
            width: 100%;
            background: transparent;
            border: 2px dashed transparent;
            border-radius: 8px;
            color: #0f172a;
            font-size: 2.25rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.02em;
            text-align: center;
            padding: 0.5rem 0.75rem;
            outline: none;
            font-family: inherit;
            transition: all 0.2s ease;
            line-height: 1.2;
        }

        .live-title-input:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .live-title-input:focus {
            border-color: {{ $accentColor }};
            background: #ffffff;
            border-style: solid;
        }

        /* Sections List */
        .live-sections {
            max-width: 850px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .live-section-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid {{ $accentColor }};
            border-radius: 10px;
            padding: 1.35rem 1.5rem;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        }

        .live-section-card:hover,
        .live-section-card:focus-within {
            border-color: #cbd5e1;
            border-left-color: {{ $accentColor }};
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.07);
        }

        /* Section Card Header */
        .section-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: {{ $accentColor }};
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 3px 9px;
            border-radius: 6px;
        }

        .section-tools {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            opacity: 0.7;
            transition: opacity 0.15s ease;
        }

        .live-section-card:hover .section-tools,
        .live-section-card:focus-within .section-tools {
            opacity: 1;
        }

        .tool-btn {
            background: #f8fafc;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.15s ease;
        }

        .tool-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        .tool-btn.danger:hover {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fca5a5;
        }

        /* Direct Editable Inputs */
        .section-title-input {
            width: 100%;
            background: transparent;
            border: 1px dashed transparent;
            border-radius: 6px;
            color: #0f172a;
            font-size: 1.25rem;
            font-weight: 700;
            padding: 0.4rem 0.6rem;
            margin-bottom: 0.5rem;
            outline: none;
            font-family: inherit;
            transition: all 0.15s ease;
        }

        .section-title-input:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .section-title-input:focus {
            border-color: {{ $accentColor }};
            background: #ffffff;
            border-style: solid;
        }

        .section-content-textarea {
            width: 100%;
            background: transparent;
            border: 1px dashed transparent;
            border-radius: 6px;
            color: #334155;
            font-size: 0.975rem;
            line-height: 1.65;
            padding: 0.5rem 0.6rem;
            outline: none;
            font-family: inherit;
            resize: none;
            overflow: hidden;
            min-height: 70px;
            transition: all 0.15s ease;
            display: block;
        }

        .section-content-textarea:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .section-content-textarea:focus {
            border-color: {{ $accentColor }};
            background: #ffffff;
            border-style: solid;
            color: #0f172a;
        }

        /* Add Section Button */
        .btn-add-section {
            width: 100%;
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            padding: 1rem;
            color: #475569;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .btn-add-section:hover {
            background: #ffffff;
            border-color: {{ $accentColor }};
            color: {{ $accentColor }};
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Keyboard shortcut badge */
        .kbd-hint {
            font-size: 0.75rem;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            margin-left: 0.3rem;
            font-family: monospace;
        }
    </style>

    <div class="legal-live-wrap">
        <!-- Top Sticky Control Bar -->
        <div class="legal-top-bar">
            <!-- Tabs -->
            <div class="legal-tabs">
                <a href="{{ route('admin.operations.legal.terms') }}" class="legal-tab {{ $activeDoc === 'terms' ? 'active' : '' }}">
                    <x-admin.icon name="file-text" /> Terms &amp; Conditions
                </a>
                <a href="{{ route('admin.operations.legal.privacy') }}" class="legal-tab {{ $activeDoc === 'privacy' ? 'active' : '' }}">
                    <x-admin.icon name="shield" /> Privacy Policy
                </a>
            </div>

            <!-- Action Controls -->
            <div class="legal-actions">

                <div class="legal-status" id="save-status">
                    <span class="status-dot"></span>
                    <span id="status-text">All changes saved</span>
                </div>

                <button type="button" class="btn-live-save" id="btn-save" title="Save document (Ctrl + S)">
                    <x-admin.icon name="check" />
                    <span id="save-btn-label">Save Changes</span>
                </button>
            </div>
        </div>

        <!-- Live Editor Form -->
        <form id="legal-form" method="POST" action="{{ $updateRoute }}">
            @csrf
            @method('PUT')

            <!-- Clean White Canvas -->
            <div class="live-page-canvas">
                <!-- Hero Section: Just the Title -->
                <div class="live-hero">
                    <input type="text" 
                           name="title" 
                           id="doc-title"
                           class="live-title-input" 
                           value="{{ $document->title ?? ($activeDoc === 'terms' ? 'Terms & Conditions' : 'Privacy Policy') }}" 
                           placeholder="Document Title" 
                           required 
                           autocomplete="off">

                    <input type="hidden" 
                           name="last_updated" 
                           id="doc-last-updated" 
                           value="{{ $document->last_updated ?? date('F d, Y') }}">
                </div>

                <!-- Sections List -->
                <div class="live-sections" id="sections-container">
                    @forelse ($document->sections ?? [] as $index => $section)
                        <div class="live-section-card" data-section-index="{{ $index }}">
                            <div class="section-header-bar">
                                <div class="section-badge">
                                    Section <span class="sec-num">{{ $index + 1 }}</span>
                                </div>
                                <div class="section-tools">
                                    <button type="button" class="tool-btn danger btn-delete-section" title="Delete section">
                                        <x-admin.icon name="trash" /> Remove
                                    </button>
                                </div>
                            </div>

                            <input type="text" 
                                   name="sections[{{ $index }}][title]" 
                                   class="section-title-input" 
                                   value="{{ $section['title'] ?? '' }}" 
                                   placeholder="Section Heading (e.g. {{ $index + 1 }}. Title)" 
                                   autocomplete="off">

                            <textarea name="sections[{{ $index }}][content]" 
                                      class="section-content-textarea" 
                                      rows="2" 
                                      placeholder="Write section content or policy details here...">{{ $section['content'] ?? '' }}</textarea>
                        </div>
                    @empty
                        <!-- Default Initial Section if none -->
                        <div class="live-section-card" data-section-index="0">
                            <div class="section-header-bar">
                                <div class="section-badge">
                                    Section <span class="sec-num">1</span>
                                </div>
                                <div class="section-tools">
                                    <button type="button" class="tool-btn danger btn-delete-section" title="Delete section">
                                        <x-admin.icon name="trash" /> Remove
                                    </button>
                                </div>
                            </div>

                            <input type="text" 
                                   name="sections[0][title]" 
                                   class="section-title-input" 
                                   value="1. Overview" 
                                   placeholder="Section Heading" 
                                   autocomplete="off">

                            <textarea name="sections[0][content]" 
                                      class="section-content-textarea" 
                                      rows="3" 
                                      placeholder="Write section content or policy details here..."></textarea>
                        </div>
                    @endforelse
                </div>

                <!-- Add Section Button -->
                <div style="max-width: 850px; margin: 1.75rem auto 0 auto;">
                    <button type="button" class="btn-add-section" id="btn-add-section">
                        <x-admin.icon name="plus" /> Add Another Section
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('legal-form');
    const container = document.getElementById('sections-container');
    const btnAdd = document.getElementById('btn-add-section');
    const btnSave = document.getElementById('btn-save');
    const saveBtnLabel = document.getElementById('save-btn-label');
    const statusWrap = document.getElementById('save-status');
    const statusText = document.getElementById('status-text');
    const dateInput = document.getElementById('doc-last-updated');

    let hasUnsavedChanges = false;
    let isSaving = false;

    // Set unsaved status
    function markChanged() {
        if (!hasUnsavedChanges) {
            hasUnsavedChanges = true;
            statusWrap.className = 'legal-status has-changes';
            statusText.textContent = 'Unsaved changes';
            btnSave.classList.remove('is-saved');
        }
    }

    // Mark as clean
    function markClean() {
        hasUnsavedChanges = false;
        statusWrap.className = 'legal-status saved';
        statusText.textContent = 'All changes saved';
    }

    // Auto resize textarea to content
    function resizeTextarea(textarea) {
        if (!textarea) return;
        textarea.style.height = 'auto';
        textarea.style.height = Math.max(60, textarea.scrollHeight + 4) + 'px';
    }

    function initAllTextareas() {
        container.querySelectorAll('.section-content-textarea').forEach(ta => {
            resizeTextarea(ta);
            ta.removeEventListener('input', onTextareaInput);
            ta.addEventListener('input', onTextareaInput);
        });
    }

    function onTextareaInput(e) {
        resizeTextarea(e.target);
        markChanged();
    }

    // Reindex all section inputs and numbers
    function reindexSections() {
        const cards = container.querySelectorAll('.live-section-card');
        cards.forEach((card, idx) => {
            card.dataset.sectionIndex = idx;

            // Number badge
            const numEl = card.querySelector('.sec-num');
            if (numEl) numEl.textContent = idx + 1;

            // Title input
            const titleInput = card.querySelector('.section-title-input');
            if (titleInput) {
                titleInput.name = `sections[${idx}][title]`;
            }

            // Content textarea
            const contentTa = card.querySelector('.section-content-textarea');
            if (contentTa) {
                contentTa.name = `sections[${idx}][content]`;
            }
        });
    }

    // Listen to changes on all inputs
    form.addEventListener('input', function () {
        markChanged();
    });

    // Delegated delete action inside sections container (no popup confirm)
    container.addEventListener('click', function (e) {
        const target = e.target.closest('button');
        if (!target) return;

        const card = target.closest('.live-section-card');
        if (!card) return;

        if (target.classList.contains('btn-delete-section')) {
            const total = container.querySelectorAll('.live-section-card').length;
            if (total <= 1) {
                const titleIn = card.querySelector('.section-title-input');
                const contentTa = card.querySelector('.section-content-textarea');
                if (titleIn) titleIn.value = '';
                if (contentTa) { contentTa.value = ''; resizeTextarea(contentTa); }
                markChanged();
                return;
            }

            card.style.opacity = '0';
            card.style.transform = 'scale(0.97)';
            setTimeout(() => {
                card.remove();
                reindexSections();
                markChanged();
            }, 100);
        }
    });

    // Add Section button
    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            const currentCount = container.querySelectorAll('.live-section-card').length;
            const nextNum = currentCount + 1;

            const card = document.createElement('div');
            card.className = 'live-section-card';
            card.dataset.sectionIndex = currentCount;
            card.innerHTML = `
                <div class="section-header-bar">
                    <div class="section-badge">
                        Section <span class="sec-num">${nextNum}</span>
                    </div>
                    <div class="section-tools">
                        <button type="button" class="tool-btn danger btn-delete-section" title="Delete section">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/></svg> Remove
                        </button>
                    </div>
                </div>
                <input type="text" 
                       name="sections[${currentCount}][title]" 
                       class="section-title-input" 
                       value="${nextNum}. " 
                       placeholder="Section Heading" 
                       autocomplete="off">
                <textarea name="sections[${currentCount}][content]" 
                          class="section-content-textarea" 
                          rows="2" 
                          placeholder="Write section content or policy details here..."></textarea>
            `;

            container.appendChild(card);
            reindexSections();
            initAllTextareas();
            markChanged();

            const newTitleInput = card.querySelector('.section-title-input');
            if (newTitleInput) {
                newTitleInput.focus();
                newTitleInput.setSelectionRange(newTitleInput.value.length, newTitleInput.value.length);
            }
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    // Auto Save / AJAX Save Handler (no popup toast)
    async function performSave() {
        if (isSaving) return;
        isSaving = true;

        btnSave.classList.add('is-saving');
        saveBtnLabel.textContent = 'Saving...';
        statusText.textContent = 'Saving changes...';

        try {
            const formData = new FormData(form);

            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error('Save failed');
            }

            const result = await response.json().catch(() => ({ success: true }));

            // Update state quietly
            markClean();
            btnSave.classList.remove('is-saving');
            btnSave.classList.add('is-saved');
            saveBtnLabel.textContent = 'Saved!';

            if (result.last_updated && dateInput) {
                dateInput.value = result.last_updated;
            }

            setTimeout(() => {
                btnSave.classList.remove('is-saved');
                saveBtnLabel.textContent = 'Save Changes';
            }, 2500);

        } catch (err) {
            console.error('Save failed:', err);
            btnSave.classList.remove('is-saving');
            saveBtnLabel.textContent = 'Save Changes';
            statusText.textContent = 'Save failed';
        } finally {
            isSaving = false;
        }
    }

    // Save button click
    if (btnSave) {
        btnSave.addEventListener('click', function (e) {
            e.preventDefault();
            performSave();
        });
    }

    // Form submit fallback
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        performSave();
    });

    // Keyboard shortcut: Ctrl + S or Cmd + S
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
            e.preventDefault();
            performSave();
        }
    });

    // Initial setup
    reindexSections();
    initAllTextareas();
});
</script>
