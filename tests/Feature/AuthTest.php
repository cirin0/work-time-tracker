<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{

    use RefreshDatabase;

    public function test_user_can_register()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'expires_in',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ]
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials'
            ]);
    }

    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password' => bcrypt('password'),
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'logout@example.com',
            'password' => 'password',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logged out successfully'
            ]);
    }

    public function test_authenticated_user_can_get_their_profile()
    {
        $company = Company::factory()->create();
        $manager = User::factory()->create(['company_id' => $company->id]);
        $workSchedule = WorkSchedule::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'manager_id' => $manager->id,
            'work_schedule_id' => $workSchedule->id,
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'role',
                'avatar',
                'work_mode',
                'has_pin_code',
                'company' => ['id', 'name'],
                'manager' => ['id', 'name'],
                'work_schedule' => ['id', 'name'],
                'created_at',
                'updated_at',
            ])
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
                'manager' => [
                    'id' => $manager->id,
                    'name' => $manager->name,
                ],
                'work_schedule' => [
                    'id' => $workSchedule->id,
                    'name' => $workSchedule->name,
                ],
            ]);
    }

    public function test_registration_requires_name_email_and_password()
    {
        $this->postJson('/api/auth/register')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_registration_requires_a_valid_email()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->postJson('/api/auth/register', $userData)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_fails_if_email_is_already_taken()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $userData = [
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->postJson('/api/auth/register', $userData)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $this->getJson('/api/me')->assertStatus(401);
        $this->postJson('/api/auth/logout')->assertStatus(401);
    }

    public function test_authenticated_user_can_refresh_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'expires_in',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ]
            ]);

        $this->assertNotEquals($token, $response->json('access_token'));
    }

    public function test_registration_accepts_any_password_length()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        // Verify user can login with short password
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => '123',
        ]);

        $loginResponse->assertStatus(200);
    }

    public function test_login_requires_valid_email_format()
    {
        $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_registration_can_set_specific_role()
    {
        $userData = [
            'name' => 'Test Manager',
            'email' => 'manager@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'manager',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'manager@example.com',
            'role' => 'manager',
        ]);
    }

    public function test_registration_with_long_name()
    {
        $userData = [
            'name' => str_repeat('A', 255),
            'email' => 'longname@example.com',
            'password' => 'password',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'longname@example.com',
        ]);
    }

    public function test_refresh_response_contains_new_token_and_user_data()
    {
        $user = User::factory()->create([
            'email' => 'refresh@example.com',
            'password' => bcrypt('password'),
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'refresh@example.com',
            'password' => 'password',
        ]);

        $oldToken = $loginResponse->json('access_token');

        $refreshResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oldToken,
        ])->postJson('/api/auth/refresh');

        $refreshResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'expires_in',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ]
            ]);

        $newToken = $refreshResponse->json('access_token');

        // Verify new token works
        $this->withHeaders(['Authorization' => 'Bearer ' . $newToken])
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJson(['email' => 'refresh@example.com']);
    }

    public function test_authentication_required_for_protected_endpoints()
    {
        $this->getJson('/api/me')
            ->assertStatus(401);

        $this->postJson('/api/auth/logout')
            ->assertStatus(401);
    }

    public function test_registration_with_numeric_name()
    {
        $userData = [
            'name' => '12345',
            'email' => 'numeric@example.com',
            'password' => 'password',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'name' => '12345',
            'email' => 'numeric@example.com',
        ]);
    }
}
