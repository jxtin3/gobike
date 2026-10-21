<?php

return [
    /*
    | Content-Security-Policy mode: 'report-only' (log violations in the
    | browser console, block nothing), 'enforce', or 'off'.
    */
    'csp_mode' => env('CSP_MODE', 'enforce'),

    /*
    | The origin (scheme + host) of the media storage bucket (e.g. https://bucket.example.com)
    | to be allowed in Content-Security-Policy (img-src, media-src).
    */
    'media_origin' => env('MEDIA_CSP_ORIGIN', ''),
];