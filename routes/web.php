<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DonationController as AdminDonationController;
use App\Http\Controllers\Admin\EmergencyAlertController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\GobikerMessageController;
use App\Http\Controllers\Admin\GobikerReportController;
use App\Http\Controllers\Admin\HomepageDataController;
use App\Http\Controllers\Admin\LegalDocumentController as AdminLegalDocumentController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\PatientController as AdminPatientController;
use App\Http\Controllers\Admin\PatientFollowUpController;
use App\Http\Controllers\Admin\PictureController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReplicationRequestController as AdminReplicationRequestController;
use App\Http\Controllers\Admin\SosRequestController;
use App\Http\Controllers\Admin\TrainingRequestController as AdminTrainingRequestController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VolunteerApplicationController as AdminVolunteerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ReplicationRequestController;
use App\Http\Controllers\TrainingRequestController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\Webhook\PayMongoWebhookController;
use App\Http\Controllers\Webhook\PayPalWebhookController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

// pages
Route::get('/', [HomeController::class, 'index']);
Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::view('/our-story', 'News.our-story')->name('our-story');
Route::redirect('/news/our-story', '/our-story', 301);
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

Route::get('/gallery', GalleryController::class);

Route::get('/volunteer', [VolunteerController::class, 'show'])->name('volunteer');

Route::post('/volunteer', [VolunteerController::class, 'store'])
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
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

// chatbot :20 requests/minute per IP
Route::post('/chatbot/message', [ChatbotController::class, 'message'])
    ->middleware('throttle:chatbot')
    ->name('chatbot.message');

// donation
Route::get('/donate', [DonationController::class, 'show'])->name('donate');
Route::post('/donate/checkout', [DonationController::class, 'checkout'])->middleware('throttle:10,1')->name('donate.checkout');
Route::get('/donate/success/{donation}', [DonationController::class, 'success'])->name('donate.success');
Route::get('/donate/cancel/{donation}', [DonationController::class, 'cancel'])->name('donate.cancel');
Route::get('/donate/retry/{donation}', [DonationController::class, 'retry'])->name('donate.retry');
Route::get('/donate/monthly/{donation}', [DonationController::class, 'manageMonthly'])->name('donate.monthly');
Route::post('/donate/monthly/{donation}/cancel', [DonationController::class, 'cancelMonthly'])
    ->middleware('throttle:5,1')
    ->name('donate.monthly.cancel');

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

