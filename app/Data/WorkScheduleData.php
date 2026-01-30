<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('WorkSchedule')]
class WorkScheduleData extends Data
{
    public function __construct(
        public int            $id,
        public string         $name,
        public ?string        $description,
        public bool           $is_default,
        public int            $company_id,
        /** @var DataCollection<DailyScheduleData> */
        #[DataCollectionOf(DailyScheduleData::class)]
        public DataCollection $daily_schedules,
    )
    {
    }
}
