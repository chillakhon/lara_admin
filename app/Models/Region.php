<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = "region";

    protected $guarded = ['id'];

    public $timestamps = false;
}
