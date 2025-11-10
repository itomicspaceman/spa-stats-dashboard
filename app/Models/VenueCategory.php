<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VenueCategory extends Model
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
    protected $table = 'venue_categories';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = ['*'];

    /**
     * Get the venues for the category.
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'category_id');
    }
}

