<div class="live-section-card" data-section-index="{{ $index }}">
    <div class="section-header-bar">
        <div class="legal-section-badge">
            Section <span class="sec-num">{{ is_int($index) ? $index + 1 : '' }}</span>
        </div>
        <div class="section-tools">
            <button type="button" class="btn btn-danger btn-sm btn-delete-section">
                <x-admin.icon name="trash" /> Remove
            </button>
        </div>
    </div>

    <input type="text"
           name="sections[{{ $index }}][title]"
           class="section-title-input"
           value="{{ $section['title'] ?? '' }}"
           placeholder="Section heading"
           maxlength="255"
           autocomplete="off">

    <textarea name="sections[{{ $index }}][content]"
              class="section-content-textarea"
              rows="2"
              placeholder="Write the section content here…">{{ $section['content'] ?? '' }}</textarea>
</div>
