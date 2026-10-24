{{--
    Toast area. It is present (and empty) from the first paint, so screen readers announce what is added later.
    resources/js/admin/toast.js fills it from <template data-flash> and from window.adminToast(). The markup of one
    toast lives here so the icons come from the same icon set as everything else.
--}}
<div class="toasts" id="toasts" role="status" aria-live="polite" aria-atomic="false"></div>

<template id="toast-template">
    <div class="toast">
        <span class="toast-icon" aria-hidden="true">
            <x-admin.icon name="check-circle" class="toast-icon-success" />
            <x-admin.icon name="alert-circle" class="toast-icon-error" />
            <x-admin.icon name="info" class="toast-icon-info" />
        </span>
        <p class="toast-text" data-toast-text></p>
        <button type="button" class="icon-btn toast-close" aria-label="Dismiss"><x-admin.icon name="x" /></button>
    </div>
</template>
