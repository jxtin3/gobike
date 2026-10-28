<?php

// Volunteer sign-ups are closed: the old page sends people to the training plans.
it('sends the old volunteer page to the training plans', function () {
    $this->get('/volunteer')->assertRedirect('/what-we-do#training-plan')->assertStatus(301);
});

it('no longer accepts volunteer applications', function () {
    $this->postJson('/volunteer', ['firstName' => 'Juan'])->assertRedirect('/what-we-do#training-plan');
    expect(\App\Models\VolunteerApplication::count())->toBe(0);
});

it('links "Get Training" in the footer and on the pages that offered volunteering', function () {
    foreach (['/', '/about', '/org-structure', '/news'] as $page) {
        $this->get($page)->assertOk()
            ->assertSee('/what-we-do#training-plan', false)
            ->assertDontSee('href="'.url('/volunteer').'"', false);
    }

    $this->get('/')->assertSee('Get Training');
});
