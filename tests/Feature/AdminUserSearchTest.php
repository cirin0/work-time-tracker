<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->company = Company::factory()->create();
});

test('admin can search users by name', function () {
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

    actingAs($this->admin, 'api')
        ->getJson('/api/admin/users?search=John')
        ->assertOk()
        ->assertJsonFragment(['name' => 'John Doe'])
        ->assertJsonMissing(['name' => 'Jane Smith']);
});

test('admin can search users by email', function () {
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

    actingAs($this->admin, 'api')
        ->getJson('/api/admin/users?search=jane@example')
        ->assertOk()
        ->assertJsonFragment(['email' => 'jane@example.com'])
        ->assertJsonMissing(['email' => 'john@example.com']);
});

test('admin can search users by partial match', function () {
    User::factory()->create([
        'name' => 'Alexander Johnson',
        'email' => 'alex@example.com',
        'company_id' => $this->company->id,
    ]);

    User::factory()->create([
        'name' => 'Alexandra Smith',
        'email' => 'alexandra@example.com',
        'company_id' => $this->company->id,
    ]);

    actingAs($this->admin, 'api')
        ->getJson('/api/admin/users?search=alex')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Alexander Johnson'])
        ->assertJsonFragment(['name' => 'Alexandra Smith']);
});

test('admin search is case insensitive', function () {
    User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'company_id' => $this->company->id,
    ]);

    actingAs($this->admin, 'api')
        ->getJson('/api/admin/users?search=JOHN')
        ->assertOk()
        ->assertJsonFragment(['name' => 'John Doe']);
});

test('admin can search users by company', function () {
    $company2 = Company::factory()->create();

    User::factory()->create([
        'name' => 'Company1 User',
        'email' => 'user1@company1.com',
        'company_id' => $this->company->id,
    ]);

    User::factory()->create([
        'name' => 'Company2 User',
        'email' => 'user2@company2.com',
        'company_id' => $company2->id,
    ]);

    actingAs($this->admin, 'api')
        ->getJson("/api/admin/companies/{$this->company->id}/users?search=Company1")
        ->assertOk()
        ->assertJsonFragment(['name' => 'Company1 User'])
        ->assertJsonMissing(['name' => 'Company2 User']);
});

test('admin gets all users when no search query provided', function () {
    User::factory()->count(3)->create(['company_id' => $this->company->id]);

    $response = actingAs($this->admin, 'api')
        ->getJson('/api/admin/users')
        ->assertOk();

    // Should have at least 4 users (3 created + 1 admin)
    expect($response->json('data'))->toHaveCount(4);
});

test('employee cannot search users', function () {
    $employee = User::factory()->create([
        'role' => UserRole::EMPLOYEE,
        'company_id' => $this->company->id,
    ]);

    actingAs($employee, 'api')
        ->getJson('/api/admin/users?search=test')
        ->assertForbidden();
});
