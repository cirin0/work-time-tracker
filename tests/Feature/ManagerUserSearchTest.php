<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'company_id' => $this->company->id,
    ]);
});

test('manager can search company users by name', function () {
    User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'company_id' => $this->company->id,
    ]);

    User::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'company_id' => $this->company->id,
    ]);

    actingAs($this->manager, 'api')
        ->getJson('/api/managers/users?search=John')
        ->assertOk()
        ->assertJsonFragment(['name' => 'John Doe'])
        ->assertJsonMissing(['name' => 'Jane Smith']);
});

test('manager can search company users by email', function () {
    User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'company_id' => $this->company->id,
    ]);

    User::factory()->create([
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'company_id' => $this->company->id,
    ]);

    actingAs($this->manager, 'api')
        ->getJson('/api/managers/users?search=jane@')
        ->assertOk()
        ->assertJsonFragment(['email' => 'jane@example.com'])
        ->assertJsonMissing(['email' => 'john@example.com']);
});

test('manager search is case insensitive', function () {
    User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'company_id' => $this->company->id,
    ]);

    actingAs($this->manager, 'api')
        ->getJson('/api/managers/users?search=JOHN')
        ->assertOk()
        ->assertJsonFragment(['name' => 'John Doe']);
});

test('manager only sees users from their company', function () {
    $otherCompany = Company::factory()->create();

    User::factory()->create([
        'name' => 'Same Company User',
        'email' => 'same@example.com',
        'company_id' => $this->company->id,
    ]);

    User::factory()->create([
        'name' => 'Other Company User',
        'email' => 'other@example.com',
        'company_id' => $otherCompany->id,
    ]);

    actingAs($this->manager, 'api')
        ->getJson('/api/managers/users?search=User')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Same Company User'])
        ->assertJsonMissing(['name' => 'Other Company User']);
});

test('manager gets all company users when no search query provided', function () {
    User::factory()->count(3)->create(['company_id' => $this->company->id]);

    $response = actingAs($this->manager, 'api')
        ->getJson('/api/managers/users')
        ->assertOk();

    // Should have at least 4 users (3 created + 1 manager)
    expect($response->json('data'))->toHaveCount(4);
});
