<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppRelease extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'channel',
        'version_code',
        'version_name',
        'apk_path',
        'changelog',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'version_code' => 'integer',
        ];
    }
}

