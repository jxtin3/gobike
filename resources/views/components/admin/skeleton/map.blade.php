<div class="sk-stats">
    @for ($i = 0; $i < 5; $i++)
        <div class="sk-card sk-stat">
            <span class="sk sk-avatar" style="border-radius: 8px"></span>
            <span class="sk-stack">
                <span class="sk sk-line" style="--w: 64px"></span>
                <span class="sk sk-line" style="--w: 36px; --h: 18px"></span>
            </span>
        </div>
    @endfor
</div>
<div class="sk-map">
    <div class="sk-card"><span class="sk sk-block" style="--h: 460px; border-radius: 8px"></span></div>
    <div class="sk-card">
        @for ($i = 0; $i < 6; $i++)
            <div class="sk-cell-user" style="padding: 8px 0">
                <span class="sk sk-avatar" style="width: 12px; height: 12px"></span>
                <span class="sk-stack">
                    <span class="sk sk-line" style="--w: 130px"></span>
                    <span class="sk sk-line" style="--w: 90px"></span>
                </span>
            </div>
        @endfor
    </div>
</div>
