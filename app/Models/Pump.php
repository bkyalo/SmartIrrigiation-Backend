<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pump extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'power_consumption',
        'flow_rate',
        'total_runtime',
        'last_maintenance',
        'error_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'power_consumption' => 'decimal:2',
        'flow_rate' => 'decimal:2',
        'total_runtime' => 'integer',
        'last_maintenance' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'stopped',
        'power_consumption' => 0,
        'flow_rate' => 0,
        'total_runtime' => 0,
    ];

    /**
     * Get the irrigation events for the pump.
     */
    public function irrigationEvents(): HasMany
    {
        return $this->hasMany(IrrigationEvent::class);
    }

    /**
     * Start the pump.
     *
     * @return bool
     */
    public function start(): bool
    {
        if ($this->status === 'error') {
            return false;
        }

        $this->status = 'running';
        return $this->save();
    }

    /**
     * Stop the pump.
     *
     * @return bool
     */
    public function stop(): bool
    {
        if ($this->status === 'error') {
            return false;
        }

        $this->status = 'stopped';
        return $this->save();
    }

    /**
     * Set the pump to error state.
     *
     * @param string $errorCode
     * @return bool
     */
    public function setError(string $errorCode): bool
    {
        $this->status = 'error';
        $this->error_code = $errorCode;
        return $this->save();
    }

    /**
     * Clear the error state of the pump.
     *
     * @return bool
     */
    public function clearError(): bool
    {
        if ($this->status !== 'error') {
            return true;
        }

        $this->status = 'stopped';
        $this->error_code = null;
        return $this->save();
    }

    /**
     * Check if the pump is running.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the pump is in error state.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Get the active irrigation event for the pump, if any.
     */
    public function activeIrrigationEvent()
    {
        return $this->hasOne(IrrigationEvent::class)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->latest()
            ->limit(1);
    }

    /**
     * Get the latest pump status.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get the total runtime in a human-readable format.
     *
     * @return string
     */
    public function getRuntimeForHumans(): string
    {
        $hours = floor($this->total_runtime / 60);
        $minutes = $this->total_runtime % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}
