<?php

use App\Models\LegalDocument;
use App\Models\User;

it('publishes the full privacy policy', function () {
    $this->get(route('privacy-policy'))
        ->assertOk()
        ->assertSee('Data Privacy Act of 2012')
        ->assertSee('Data Protection Officer')
        ->assertSee('National Privacy Commission')
        ->assertSee('mailto:gobike@pydn.org', false);
});

it('publishes the full terms and the donation refund rule', function () {
    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Acceptable Use')
        ->assertSee('<strong>within 30 days</strong>', false)
        ->assertSee('Governing Law and Disputes');
});

it('publishes a cookie policy listing the cookies the site sets', function () {
    $this->get(route('cookie-policy'))
        ->assertOk()
        ->assertSee('Cookie Policy')
        ->assertSee('gobike-session')
        ->assertSee('XSRF-TOKEN')
        ->assertSee('gobike_cookie_consent');
});

it('links every legal page to the other two', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('href="'.route('privacy-policy').'"', false)
        ->assertSee('href="'.route('terms').'"', false)
        ->assertSee('href="'.route('cookie-policy').'"', false);
})->with(['privacy-policy', 'terms', 'cookie-policy']);

it('falls back to the default text when a legal page is not in the database', function () {
    LegalDocument::query()->delete();

    $this->get(route('cookie-policy'))
        ->assertOk()
        ->assertSee('Strictly Necessary Cookies');
});

it('formats the plain text typed in the legal editor', function () {
    $html = LegalDocument::formatPlainText(
        "We collect:\n- **Name**\n- Email, see [Contact](/contact)\n\nWrite to a.b@example.org or https://privacy.gov.ph."
    );

    expect($html)
        ->toContain('<p>We collect:</p><ul><li><strong>Name</strong></li>')
        ->toContain('<a href="/contact">Contact</a>')
        ->toContain('<a href="mailto:a.b@example.org">a.b@example.org</a>')
        ->toContain('<a href="https://privacy.gov.ph" target="_blank" rel="noopener">https://privacy.gov.ph</a>.');
});

it('escapes text and only links safe addresses', function () {
    $html = LegalDocument::formatPlainText('5 < 6 & "quotes" [click](javascript:alert(1))');

    expect($html)
        ->toContain('5 &lt; 6 &amp; &quot;quotes&quot;')
        ->not->toContain('href="javascript');
});

it('shows the cookie banner on public pages', function () {
    $this->get('/about')
        ->assertOk()
        ->assertSee('class="cookie-banner"', false)
        ->assertSee('Accept all')
        ->assertSee('Essential only')
        ->assertSee('Cookie settings');
});

it('loads the google map on the contact page only after consent', function () {
    $this->get('/contact')
        ->assertOk()
        ->assertSee('Load map')
        ->assertSee('x-if="allowed"', false)
        ->assertSee('mailto:gobike@pydn.org', false)
        ->assertDontSee('onegobike@gmail.com');
});

it('lets admins edit the cookie policy', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.operations.legal.cookies'))
        ->assertOk()
        ->assertSee('Cookie Policy');

    $this->actingAs($admin)
        ->put(route('admin.operations.legal.cookies.update'), [
            'title' => 'Cookie Policy',
            'sections' => [['title' => 'Updated', 'content' => 'We use one cookie.']],
        ])
        ->assertRedirect(route('admin.operations.legal.cookies'));

    expect(LegalDocument::getByKey('cookies')->sections)
        ->toBe([['title' => 'Updated', 'content' => 'We use one cookie.']]);
});
