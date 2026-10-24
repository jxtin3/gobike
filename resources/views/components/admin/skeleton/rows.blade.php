<div class="table-wrap sk-table">
    <div class="sk-tr sk-tr-head">
        @foreach ([72, 56, 88, 64] as $w)
            <span class="sk sk-line" style="--w: {{ $w }}px"></span>
        @endforeach
    </div>
    @foreach ([150, 120, 170, 140, 160, 130] as $w)
        <div class="sk-tr">
            <span class="sk-cell-user">
                <span class="sk sk-avatar"></span>
                <span class="sk-stack">
                    <span class="sk sk-line" style="--w: {{ $w }}px"></span>
                    <span class="sk sk-line" style="--w: 190px"></span>
                </span>
            </span>
            <span class="sk sk-pill"></span>
            <span class="sk sk-line" style="--w: 110px"></span>
            <span class="sk sk-line" style="--w: 80px"></span>
            <span class="sk sk-icon"></span>
        </div>
    @endforeach
</div>
<div class="sk-pager">
    <span class="sk sk-line" style="--w: 170px"></span>
    <span class="sk sk-line" style="--w: 220px"></span>
</div>
