{{--
    Cookie consent. Strictly necessary cookies (session, CSRF, this choice) always run.
    "Accept all" also allows third-party embeds that set their own cookies, e.g. the Google Map on /contact.
    Other components read the choice with window.gobikeConsent.allowsThirdParty()
    and listen for the "cookie-consent" window event. Keep in sync with resources/legal/cookies.php.
--}}
<script>
    window.gobikeConsent = {
        get() {
            const match = document.cookie.match(/(?:^|;\s*)gobike_cookie_consent=(all|essential)(?:;|$)/);
            return match ? match[1] : null;
        },
        set(value) {
            const secure = location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = 'gobike_cookie_consent=' + value + '; Max-Age=15552000; Path=/; SameSite=Lax' + secure;
            window.dispatchEvent(new CustomEvent('cookie-consent', { detail: value }));
        },
        allowsThirdParty() {
            return this.get() === 'all';
        },
    };
</script>

<div
    x-data="{ open: window.gobikeConsent.get() === null }"
    x-show="open"
    x-cloak
    x-transition.opacity.duration.200ms
    @open-cookie-settings.window="open = true"
    class="cookie-banner"
    role="region"
    aria-label="Cookie preferences"
>
    <p class="cookie-banner-title">Your privacy matters</p>
    <p class="cookie-banner-text">
        We use essential cookies to make this website work. With your permission, we'll also load content
        from other services, such as Google Maps, which may set their own cookies.
        <a href="{{ route('cookie-policy') }}">Read our Cookie Policy</a>
    </p>
    <div class="cookie-banner-actions">
        <button type="button" class="cookie-btn cookie-btn--primary" @click="window.gobikeConsent.set('all'); open = false">
            Accept all
        </button>
        <button type="button" class="cookie-btn cookie-btn--ghost" @click="window.gobikeConsent.set('essential'); open = false">
            Essential only
        </button>
    </div>
</div>
