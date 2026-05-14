<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryMethodByCountry extends Model
{
    protected $table = "delivery_methods_by_countries";

    protected $guarded = ['id'];

    public $timestamps = false;
}
