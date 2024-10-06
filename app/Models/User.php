<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    const ROLE_ADMIN = 'Human Resource';

    const ROLE_VICEPRES = 'Vice President';

    const ROLE_PROJECTCLERK = 'Project Clerk';

    const ROLE_ADMINUSER = 'Admin';

    const ROLES = [
        self::ROLE_ADMIN => 'Human Resource',
        self::ROLE_VICEPRES => 'Vice President',
        self::ROLE_PROJECTCLERK => 'Project Clerk',
    ];


    public function canAccessPanel(Panel $panel): bool{
        return true;
    }

    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'Username',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
