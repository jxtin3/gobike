<div class="sk-detail">
    <div class="sk-col">
        @foreach ([4, 3, 2] as $rows)
            <div class="sk-card">
                <span class="sk sk-line" style="--w: 150px; margin-bottom: 20px"></span>
                <div class="sk-dl">
                    @for ($i = 0; $i < $rows; $i++)
                        <span class="sk-stack">
                            <span class="sk sk-line" style="--w: 64px"></span>
                            <span class="sk sk-line" style="--w: {{ 110 + $i * 20 }}px"></span>
                        </span>
                    @endfor
                </div>
            </div>
        @endforeach
    </div>
    <div class="sk-col">
        <div class="sk-card">
            <span class="sk sk-line" style="--w: 130px; margin-bottom: 16px"></span>
            <span class="sk sk-pill"></span>
            <span class="sk sk-input" style="margin-top: 16px"></span>
        </div>
        <span class="sk sk-btn" style="--w: 100%"></span>
    </div>
</div>
