<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request, $receiverId)
    {
        $senderId = Auth::id();

        $messages = Message::query()->where(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $senderId)
                ->where('receiver_id', $receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->where('sender_id', $receiverId)
                ->where('receiver_id', $senderId);
        })
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'message' => 'required|string',
            'receiver_id' => 'required|integer|exists:users,id',
        ]);

        $user = Auth::user();

        if ((int)$data['receiver_id'] === (int)$user->id) {
            return response()->json([
                'message' => 'You cannot send a message to yourself.',
            ], 422);
        }

        try {
            $message = DB::transaction(function () use ($user, $data) {
                return Message::query()->create([
                    'sender_id' => $user->id,
                    'receiver_id' => $data['receiver_id'],
                    'message' => $data['message'],
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to send message.',
                'error' => $e->getMessage(),
            ], 500);
        }

        $event = new MessageSent(
            $message->message,
            $user,
            $data['receiver_id']
        );

        broadcast($event)->toOthers();

        return response()->json($message);
    }
}
