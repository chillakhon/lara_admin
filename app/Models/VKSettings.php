<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VKSettings extends Model
{
    protected $table = 'vk_settings';

    protected $fillable = [
        'community_id',
        'access_token',
        'confirmation_token',
        'api_version',
    ];

    protected $hidden = [
        'access_token',
    ];
}
