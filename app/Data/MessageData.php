<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Message')]
class MessageData extends Data
{
    public function __construct(
        public int            $id,
        public int            $sender_id,
        public int            $receiver_id,
        public string         $message,
        public string         $created_at,
        #[Computed]
        public ?UserBasicData $sender,
        #[Computed]
        public ?UserBasicData $receiver,
    )
    {
    }
}
