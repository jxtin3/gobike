<?php

return [

    /*
    | Online payments (donations through PayPal / PayMongo, and paying the chapter
    | training fee online). Off unless ONLINE_PAYMENTS_ENABLED=true, so the live site
    | never sends people to a payment setup that is not finished. While off, the Donate
    | page explains that online donations are opening soon, checkout is refused, and
    | chapter inquiries are sent without the "pay now" choice.
    |
    | To turn on (on the server): set ONLINE_PAYMENTS_ENABLED=true in .env,
    | then run php artisan config:cache.
    */
    'online' => (bool) env('ONLINE_PAYMENTS_ENABLED', false),

];
