<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningMaterial extends Model
{
    protected $fillable = [
        'name',
        'description',
        'author',
        'learning_area',
        'grade_level',
        'resource_type',
        'publication_date',
        'publisher',
    ];

    protected function casts(): array
    {
        return [
            'publication_date' => 'date',
        ];
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(LearningMaterialInventory::class);
    }
}
