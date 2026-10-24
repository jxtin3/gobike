<div class="sk-stack sk-intro">
    <span class="sk sk-line" style="--w: 86%"></span>
    <span class="sk sk-line" style="--w: 52%"></span>
</div>
<div class="sk-dash">
    <div class="sk-kpis">
        @for ($i = 0; $i < 5; $i++)
            <div class="sk-card">
                <span class="sk sk-line" style="--w: 60%"></span>
                <span class="sk sk-line" style="--w: 36%; --h: 28px"></span>
                <span class="sk sk-line" style="--w: 82%"></span>
            </div>
        @endfor
    </div>
    <div class="sk-card sk-donut">
        <span class="sk sk-line" style="--w: 55%"></span>
        <span class="sk sk-disc"></span>
    </div>
</div>
<div class="sk-charts">
    @for ($i = 0; $i < 2; $i++)
        <div class="sk-card">
            <span class="sk sk-line" style="--w: 45%; margin-bottom: 16px"></span>
            <span class="sk sk-block" style="--h: 250px"></span>
        </div>
    @endfor
</div>
