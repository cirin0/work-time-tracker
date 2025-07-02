<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_schedule_id',
        'day_of_week',
        'start_time',
        'end_time',
        'break_duration',
        'is_working_day',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'is_working_day' => 'boolean',
        ];
    }
}
