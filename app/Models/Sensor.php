<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Plot;
use App\Models\Tank;

class Sensor extends Model
{
    use SoftDeletes;

    /**
     * The sensor types.
     */
    public const TYPE_SOIL_MOISTURE = 'soil_moisture';
    public const TYPE_TEMPERATURE = 'temperature';
    public const TYPE_HUMIDITY = 'humidity';
    public const TYPE_RAINFALL = 'rainfall';
    public const TYPE_WATER_LEVEL = 'water_level';
    public const TYPE_FLOW = 'flow';
    public const TYPE_PRESSURE = 'pressure';
    public const TYPE_PH = 'ph';
    public const TYPE_EC = 'ec';
    public const TYPE_LIGHT = 'light';

    /**
     * The location types for the sensor.
     */
    public const LOCATION_TYPE_TANK = 'tank';
    public const LOCATION_TYPE_PLOT = 'plot';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'external_device_id',
        'type',
        'location_type',
        'location_id',
        'reading_interval',
        'last_reading_at',
        'status',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'reading_interval' => 'integer',
        'last_reading_at' => 'datetime',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'active',
        'reading_interval' => 300, // 5 minutes in seconds
        'metadata' => '{}',
    ];

    /**
     * Get the location that owns the sensor.
     */
    public function location(): MorphTo
    {
        // Use a simpler morphTo without the type map
        return $this->morphTo('location', 'location_type', 'location_id');
    }
    
    /**
     * Get the location type in the correct case.
     */
    public function getLocationTypeAttribute($value)
    {
        // Ensure the type is in lowercase to match the morph map
        return strtolower($value);
    }
    
    /**
     * Set the location type in the correct case.
     */
    public function setLocationTypeAttribute($value)
    {
        // Ensure the type is in lowercase to match the morph map
        $this->attributes['location_type'] = strtolower($value);
    }

    /**
     * Get the readings for the sensor.
     */
    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    /**
     * Get the latest reading for the sensor.
     */
    public function latestReading()
    {
        return $this->hasOne(SensorReading::class)->latest('recorded_at');
    }

    /**
     * Get the latest readings for the sensor.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function latestReadings(int $limit = 10)
    {
        return $this->hasMany(SensorReading::class)
            ->latest('recorded_at')
            ->limit($limit);
    }

    /**
     * Scope a query to only include sensors of a given type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include active sensors.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the unit for the sensor's readings.
     *
     * @return string
     */
    public function getUnitAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SOIL_MOISTURE => '%',
            self::TYPE_TEMPERATURE => '°C',
            self::TYPE_HUMIDITY => '%',
            self::TYPE_RAINFALL => 'mm',
            self::TYPE_WATER_LEVEL => 'cm',
            self::TYPE_FLOW => 'L/min',
            self::TYPE_PRESSURE => 'bar',
            self::TYPE_PH => 'pH',
            self::TYPE_EC => 'µS/cm',
            self::TYPE_LIGHT => 'lux',
            default => '',
        };
    }

    /**
     * Get the display name for the sensor type.
     *
     * @return string
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SOIL_MOISTURE => 'Soil Moisture',
            self::TYPE_TEMPERATURE => 'Temperature',
            self::TYPE_HUMIDITY => 'Humidity',
            self::TYPE_RAINFALL => 'Rainfall',
            self::TYPE_WATER_LEVEL => 'Water Level',
            self::TYPE_FLOW => 'Flow Rate',
            self::TYPE_PRESSURE => 'Pressure',
            self::TYPE_PH => 'pH Level',
            self::TYPE_EC => 'EC (Electrical Conductivity)',
            self::TYPE_LIGHT => 'Light Intensity',
            default => ucwords(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Get all available sensor types.
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_SOIL_MOISTURE => 'Soil Moisture',
            self::TYPE_TEMPERATURE => 'Temperature',
            self::TYPE_HUMIDITY => 'Humidity',
            self::TYPE_RAINFALL => 'Rainfall',
            self::TYPE_WATER_LEVEL => 'Water Level',
            self::TYPE_FLOW => 'Flow Rate',
            self::TYPE_PRESSURE => 'Pressure',
            self::TYPE_PH => 'pH Level',
            self::TYPE_EC => 'EC (Electrical Conductivity)',
            self::TYPE_LIGHT => 'Light Intensity',
        ];
    }
}
