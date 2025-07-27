<?php

namespace App\Jobs;

use App\Models\IrrigationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIrrigationCompletion implements ShouldQueue
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
        
        // Check if the event is still in progress
        if (!$event || $event->status !== IrrigationEvent::STATUS_IN_PROGRESS) {
            Log::info('Irrigation event is not in progress', [
                'event_id' => $event ? $event->id : 'unknown',
                'status' => $event ? $event->status : 'not_found'
            ]);
            return;
        }
        
        try {
            // Close the valve
            if ($valve = $event->valve) {
                $valve->close();
                
                // Log the valve state change
                Log::info('Valve closed after irrigation', [
                    'event_id' => $event->id,
                    'valve_id' => $valve->id,
                    'plot_id' => $event->plot_id
                ]);
            }
            
            // Update the event status to completed
            $event->status = IrrigationEvent::STATUS_COMPLETED;
            $event->end_time = now();
            
            // Calculate actual duration in minutes
            if ($event->start_time) {
                $event->duration_minutes = $event->start_time->diffInMinutes($event->end_time);
                
                // Calculate water volume used (flow rate * duration in hours)
                if ($valve && $valve->flow_rate) {
                    $event->volume_used = $valve->flow_rate * ($event->duration_minutes / 60);
                }
            }
            
            // Save the updated event
            $event->save();
            
            Log::info('Irrigation completed successfully', [
                'event_id' => $event->id,
                'duration_minutes' => $event->duration_minutes,
                'volume_used' => $event->volume_used
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to complete irrigation', [
                'event_id' => $event ? $event->id : 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update event status to failed
            if ($event) {
                $event->status = IrrigationEvent::STATUS_FAILED;
                $event->save();
            }
            
            throw $e; // Let the queue handle the retry logic
        }
    }
}
