<dialog class="dlg" id="confirmDialog" data-tone="danger" aria-labelledby="cf-title" aria-describedby="cf-text">
    <form method="dialog" class="dlg-body">
        <span class="dlg-icon"><x-admin.icon name="alert-circle" /></span>
        <h2 id="cf-title" data-cf-title></h2>
        <p id="cf-text" data-cf-text></p>
        <div class="dlg-actions">
            <button type="submit" value="cancel" class="btn btn-secondary" autofocus>Cancel</button>
            <button type="submit" value="confirm" class="btn btn-danger" data-cf-ok>Confirm</button>
        </div>
    </form>
</dialog>