<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TimeEntry')]
class TimeEntryData extends Data
{
    public function __construct(
        public int            $id,
        public int            $user_id,
        public string         $start_time,
        public ?string        $stop_time,
        public ?int           $duration,
        public ?string        $entry_type,
        public ?array         $location_data,
        public ?string        $start_comment,
        public ?string        $stop_comment,
        public string         $created_at,
        public string         $updated_at,
        #[Computed]
        public ?UserBasicData $user,
    )
    {
    }
}
