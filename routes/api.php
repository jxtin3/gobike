<?php

use App\Http\Controllers\Admin\LocationController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\PatientController;


// Go Biker mobile authentication
Route::post('/mobile/login', [
    MobileAuthController::class,
    'login',
])->middleware('throttle:5,1')->name('api.mobile.login');

Route::post('/mobile/register', [
    MobileAuthController::class,
    'register',
])->middleware('throttle:5,1');

//goggle
Route::post('/mobile/google', [
    MobileAuthController::class,
    'google',
])->middleware('throttle:10,1');

Route::get('/mobile/me', [
    MobileAuthController::class,
    'me',
])->middleware('auth:sanctum');

//
Route::post('/mobile/logout', [
    MobileAuthController::class,
    'logout',
])->middleware('auth:sanctum')->name('api.mobile.logout');


//admin
Route::middleware([EnsureAdmin::class])->group(function () {
    Route::get('/locations', [LocationController::class, 'index'])->name('api.locations.index');
    Route::post('/locations', [LocationController::class, 'store'])->name('api.locations.store');
});


Route::middleware(['auth:sanctum', 'gobiker'])->prefix('gobiker')->group(function () {
    Route::post('/location', [
        LocationController::class,
        'updateLocation',
    ])->name('api.gobiker.location');

    
    Route::post('/active/start', [
        LocationController::class,
        'startActiveSession',
    ])->name('api.gobiker.active.start');

    Route::post('/active/stop', [
        LocationController::class,
        'stopActiveSession',
    ])->name('api.gobiker.active.stop');

    Route::post('/emergency', [
        LocationController::class,
        'sendEmergency',
    ])->name('api.gobiker.emergency');

    Route::get('/active', [
        LocationController::class, 
        'currentSession'
    ])->name('api.gobiker.active.current');

    Route::post('message', [\App\Http\Controllers\Api\GobikerController::class, 'sendMessage']);
    

    Route::get('/patients', [PatientController::class, 'index'])->name('api.gobiker.patients.index');
    Route::post('/patients', [PatientController::class, 'store'])->name('api.gobiker.patients.store');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('api.gobiker.patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('api.gobiker.patients.destroy');
    
});