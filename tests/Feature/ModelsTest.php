<?php

use App\Models\Company;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user has pin_code and it is hidden', function () {
    $user = User::factory()->create([
        'pin_code' => '1234',
    ]);

    expect($user->pin_code)->not->toBeNull();

    $array = $user->toArray();
    expect($array)->not->toHaveKey('pin_code');
});

test('company has new fields and qr_secret is hidden', function () {
    $company = Company::factory()->create([
        'latitude' => 50.4501,
        'longitude' => 30.5234,
        'radius_meters' => 100,
        'qr_secret' => 'secret-123',
    ]);

    expect((float)$company->latitude)->toBe(50.4501)
        ->and((float)$company->longitude)->toBe(30.5234)
        ->and($company->radius_meters)->toBe(100)
        ->and($company->qr_secret)->toBe('secret-123');

    $array = $company->toArray();
    expect($array)->not->toHaveKey('qr_secret');
});

test('time entry has new fields and location_data is cast to array', function () {
    $location = ['lat' => 50.4501, 'lng' => 30.5234];
    $timeEntry = TimeEntry::factory()->create([
        'entry_type' => 'qr',
        'location_data' => $location,
    ]);

    expect($timeEntry->entry_type)->toBe('qr')
        ->and($timeEntry->location_data)->toBe($location)
        ->and($timeEntry->location_data)->toBeArray();
});
