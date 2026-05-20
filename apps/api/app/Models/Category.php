<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'icon',
        'description',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function tours(): BelongsToMany
    {
        return $this->belongsToMany(Tour::class);
    }
}
