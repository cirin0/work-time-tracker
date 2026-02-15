<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_time',
        'stop_time',
        'duration',
        'entry_type',
        'location_data',
        'start_comment',
        'stop_comment',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'stop_time' => 'datetime',
        'location_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
