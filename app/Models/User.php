<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'email',
        'password',
        'type'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public const TYPE_CLIENT = 'client';
    public const TYPE_ADMIN = 'admin';
    public const TYPE_MANAGER = 'manager';

    public function adminUser()
    {
        return $this->hasOne(AdminUser::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function hasRole($role)
    {
        return $this->type === $role;
    }

    public function hasAnyRole($roles)
    {
        return in_array($this->type, (array) $roles);
    }

    public function hasPermission($permission)
    {
        if ($this->type === 'client') {
            return false;
        }

        return $this->adminUser->permissions && in_array($permission, $this->adminUser->permissions);
    }

    public function isClient(): bool
    {
        return $this->type === self::TYPE_CLIENT;
    }
}
