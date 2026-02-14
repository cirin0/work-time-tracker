<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveRequestType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use hasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'processed_by',
        'manager_comment',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    protected function casts(): array
    {
        return [
            'type' => LeaveRequestType::class,
            'status' => LeaveRequestStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