Route::middleware([EnsureAdmin::class, AuthenticateSession::class])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::view('/live-map', 'admin.live-map')->name('live-map.index');

    Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
    Route::patch('/locations/{location}/status', [LocationController::class, 'updateStatus'])->name('locations.status');
    Route::get('/emergencies', [EmergencyAlertController::class, 'index'])->name('emergencies.index');
    Route::get('/sos-pins', [SosRequestController::class, 'mapPins'])->name('sos-pins.index');

    // profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::prefix('operations')->name('operations.')->group(function () {

        // homepage content
        Route::get('/homepage-data', [HomepageDataController::class, 'index'])->name('homepage-data.index');
        Route::post('/homepage-data/impact-stats', [HomepageDataController::class, 'storeImpactStat'])
            ->name('homepage-data.impact-stats.store');
        Route::put('/homepage-data/impact-stats/{impactStat}', [HomepageDataController::class, 'updateImpactStat'])
            ->name('homepage-data.impact-stats.update');
        Route::delete('/homepage-data/impact-stats/{impactStat}', [HomepageDataController::class, 'destroyImpactStat'])
            ->name('homepage-data.impact-stats.destroy');

        Route::resource('announcements', AnnouncementController::class)->except('show');
        Route::resource('news', AdminNewsController::class);
        Route::resource('pictures', PictureController::class)->only(['index', 'store', 'show', 'destroy']);

        // users
        Route::resource('users', AdminUserController::class);
        Route::patch('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
        Route::delete('/users/{user}/decline', [AdminUserController::class, 'decline'])->name('users.decline');

        // contact form messages
        Route::get('/messages', [ContactMessageController::class, 'index'])->name('messages.index');
        Route::patch('/messages/{message}/read', [ContactMessageController::class, 'markRead'])->name('messages.read');
        Route::delete('/messages/{message}', [ContactMessageController::class, 'destroy'])->name('messages.destroy');

        // GoBiker -> admin messages
        Route::get('/gobiker-messages', [GobikerMessageController::class, 'index'])->name('gobiker-messages.index');
        Route::patch('/gobiker-messages/{message}/read', [GobikerMessageController::class, 'markRead'])->name('gobiker-messages.read');
        Route::delete('/gobiker-messages/{message}', [GobikerMessageController::class, 'destroy'])->name('gobiker-messages.destroy');

        // residents' home-visit requests
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
        Route::patch('/appointments/{appointment}/decline', [AppointmentController::class, 'decline'])->name('appointments.decline');

        // residents' SOS requests
        Route::get('/sos', [SosRequestController::class, 'index'])->name('sos.index');
        Route::get('/sos/{sos}', [SosRequestController::class, 'show'])->name('sos.show');
        Route::patch('/sos/{sos}/status', [SosRequestController::class, 'updateStatus'])->name('sos.status');
        Route::get('/sos/{sos}/gobikers', [SosRequestController::class, 'gobikers'])->name('sos.gobikers');
        Route::delete('/sos/{sos}', [SosRequestController::class, 'destroy'])->name('sos.destroy');

        // patients
        Route::get('/patients', [AdminPatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/{patient}', [AdminPatientController::class, 'show'])->name('patients.show');
        Route::delete('/patients/{patient}', [AdminPatientController::class, 'destroy'])->name('patients.destroy');
        Route::patch('/patients/{patient}/follow-up', [PatientFollowUpController::class, 'update'])->name('patients.follow-up');

        // residents' ratings of the GoBikers who helped them
        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');

        // GoBiker activity reports
        Route::get('/gobiker-reports', [GobikerReportController::class, 'index'])->name('gobiker-reports.index');
        Route::get('/gobiker-reports/{gobiker}', [GobikerReportController::class, 'show'])->name('gobiker-reports.show');

        // donations
        Route::get('/donations', [AdminDonationController::class, 'index'])->name('donations.index');
        Route::post('/donations/{donation}/cancel-subscription', [AdminDonationController::class, 'cancelSubscription'])
            ->name('donations.cancel-subscription');
        Route::delete('/donations/{donation}', [AdminDonationController::class, 'destroy'])->name('donations.destroy');

        // training plan requests
        Route::get('/training-requests', [AdminTrainingRequestController::class, 'index'])->name('training-requests.index');
        Route::get('/training-requests/{trainingRequest}/id/{index}', [AdminTrainingRequestController::class, 'idDocument'])
            ->whereNumber('index')
            ->name('training-requests.id');
        Route::patch('/training-requests/{trainingRequest}/status', [AdminTrainingRequestController::class, 'updateStatus'])
            ->name('training-requests.status');
        Route::delete('/training-requests/{trainingRequest}', [AdminTrainingRequestController::class, 'destroy'])
            ->name('training-requests.destroy');

        // chapter replication inquiries
        Route::get('/chapter-inquiries', [AdminReplicationRequestController::class, 'index'])->name('chapter-inquiries.index');
        Route::get('/chapter-inquiries/{replicationRequest}', [AdminReplicationRequestController::class, 'show'])
            ->name('chapter-inquiries.show');
        Route::patch('/chapter-inquiries/{replicationRequest}', [AdminReplicationRequestController::class, 'update'])
            ->name('chapter-inquiries.update');
        Route::delete('/chapter-inquiries/{replicationRequest}', [AdminReplicationRequestController::class, 'destroy'])
            ->name('chapter-inquiries.destroy');

        // volunteer applications
        Route::get('/volunteers', [AdminVolunteerController::class, 'index'])->name('volunteers.index');
        Route::post('/volunteers/classes', [AdminVolunteerController::class, 'scheduleClass'])->name('volunteers.classes.store');
        Route::patch('/volunteers/classes/{volunteerClass}/complete', [AdminVolunteerController::class, 'completeClass'])->name('volunteers.classes.complete');
        Route::post('/volunteers/classes/{volunteerClass}/email', [AdminVolunteerController::class, 'emailClass'])->name('volunteers.classes.email');
        Route::post('/volunteers/{volunteerApplication}/email-class', [AdminVolunteerController::class, 'emailOne'])->name('volunteers.email-class');
        Route::get('/volunteers/{volunteerApplication}', [AdminVolunteerController::class, 'show'])->name('volunteers.show');
        Route::get('/volunteers/{volunteerApplication}/id/{index}', [AdminVolunteerController::class, 'idDocument'])
            ->whereNumber('index')
            ->name('volunteers.id');
        Route::patch('/volunteers/{volunteerApplication}/status', [AdminVolunteerController::class, 'updateStatus'])
            ->name('volunteers.status');
        Route::delete('/volunteers/{volunteerApplication}', [AdminVolunteerController::class, 'destroy'])
            ->name('volunteers.destroy');

        // legal pages (editable)
        foreach (['privacy' => 'Privacy', 'terms' => 'Terms', 'cookies' => 'Cookies'] as $key => $method) {
            Route::get("/legal/{$key}", [AdminLegalDocumentController::class, "edit{$method}"])->name("legal.{$key}");
            Route::put("/legal/{$key}", [AdminLegalDocumentController::class, "update{$method}"])->name("legal.{$key}.update");
        }
    });
});
