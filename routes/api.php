<?php

use App\Http\Controllers\Api\GobikerAnnouncementController;
use App\Http\Controllers\Api\GobikerAppointmentController;
use App\Http\Controllers\Api\GobikerController;
use App\Http\Controllers\Api\GobikerLocationController;
use App\Http\Controllers\Api\GobikerProfileController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ResidentAppointmentController;
use App\Http\Controllers\Api\ResidentController;
use App\Http\Controllers\Api\ResidentSosController;
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

Route::middleware(['auth:sanctum', 'throttle:mobile'])->group(function () {
    Route::post('/mobile/device-token', [MobileAuthController::class, 'storeDeviceToken'])->name('api.mobile.device-token.store');
    Route::delete('/mobile/device-token', [MobileAuthController::class, 'destroyDeviceToken'])->name('api.mobile.device-token.destroy');
    Route::put('/mobile/notification-preferences', [MobileAuthController::class, 'updateNotificationPrefs'])->name('api.mobile.notification-prefs');
});

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

    Route::get('/appointments', [GobikerAppointmentController::class, 'index'])->name('api.gobiker.appointments.index');
    Route::post('/appointments/{appointment}/accept', [GobikerAppointmentController::class, 'accept'])->name('api.gobiker.appointments.accept');
    Route::post('/appointments/{appointment}/complete', [GobikerAppointmentController::class, 'complete'])->name('api.gobiker.appointments.complete');

});

// Residents (role "User")
Route::middleware(['auth:sanctum', 'resident', 'throttle:mobile'])->prefix('resident')->group(function () {
    Route::put('/profile', [ResidentController::class, 'updateProfile'])->name('api.resident.profile.update');
    Route::get('/visits', [ResidentController::class, 'visits'])->name('api.resident.visits');

    Route::get('/sos', [ResidentSosController::class, 'index'])->name('api.resident.sos.index');
    // Few per minute, so a stuck button or a prank can't flood the admin alarm.
    Route::post('/sos', [ResidentSosController::class, 'store'])->middleware('throttle:3,1')->name('api.resident.sos.store');
    Route::post('/sos/{sos}/media', [ResidentSosController::class, 'media'])->middleware('throttle:10,1')->name('api.resident.sos.media');
    Route::post('/sos/{sos}/resolve', [ResidentSosController::class, 'resolve'])->name('api.resident.sos.resolve');

    Route::get('/appointments', [ResidentAppointmentController::class, 'index'])->name('api.resident.appointments.index');
    Route::post('/appointments', [ResidentAppointmentController::class, 'store'])->middleware('throttle:5,1')->name('api.resident.appointments.store');
    Route::post('/appointments/{appointment}/cancel', [ResidentAppointmentController::class, 'cancel'])->name('api.resident.appointments.cancel');
});
