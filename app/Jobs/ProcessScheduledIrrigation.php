<?php

namespace App\Jobs;

use App\Models\IrrigationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessScheduledIrrigation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The irrigation event instance.
     *
     * @var \App\Models\IrrigationEvent
     */
    protected $irrigationEvent;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\IrrigationEvent  $irrigationEvent
     * @return void
     */
    public function __construct(IrrigationEvent $irrigationEvent)
    {
        $this->irrigationEvent = $irrigationEvent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Reload the event to ensure we have the latest data
        $event = $this->irrigationEvent->fresh();
        
        // Check if the event is still scheduled and not already started/completed
        if (!$event || $event->status !== IrrigationEvent::STATUS_SCHEDULED) {
            Log::info('Irrigation event is no longer scheduled', [
                'event_id' => $event ? $event->id : 'unknown',
                'status' => $event ? $event->status : 'not_found'
            ]);
            return;
        }
        
        try {
            // Start the irrigation
            $event->status = IrrigationEvent::STATUS_IN_PROGRESS;
            $event->start_time = now();
            $event->save();
            
            // Open the valve
            if ($valve = $event->valve) {
                $valve->open();
                
                // Log the valve state change
                Log::info('Valve opened for irrigation', [
                    'event_id' => $event->id,
                    'valve_id' => $valve->id,
                    'plot_id' => $event->plot_id
                ]);
                
                // Schedule the valve to close after the specified duration
                $closeTime = now()->addMinutes((int)$event->duration_minutes);
                
                // Dispatch a job to close the valve after the specified duration
                CloseValveJob::dispatch($event->valve, $event)
                    ->delay($closeTime);
                
                Log::info('Scheduled valve to close', [
                    'event_id' => $event->id,
                    'close_time' => $closeTime->toDateTimeString(),
                    'duration_minutes' => $event->duration_minutes
                ]);
                
                // If this is a recurring event, schedule the next occurrence
                if ($event->is_recurring || $event->parent_event_id) {
                    $this->scheduleNextRecurringEvent($event);
                }
            } else {
                throw new \Exception('No valve associated with this irrigation event');
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to process scheduled irrigation', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update event status to failed
            $event->status = IrrigationEvent::STATUS_FAILED;
            $event->save();
            
            throw $e; // Let the queue handle the retry logic
        }
    }
    
    /**
     * Schedule the next occurrence of a recurring event.
     *
     * @param  \App\Models\IrrigationEvent  $event
     * @return void
     */
    protected function scheduleNextRecurringEvent(IrrigationEvent $event)
    {
        // For child events, get the parent to schedule the next occurrence
        $parentEvent = $event->parent_event_id ? $event->parent : $event;
        
        // Only the parent event has the recurrence rule
        if (!$parentEvent->is_recurring || !$parentEvent->recurrence_rule) {
            return;
        }
        
        // Calculate the next occurrence
        $nextOccurrence = $parentEvent->calculateNextOccurrence();
        
        // Check if we've reached the end date for the recurring series
        if ($parentEvent->recurrence_end_date && $nextOccurrence->gt($parentEvent->recurrence_end_date)) {
            Log::info('Recurring irrigation series completed', [
                'parent_event_id' => $parentEvent->id,
                'end_date' => $parentEvent->recurrence_end_date->toDateTimeString()
            ]);
            return;
        }
        
        // Create a new event for the next occurrence
        $nextEvent = new IrrigationEvent([
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
        
        if ($nextEvent->save()) {
            // Schedule the job to handle the next occurrence
            self::dispatch($nextEvent)
                ->delay($nextOccurrence);
                
            Log::info('Scheduled next recurring irrigation', [
                'parent_event_id' => $parentEvent->id,
                'next_event_id' => $nextEvent->id,
                'next_occurrence' => $nextOccurrence->toDateTimeString()
            ]);
        }
    }
}
