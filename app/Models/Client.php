<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'client_level_id',
        'bonus_balance',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bonus_balance' => 'decimal:2',
    ];

    /**
     * Get the user that owns the client.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the level that owns the client.
     */
    public function level()
    {
        return $this->belongsTo(ClientLevel::class, 'client_level_id');
    }

    /**
     * Get the orders for the client.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the full name of the client.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return $this->user->profile->full_name;
    }
}
