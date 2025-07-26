<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

class Pump extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'external_device_id',
        'power_consumption',
        'flow_rate',
        'error_code',
        'notes',
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
        'deleted_at' => 'datetime',
        'notes' => 'string',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function booted()
    {
        static::deleting(function ($pump) {
            $pump->sessions()->delete();
        });
    }

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
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
        'error_code' => null,
        'notes' => null,
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the pump sessions.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(PumpSession::class);
    }

    /**
     * Get the current active session.
     */
    public function currentSession()
    {
        return $this->sessions()->running()->latest()->first();
    }

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
        if ($this->status === 'error' || $this->status === 'running') {
            return false;
        }

        // Create a new pump session
        $this->sessions()->create([
            'started_at' => now(),
            'duration_seconds' => 0,
        ]);

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
        if ($this->status !== 'running') {
            return false;
        }

        // End the current session
        if ($session = $this->currentSession()) {
            $session->stop();
            
            // Update total runtime
            $this->total_runtime += $session->duration_seconds / 60; // Convert to minutes
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
        $totalMinutes = $this->getTotalRuntimeInMinutes();
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Get the total runtime in minutes, including current session.
     *
     * @return int
     */
    public function getTotalRuntimeInMinutes(): int
    {
        $totalMinutes = $this->total_runtime;
        
        // Add current session time if pump is running
        if ($this->status === 'running' && $session = $this->currentSession()) {
            $currentSessionMinutes = now()->diffInMinutes($session->started_at);
            $totalMinutes += $currentSessionMinutes;
        }
        
        return (int) $totalMinutes;
    }
    
    /**
     * Get the total runtime in seconds for all completed sessions.
     *
     * @return int
     */
    public function getTotalRuntimeInSeconds(): int
    {
        return (int) $this->sessions()
            ->whereNotNull('stopped_at')
            ->sum('duration_seconds');
    }
}
