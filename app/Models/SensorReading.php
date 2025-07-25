<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sensor_id',
        'value',
        'unit',
        'recorded_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'float',
        'recorded_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'recorded_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'metadata' => '{}',
    ];

    /**
     * Get the sensor that owns the reading.
     */
    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }

    /**
     * Scope a query to only include readings from a specific sensor.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $sensorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSensor($query, $sensorId)
    {
        return $query->where('sensor_id', $sensorId);
    }

    /**
     * Scope a query to only include readings from a specific time range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('recorded_at', [$from, $to]);
    }

    /**
     * Scope a query to only include recent readings.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $hours
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    /**
     * Get the formatted value with unit.
     *
     * @return string
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->sensor->type === Sensor::TYPE_TEMPERATURE) {
            return round($this->value, 1) . '°C';
        }

        if ($this->sensor->type === Sensor::TYPE_HUMIDITY || $this->sensor->type === Sensor::TYPE_SOIL_MOISTURE) {
            return round($this->value, 1) . '%';
        }

        if ($this->sensor->type === Sensor::TYPE_PH) {
            return round($this->value, 2) . ' pH';
        }

        if ($this->sensor->type === Sensor::TYPE_EC) {
            return round($this->value, 2) . ' µS/cm';
        }

        if ($this->sensor->type === Sensor::TYPE_LIGHT) {
            return number_format($this->value) . ' lux';
        }

        if ($this->sensor->type === Sensor::TYPE_PRESSURE) {
            return round($this->value, 2) . ' bar';
        }

        if ($this->sensor->type === Sensor::TYPE_FLOW) {
            return round($this->value, 2) . ' L/min';
        }

        if ($this->sensor->type === Sensor::TYPE_WATER_LEVEL) {
            return round($this->value, 1) . ' cm';
        }

        if ($this->sensor->type === Sensor::TYPE_RAINFALL) {
            return round($this->value, 1) . ' mm';
        }

        return (string) $this->value . ($this->unit ? ' ' . $this->unit : '');
    }

    /**
     * Get the reading value with the sensor's unit.
     *
     * @return string
     */
    public function getValueWithUnitAttribute(): string
    {
        return $this->value . ' ' . $this->sensor->unit;
    }

    /**
     * Get the reading timestamp in a human-readable format.
     *
     * @return string
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->recorded_at->format('Y-m-d H:i:s');
    }

    /**
     * Get the time difference from now in a human-readable format.
     *
     * @return string
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->recorded_at->diffForHumans();
    }

    /**
     * Get the reading metadata as a JSON string.
     *
     * @return string
     */
    public function getMetadataAsJsonAttribute(): string
    {
        return json_encode($this->metadata, JSON_PRETTY_PRINT);
    }
}
