<div class="sk-card sk-bar">
    @foreach ([96, 80, 110] as $w)
        <span class="sk sk-pill" style="--w: {{ $w }}px"></span>
    @endforeach
    <span class="sk sk-btn" style="margin-left: auto"></span>
</div>
<div class="sk-card sk-doc">
    <span class="sk sk-title" style="--w: 55%; margin: 0 auto 32px"></span>
    @for ($i = 0; $i < 3; $i++)
        <div class="sk-card">
            <span class="sk sk-line" style="--w: 38%; --h: 18px; margin-bottom: 14px"></span>
            <span class="sk sk-line" style="--w: 100%; margin-bottom: 8px"></span>
            <span class="sk sk-line" style="--w: 92%; margin-bottom: 8px"></span>
            <span class="sk sk-line" style="--w: 64%"></span>
        </div>
    @endfor
</div>
