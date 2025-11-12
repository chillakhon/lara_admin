<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $table = 'mail_settings';

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at', 'password'];
}
