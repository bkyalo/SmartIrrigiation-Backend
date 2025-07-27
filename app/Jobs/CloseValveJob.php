<?php

namespace App\Jobs;

use App\Models\Valve;
use App\Models\IrrigationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CloseValveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The valve instance.
     *
     * @var \App\Models\Valve
     */
    protected $valve;

    /**
     * The irrigation event instance.
     *
     * @var \App\Models\IrrigationEvent
     */
    protected $irrigationEvent;

    /**
     * Create a new job instance.
     *
     * @param  \App\Models\Valve  $valve
     * @param  \App\Models\IrrigationEvent  $irrigationEvent
     * @return void
     */
    public function __construct(Valve $valve, IrrigationEvent $irrigationEvent)
    {
        $this->valve = $valve;
        $this->irrigationEvent = $irrigationEvent;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Reload the event to ensure we have the latest data
            $event = $this->irrigationEvent->fresh();
            $valve = $this->valve->fresh();
            
            // Check if the event is still in progress and the valve is still open
            if (!$event || $event->status !== IrrigationEvent::STATUS_IN_PROGRESS) {
                Log::info('Irrigation event is no longer in progress, skipping valve closure', [
                    'event_id' => $event ? $event->id : 'unknown',
                    'status' => $event ? $event->status : 'not_found'
                ]);
                return;
            }
            
            // Check if the valve is still open
            if (!$valve || !$valve->is_open) {
                Log::info('Valve is already closed or not found, skipping closure', [
                    'valve_id' => $valve ? $valve->id : 'unknown',
                    'is_open' => $valve ? $valve->is_open : 'not_found'
                ]);
                
                // Still mark the event as completed if it's still in progress
                if ($event->status === IrrigationEvent::STATUS_IN_PROGRESS) {
                    $event->markAsCompleted(0);
                }
                
                return;
            }
            
            // Close the valve using the correct method
            if (!$valve->closeValve('irrigation', $event->initiated_by ?? 1, 'Scheduled irrigation completed')) {
                throw new \Exception('Failed to close valve');
            }
            
            // Mark the irrigation event as completed
            $event->markAsCompleted(0); // Volume used can be calculated or logged separately
            
            Log::info('Valve closed after scheduled irrigation', [
                'event_id' => $event->id,
                'valve_id' => $valve->id,
                'plot_id' => $event->plot_id,
                'initiated_by' => $event->initiated_by,
                'duration_minutes' => $event->duration_minutes,
                'actual_duration' => $event->start_time ? now()->diffInMinutes($event->start_time) : null
            ]);
            
        } catch (\Exception $e) {
            // Log the error with detailed context
            Log::error('Failed to close valve after irrigation', [
                'event_id' => $this->irrigationEvent->id,
                'valve_id' => $this->valve->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Mark the event as failed if it's still in progress
            if ($this->irrigationEvent->fresh()->status === IrrigationEvent::STATUS_IN_PROGRESS) {
                $this->irrigationEvent->markAsFailed('Failed to close valve: ' . $e->getMessage());
            }
            
            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }
}
