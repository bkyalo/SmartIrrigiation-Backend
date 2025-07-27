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
    public function handle()
    {
        try {
            // Check if the valve is still open and the event is still in progress
            if ($this->valve->is_open && $this->irrigationEvent->isInProgress()) {
                // Close the valve
                $this->valve->close();
                
                // Update the irrigation event as completed
                $this->irrigationEvent->markAsCompleted(0); // Volume used can be calculated or logged separately
                
                Log::info("Valve {$this->valve->id} closed after irrigation event {$this->irrigationEvent->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to close valve {$this->valve->id} after irrigation: " . $e->getMessage());
            
            // Mark the event as failed
            if ($this->irrigationEvent->isInProgress()) {
                $this->irrigationEvent->markAsFailed('Failed to close valve: ' . $e->getMessage());
            }
            
            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }
}
