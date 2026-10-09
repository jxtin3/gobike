<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\HomepageDataController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\PictureController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\Webhook\PayMongoWebhookController;
use App\Http\Controllers\Webhook\PayPalWebhookController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PatientController as AdminPatientController;

// pages
Route::get('/', [HomeController::class, 'index']);
Route::get('/news', [NewsController::class, 'index']);

Route::get('/gallery', function () {
    $pictures = \App\Models\Picture::latest()->paginate(12);
    return view('join-the-movement.gallery', compact('pictures'));
});

Route::get('/volunteer', function () {
    return view('join-the-movement.volunteer');
})->name('volunteer');

Route::post('/volunteer', [\App\Http\Controllers\VolunteerController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('volunteer.store');
    

Route::get('/about', function () {
    return view('who-we-are.about');
});

Route::get('/org-structure', function () {
    return view('who-we-are.org-structure');
});

Route::get('/replicate-go-bike', function () {
    return view('what-we-do.replicate');
})->name('replicate');

Route::get('/what-we-do', function () {
    return view('what-we-do.programs');
});

Route::redirect('/programs', '/what-we-do');

Route::redirect('/community', '/news');

Route::get('/contact', function () {
    return view('contact');
});

// limit visitor to send 5 messages/min in contact page
Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

//chatbot ----20 requests/minute per IP
Route::post('/chatbot/message', [ChatbotController::class, 'message'])
    ->middleware('throttle:20,1')
    ->name('chatbot.message');


//donation
Route::get('/donate', [DonationController::class, 'show'])->name('donate');
Route::post('/donate/checkout', [DonationController::class, 'checkout'])->name('donate.checkout');
Route::get('/donate/success/{donation}', [DonationController::class, 'success'])->name('donate.success');
Route::get('/donate/cancel/{donation}', [DonationController::class, 'cancel'])->name('donate.cancel');
Route::get('/donate/retry/{donation}', [DonationController::class, 'retry'])->name('donate.retry');

Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle']);
Route::post('/webhooks/paymongo', [PayMongoWebhookController::class, 'handle']);


// privacy / terms
Route::get('/privacy-policy', function () {
    return view('pages.privacy');
});

Route::get('/terms', function () {
    return view('pages.terms');
});


// Auth routes
Route::middleware('guest')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware([EnsureAdmin::class])->group(function () {
    Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::view('/admin/live-map', 'admin.live-map')->name('admin.live-map.index');
    Route::get('/admin/locations', [LocationController::class, 'index'])->name('admin.locations.index');
    Route::post('/admin/locations', [LocationController::class, 'store'])->name('admin.locations.store');
    Route::patch('/admin/locations/{location}/status', [LocationController::class, 'updateStatus'])->name('admin.locations.status');
    Route::get('/admin/profile', [ProfileController::class, 'show'])->name('admin.profile.show');
    Route::get('/admin/profile/edit', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::put('/admin/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
    Route::get('/admin/operations', [OperationsController::class, 'index'])->name('admin.operations');
    Route::get('/admin/operations/reports', [ReportController::class, 'index'])
        ->name('admin.operations.reports.index');
    Route::get('/admin/operations/homepage-data', [HomepageDataController::class, 'index'])
        ->name('admin.operations.homepage-data.index');
    Route::post('/admin/operations/homepage-data/partners', [HomepageDataController::class, 'storePartner'])
        ->name('admin.operations.homepage-data.partners.store');
    Route::put('/admin/operations/homepage-data/partners/{partner}', [HomepageDataController::class, 'updatePartner'])
        ->name('admin.operations.homepage-data.partners.update');
    Route::delete('/admin/operations/homepage-data/partners/{partner}', [HomepageDataController::class, 'destroyPartner'])
        ->name('admin.operations.homepage-data.partners.destroy');
    Route::post('/admin/operations/homepage-data/impact-stats', [HomepageDataController::class, 'storeImpactStat'])
        ->name('admin.operations.homepage-data.impact-stats.store');
    Route::put('/admin/operations/homepage-data/impact-stats/{impactStat}', [HomepageDataController::class, 'updateImpactStat'])
        ->name('admin.operations.homepage-data.impact-stats.update');
    Route::delete('/admin/operations/homepage-data/impact-stats/{impactStat}', [HomepageDataController::class, 'destroyImpactStat'])
        ->name('admin.operations.homepage-data.impact-stats.destroy');
    Route::get('/admin/operations/news/homepage', [AdminNewsController::class, 'homepage'])
        ->name('admin.operations.news.homepage');
    Route::put('/admin/operations/news/homepage', [AdminNewsController::class, 'updateHomepage'])
        ->name('admin.operations.news.homepage.update');
    Route::resource('/admin/operations/news', AdminNewsController::class)->names('admin.operations.news');
    Route::resource('/admin/operations/pictures', PictureController::class)->names('admin.operations.pictures');
    Route::resource('/admin/operations/users', AdminUserController::class)->names('admin.operations.users');

//operation
    Route::patch('/admin/operations/users/{user}/approve', [AdminUserController::class, 'approve'])
        ->name('admin.operations.users.approve');

    Route::delete('/admin/operations/users/{user}/decline', [AdminUserController::class, 'decline'])
        ->name('admin.operations.users.decline');

//messages in admin
    Route::get('/admin/operations/messages', [\App\Http\Controllers\Admin\ContactMessageController::class, 'index'])
        ->name('admin.operations.messages.index');

    Route::patch('/admin/operations/messages/{message}/read', [\App\Http\Controllers\Admin\ContactMessageController::class, 'markRead'])
        ->name('admin.operations.messages.read');

    Route::delete('/admin/operations/messages/{message}', [\App\Http\Controllers\Admin\ContactMessageController::class, 'destroy'])
        ->name('admin.operations.messages.destroy');

//patient
    Route::get('/admin/operations/patients', [AdminPatientController::class, 'index'])
        ->name('admin.operations.patients.index');

    Route::get('/admin/operations/patients/{patient}', [AdminPatientController::class, 'show'])
        ->name('admin.operations.patients.show');
    Route::delete('/admin/operations/patients/{patient}', [AdminPatientController::class, 'destroy'])
        ->name('admin.operations.patients.destroy');

// GoBiker → Admin messages
Route::get('/admin/operations/gobiker-messages', [\App\Http\Controllers\Admin\GobikerMessageController::class, 'index'])
    ->name('admin.operations.gobiker-messages.index');

Route::patch('/admin/operations/gobiker-messages/{message}/read', [\App\Http\Controllers\Admin\GobikerMessageController::class, 'markRead'])
    ->name('admin.operations.gobiker-messages.mark-read');
    
Route::delete('/admin/operations/gobiker-messages/{message}', [\App\Http\Controllers\Admin\GobikerMessageController::class, 'destroy'])
    ->name('admin.operations.gobiker-messages.destroy');



});