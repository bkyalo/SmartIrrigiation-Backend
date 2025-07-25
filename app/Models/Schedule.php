<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Schedule extends Model
{
    use SoftDeletes;

    /**
     * The schedule statuses.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';

    /**
     * The schedule frequency types.
     */
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_CUSTOM = 'custom';

    /**
     * The days of the week.
     */
    public const MONDAY = 'monday';
    public const TUESDAY = 'tuesday';
    public const WEDNESDAY = 'wednesday';
    public const THURSDAY = 'thursday';
    public const FRIDAY = 'friday';
    public const SATURDAY = 'saturday';
    public const SUNDAY = 'sunday';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plot_id',
        'name',
        'description',
        'start_time',
        'duration_minutes',
        'frequency',
        'frequency_params',
        'is_active',
        'status',
        'last_run',
        'next_run',
        'end_date',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'duration_minutes' => 'integer',
        'frequency_params' => 'array',
        'is_active' => 'boolean',
        'last_run' => 'datetime',
        'next_run' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'frequency' => self::FREQUENCY_DAILY,
        'is_active' => true,
        'status' => self::STATUS_ACTIVE,
        'frequency_params' => '{}',
        'metadata' => '{}',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'start_time',
        'last_run',
        'next_run',
        'end_date',
        'deleted_at',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($schedule) {
            if (empty($schedule->next_run)) {
                $schedule->calculateNextRun();
            }
        });
    }

    /**
     * Get the plot that the schedule belongs to.
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class);
    }

    /**
     * Get the irrigation events for the schedule.
     */
    public function irrigationEvents(): HasMany
    {
        return $this->hasMany(IrrigationEvent::class);
    }

    /**
     * Scope a query to only include active schedules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('status', self::STATUS_ACTIVE)
                    ->where(function ($query) {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    });
    }

    /**
     * Scope a query to only include schedules that are due to run.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDue($query)
    {
        return $query->active()
                    ->where('next_run', '<=', now());
    }

    /**
     * Check if the schedule is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active && 
               $this->status === self::STATUS_ACTIVE && 
               ($this->end_date === null || $this->end_date->isFuture());
    }

    /**
     * Check if the schedule is due to run.
     *
     * @return bool
     */
    public function isDue(): bool
    {
        return $this->isActive() && $this->next_run && $this->next_run->isPast();
    }

    /**
     * Calculate the next run time for the schedule.
     *
     * @return $this
     */
    public function calculateNextRun()
    {
        if (!$this->isActive()) {
            $this->next_run = null;
            return $this;
        }

        $now = now();
        $nextRun = $this->last_run ? clone $this->last_run : $now;

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                $nextRun->addDay();
                break;

            case self::FREQUENCY_WEEKLY:
                $days = $this->frequency_params['days'] ?? [];
                if (empty($days)) {
                    $nextRun->addWeek();
                } else {
                    $currentDay = strtolower($nextRun->englishDayOfWeek);
                    $currentDayIndex = array_search($currentDay, $days);
                    
                    if ($currentDayIndex !== false && $nextRun->isToday()) {
                        // If today is one of the scheduled days and we haven't run yet today
                        $nextRun->setTimeFromTimeString($this->start_time->format('H:i:s'));
                        if ($nextRun->isPast()) {
                            // If the time has passed, move to the next scheduled day
                            $nextDayIndex = ($currentDayIndex + 1) % count($days);
                            $daysToAdd = $nextDayIndex > $currentDayIndex 
                                ? $nextDayIndex - $currentDayIndex 
                                : 7 - ($currentDayIndex - $nextDayIndex);
                            $nextRun->addDays($daysToAdd);
                        }
                    } else {
                        // Find the next scheduled day
                        $nextDay = null;
                        foreach ($days as $day) {
                            $dayIndex = array_search($day, [
                                'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'
                            ]);
                            $nextDayOfWeek = $nextRun->copy()->next($dayIndex);
                            
                            if (!$nextDay || $nextDayOfWeek->lt($nextDay)) {
                                $nextDay = $nextDayOfWeek;
                            }
                        }
                        $nextRun = $nextDay;
                    }
                }
                break;

            case self::FREQUENCY_MONTHLY:
                $days = $this->frequency_params['days'] ?? [1];
                $nextRun->addMonth();
                $nextRun->day = $days[0];
                break;

            case self::FREQUENCY_CUSTOM:
                $interval = $this->frequency_params['interval'] ?? 1;
                $unit = $this->frequency_params['unit'] ?? 'days';
                $nextRun->add($interval, $unit);
                break;
        }

        // Set the time from the start_time
        if ($this->start_time) {
            $nextRun->setTimeFromTimeString($this->start_time->format('H:i:s'));
        }

        // If the calculated next run is in the past, set it to now
        if ($nextRun->isPast()) {
            $nextRun = now();
        }

        $this->next_run = $nextRun;
        return $this;
    }

    /**
     * Mark the schedule as run.
     *
     * @return $this
     */
    public function markAsRun()
    {
        $this->last_run = now();
        $this->calculateNextRun();
        $this->save();
        
        return $this;
    }

    /**
     * Get the schedule status in a human-readable format.
     *
     * @return string
     */
    public function getStatusNameAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        return match($this->status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_PAUSED => 'Paused',
            self::STATUS_COMPLETED => 'Completed',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the frequency in a human-readable format.
     *
     * @return string
     */
    public function getFrequencyNameAttribute(): string
    {
        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                return 'Daily';
                
            case self::FREQUENCY_WEEKLY:
                $days = $this->frequency_params['days'] ?? [];
                if (empty($days)) {
                    return 'Weekly';
                }
                
                $dayNames = array_map('ucfirst', $days);
                return 'Weekly on ' . implode(', ', $dayNames);
                
            case self::FREQUENCY_MONTHLY:
                $days = $this->frequency_params['days'] ?? [1];
                return 'Monthly on day ' . implode(', ', $days);
                
            case self::FREQUENCY_CUSTOM:
                $interval = $this->frequency_params['interval'] ?? 1;
                $unit = $this->frequency_params['unit'] ?? 'days';
                return "Every {$interval} " . str_plural($unit, $interval);
                
            default:
                return ucfirst($this->frequency);
        }
    }

    /**
     * Get the next run time in a human-readable format.
     *
     * @return string
     */
    public function getNextRunForHumansAttribute(): string
    {
        if (!$this->next_run) {
            return 'Not scheduled';
        }
        
        return $this->next_run->diffForHumans();
    }

    /**
     * Get the last run time in a human-readable format.
     *
     * @return string
     */
    public function getLastRunForHumansAttribute(): string
    {
        if (!$this->last_run) {
            return 'Never';
        }
        
        return $this->last_run->diffForHumans();
    }

    /**
     * Get the duration in a human-readable format.
     *
     * @return string
     */
    public function getDurationForHumansAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    /**
     * Pause the schedule.
     *
     * @return bool
     */
    public function pause(): bool
    {
        if ($this->status === self::STATUS_ACTIVE) {
            $this->status = self::STATUS_PAUSED;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Resume the schedule.
     *
     * @return bool
     */
    public function resume(): bool
    {
        if ($this->status === self::STATUS_PAUSED) {
            $this->status = self::STATUS_ACTIVE;
            $this->calculateNextRun();
            return $this->save();
        }
        
        return false;
    }

    /**
     * Complete the schedule.
     *
     * @return bool
     */
    public function complete(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->is_active = false;
        $this->next_run = null;
        return $this->save();
    }
}
