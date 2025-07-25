<?php

namespace App\Http\Resources;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'is_active' => (bool) $this->is_active,
            'frequency' => $this->frequency,
            'frequency_name' => $this->frequency_name,
            'frequency_params' => $this->when($this->frequency_params, $this->frequency_params, (object)[]),
            'start_time' => $this->start_time?->toIso8601String(),
            'start_time_for_humans' => $this->start_time?->diffForHumans(),
            'end_date' => $this->end_date?->toIso8601String(),
            'end_date_for_humans' => $this->end_date?->diffForHumans(),
            'duration_minutes' => (int) $this->duration_minutes,
            'duration_for_humans' => $this->duration_for_humans,
            'last_run' => $this->last_run?->toIso8601String(),
            'last_run_for_humans' => $this->last_run_for_humans,
            'next_run' => $this->next_run?->toIso8601String(),
            'next_run_for_humans' => $this->next_run_for_humans,
            'is_due' => $this->isDue(),
            'metadata' => $this->metadata ?? (object)[], // Always return an object, never null
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->toIso8601String()),
            
            // Relationships
            'plot' => $this->whenLoaded('plot', function () {
                return new PlotResource($this->plot);
            }),
            'irrigation_events' => $this->whenLoaded('irrigationEvents', function () {
                return IrrigationEventResource::collection($this->irrigationEvents);
            }),
            
            // Links
            'links' => [
                'self' => route('api.schedules.show', $this->id),
                'plot' => $this->whenLoaded('plot', function () {
                    return route('api.plots.show', $this->plot_id);
                }),
                'irrigation_events' => route('api.irrigation-events.index', ['schedule_id' => $this->id]),
            ],
            
            // Actions
            'actions' => [
                'pause' => route('api.schedules.pause', $this->id),
                'resume' => route('api.schedules.resume', $this->id),
                'complete' => route('api.schedules.complete', $this->id),
                'next_run' => route('api.schedules.next-run', $this->id),
            ],
        ];
    }
    
    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'statuses' => [
                    Schedule::STATUS_ACTIVE => 'Active',
                    Schedule::STATUS_PAUSED => 'Paused',
                    Schedule::STATUS_COMPLETED => 'Completed',
                ],
                'frequencies' => [
                    Schedule::FREQUENCY_DAILY => 'Daily',
                    Schedule::FREQUENCY_WEEKLY => 'Weekly',
                    Schedule::FREQUENCY_MONTHLY => 'Monthly',
                    Schedule::FREQUENCY_CUSTOM => 'Custom',
                ],
            ],
        ];
    }
}
