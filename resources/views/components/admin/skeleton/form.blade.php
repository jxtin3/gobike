<div class="sk-card sk-form">
    @for ($section = 0; $section < 3; $section++)
        <span class="sk sk-line" style="--w: 120px"></span>
        <div class="sk-fields">
            @for ($field = 0; $field < 2; $field++)
                <span class="sk-stack">
                    <span class="sk sk-line" style="--w: 84px"></span>
                    <span class="sk sk-input" style="--w: 100%"></span>
                </span>
            @endfor
        </div>
    @endfor
    <div class="sk-actions">
        <span class="sk sk-btn" style="--w: 140px"></span>
        <span class="sk sk-btn" style="--w: 90px"></span>
    </div>
</div>
