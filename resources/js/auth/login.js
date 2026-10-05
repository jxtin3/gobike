const EXIT_MS = 750;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function showError(message) {
    const box = document.getElementById('login-error');
    if (!box) return;
    box.textContent = message;
    box.classList.remove('hidden');
}

function playExitThenGo(url) {
    const split = document.getElementById('login-split');
    if (!split) {
        window.location.href = url;
        return;
    }

    split.classList.add('is-exiting');
    window.setTimeout(() => {
        window.location.href = url;
    }, EXIT_MS);
}

function setLoading(loading) {
    const button = document.getElementById('login-submit');
    const label = document.getElementById('login-submit-label');
    const spinner = document.getElementById('login-spinner');
    if (button) button.disabled = loading;
    if (label) label.textContent = loading ? 'Signing in…' : 'Sign in';
    spinner?.classList.toggle('hidden', !loading);
}

function bindLoginForm() {
    const form = document.getElementById('login-form');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        setLoading(true);

        const errorBox = document.getElementById('login-error');
        errorBox?.classList.add('hidden');

        const body = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
                body,
            });

            if (response.ok) {
                const data = await response.json();
                playExitThenGo(data.redirect ?? '/admin');
                return;
            }

            let message = 'Unable to sign in. Please try again.';
            try {
                const data = await response.json();
                message = data.message
                    ?? data.errors?.email?.[0]
                    ?? Object.values(data.errors ?? {})?.flat()?.[0]
                    ?? message;
            } catch {
                /* keep default */
            }

            showError(message);
            setLoading(false);
        } catch {
            showError('Network error. Check your connection and try again.');
            setLoading(false);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindLoginForm);
} else {
    bindLoginForm();
}
