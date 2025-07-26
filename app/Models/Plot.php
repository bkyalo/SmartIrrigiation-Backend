<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Plot extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'area',
        'crop_type',
        'soil_type',
        'moisture_threshold',
        'irrigation_duration',
        'status',
        'latitude',
        'longitude',
        'geometry',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'area' => 'decimal:2',
        'moisture_threshold' => 'decimal:2',
        'irrigation_duration' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'geometry' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the valves associated with the plot.
     */
    public function valves()
    {
        return $this->belongsToMany(Valve::class);
    }

    /**
     * Get the sensors associated with the plot.
     */
    public function sensors()
    {
        return $this->morphMany(Sensor::class, 'location');
    }

    /**
     * Get the schedules for the plot.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    /**
     * Get the irrigation events for the plot.
     */
    public function irrigationEvents(): HasMany
    {
        return $this->hasMany(IrrigationEvent::class);
    }

    /**
     * Get the latest soil moisture reading.
     */
    public function latestMoistureReading()
    {
        return $this->sensors()
            ->where('type', 'soil_moisture')
            ->with(['readings' => function ($query) {
                $query->latest('recorded_at')->limit(1);
            }])
            ->first()?->readings->first();
    }

    /**
     * Check if the plot needs irrigation.
     *
     * @return bool
     */
    public function needsIrrigation(): bool
    {
        $latestReading = $this->latestMoistureReading();
        
        if (!$latestReading) {
            return false;
        }
        
        return $latestReading->value < $this->moisture_threshold;
    }

    /**
     * Get the active irrigation event for the plot, if any.
     */
    public function activeIrrigationEvent()
    {
        return $this->hasOne(IrrigationEvent::class)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->latest()
            ->limit(1);
    }
    
    /**
     * Get the display name for the irrigation method.
     *
     * @return string
     */
    public function getIrrigationMethodName(): string
    {
        return match($this->irrigation_method) {
            'drip' => 'Drip Irrigation',
            'sprinkler' => 'Sprinkler System',
            'flood' => 'Flood Irrigation',
            'manual' => 'Manual Watering',
            default => ucfirst($this->irrigation_method ?? 'Not Specified'),
        };
    }
    
    /**
     * Get the display name for the status.
     *
     * @return string
     */
    public function getStatusName(): string
    {
        return match($this->status) {
            'idle' => 'Idle',
            'irrigating' => 'Irrigating',
            'scheduled' => 'Scheduled',
            'error' => 'Error',
            'active' => 'Active',
            'fallow' => 'Fallow',
            'maintenance' => 'Maintenance',
            default => ucfirst($this->status ?? 'Unknown'),
        };
    }
}
