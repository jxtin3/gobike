<?php

use App\Models\TrainingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sends the visitor back to a thank-you note after a training request', function () {
    $this->from('/what-we-do')
        ->post(route('training-request.store'), [
            'name' => 'Lito Garcia',
            'email' => 'lito@example.com',
            'address' => 'Purok 3, Barangay Poblacion',
            'tier' => 'standard',
        ])
        ->assertRedirect(url('/what-we-do').'#training-thanks');

    expect(TrainingRequest::where('email', 'lito@example.com')->exists())->toBeTrue();

    $this->get('/what-we-do')
        ->assertOk()
        ->assertSee('id="training-thanks"', false)
        ->assertSee('Thank you, Lito!')
        ->assertSee('Standard (₱499)')
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
