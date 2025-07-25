<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tank extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'capacity',
        'current_level',
        'latitude',
        'longitude',
        'is_active',
        'min_threshold',
        'max_threshold',
        'status',
        'last_maintenance',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'decimal:2',
        'current_level' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'min_threshold' => 'decimal:2',
        'max_threshold' => 'decimal:2',
        'last_maintenance' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the valves associated with the tank.
     */
    public function valves(): HasMany
    {
        return $this->hasMany(Valve::class);
    }

    /**
     * Get the sensors associated with the tank.
     */
    public function sensors()
    {
        return $this->morphMany(Sensor::class, 'location');
    }

    /**
     * Check if the tank needs to be refilled.
     *
     * @return bool
     */
    public function needsRefill(): bool
    {
        return $this->current_level <= $this->min_threshold;
    }

    /**
     * Check if the tank is full.
     *
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->current_level >= $this->max_threshold;
    }

    /**
     * Get the fill percentage of the tank.
     *
     * @return float
     */
    public function getFillPercentage(): float
    {
        if ($this->capacity <= 0) {
            return 0;
        }
        
        return min(100, max(0, ($this->current_level / $this->capacity) * 100));
    }

    /**
     * Get the active tank.
     *
     * @return Tank|null
     */
    public static function getActiveTank(): ?Tank
    {
        return self::where('is_active', true)->first();
    }
}
