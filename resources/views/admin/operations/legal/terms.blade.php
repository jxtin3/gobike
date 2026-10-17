@extends('admin.operations.layout')
@section('title', 'Edit Terms & Conditions')
@section('heading', 'Terms & Conditions')
@section('subheading', 'Manage and update the Terms & Conditions published on the public website.')

@section('heading-actions')
<a href="{{ url('/terms') }}" target="_blank" class="btn btn-secondary btn-sm">
    <x-admin.icon name="external-link" /> View on website
</a>
@endsection

@section('content')
<div x-data="{
    tab: 'edit',
    title: '{{ addslashes($document->title ?? 'Terms & Conditions') }}',
    lastUpdated: '{{ addslashes($document->last_updated ?? date('F d, Y')) }}',
    sections: {{ json_encode($document->sections ?? []) }},
    addSection() {
        const nextNum = this.sections.length + 1;
        this.sections.push({
            title: nextNum + '. New Section',
            content: ''
        });
    },
    removeSection(index) {
        if (confirm('Are you sure you want to remove this section?')) {
            this.sections.splice(index, 1);
        }
    },
    moveUp(index) {
        if (index > 0) {
            const item = this.sections.splice(index, 1)[0];
            this.sections.splice(index - 1, 0, item);
        }
    },
    moveDown(index) {
        if (index < this.sections.length - 1) {
            const item = this.sections.splice(index, 1)[0];
            this.sections.splice(index + 1, 0, item);
        }
    }
}" class="legal-editor-wrap">

    <!-- Tab Navigation -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid #e2e8f0; padding-bottom:0.75rem;">
        <div style="display:flex; gap:0.5rem;">
            <button type="button" 
                    @click="tab = 'edit'"
                    :class="tab === 'edit' ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm'"
                    style="display:inline-flex; align-items:center; gap:0.4rem;">
                <x-admin.icon name="edit" /> Edit Content
            </button>
            <button type="button" 
                    @click="tab = 'preview'"
                    :class="tab === 'preview' ? 'btn btn-primary btn-sm' : 'btn btn-secondary btn-sm'"
                    style="display:inline-flex; align-items:center; gap:0.4rem;">
                <x-admin.icon name="eye" /> Live Preview
            </button>
        </div>
        <div style="font-size:0.875rem; color:#64748b;">
            <span x-text="sections.length"></span> sections configured
        </div>
    </div>

    <!-- EDIT TAB -->
    <div x-show="tab === 'edit'">
        <form method="POST" action="{{ route('admin.operations.legal.terms.update') }}" class="legal-form">
            @csrf
            @method('PUT')

            <!-- Meta Settings Card -->
            <div class="detail-card" style="margin-bottom:1.5rem;">
                <h3 style="font-size:1.1rem; font-weight:700; color:#0f172a; margin-top:0; margin-bottom:1rem; display:flex; align-items:center; gap:0.5rem;">
                    <x-admin.icon name="file-text" /> Document Settings
                </h3>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:1.25rem;">
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:600; color:#334155; margin-bottom:0.4rem;">
                            Document Title <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               x-model="title" 
                               required 
                               class="input" 
                               style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:0.6rem 0.75rem; font-size:0.95rem; color:#0f172a; background:#fff;"
                               placeholder="e.g. Terms & Conditions">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.875rem; font-weight:600; color:#334155; margin-bottom:0.4rem;">
                            Last Updated Date
                        </label>
                        <input type="text" 
                               name="last_updated" 
                               x-model="lastUpdated" 
                               class="input" 
                               style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:0.6rem 0.75rem; font-size:0.95rem; color:#0f172a; background:#fff;"
                               placeholder="e.g. {{ date('F d, Y') }}">
                        <small style="color:#64748b; font-size:0.75rem; display:block; margin-top:0.25rem;">
                            Shown under the title on the public page. Leave empty to auto-update.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Sections List -->
            <div style="margin-bottom:1.5rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                    <h3 style="font-size:1.1rem; font-weight:700; color:#0f172a; margin:0;">
                        Terms Sections (<span x-text="sections.length"></span>)
                    </h3>
                    <button type="button" @click="addSection()" class="btn btn-secondary btn-sm" style="display:inline-flex; align-items:center; gap:0.35rem;">
                        <x-admin.icon name="plus" /> Add Section
                    </button>
                </div>

                <div style="display:flex; flex-direction:column; gap:1rem;">
                    <template x-for="(section, idx) in sections" :key="idx">
                        <div class="detail-card" style="border-left: 4px solid #f97316; position:relative; padding:1.25rem;">
                            <!-- Header Bar for Section -->
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <span style="display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; background:#fff7ed; color:#f97316; font-weight:700; font-size:0.75rem; border-radius:50%;" x-text="idx + 1"></span>
                                    <span style="font-size:0.875rem; font-weight:600; color:#64748b;" x-text="'Section #' + (idx + 1)"></span>
                                </div>
                                <div style="display:flex; align-items:center; gap:0.35rem;">
                                    <button type="button" @click="moveUp(idx)" :disabled="idx === 0" class="icon-btn" title="Move Up" style="padding:4px; font-size:0.75rem;">
                                        <x-admin.icon name="arrow-up" />
                                    </button>
                                    <button type="button" @click="moveDown(idx)" :disabled="idx === sections.length - 1" class="icon-btn" title="Move Down" style="padding:4px; font-size:0.75rem;">
                                        <x-admin.icon name="arrow-down" />
                                    </button>
                                    <button type="button" @click="removeSection(idx)" class="icon-btn danger" title="Delete Section" style="padding:4px;">
                                        <x-admin.icon name="trash" />
                                    </button>
                                </div>
                            </div>

                            <!-- Section Title -->
                            <div style="margin-bottom:0.75rem;">
                                <label style="display:block; font-size:0.8rem; font-weight:600; color:#475569; margin-bottom:0.3rem;">
                                    Section Heading
                                </label>
                                <input type="text" 
                                       :name="'sections[' + idx + '][title]'" 
                                       x-model="section.title" 
                                       required
                                       style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:0.5rem 0.75rem; font-size:0.95rem; font-weight:600; color:#0f172a; background:#fff;"
                                       placeholder="e.g. 1. Acceptance of Terms">
                            </div>

                            <!-- Section Body -->
                            <div>
                                <label style="display:block; font-size:0.8rem; font-weight:600; color:#475569; margin-bottom:0.3rem;">
                                    Section Content / Paragraphs
                                </label>
                                <textarea :name="'sections[' + idx + '][content]'" 
                                          x-model="section.content" 
                                          rows="4" 
                                          required
                                          style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:0.6rem 0.75rem; font-size:0.9rem; line-height:1.5; color:#334155; background:#fff; resize:vertical;"
                                          placeholder="Enter the detailed terms for this section..."></textarea>
                            </div>
                        </div>
                    </template>
                </div>

                <div style="margin-top:1.25rem; text-align:center;">
                    <button type="button" @click="addSection()" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:0.4rem;">
                        <x-admin.icon name="plus" /> Add Another Section
                    </button>
                </div>
            </div>

            <!-- Action Buttons Footer -->
            <div style="display:flex; justify-content:space-between; align-items:center; background:#fff; padding:1.25rem; border-radius:8px; border:1px solid #e2e8f0; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <a href="{{ url('/terms') }}" target="_blank" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:0.4rem;">
                    <x-admin.icon name="external-link" /> View on Live Website
                </a>
                <button type="submit" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:0.4rem; padding:0.65rem 1.5rem; font-size:0.95rem;">
                    <x-admin.icon name="check" /> Save Terms & Conditions
                </button>
            </div>
        </form>
    </div>

    <!-- PREVIEW TAB -->
    <div x-show="tab === 'preview'" style="display:none;">
        <div style="background:#0D1B2A; color:#fff; border-radius:12px; padding:2.5rem 2rem; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
            <div style="text-align:center; max-width:760px; margin:0 auto 2.5rem;">
                <h1 style="font-size:2.25rem; font-weight:800; text-transform:uppercase; margin-bottom:0.75rem; color:#fff;" x-text="title || 'Terms & Conditions'"></h1>
                <p style="color:#F97316; font-weight:700; font-size:0.875rem; text-transform:uppercase; letter-spacing:0.05em;" x-text="'Last updated: ' + (lastUpdated || 'Today')"></p>
            </div>

            <div style="max-width:760px; margin:0 auto; display:flex; flex-direction:column; gap:2rem;">
                <template x-for="(section, idx) in sections" :key="idx">
                    <div style="padding-bottom:1.5rem; border-bottom:1px solid rgba(255,255,255,0.08);">
                        <h2 style="font-size:1.35rem; font-weight:700; color:#fff; margin-bottom:0.75rem;" x-text="section.title"></h2>
                        <div style="color:rgba(255,255,255,0.75); line-height:1.7; font-size:1rem; white-space:pre-line;" x-text="section.content"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection
