<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TimeEntrySummary')]
class TimeEntrySummaryData extends Data
{
    public function __construct(
        public int                        $user_id,
        public float                      $total_hours,
        public int                        $total_minutes,
        public int                        $entries_count,
        public float                      $average_work_time,
        public TimeEntrySummaryPeriodData $summary,
    )
    {
    }
}
