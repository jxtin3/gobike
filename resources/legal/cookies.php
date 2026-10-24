<?php

/*
 * Default Cookie Policy. Published to the legal_documents table by migration and used as the
 * fallback when no database copy exists. After that, edit it in Admin → Legal.
 *
 * Keep this list in sync with the cookies the site actually sets (session, CSRF, remember-me,
 * consent) and with the consent banner in resources/views/partials/cookie-consent.blade.php.
 *
 * Formatting: blank line = new paragraph, "- " = bullet, **bold**, [text](/path or https://…).
 */

return [
    'title' => 'Cookie Policy',
    'last_updated' => 'October 24, 2026',
    'sections' => [
        [
            'title' => '1. What Are Cookies?',
            'content' => <<<'TXT'
Cookies are small text files that a website stores in your browser. They help the website work properly, keep it secure and remember your choices. Similar technologies, such as your browser's local storage, work in a similar way, and in this policy we call them all "cookies".

This Cookie Policy explains which cookies the Go Bike Project website (the "Site") uses and how you can control them. It is part of our [Privacy Policy](/privacy-policy).
TXT,
        ],
        [
            'title' => '2. How We Use Cookies',
            'content' => <<<'TXT'
We keep cookies to a minimum.

- The Site uses only the cookies it needs to work and to stay secure.
- **We do not use advertising cookies**, and we do not use cookies to track you across other websites.
- We do not currently use analytics cookies. If we add them in the future, we will update this policy and ask for your consent first.
- Content from other companies, such as Google Maps, is loaded only if you allow it.
TXT,
        ],
        [
            'title' => '3. Strictly Necessary Cookies',
            'content' => <<<'TXT'
These cookies are needed for the Site to work and cannot be switched off. They are set by our own website and are not used for advertising.

- **gobike-session**: keeps your visit working as you move between pages, for example while you fill in a form, and keeps administrators signed in. It expires after 2 hours of inactivity.
- **XSRF-TOKEN**: a security token that protects our forms against cross-site request forgery attacks. It expires after 2 hours.
- **remember_web_…**: set only when an administrator ticks "Remember me" when signing in, to keep them signed in on that device. It expires after about 400 days, or when they sign out.
- **gobike_cookie_consent**: remembers whether you chose "Accept all" or "Essential only", so we don't ask on every visit. It expires after 6 months.
- **gobike_admin_rail** and **gobike_admin_closed**: set only in the administrators' area, to remember whether the sidebar is collapsed and which menu groups are closed. They contain no personal information and expire after 6 months.

The exact name of the session cookie may differ slightly depending on our server configuration.
TXT,
        ],
        [
            'title' => '4. Third-Party Cookies',
            'content' => <<<'TXT'
Some features use content provided by other companies, which may set their own cookies. We load this content only if you choose "Accept all", or when you ask for it.

- **Google Maps**: the map on our [Contact page](/contact) is provided by Google. When it loads, Google may set cookies and collect information such as your IP address, under Google's Privacy Policy (https://policies.google.com/privacy). If you choose "Essential only", the map is not loaded unless you click "Load map".

When you donate, you are taken to the PayPal or PayMongo website to complete your payment. Those pages are run by the payment providers, which use their own cookies to process payments securely and prevent fraud. Their own cookie and privacy policies apply.
TXT,
        ],
        [
            'title' => '5. GoBot Conversations',
            'content' => <<<'TXT'
GoBot, our website assistant, keeps your current conversation in your browser's memory only while the page is open. It is not stored in a cookie, and it disappears when you leave or reload the page.
TXT,
        ],
        [
            'title' => '6. Managing Your Choices',
            'content' => <<<'TXT'
- Use the **Cookie settings** link at the bottom of any page to change your choice at any time.
- You can also block or delete cookies in your browser settings. If you block strictly necessary cookies, parts of the Site, such as our forms, may not work.
- Choosing "Essential only" does not turn off strictly necessary cookies, because the Site needs them to function.
TXT,
        ],
        [
            'title' => '7. Changes to This Policy',
            'content' => <<<'TXT'
We will update this Cookie Policy if we change the cookies we use. The "Last updated" date at the top of this page shows the current version.
TXT,
        ],
        [
            'title' => '8. Contact Us',
            'content' => <<<'TXT'
Questions about cookies or your privacy? Email us at gobike@pydn.org.
TXT,
        ],
    ],
];
