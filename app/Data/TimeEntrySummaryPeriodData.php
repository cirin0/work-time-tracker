<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TimeEntrySummaryPeriod')]
class TimeEntrySummaryPeriodData extends Data
{
    public function __construct(
        public float $today,
        public float $week,
        public float $month,
    )
    {
    }
}
