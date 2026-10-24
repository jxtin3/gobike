{{--
    Detail dialog, opened from a table row by resources/js/admin/admin.js (tr[data-dialog="#id"]) and filled from the
    row's data-* attributes. One anatomy everywhere: a head (kicker, title, status, close), a body that scrolls, and a
    foot with the destructive action on the left and Close / the main action on the right.

    id     dialog id, e.g. messageDialog (the title gets "{id}-title")
    kicker small label above the title, e.g. "Contact message"
    field  the data-field that fills the title
    size   sm | lg
    <x-slot:sub>  under the title (status pill)
    <x-slot:foot> footer buttons: <form class="dlg-foot-danger"> for delete, <div class="dlg-foot-main"> for the rest
--}}
@props(['id', 'kicker' => null, 'field' => 'name', 'size' => 'lg'])
<dialog id="{{ $id }}" class="dlg dlg-{{ $size }} msg-modal" aria-labelledby="{{ $id }}-title">
    <header class="dlg-head">
        <div class="dlg-head-main">
            @if ($kicker)<p class="dlg-kicker">{{ $kicker }}</p>@endif
            <h2 id="{{ $id }}-title" data-field="{{ $field }}"></h2>
            @isset($sub)<div class="dlg-sub">{{ $sub }}</div>@endisset
        </div>
        <button type="button" class="icon-btn" data-dialog-close aria-label="Close"><x-admin.icon name="x" /></button>
    </header>

    <div class="dlg-content">
        {{ $slot }}
        <p class="alert error" data-dialog-error role="alert" hidden><x-admin.icon name="alert-circle" /><span data-dialog-error-text></span></p>
    </div>

    @isset($foot)
        <footer class="dlg-foot">{{ $foot }}</footer>
    @endisset
</dialog>
