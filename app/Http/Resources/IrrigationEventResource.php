<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class IrrigationEventResource extends JsonResource
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
            'is_recurring' => (bool) $this->is_recurring,
            'recurrence_pattern' => $this->when($this->is_recurring, $this->recurrence_pattern),
            'recurrence_interval' => $this->when($this->is_recurring, (int) $this->recurrence_interval),
            'recurrence_ends_at' => $this->when($this->is_recurring, $this->recurrence_ends_at?->toIso8601String()),
            'timing' => [
                'scheduled' => [
                    'start' => $this->start_time->toIso8601String(),
                    'end' => $this->end_time->toIso8601String(),
                    'duration_minutes' => $this->start_time->diffInMinutes($this->end_time),
                ],
                'actual' => [
                    'start' => $this->actual_start_time?->toIso8601String(),
                    'end' => $this->actual_end_time?->toIso8601String(),
                    'duration_minutes' => $this->actual_start_time && $this->actual_end_time 
                        ? $this->actual_start_time->diffInMinutes($this->actual_end_time)
                        : null,
                ],
            ],
            'water_usage' => [
                'volume' => $this->water_volume ? (float) $this->water_volume : null,
                'flow_rate' => $this->water_flow_rate ? (float) $this->water_flow_rate : null,
                'unit' => 'liters',
            ],
            'plot' => $this->whenLoaded('plot', [
                'id' => $this->plot->id,
                'name' => $this->plot->name,
                'area' => $this->plot->area,
                'area_unit' => $this->plot->area_unit,
            ]),
            'valves' => $this->whenLoaded('valves', function () {
                return $this->valves->map(function ($valve) {
                    return [
                        'id' => $valve->id,
                        'name' => $valve->name,
                        'status' => $valve->status,
                        'flow_rate' => $valve->flow_rate,
                    ];
                });
            }),
            'schedules' => $this->whenLoaded('schedules', function () {
                return $this->schedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'name' => $schedule->name,
                        'status' => $schedule->status,
                        'next_run_at' => $schedule->next_run_at?->toIso8601String(),
                    ];
                });
            }),
            'approval_requests' => $this->whenLoaded('approvalRequests', function () {
                return $this->approvalRequests->map(function ($approval) {
                    return [
                        'id' => $approval->id,
                        'status' => $approval->status,
                        'requested_by' => $approval->requested_by,
                        'approved_by' => $approval->approved_by,
                        'requested_at' => $approval->created_at->toIso8601String(),
                    ];
                });
            }),
            'weather_conditions' => $this->weather_conditions ? (object) $this->weather_conditions : (object) [],
            'metadata' => $this->metadata ? (object) $this->metadata : (object) [],
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'links' => [
                'self' => route('api.irrigation-events.show', $this->id),
                'plot' => route('api.plots.show', $this->plot_id),
            ],
        ];
    }
}
