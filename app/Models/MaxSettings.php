<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaxSettings extends Model
{
    protected $table = 'max_settings';

    protected $fillable = [
        'bot_token',
        'is_active',
    ];

    protected $hidden = [
        'bot_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
