<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'url',
        'alt',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
