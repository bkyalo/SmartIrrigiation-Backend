<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class IrrigationEvent extends Model
{
    use SoftDeletes;

    /**
     * The event statuses.
     */
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The trigger types for the event.
     */
    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_SCHEDULE = 'schedule';
    public const TRIGGER_SENSOR = 'sensor';
    public const TRIGGER_AI = 'ai';
    public const TRIGGER_MAINTENANCE = 'maintenance';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plot_id',
        'valve_id',
        'pump_id',
        'user_id',
        'schedule_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'volume_used',
        'status',
        'trigger_type',
        'notes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'volume_used' => 'decimal:2',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => self::STATUS_SCHEDULED,
        'volume_used' => 0,
        'metadata' => '{}',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'start_time',
        'end_time',
        'deleted_at',
    ];

    /**
     * Get the plot that the irrigation event belongs to.
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    /**
     * Get the valve that was used for the irrigation event.
     */
    public function valve(): BelongsTo
    {
        return $this->belongsTo(Valve::class);
    }

    /**
     * Get the pump that was used for the irrigation event.
     */
    public function pump(): BelongsTo
    {
        return $this->belongsTo(Pump::class);
    }

    /**
     * Get the user who initiated the irrigation event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the schedule that triggered the irrigation event, if any.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Scope a query to only include events in progress.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope a query to only include scheduled events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope a query to only include completed events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include events within a date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('start_time', [$from, $to])
            ->orWhereBetween('end_time', [$from, $to])
            ->orWhere(function ($q) use ($from, $to) {
                $q->where('start_time', '<=', $from)
                  ->where('end_time', '>=', $to);
            });
    }

    /**
     * Check if the event is in progress.
     *
     * @return bool
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if the event is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the event is scheduled.
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if the event is in a terminal state (completed, failed, or cancelled).
     *
     * @return bool
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Get the duration of the event in a human-readable format.
     *
     * @return string
     */
    public function getDurationForHumansAttribute(): string
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Get the event status in a human-readable format.
     *
     * @return string
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucwords(str_replace('_', ' ', $this->status)),
        };
    }

    /**
     * Get the trigger type in a human-readable format.
     *
     * @return string
     */
    public function getTriggerNameAttribute(): string
    {
        return match($this->trigger_type) {
            self::TRIGGER_MANUAL => 'Manual',
            self::TRIGGER_SCHEDULE => 'Schedule',
            self::TRIGGER_SENSOR => 'Sensor',
            self::TRIGGER_AI => 'AI',
            self::TRIGGER_MAINTENANCE => 'Maintenance',
            default => ucwords(str_replace('_', ' ', $this->trigger_type)),
        };
    }

    /**
     * Mark the event as started.
     *
     * @return bool
     */
    public function markAsStarted(): bool
    {
        if ($this->isTerminal()) {
            return false;
        }

        $this->status = self::STATUS_IN_PROGRESS;
        $this->start_time = now();
        return $this->save();
    }

    /**
     * Mark the event as completed.
     *
     * @param float $volumeUsed
     * @return bool
     */
    public function markAsCompleted(float $volumeUsed): bool
    {
        if ($this->isTerminal()) {
            return false;
        }

        $this->status = self::STATUS_COMPLETED;
        $this->end_time = now();
        $this->volume_used = $volumeUsed;
        $this->duration_minutes = $this->end_time->diffInMinutes($this->start_time);
        
        return $this->save();
    }

    /**
     * Mark the event as failed.
     *
     * @param string $reason
     * @return bool
     */
    public function markAsFailed(string $reason): bool
    {
        if ($this->isTerminal()) {
            return false;
        }

        $this->status = self::STATUS_FAILED;
        $this->end_time = now();
        $this->duration_minutes = $this->end_time->diffInMinutes($this->start_time);
        
        // Add the failure reason to metadata
        $metadata = $this->metadata ?? [];
        $metadata['failure_reason'] = $reason;
        $this->metadata = $metadata;
        
        return $this->save();
    }

    /**
     * Cancel the event.
     *
     * @param string $reason
     * @return bool
     */
    public function cancel(string $reason): bool
    {
        if ($this->isTerminal()) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        
        // If the event was in progress, set the end time
        if ($this->isInProgress()) {
            $this->end_time = now();
            $this->duration_minutes = $this->end_time->diffInMinutes($this->start_time);
        }
        
        // Add the cancellation reason to metadata
        $metadata = $this->metadata ?? [];
        $metadata['cancellation_reason'] = $reason;
        $this->metadata = $metadata;
        
        return $this->save();
    }
}
