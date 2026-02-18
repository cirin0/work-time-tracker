<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_sees_limited_user_info()
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $otherUser = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($employee, 'api')->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'avatar'],
            ],
        ]);

        // Employee should not see role, work_mode, etc.
        $response->assertJsonMissing(['role']);
        $response->assertJsonMissing(['work_mode']);
        $response->assertJsonMissing(['has_pin_code']);
    }

    public function test_manager_sees_extended_user_info()
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($manager, 'api')->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'work_mode',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

        // Manager should not see role and has_pin_code
        $firstUser = $response->json('data.0');
        $this->assertArrayNotHasKey('role', $firstUser);
        $this->assertArrayNotHasKey('has_pin_code', $firstUser);
    }

    public function test_admin_sees_full_user_info()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $employee = User::factory()->create(['role' => 'employee']);

        $response = $this->actingAs($admin, 'api')->getJson('/api/users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'work_mode',
                    'role',
                    'has_pin_code',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
    }
}
