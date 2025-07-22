<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     required={"id", "user_id", "bonus_balance", "created_at", "updated_at"},
 *     @OA\Property(property="id", type="integer", description="Unique identifier of the client"),
 *     @OA\Property(property="user_id", type="integer", description="ID of the associated user"),
 *     @OA\Property(property="client_level_id", type="integer", nullable=true, description="ID of the client's level"),
 *     @OA\Property(property="bonus_balance", type="number", format="float", description="The bonus balance of the client"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Timestamp when the client was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Timestamp when the client was last updated"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true, description="Timestamp when the client was deleted, null if not deleted")
 * )
 */
class Client extends Model
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'user_id',
    //     'client_level_id',
    //     'bonus_balance',
    // ];

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
     * Get the user that owns the client.
     */
    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

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
}
