<?php

namespace App\Data;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveRequestType;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('LeaveRequest')]
class LeaveRequestData extends Data
{
    public function __construct(
        public int                $id,
        public int                $user_id,
        public LeaveRequestType   $type,
        public string             $start_date,
        public string             $end_date,
        public ?string            $reason,
        public LeaveRequestStatus $status,
        public ?int               $processed_by_manager_id,
        public ?string            $manager_comment,
        public string             $created_at,
        public string             $updated_at,
        #[Computed]
        public ?UserBasicData     $user,
        #[Computed]
        public ?UserBasicData     $manager,
    )
    {
    }
}
