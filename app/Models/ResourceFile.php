<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ResourceFile extends Model
{
    // The $fillable array tells Laravel exactly which columns are allowed to be saved.
    // 'file_type' and 'is_locked' must be here!
    protected $fillable = [
        'folder_id',
        'title',
        'category',
        'file_path',
        'preview_image_path',
        'file_type',
        'is_locked',
        'unlock_starts_at',
        'unlock_ends_at',
    ];

    // Automatically converts 0/1 to false/true in Vue
    protected $casts = [
        'is_locked' => 'boolean',
        'unlock_starts_at' => 'datetime',
        'unlock_ends_at' => 'datetime',
    ];

    protected $appends = [
        'is_effectively_locked',
        'is_temporarily_unlocked',
    ];

    // A file belongs to a specific folder
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function resourceTrackings(): HasMany
    {
        return $this->hasMany(ResourceTracking::class, 'resource_file_id');
    }

    public function isWithinUnlockWindow(?Carbon $referenceTime = null): bool
    {
        if (! $this->unlock_starts_at || ! $this->unlock_ends_at) {
            return false;
        }

        $time = $referenceTime ?? now();

        return $time->greaterThanOrEqualTo($this->unlock_starts_at)
            && $time->lessThanOrEqualTo($this->unlock_ends_at);
    }

    public function isCurrentlyLocked(?Carbon $referenceTime = null): bool
    {
        if (! $this->is_locked) {
            return false;
        }

        return ! $this->isWithinUnlockWindow($referenceTime);
    }

    protected function isEffectivelyLocked(): Attribute
    {
        return Attribute::get(fn () => $this->isCurrentlyLocked());
    }

    protected function isTemporarilyUnlocked(): Attribute
    {
        return Attribute::get(fn () => $this->is_locked && $this->isWithinUnlockWindow());
    }
}
