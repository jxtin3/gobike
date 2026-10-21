<?php

it('shows the GoBot chat widget on the home page only', function () {
    $this->get('/')->assertOk()->assertSee('chatbotWidget()', false);

    foreach (['/about', '/news', '/contact', '/what-we-do', '/donate'] as $path) {
        $this->get($path)->assertOk()->assertDontSee('chatbotWidget()', false);
    }
});
