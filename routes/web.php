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
use App\Http\Controllers\Admin\DonationController as AdminDonationController;
use App\Http\Controllers\Admin\TrainingRequestController as AdminTrainingRequestController;
use App\Http\Controllers\Admin\VolunteerApplicationController as AdminVolunteerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ReplicationRequestController;
use App\Http\Controllers\Admin\ReplicationRequestController as AdminReplicationRequestController;
use App\Http\Controllers\TrainingRequestController;
use App\Http\Controllers\Webhook\PayMongoWebhookController;
use App\Http\Controllers\Webhook\PayPalWebhookController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\PatientController as AdminPatientController;

// pages
Route::get('/', [HomeController::class, 'index']);
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::view('/our-story', 'News.our-story')->name('our-story');
Route::redirect('/news/our-story', '/our-story', 301);
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

Route::get('/gallery', GalleryController::class);

Route::view('/volunteer', 'join-the-movement.volunteer')->name('volunteer');

Route::post('/volunteer', [\App\Http\Controllers\VolunteerController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('volunteer.store');
    

Route::view('/about', 'who-we-are.about');

Route::view('/org-structure', 'who-we-are.org-structure');

Route::view('/replicate-go-bike', 'what-we-do.replicate')->name('replicate');

// chapter replication inquiries + early online payment of the training fee
Route::post('/replicate-go-bike/inquiry', [ReplicationRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('replicate.inquiry.store');
Route::get('/replicate-go-bike/inquiry/{replicationRequest}', [ReplicationRequestController::class, 'show'])
    ->name('replicate.inquiry.show');
Route::get('/replicate-go-bike/inquiry/{replicationRequest}/pay', [ReplicationRequestController::class, 'pay'])
    ->middleware('throttle:10,1')
    ->name('replicate.inquiry.pay');

Route::view('/what-we-do', 'what-we-do.programs');

Route::view('/what-we-do/programs/gobike', 'what-we-do.programs.gobike')->name('programs.gobike');

Route::view('/what-we-do/programs/alwar', 'what-we-do.programs.alwar')->name('programs.alwar');

Route::view('/what-we-do/programs/ronda-kalusugan', 'what-we-do.programs.ronda-kalusugan')->name('programs.ronda-kalusugan');

Route::view('/what-we-do/programs/padyakaalaman', 'what-we-do.programs.padyakaalaman')->name('programs.padyakaalaman');

Route::view('/what-we-do/programs/padyakaisipan', 'what-we-do.programs.padyakaisipan')->name('programs.padyakaisipan');

Route::view('/what-we-do/programs/hatake', 'what-we-do.programs.hatake')->name('programs.hatake');

Route::redirect('/programs/gobike', '/what-we-do/programs/gobike');
Route::redirect('/programs/alwar', '/what-we-do/programs/alwar');
Route::redirect('/programs/ronda-kalusugan', '/what-we-do/programs/ronda-kalusugan');
Route::redirect('/programs/padyakaalaman', '/what-we-do/programs/padyakaalaman');
Route::redirect('/programs/padyakaisipan', '/what-we-do/programs/padyakaisipan');
Route::redirect('/programs/hatake', '/what-we-do/programs/hatake');
Route::redirect('/programs', '/what-we-do');

Route::redirect('/community', '/news');

Route::redirect('/reach-us', '/contact');

Route::view('/contact', 'contact');

// limit visitor to send 5 messages/min in contact page
Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

//chatbot :20 requests/minute per IP
Route::post('/chatbot/message', [ChatbotController::class, 'message'])
    ->middleware('throttle:chatbot')
    ->name('chatbot.message');


//donation
Route::get('/donate', [DonationController::class, 'show'])->name('donate');
Route::post('/donate/checkout', [DonationController::class, 'checkout'])->middleware('throttle:10,1')->name('donate.checkout');
Route::get('/donate/success/{donation}', [DonationController::class, 'success'])->name('donate.success');
Route::get('/donate/cancel/{donation}', [DonationController::class, 'cancel'])->name('donate.cancel');
Route::get('/donate/retry/{donation}', [DonationController::class, 'retry'])->name('donate.retry');

Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle']);
Route::post('/webhooks/paymongo', [PayMongoWebhookController::class, 'handle']);

// training plan requests
Route::post('/programs/training-request', [TrainingRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('training-request.store');


// privacy / terms
Route::get('/privacy-policy', [LegalDocumentController::class, 'privacy'])->name('privacy-policy');

Route::get('/terms', [LegalDocumentController::class, 'terms'])->name('terms');

Route::get('/cookie-policy', [LegalDocumentController::class, 'cookies'])->name('cookie-policy');


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
    Route::patch('/admin/locations/{location}/status', [LocationController::class, 'updateStatus'])->name('admin.locations.status');

    Route::get('/admin/profile', [ProfileController::class, 'show'])->name('admin.profile.show');
    Route::get('/admin/profile/edit', [ProfileController::class, 'edit'])->name('admin.profile.edit');
    Route::put('/admin/profile', [ProfileController::class, 'update'])->name('admin.profile.update');

    Route::get('/admin/operations', [OperationsController::class, 'index'])->name('admin.operations');
    Route::get('/admin/operations/reports', [ReportController::class, 'index'])
        ->name('admin.operations.reports.index');
    Route::get('/admin/operations/homepage-data', [HomepageDataController::class, 'index'])
        ->name('admin.operations.homepage-data.index');

    Route::post('/admin/operations/homepage-data/impact-stats', [HomepageDataController::class, 'storeImpactStat'])
        ->name('admin.operations.homepage-data.impact-stats.store');
    Route::put('/admin/operations/homepage-data/impact-stats/{impactStat}', [HomepageDataController::class, 'updateImpactStat'])
        ->name('admin.operations.homepage-data.impact-stats.update');
    Route::delete('/admin/operations/homepage-data/impact-stats/{impactStat}', [HomepageDataController::class, 'destroyImpactStat'])
        ->name('admin.operations.homepage-data.impact-stats.destroy');

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

// donations
    Route::get('/admin/operations/donations', [AdminDonationController::class, 'index'])
        ->name('admin.operations.donations.index');

// training plan requests
    Route::get('/admin/operations/training-requests', [AdminTrainingRequestController::class, 'index'])
        ->name('admin.operations.training-requests.index');
    Route::patch('/admin/operations/training-requests/{trainingRequest}/status', [AdminTrainingRequestController::class, 'updateStatus'])
        ->name('admin.operations.training-requests.status');

// chapter replication inquiries
    Route::get('/admin/operations/chapter-inquiries', [AdminReplicationRequestController::class, 'index'])
        ->name('admin.operations.chapter-inquiries.index');
    Route::get('/admin/operations/chapter-inquiries/{replicationRequest}', [AdminReplicationRequestController::class, 'show'])
        ->name('admin.operations.chapter-inquiries.show');
    Route::patch('/admin/operations/chapter-inquiries/{replicationRequest}', [AdminReplicationRequestController::class, 'update'])
        ->name('admin.operations.chapter-inquiries.update');

// volunteer applications
    Route::get('/admin/operations/volunteers', [AdminVolunteerController::class, 'index'])
        ->name('admin.operations.volunteers.index');
    Route::get('/admin/operations/volunteers/{volunteerApplication}', [AdminVolunteerController::class, 'show'])
        ->name('admin.operations.volunteers.show');
    Route::patch('/admin/operations/volunteers/{volunteerApplication}/status', [AdminVolunteerController::class, 'updateStatus'])
        ->name('admin.operations.volunteers.status');

// legal (editable)
    Route::get('/admin/operations/legal/privacy', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'editPrivacy'])
        ->name('admin.operations.legal.privacy');
    Route::put('/admin/operations/legal/privacy', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'updatePrivacy'])
        ->name('admin.operations.legal.privacy.update');

    Route::get('/admin/operations/legal/terms', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'editTerms'])
        ->name('admin.operations.legal.terms');
    Route::put('/admin/operations/legal/terms', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'updateTerms'])
        ->name('admin.operations.legal.terms.update');

    Route::get('/admin/operations/legal/cookies', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'editCookies'])
        ->name('admin.operations.legal.cookies');
    Route::put('/admin/operations/legal/cookies', [\App\Http\Controllers\Admin\LegalDocumentController::class, 'updateCookies'])
        ->name('admin.operations.legal.cookies.update');

});