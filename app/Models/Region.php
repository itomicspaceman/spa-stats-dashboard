<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
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
    protected $table = 'regions';

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
     * Get the countries for the region.
     */
    public function countries(): HasMany
    {
        return $this->hasMany(Country::class, 'region_id');
    }
}

