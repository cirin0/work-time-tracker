<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'manager_id',
        'email',
        'phone',
        'address',
        'description',
        'logo',
        'latitude',
        'longitude',
        'radius_meters',
        'qr_secret',
    ];

    protected $hidden = [
        'qr_secret',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function getEmployeeCountAttribute(): int
    {
        return $this->employees()->count();
    }

    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function defaultWorkSchedule()
    {
        return $this->workSchedules()->where('is_default', true)->first();
    }

    public function workSchedules(): HasMany
    {
        return $this->hasMany(WorkSchedule::class);
    }
}
