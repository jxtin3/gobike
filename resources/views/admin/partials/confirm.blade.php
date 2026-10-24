{{-- The one confirm dialog (resources/js/admin/admin.js fills it). The tone picks the icon and the OK button colour. --}}
<dialog class="dlg dlg-sm" id="confirmDialog" data-tone="danger" aria-labelledby="cf-title" aria-describedby="cf-text">
    <form method="dialog" class="dlg-confirm">
        <button type="submit" value="cancel" class="icon-btn dlg-x" aria-label="Close"><x-admin.icon name="x" /></button>
        <span class="dlg-icon" aria-hidden="true">
            <x-admin.icon name="alert-circle" class="dlg-icon-danger" />
            <x-admin.icon name="check-circle" class="dlg-icon-ok" />
            <x-admin.icon name="info" class="dlg-icon-primary" />
        </span>
        <h2 id="cf-title" data-cf-title></h2>
        <p id="cf-text" class="dlg-desc" data-cf-text></p>
        <div class="dlg-actions">
            <button type="submit" value="cancel" class="btn btn-secondary" autofocus>Cancel</button>
            <button type="submit" value="confirm" class="btn btn-danger" data-cf-ok>Confirm</button>
        </div>
    </form>
</dialog>
