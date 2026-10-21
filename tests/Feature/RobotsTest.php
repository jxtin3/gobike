<?php

it('blocks crawlers from the admin area', function () {
    expect(file_get_contents(public_path('robots.txt')))
        ->toContain('Disallow: /admin')
        ->toContain('Disallow: /login');
});
