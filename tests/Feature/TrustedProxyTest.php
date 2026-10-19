<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('uses the client IP and scheme forwarded by the proxy', function () {
    Route::get('/_proxy-check', fn (Request $request) => response()->json([
        'ip' => $request->ip(),
        'secure' => $request->isSecure(),
    ]));

    $this->withServerVariables([
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ])->getJson('/_proxy-check')
        ->assertOk()
        ->assertJson(['ip' => '203.0.113.7', 'secure' => true]);
});