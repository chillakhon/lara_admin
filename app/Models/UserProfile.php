<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;


    protected $guarded = ["id"];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function Country()
    {
        return $this->belongsTo(Country::class, 'delivery_country_id');
    }

    // Связь с City
    public function City()
    {
        return $this->belongsTo(City::class, 'delivery_city_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
