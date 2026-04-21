<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
        'district',
        'school_name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function resourceTrackings(): HasMany
    {
        return $this->hasMany(ResourceTracking::class);
    }

    public function announcementStates(): HasMany
    {
        return $this->hasMany(AnnouncementUserState::class);
    }

    public function learningMaterialInventories(): HasMany
    {
        return $this->hasMany(LearningMaterialInventory::class);
    }
}
