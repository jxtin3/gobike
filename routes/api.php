<?php

use App\Http\Controllers\Api\GobikerAnnouncementController;
use App\Http\Controllers\Api\GobikerController;
use App\Http\Controllers\Api\GobikerLocationController;
use App\Http\Controllers\Api\GobikerProfileController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ResidentController;
use Illuminate\Support\Facades\Route;

// Go Biker mobile authentication
Route::post('/mobile/login', [
    MobileAuthController::class,
    'login',
])->middleware('throttle:5,1')->name('api.mobile.login');

Route::post('/mobile/register', [
    MobileAuthController::class,
    'register',
])->middleware('throttle:5,1');

// goggle
Route::post('/mobile/google', [
    MobileAuthController::class,
    'google',
])->middleware('throttle:10,1');

Route::get('/mobile/me', [
    MobileAuthController::class,
    'me',
])->middleware(['auth:sanctum', 'throttle:mobile']);

Route::put('/mobile/password', [
    MobileAuthController::class,
    'updatePassword',
])->middleware(['auth:sanctum', 'throttle:5,1'])->name('api.mobile.password');

//
Route::post('/mobile/logout', [
    MobileAuthController::class,
    'logout',
])->middleware('auth:sanctum')->name('api.mobile.logout');

Route::middleware(['auth:sanctum', 'gobiker', 'throttle:mobile'])->prefix('gobiker')->group(function () {
    Route::put('/profile', [GobikerProfileController::class, 'update'])->name('api.gobiker.profile.update');

    Route::post('/location', [
        GobikerLocationController::class,
        'updateLocation',
    ])->name('api.gobiker.location');

    Route::post('/active/start', [
        GobikerLocationController::class,
        'startActiveSession',
    ])->name('api.gobiker.active.start');

    Route::post('/active/stop', [
        GobikerLocationController::class,
        'stopActiveSession',
    ])->name('api.gobiker.active.stop');

    Route::post('/emergency', [
        GobikerLocationController::class,
        'sendEmergency',
    ])->name('api.gobiker.emergency');

    Route::get('/announcements', [GobikerAnnouncementController::class, 'index'])->name('api.gobiker.announcements');

    Route::get('/emergency', [
        GobikerLocationController::class,
        'emergencyStatus',
    ])->name('api.gobiker.emergency.status');

    Route::get('/active', [
        GobikerLocationController::class,
        'currentSession',
    ])->name('api.gobiker.active.current');

    Route::post('message', [GobikerController::class, 'sendMessage']);

    Route::get('/patients', [PatientController::class, 'index'])->name('api.gobiker.patients.index');
    Route::post('/patients', [PatientController::class, 'store'])->name('api.gobiker.patients.store');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('api.gobiker.patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('api.gobiker.patients.destroy');

});

// Residents (role "User")
Route::middleware(['auth:sanctum', 'resident', 'throttle:mobile'])->prefix('resident')->group(function () {
    Route::put('/profile', [ResidentController::class, 'updateProfile'])->name('api.resident.profile.update');
    Route::get('/visits', [ResidentController::class, 'visits'])->name('api.resident.visits');
});
