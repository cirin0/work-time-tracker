<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PinCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_setup_initial_pin_code(): void
    {
        // Створюємо користувача БЕЗ пін-коду
        $user = User::factory()->create(['pin_code' => null]);

        $response = $this->actingAs($user, 'api')->postJson('/api/me/pin-code', [
            'pin_code' => '1234',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Pin code setup successfully']);

        $user->refresh();
        $this->assertTrue(Hash::check('1234', $user->pin_code));
    }

    public function test_user_cannot_setup_pin_code_if_already_exists(): void
    {
        $user = User::factory()->create(['pin_code' => '1111']);

        $response = $this->actingAs($user, 'api')->postJson('/api/me/pin-code', [
            'pin_code' => '2222',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['message' => 'Pin code is already set.']);
    }

    public function test_user_can_change_existing_pin_code(): void
    {
        $user = User::factory()->create(['pin_code' => '1111']);

        $response = $this->actingAs($user, 'api')->patchJson('/api/me/pin-code', [
            'current_pin_code' => '1111',
            'new_pin_code' => '2222',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Pin code changed successfully']);

        $user->refresh();
        $this->assertTrue(Hash::check('2222', $user->pin_code));
    }

    public function test_user_cannot_change_pin_code_with_invalid_current_pin(): void
    {
        $user = User::factory()->create(['pin_code' => '1111']);

        $response = $this->actingAs($user, 'api')->patchJson('/api/me/pin-code', [
            'current_pin_code' => 'wrong',
            'new_pin_code' => '2222',
        ]);

        $response->assertStatus(422); // Validation error (size:4)

        $response = $this->actingAs($user, 'api')->patchJson('/api/me/pin-code', [
            'current_pin_code' => '0000',
            'new_pin_code' => '2222',
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['message' => 'The current pin code is incorrect.']);
    }

    public function test_pin_code_validation_rules(): void
    {
        $user = User::factory()->create(['pin_code' => null]);

        // Wrong size
        $this->actingAs($user, 'api')->postJson('/api/me/pin-code', ['pin_code' => '123'])
            ->assertStatus(422);

        // Not digits
        $this->actingAs($user, 'api')->postJson('/api/me/pin-code', ['pin_code' => 'abcd'])
            ->assertStatus(422);
    }
}
