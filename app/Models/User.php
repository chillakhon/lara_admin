<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-10-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-10-01T12:00:00Z")
 * )
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    const TYPE_CLIENT = 'user';
    // protected $fillable = [
    //     'email',
    //     'password',
    // ];

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withTimestamps();
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->where('slug', $role)->isNotEmpty();
        }
        return $role->intersect($this->roles)->isNotEmpty();
    }

    public function hasAnyRole($roles)
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        return $this->roles->whereIn('slug', $roles)->isNotEmpty();
    }

    public function hasPermission($permission)
    {
        // Проверяем прямые права пользователя
        if ($this->permissions->where('slug', $permission)->isNotEmpty()) {
            return true;
        }

        // Проверяем права через роли
        foreach ($this->roles as $role) {
            if ($role->permissions->where('slug', $permission)->isNotEmpty()) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission($permissions)
    {
        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function routeNotificationForTelegram()
    {
        return $this->profile()->telegram_user_id;
    }
}
