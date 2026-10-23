<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'full_name',
        'username',
        'employee_id',
        'email',
        'password',
        'phone',
        'avatar_url',
        'role',
        'status',
        'department',
        'join_date',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'join_date' => 'date',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isFleetManager(): bool
    {
        return in_array($this->role, ['fleet_manager', 'admin']);
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isMechanic(): bool
    {
        return $this->role === 'mechanic';
    }

    public function isDispatcher(): bool
    {
        return in_array($this->role, ['dispatcher', 'admin']);
    }
}
