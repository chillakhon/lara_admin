<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'email',
        'password',
    ];

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
}
