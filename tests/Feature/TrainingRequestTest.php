<?php

use App\Models\TrainingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sends the visitor back to a thank-you note after a training request', function () {
    $this->from('/what-we-do')
        ->post(route('training-request.store'), [
            'name' => 'Lito Garcia',
            'email' => 'lito@example.com',
            'phone' => '09181234567',
            'address' => 'Purok 3, Barangay Poblacion',
            'tier' => 'standard',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'preferred_window' => 'afternoon',
        ])
        ->assertRedirect(url('/what-we-do').'#training-thanks');

    expect(TrainingRequest::where('email', 'lito@example.com')->exists())->toBeTrue();

    $this->get('/what-we-do')
        ->assertOk()
        ->assertSee('id="training-thanks"', false)
        ->assertSee('Thank you, Lito!')
        ->assertSee('Standard (₱1,500)')
        ->assertSee(now()->addDays(3)->format('D, M j, Y').' · Afternoon')
        ->assertSee('What happens next');
});

it('reopens the form with an error when no plan was chosen', function () {
    $this->from('/what-we-do')
        ->post(route('training-request.store'), [
            'name' => 'Lito Garcia',
            'email' => 'lito@example.com',
            'address' => 'Purok 3, Barangay Poblacion',
        ])
        ->assertRedirect('/what-we-do');

    $this->get('/what-we-do')
        ->assertOk()
        ->assertSee('Please choose a training plan above');
});

it('offers two paid plans and no free plan, starting on a date the person picks', function () {
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class); // several posts in a row
    $this->get('/what-we-do')->assertOk()
        ->assertSee('₱1,500')
        ->assertSee('₱2,500')
        ->assertDontSee("price: 'Free'", false)
        ->assertSee('name="preferred_date"', false)
        ->assertSee(route('volunteer'), false);

    $base = [
        'name' => 'Lito Garcia', 'email' => 'lito@example.com', 'phone' => '09181234567', 'address' => 'Purok 3',
        'preferred_date' => now()->addDays(3)->toDateString(), 'preferred_window' => 'morning',
    ];

    // The free plan is gone, and a date in the past or too far ahead is refused.
    $this->from('/what-we-do')->post(route('training-request.store'), $base + ['tier' => 'basic'])->assertSessionHasErrors('tier');
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'preferred_date' => now()->toDateString()] + $base)->assertSessionHasErrors('preferred_date');
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'preferred_date' => now()->addDays(61)->toDateString()] + $base)->assertSessionHasErrors('preferred_date');
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'preferred_date' => null] + $base)->assertSessionHasErrors('preferred_date');
    expect(TrainingRequest::count())->toBe(0);

    // The phone number is needed to confirm the date.
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'phone' => ''] + $base)->assertSessionHasErrors('phone');
    expect(TrainingRequest::count())->toBe(0);

    $this->from('/what-we-do')->post(route('training-request.store'), $base + ['tier' => 'advance'])->assertSessionHasNoErrors();
    expect(TrainingRequest::first())->tier->toBe('advance')->preferred_window->toBe('morning');

    // The time of day is optional.
    $this->from('/what-we-do')->post(route('training-request.store'), ['tier' => 'standard', 'preferred_window' => null] + $base)->assertSessionHasNoErrors();
    expect(TrainingRequest::latest('id')->first()->schedule_label)->toBe(now()->addDays(3)->format('D, M j, Y'));
});
