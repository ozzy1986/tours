<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Tour extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'summary',
        'description',
        'duration_days',
        'route_geojson',
        'cover_url',
        'meta_title',
        'meta_description',
        'published_at',
    ];

    protected $casts = [
        'route_geojson' => 'array',
        'published_at' => 'datetime',
        'duration_days' => 'integer',
    ];

    protected $hidden = [
        'embedding',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(TourPhoto::class)->orderBy('position');
    }

    public function departures(): HasMany
    {
        return $this->hasMany(TourDeparture::class)->orderBy('starts_on');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', Carbon::now());
    }

    /**
     * Order results by cosine similarity to a 384-dim vector (pgvector <=>).
     *
     * @param  array<int, float>  $embedding
     */
    public function scopeOrderByEmbedding(Builder $query, array $embedding): Builder
    {
        $vector = '[' . implode(',', $embedding) . ']';

        return $query
            ->whereNotNull('embedding')
            ->selectRaw('tours.*, embedding <=> ?::vector AS distance', [$vector])
            ->orderByRaw('embedding <=> ?::vector', [$vector]);
    }
}
