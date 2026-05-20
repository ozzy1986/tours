<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TourDeparture extends Model
{
    use HasFactory;

    protected $fillable = [
        'tour_id',
        'starts_on',
        'ends_on',
        'price_cents',
        'currency',
        'seats_total',
        'seats_available',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'price_cents' => 'integer',
        'seats_total' => 'integer',
        'seats_available' => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_on', '>=', Carbon::today());
    }

    public function getPriceAttribute(): float
    {
        return $this->price_cents / 100;
    }
}
