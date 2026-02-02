<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('DailySchedule')]
class DailyScheduleData extends Data
{
    public function __construct(
        public int     $id,
        public string  $day_of_week,
        public ?string $start_time,
        public ?string $end_time,
        public bool    $is_working_day,
    )
    {
    }
}
