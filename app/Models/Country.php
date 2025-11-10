<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'squash_remote';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'countries';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'venues_count' => 'integer',
        'population' => 'integer',
        'landarea' => 'integer',
        'center_lat' => 'decimal:8',
        'center_lng' => 'decimal:8',
        'sw_lat' => 'decimal:8',
        'sw_lng' => 'decimal:8',
        'ne_lat' => 'decimal:8',
        'ne_lng' => 'decimal:8',
        'api_display' => 'boolean',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['*'];

    /**
     * Scope a query to only include countries with venues.
     */
    public function scopeWithVenues($query)
    {
        return $query->whereHas('venues');
    }

    /**
     * Scope a query to only include countries visible in API.
     */
    public function scopeApiVisible($query)
    {
        return $query->where('api_display', true);
    }

    /**
     * Get the venues for the country.
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'country_id');
    }

    /**
     * Get the region that owns the country.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}

