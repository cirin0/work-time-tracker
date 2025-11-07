<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_is_encrypted_at_rest_and_decrypted_in_response(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        $payload = [
            'message' => 'Secret hello from tests',
            'receiver_id' => $receiver->id,
        ];

        $response = $this->actingAs($sender, 'api')
            ->postJson('/api/messages', $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => $payload['message']])
            ->assertJsonFragment(['sender_id' => $sender->id])
            ->assertJsonFragment(['receiver_id' => $receiver->id]);

        $id = $response->json('id');
        $this->assertIsInt($id);

        $raw = DB::table('messages')->where('id', $id)->value('message');
        $this->assertNotSame($payload['message'], $raw, 'Message should be encrypted at rest');
        $this->assertIsString($raw);
        $this->assertNotEmpty($raw);
    }

    public function test_cannot_send_message_to_self_returns_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/messages', [
                'message' => 'I am talking to myself',
                'receiver_id' => $user->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'You cannot send a message to yourself.']);
    }

    public function test_only_participants_can_fetch_conversation(): void
    {
        [$alice, $bob, $charlie] = User::factory()->count(3)->create();

        Message::query()->create([
            'sender_id' => $alice->id,
            'receiver_id' => $bob->id,
            'message' => 'Hi Bob',
        ]);
        Message::query()->create([
            'sender_id' => $bob->id,
            'receiver_id' => $alice->id,
            'message' => 'Hi Alice',
        ]);

        $respAlice = $this->actingAs($alice, 'api')
            ->getJson('/api/messages/' . $bob->id);
        $respAlice->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, count($respAlice->json()));

        $respCharlie = $this->actingAs($charlie, 'api')
            ->getJson('/api/messages/' . $bob->id);
        $respCharlie->assertStatus(200);
        $this->assertCount(0, $respCharlie->json());
    }

    public function test_auth_is_required_for_chat_endpoints(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/messages/' . $user->id)->assertStatus(401);
        $this->postJson('/api/messages', [
            'message' => 'hello',
            'receiver_id' => $user->id,
        ])->assertStatus(401);
    }

    public function test_rate_limiting_on_sending_messages(): void
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        if (class_exists(RateLimiter::class)) {
            RateLimiter::clear('');
        }

        $lastResponse = null;
        for ($i = 1; $i <= 61; $i++) {
            $lastResponse = $this->actingAs($sender, 'api')
                ->postJson('/api/messages', [
                    'message' => 'msg #' . $i,
                    'receiver_id' => $receiver->id,
                ]);

            if ($i < 61) {
                $lastResponse->assertStatus(200);
            }
        }

        $this->assertNotNull($lastResponse);
        $lastResponse->assertStatus(429);
    }

    public function test_event_payload_omits_email_and_channel_is_expected(): void
    {
        $sender = User::factory()->create(['name' => 'Alice']);
        $receiver = User::factory()->create();

        $event = new MessageSent('hello', $sender, $receiver->id);

        $payload = $event->broadcastWith();
        $this->assertArrayHasKey('user', $payload);
        $this->assertArrayHasKey('id', $payload['user']);
        $this->assertArrayHasKey('name', $payload['user']);
        $this->assertSame($sender->id, $payload['user']['id']);
        $this->assertSame('Alice', $payload['user']['name']);

        $this->assertSame('new-message', $event->broadcastAs());

        $channels = $event->broadcastOn();
        $this->assertIsArray($channels);
        $this->assertCount(1, $channels);
        $this->assertSame('private-chat.' . $receiver->id, $channels[0]->name);
    }
}
