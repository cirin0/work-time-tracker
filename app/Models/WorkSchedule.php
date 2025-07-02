<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{

    use HasFactory;

    protected $fillable = [
        'name',
        'company_id',
        'is_default',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isWorkingDay(string $dayOfWeek): bool
    {
        $dailySchedule = $this->getDailySchedule($dayOfWeek);
        return $dailySchedule ? $dailySchedule->is_working_day : false;
    }

    public function getDailySchedule(string $dayOfWeek): ?DailySchedule
    {
        return $this->dailySchedules()->where('day_of_week', $dayOfWeek)->first();
    }

    public function dailySchedules(): HasMany
    {
        return $this->hasMany(DailySchedule::class);
    }

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }
}
