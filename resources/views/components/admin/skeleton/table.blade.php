<div class="sk-tabs">
    @foreach ([96, 124, 84, 68] as $w)
        <span class="sk sk-pill" style="--w: {{ $w }}px"></span>
    @endforeach
</div>
<div class="sk-toolbar">
    <span class="sk sk-input"></span>
    <span class="sk sk-btn" style="--w: 88px"></span>
</div>
<x-admin.skeleton.rows />
