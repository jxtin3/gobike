<?php
use App\Models\VolunteerApplication;


// volunteer
it('volunteer page',
function () {
        $response = $this->get('/volunteer');
        $response->assertStatus(200);
});

// store volunteer page
it('stores a valid volunteer application', function () {
    $response = $this->postJson('/volunteer', [
        'firstName' => 'Test',
        'lastName' => 'Volunteer',
        'email' => 'volunteer@example.com',
        'phone' => '09123456789',
        'age' => 18,
        'status' => 'student',
        'school' => 'Test University',
        'barangay' => 'Test Barangay',
        'municipality' => 'Test Municipality',
        'province' => 'Test Province',
        'availability' => 'weekends',
        'hasBike' => 'yes',
        'motivation' => 'I want to help the community.',
        'emergencyName' => 'Emergency Contact',
        'emergencyPhone' => '09987654321',
        'agree' => true,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Application received.',
        ]);

    $this->assertDatabaseHas('volunteer_applications', [
        'first_name' => 'Test',
        'last_name' => 'Volunteer',
        'email' => 'volunteer@example.com',
        'age' => 18,
        'occupation_status' => 'student',
        'barangay' => 'Test Barangay',
        'municipality' => 'Test Municipality',
        'province' => 'Test Province',
        'availability' => 'weekends',
        'has_bike' => true,
        'emergency_name' => 'Emergency Contact',
        'emergency_phone' => '09987654321',
    ]);
});

// minor volunteer test
it('requires guardian information for a minor volunteer', function () {
    $response = $this->postJson('/volunteer', [
        'firstName' => 'Minor',
        'lastName' => 'Volunteer',
        'email' => 'minor@example.com',
        'phone' => '09123456789',
        'age' => 17,
        'status' => 'student',
        'school' => 'Test High School',
        'barangay' => 'Test Barangay',
        'municipality' => 'Test Municipality',
        'province' => 'Test Province',
        'availability' => 'weekends',
        'hasBike' => 'no',
        'motivation' => 'I want to help.',
        'emergencyName' => 'Emergency Contact',
        'emergencyPhone' => '09987654321',
        'agree' => true,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'guardianName',
            'guardianPhone',
            'guardianConsent',
        ]);
});


// store guardian information with minor (<18)
it('stores a valid minor volunteer application with guardian information', function () {
    $response = $this->postJson('/volunteer', [
        'firstName' => 'Minor',
        'lastName' => 'Volunteer',
        'email' => 'minor@example.com',
        'phone' => '09123456789',
        'age' => 17,
        'status' => 'student',
        'school' => 'Test High School',
        'barangay' => 'Test Barangay',
        'municipality' => 'Test Municipality',
        'province' => 'Test Province',
        'availability' => 'weekends',
        'hasBike' => 'no',
        'motivation' => 'I want to help the community.',
        'emergencyName' => 'Emergency Contact',
        'emergencyPhone' => '09987654321',

        'guardianName' => 'Parent Guardian',
        'guardianPhone' => '09876543210',
        'guardianConsent' => true,

        'agree' => true,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Application received.',
        ]);

    $this->assertDatabaseHas('volunteer_applications', [
        'first_name' => 'Minor',
        'last_name' => 'Volunteer',
        'age' => 17,
        'guardian_name' => 'Parent Guardian',
        'guardian_phone' => '09876543210',
        'guardian_consent' => true,
    ]);
});

// reject a volunteer with no require fields
it('rejects a volunteer application with missing required fields', function () {
    $response = $this->postJson('/volunteer', []);

    $response->assertStatus(422);

    $errors = $response->json('errors');

    expect($errors)->toHaveKeys([
        'firstName',
        'lastName',
        'email',
        'phone',
        'age',
        'status',
        'barangay',
        'municipality',
        'province',
        'availability',
        'hasBike',
        'emergencyName',
        'emergencyPhone',
        'agree',
    ]);
});