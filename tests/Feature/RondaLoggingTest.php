<?php

use App\Models\Ronda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('records each ronda a GoBiker starts and stops', function () {
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active', 'barangay' => 'Poblacion']);
    Sanctum::actingAs($gobiker);

    $this->postJson(route('api.gobiker.active.start'))->assertOk();

    $ronda = Ronda::sole();
    expect($ronda->user_id)->toBe($gobiker->id)
        ->and($ronda->barangay)->toBe('Poblacion')
        ->and($ronda->ended_at)->toBeNull();

    $this->travel(90)->minutes();
    $this->postJson(route('api.gobiker.active.stop'))->assertOk();

    expect($ronda->fresh()->ended_at)->not->toBeNull()
        ->and($ronda->fresh()->durationMinutes())->toBe(90);
});

it('closes a forgotten ronda when the next one starts', function () {
    $gobiker = User::factory()->create(['role' => 'GoBiker', 'status' => 'Active']);
    Sanctum::actingAs($gobiker);

    $this->postJson(route('api.gobiker.active.start'), ['designated_barangay' => 'Salasa'])->assertOk();
    $this->travel(1)->day();
    $this->postJson(route('api.gobiker.active.start'))->assertOk();

    expect(Ronda::count())->toBe(2)
        ->and(Ronda::whereNull('ended_at')->count())->toBe(1)
        ->and(Ronda::oldest('id')->first()->barangay)->toBe('Salasa');
});
