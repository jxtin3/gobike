@php
    $isActive = old('is_active', $announcement ? $announcement->is_active : true);
    $expires = old('expires_at', $announcement?->expires_at?->format('Y-m-d\TH:i'));
@endphp
<form class="form-card" action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <fieldset class="form-section">
        <legend>Message</legend>
        <div class="field">
            <label for="title">Title</label>
            <input class="input @error('title') is-invalid @enderror" id="title" name="title" required maxlength="120"
                   value="{{ old('title', $announcement?->title) }}" placeholder="e.g. Ronda schedule update" aria-describedby="title-error">
            <x-admin.field-error name="title" />
        </div>
        <div class="field">
            <label for="body">Message</label>
            <textarea class="input @error('body') is-invalid @enderror" id="body" name="body" rows="5" required maxlength="1000"
                      placeholder="e.g. Tomorrow's ronda will start at 7:30 AM instead of 8:00 AM." aria-describedby="body-hint body-error">{{ old('body', $announcement?->body) }}</textarea>
            <span class="hint" id="body-hint">Plain text, up to 1,000 characters. Every GoBiker sees this.</span>
            <x-admin.field-error name="body" />
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Visibility</legend>
        <div class="grid-2">
            <div class="field">
                <label for="expires_at">Hide automatically on <span class="muted">(optional)</span></label>
                <input class="input @error('expires_at') is-invalid @enderror" id="expires_at" type="datetime-local" name="expires_at"
                       value="{{ $expires }}" aria-describedby="expires_at-hint expires_at-error">
                <span class="hint" id="expires_at-hint">Leave empty to keep it until you hide or delete it.</span>
                <x-admin.field-error name="expires_at" />
            </div>
            <div class="field">
                <span class="field-title">Show in the GoBiker app</span>
                <label class="check-row" for="is_active">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked($isActive)>
                    <span>Visible to GoBikers</span>
                </label>
                <span class="hint">Untick to save it as a draft or to hide it again.</span>
            </div>
        </div>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-ok" type="submit"><x-admin.icon name="check" /> {{ $button }}</button>
        <a class="btn btn-secondary" href="{{ route('admin.operations.announcements.index') }}">Cancel</a>
    </div>
</form>
