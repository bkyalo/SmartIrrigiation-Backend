<?php

namespace App\Jobs;

use App\Models\IrrigationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
            Log::info('Irrigation event is no longer scheduled or already processed', [
                'event_id' => $event ? $event->id : 'unknown',
                'status' => $event ? $event->status : 'not_found'
            ]);
            return;
        }
        
        // Start a database transaction to ensure data consistency
        return DB::transaction(function () use ($event) {
            try {
                // Mark the event as in progress
                $event->status = IrrigationEvent::STATUS_IN_PROGRESS;
                $event->start_time = now();
                
                if (!$event->save()) {
                    throw new \Exception('Failed to update irrigation event status');
                }
                
                // Get the associated valve
                $valve = $event->valve;
                if (!$valve) {
                    throw new \Exception('No valve associated with this irrigation event');
                }
                
                // Open the valve using the correct method
                if (!$valve->openValve('irrigation', $event->initiated_by, 'Scheduled irrigation started')) {
                    throw new \Exception('Failed to open valve');
                }
                
                Log::info('Valve opened for scheduled irrigation', [
                    'event_id' => $event->id,
                    'valve_id' => $valve->id,
                    'plot_id' => $event->plot_id,
                    'initiated_by' => $event->initiated_by,
                    'duration_minutes' => $event->duration_minutes
                ]);
                
                // Schedule the valve to close after the specified duration
                $closeTime = now()->addMinutes((int)$event->duration_minutes);
                
                // Dispatch a job to close the valve after the specified duration
                CloseValveJob::dispatch($valve, $event)
                    ->delay($closeTime);
                
                Log::info('Scheduled valve to close after duration', [
                    'event_id' => $event->id,
                    'valve_id' => $valve->id,
                    'close_time' => $closeTime->toDateTimeString(),
                    'duration_minutes' => $event->duration_minutes
                ]);
                
                // If this is a recurring event, schedule the next occurrence
                if ($event->is_recurring || $event->parent_event_id) {
                    $this->scheduleNextRecurringEvent($event);
                }
                
                return true;
                
            } catch (\Exception $e) {
                // Log the error
                Log::error('Failed to process scheduled irrigation', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Update event status to failed
                $event->status = IrrigationEvent::STATUS_FAILED;
                $event->save();
                
                // If there's a valve, make sure it's closed
                if ($event->valve) {
                    try {
                        $event->valve->closeValve('system', 1, 'Emergency close after irrigation failure');
                    } catch (\Exception $closeException) {
                        Log::error('Failed to close valve after irrigation failure', [
                            'event_id' => $event->id,
                            'error' => $closeException->getMessage()
                        ]);
                    }
                }
                
                // Re-throw the exception to mark the job as failed
                throw $e;
            }
        });
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
        
        // Calculate the next occurrence using the parent event's method
        $nextOccurrence = $parentEvent->calculateNextOccurrence();
        
        // If no next occurrence (e.g., past end date), return
        if (!$nextOccurrence) {
            Log::info('Recurring irrigation series completed - no more occurrences', [
                'parent_event_id' => $parentEvent->id,
                'end_date' => $parentEvent->recurrence_end_date ? $parentEvent->recurrence_end_date->toDateTimeString() : 'none'
            ]);
            return;
        }
        
        // Ensure nextOccurrence is a Carbon instance
        if (!$nextOccurrence instanceof \Carbon\Carbon) {
            Log::error('Invalid next occurrence date', [
                'parent_event_id' => $parentEvent->id,
                'next_occurrence' => $nextOccurrence,
                'type' => gettype($nextOccurrence)
            ]);
            return;
        }
        
        // Create a new event for the next occurrence
        try {
            $nextEvent = new IrrigationEvent([
                'plot_id' => $parentEvent->plot_id,
                'valve_id' => $parentEvent->valve_id,
                'initiated_by' => $parentEvent->initiated_by,
                'start_time' => $nextOccurrence,
                'duration_minutes' => (int)$parentEvent->duration_minutes,
                'status' => IrrigationEvent::STATUS_SCHEDULED,
                'trigger_type' => $parentEvent->trigger_type,
                'parent_event_id' => $parentEvent->id,
                'is_recurring' => false,
                'recurrence_rule' => $parentEvent->recurrence_rule,
                'recurrence_end_date' => $parentEvent->recurrence_end_date,
            ]);
            
            DB::beginTransaction();
            
            if ($nextEvent->save()) {
                // Schedule the job to handle the next occurrence
                self::dispatch($nextEvent)
                    ->delay($nextOccurrence);
                    
                Log::info('Scheduled next recurring irrigation', [
                    'parent_event_id' => $parentEvent->id,
                    'next_event_id' => $nextEvent->id,
                    'next_occurrence' => $nextOccurrence->toDateTimeString(),
                    'duration_minutes' => $nextEvent->duration_minutes
                ]);
                
                DB::commit();
                return;
            }
            
            DB::rollBack();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to schedule next recurring irrigation', [
                'parent_event_id' => $parentEvent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
