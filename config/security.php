<?php

return [
    /*
    | Content-Security-Policy mode: 'report-only' (log violations in the
    | browser console, block nothing), 'enforce', or 'off'.
    */
    'csp_mode' => env('CSP_MODE', 'enforce'),
];