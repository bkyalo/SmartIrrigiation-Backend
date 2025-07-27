<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Jobs\ProcessScheduledIrrigation;

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
        'latitude',
        'longitude',
        'notes',
    ];
    
    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'idle',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'area' => 'decimal:2',
        'moisture_threshold' => 'float',
        'irrigation_duration' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the valve associated with the plot.
     */
    public function valve()
    {
        return $this->hasOne(Valve::class);
    }
    

    
    /**
     * Get the upcoming irrigation events for the plot.
     */
    public function upcomingIrrigationEvents()
    {
        return $this->irrigationEvents()
            ->where('start_time', '>=', now())
            ->where('status', IrrigationEvent::STATUS_SCHEDULED)
            ->orderBy('start_time');
    }
    
    /**
     * Get the past irrigation events for the plot.
     */
    public function pastIrrigationEvents($limit = 10)
    {
        return $this->irrigationEvents()
            ->where('end_time', '<=', now())
            ->where('status', IrrigationEvent::STATUS_COMPLETED)
            ->orderBy('end_time', 'desc')
            ->limit($limit);
    }
    
    /**
     * Check if the plot has any active irrigation events.
     * 
     * @return bool
     */
    public function hasActiveIrrigation()
    {
        return $this->irrigationEvents()
            ->where('status', IrrigationEvent::STATUS_IN_PROGRESS)
            ->exists();
    }
    
    /**
     * Check if there are any overlapping irrigation events for the given time range.
     * 
     * @param \Carbon\Carbon $startTime
     * @param int $durationMinutes
     * @param int|null $excludeEventId Optional event ID to exclude from the check
     * @return bool
     */
    public function hasOverlappingIrrigation($startTime, $durationMinutes, $excludeEventId = null)
    {
        $endTime = (clone $startTime)->addMinutes($durationMinutes);
        
        $query = $this->irrigationEvents()
            ->where(function($q) use ($startTime, $endTime) {
                // Check for events that overlap with the given time range
                $q->where(function($q) use ($startTime, $endTime) {
                    // Event starts during the new event
                    $q->where('start_time', '>=', $startTime)
                      ->where('start_time', '<', $endTime);
                })->orWhere(function($q) use ($startTime, $endTime) {
                    // Event ends during the new event
                    $q->where('end_time', '>', $startTime)
                      ->where('end_time', '<=', $endTime);
                })->orWhere(function($q) use ($startTime, $endTime) {
                    // Event completely contains the new event
                    $q->where('start_time', '<=', $startTime)
                      ->where('end_time', '>=', $endTime);
                });
            })
            ->whereIn('status', [
                IrrigationEvent::STATUS_SCHEDULED, 
                IrrigationEvent::STATUS_IN_PROGRESS
            ]);
            
        if ($excludeEventId) {
            $query->where('id', '!=', $excludeEventId);
        }
        
        return $query->exists();
    }
    
    /**
     * Start manual irrigation for the plot.
     * 
     * @param int $userId ID of the user initiating the irrigation
     * @param int|null $durationMinutes Optional duration in minutes, defaults to plot's irrigation_duration
     * @return \App\Models\IrrigationEvent
     * @throws \Exception If no valve is assigned to the plot or if there's an overlapping event
     */
    public function startManualIrrigation($userId, $durationMinutes = null)
    {
        if (!$this->valve) {
            throw new \Exception('No valve assigned to this plot');
        }
        
        if ($this->hasActiveIrrigation()) {
            throw new \Exception('Another irrigation event is already in progress for this plot');
        }
        
        $duration = $durationMinutes ?? $this->irrigation_duration;
        $startTime = now();
        
        if ($this->hasOverlappingIrrigation($startTime, $duration)) {
            throw new \Exception('There is an overlapping irrigation event scheduled for this time period');
        }
        
        // Create a new irrigation event
        $event = new IrrigationEvent([
            'plot_id' => $this->id,
            'valve_id' => $this->valve->id,
            'initiated_by' => $userId,
            'start_time' => $startTime,
            'end_time' => (clone $startTime)->addMinutes($duration),
            'duration_minutes' => $duration,
            'status' => IrrigationEvent::STATUS_IN_PROGRESS,
            'trigger_type' => IrrigationEvent::TRIGGER_MANUAL,
        ]);
        
        if ($event->save()) {
            // Open the valve
            $this->valve->open();
            
            // Schedule the valve to close after the specified duration
            $event->closeAfterDuration();
            
            return $event;
        }
        
        throw new \Exception('Failed to start irrigation');
    }
    
    /**
     * Schedule a one-time irrigation event.
     * 
     * @param Carbon $startTime When to start the irrigation
     * @param int $userId ID of the user scheduling the irrigation
     * @param int|null $durationMinutes Optional duration in minutes, defaults to plot's irrigation_duration
     * @return \App\Models\IrrigationEvent
     * @throws \Exception If no valve is assigned to the plot or if there's an overlapping event
     */
    public function scheduleOneTimeIrrigation(Carbon $startTime, $userId, $durationMinutes = null)
    {
        if (!$this->valve) {
            throw new \Exception('No valve assigned to this plot');
        }
        
        $duration = $durationMinutes ?? $this->irrigation_duration;
        $endTime = (clone $startTime)->addMinutes($duration);
        
        // Check for overlapping events (including currently running ones)
        if ($this->hasOverlappingIrrigation($startTime, $duration)) {
            throw new \Exception('There is an overlapping irrigation event scheduled for this time period');
        }
        
        // If this is a future event, also check if there will be an active irrigation at that time
        if ($startTime > now() && $this->hasActiveIrrigation()) {
            throw new \Exception('Cannot schedule irrigation while another event is in progress');
        }
        
        $event = new IrrigationEvent([
            'plot_id' => $this->id,
            'valve_id' => $this->valve->id,
            'initiated_by' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $duration,
            'status' => IrrigationEvent::STATUS_SCHEDULED,
            'trigger_type' => IrrigationEvent::TRIGGER_SCHEDULE,
        ]);
        
        if ($event->save()) {
            // Schedule the job to handle the irrigation at the specified time
            ProcessScheduledIrrigation::dispatch($event)
                ->delay($startTime);
                
            return $event;
        }
        
        throw new \Exception('Failed to schedule irrigation');
    }
    
    /**
     * Schedule recurring irrigation.
     * 
     * @param string $recurrenceRule Recurrence rule (e.g., 'DAILY', 'WEEKLY', 'MONDAY,WEDNESDAY,FRIDAY')
     * @param Carbon $startTime Initial start time
     * @param Carbon|null $endDate Optional end date for the recurring schedule
     * @param int $userId ID of the user scheduling the irrigation
     * @param int|null $durationMinutes Optional duration in minutes, defaults to plot's irrigation_duration
     * @return \App\Models\IrrigationEvent
     * @throws \Exception If no valve is assigned to the plot or if there's an overlapping event
     */
    public function scheduleRecurringIrrigation($recurrenceRule, Carbon $startTime, $userId, Carbon $endDate = null, $durationMinutes = null)
    {
        if (!$this->valve) {
            throw new \Exception('No valve assigned to this plot');
        }
        
        $duration = $durationMinutes ?? $this->irrigation_duration;
        
        // For recurring events, we need to check for overlapping events for the first occurrence
        $firstOccurrenceEnd = (clone $startTime)->addMinutes($duration);
        
        // Check for overlapping events for the first occurrence
        if ($this->hasOverlappingIrrigation($startTime, $duration)) {
            throw new \Exception('There is an overlapping irrigation event scheduled for the initial time period');
        }
        
        // If this is a future event, also check if there will be an active irrigation at that time
        if ($startTime > now() && $this->hasActiveIrrigation()) {
            throw new \Exception('Cannot schedule recurring irrigation while another event is in progress');
        }
        
        // Create a parent event to represent the recurring series
        $event = new IrrigationEvent([
            'plot_id' => $this->id,
            'valve_id' => $this->valve->id,
            'user_id' => $userId,
            'start_time' => $startTime,
            'duration_minutes' => $duration,
            'status' => IrrigationEvent::STATUS_SCHEDULED,
            'trigger_type' => IrrigationEvent::TRIGGER_SCHEDULE,
            'is_recurring' => true,
            'recurrence_rule' => $recurrenceRule,
            'recurrence_end_date' => $endDate,
        ]);
        
        if ($event->save()) {
            // Schedule the first occurrence
            $this->scheduleNextRecurringEvent($event);
            
            return $event;
        }
        
        throw new \Exception('Failed to schedule recurring irrigation');
    }
    
    /**
     * Schedule the next occurrence of a recurring event.
     * 
     * @param IrrigationEvent $parentEvent The parent recurring event
     * @return void
     */
    protected function scheduleNextRecurringEvent(IrrigationEvent $parentEvent)
    {
        $nextOccurrence = $parentEvent->calculateNextOccurrence();
        
        if (!$nextOccurrence) {
            return;
        }
        
        // Create a child event for this occurrence
        $event = new IrrigationEvent([
            'plot_id' => $parentEvent->plot_id,
            'valve_id' => $parentEvent->valve_id,
            'user_id' => $parentEvent->user_id,
            'start_time' => $nextOccurrence,
            'duration_minutes' => $parentEvent->duration_minutes,
            'status' => IrrigationEvent::STATUS_SCHEDULED,
            'trigger_type' => $parentEvent->trigger_type,
            'parent_event_id' => $parentEvent->id,
            'is_recurring' => false,
        ]);
        
        if ($event->save()) {
            // Schedule the job to handle this occurrence
            ProcessScheduledIrrigation::dispatch($event)
                ->delay($nextOccurrence);
        }
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
            default => ucfirst($this->status ?? 'Unknown'),
        };
    }
}
