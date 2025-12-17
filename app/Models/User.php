<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'roles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'roles' => 'array',
        ];
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? []);
    }

    /**
     * Check if user has bayi role
     */
    public function hasBayi(): bool
    {
        return in_array('bayi', $this->roles ?? []);
    }

    /**
     * Check if user has isletme role
     */
    public function hasIsletme(): bool
    {
        return in_array('isletme', $this->roles ?? []);
    }

    /**
     * Check if user has multiple roles
     */
    public function hasMultipleRoles(): bool
    {
        return count($this->roles ?? []) > 1;
    }

    /**
     * Get user's first role
     */
    public function getFirstRole(): ?string
    {
        return $this->roles[0] ?? null;
    }
}
