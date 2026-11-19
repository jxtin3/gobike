{{--
    Toast area. It is present (and empty) from the first paint, so screen readers announce what is added later.
    resources/js/admin/toast.js fills it from <template data-flash> and from window.adminToast(). The markup of one
    toast lives here so the icons come from the same icon set as everything else.
    Tone "new" is for something that just came in (live-updates.js): a title, a line about it and a link to open it.
--}}
<div class="toasts" id="toasts" role="status" aria-live="polite" aria-atomic="false"></div>

<template id="toast-template">
    <div class="toast">
        <span class="toast-icon" aria-hidden="true">
            <x-admin.icon name="check-circle" class="toast-icon-success" />
            <x-admin.icon name="alert-circle" class="toast-icon-error" />
            <x-admin.icon name="info" class="toast-icon-info" />
            <x-admin.icon name="bell" class="toast-icon-new" />
        </span>
        <div class="toast-body">
            <b class="toast-title" data-toast-title hidden></b>
            <p class="toast-text" data-toast-text></p>
            <a class="toast-link" data-toast-link hidden></a>
        </div>
        <button type="button" class="icon-btn toast-close" aria-label="Dismiss"><x-admin.icon name="x" /></button>
    </div>
</template>
