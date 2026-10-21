<?php

it('renders the branded 404 page for unknown URLs', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('find that page')
        ->assertSee('Go back home');
});

it('renders the branded 404 page for hidden news articles', function () {
    $this->get('/news/not-a-real-article')
        ->assertNotFound()
        ->assertSee('find that page');
});
