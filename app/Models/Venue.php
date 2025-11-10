<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venue extends Model
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
    protected $table = 'venues';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'decimal:10',
        'longitude' => 'decimal:10',
        'elevation' => 'float',
        'no_of_courts' => 'integer',
        'no_of_glass_courts' => 'integer',
        'no_of_non_glass_courts' => 'integer',
        'no_of_outdoor_courts' => 'integer',
        'no_of_doubles_courts' => 'integer',
        'no_of_singles_courts' => 'integer',
        'no_of_hardball_doubles_courts' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'date_approved' => 'datetime',
        'date_flagged_for_deletion' => 'datetime',
        'date_deleted' => 'datetime',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['*'];

    /**
     * Scope a query to only include approved venues.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', '1');
    }

    /**
     * Scope a query to only include venues with coordinates.
     */
    public function scopeWithCoordinates($query)
    {
        return $query->whereNotNull('latitude')
                     ->whereNotNull('longitude');
    }

    /**
     * Scope a query to only include venues with court data.
     */
    public function scopeWithCourts($query)
    {
        return $query->where('no_of_courts', '>', 0);
    }

    /**
     * Get the country that owns the venue.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Get the state that owns the venue.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * Get the category that owns the venue.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(VenueCategory::class, 'category_id');
    }
}

