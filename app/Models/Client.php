<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class Client extends Model
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;


    protected $guarded = ['id'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bonus_balance' => 'decimal:2',
    ];

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
    public function get_full_name()
    {
        return $this?->profile?->getFullNameAttribute();
    }


    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }


    public function routeNotificationForTelegram()
    {
        return $this->profile()->telegram_user_id;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // is not necessary for clients, but in order to not get a error
    // just let it be here
    public function hasAnyRole($roles)
    {
        return false;
    }


    public function promoCodes()
    {
        return $this->belongsToMany(PromoCode::class, 'promo_code_client')
            ->withPivot('birthday_discount', 'notified_at', 'reminder_sent')
            ->withTimestamps();
    }

}
