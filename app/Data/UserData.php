<?php

namespace App\Data;

use App\Enums\UserRole;
use App\Enums\WorkMode;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('User')]
class UserData extends Data
{
    public function __construct(
        public int               $id,
        public string            $name,
        public string            $email,
        public UserRole          $role,
        public ?int              $company_id,
        public ?int              $manager_id,
        public ?string           $avatar,
        public ?int              $work_schedule_id,
        public WorkMode $work_mode,
        public ?string  $pin_code,
        #[Computed]
        public ?CompanyData      $company,
        #[Computed]
        public ?UserBasicData    $manager,
        #[Computed]
        public ?WorkScheduleData $work_schedule,
    )
    {
    }
}
