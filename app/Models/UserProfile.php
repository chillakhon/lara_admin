<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    // protected $fillable = [
    //     'user_id',
    //     'first_name',
    //     'last_name',
    //     'phone',
    //     'address',
    // ];

    protected $guarded = ["id"];

    public function user()
    {
        return $this->belongsTo(User::class);
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
