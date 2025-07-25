<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlotResource extends JsonResource
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
            'location' => $this->location,
            'area' => $this->whenNotNull($this->area, fn () => (float) $this->area),
            'area_unit' => $this->area_unit,
            'crop_type' => $this->crop_type,
            'planting_date' => $this->when(
                $this->planting_date,
                fn () => $this->planting_date->toDateString(),
                null
            ),
            'soil_type' => $this->soil_type,
            'optimal_moisture_min' => $this->whenNotNull(
                $this->optimal_moisture_min, 
                fn () => (float) $this->optimal_moisture_min
            ),
            'optimal_moisture_max' => $this->whenNotNull(
                $this->optimal_moisture_max, 
                fn () => (float) $this->optimal_moisture_max
            ),
            'irrigation_method' => $this->irrigation_method,
            'irrigation_method_display' => $this->getIrrigationMethodName(),
            'status' => $this->status,
            'status_display' => $this->getStatusName(),
            'current_moisture' => $this->when(
                $this->relationLoaded('sensors'),
                fn () => $this->getCurrentMoistureReading()
            ),
            'needs_irrigation' => $this->when(
                $this->relationLoaded('sensors'),
                fn () => $this->needsIrrigation()
            ),
            'valves_count' => $this->when(
                $this->relationLoaded('valves'),
                fn () => $this->valves->count()
            ),
            'sensors_count' => $this->when(
                $this->relationLoaded('sensors'),
                fn () => $this->sensors->count()
            ),
            'schedules_count' => $this->when(
                $this->relationLoaded('schedules'),
                fn () => $this->schedules->count()
            ),
            'active_irrigation_events_count' => $this->when(
                $this->relationLoaded('irrigationEvents'),
                fn () => $this->irrigationEvents()->whereNull('end_time')->count()
            ),
            'valves' => ValveResource::collection(
                $this->whenLoaded('valves')
            ),
            'sensors' => SensorResource::collection(
                $this->whenLoaded('sensors')
            ),
            'schedules' => ScheduleResource::collection(
                $this->whenLoaded('schedules')
            ),
            'active_irrigation_events' => IrrigationEventResource::collection(
                $this->whenLoaded('irrigationEvents', function () {
                    return $this->irrigationEvents()->whereNull('end_time')->get();
                })
            ),
            'last_irrigation' => $this->when(
                $this->relationLoaded('irrigationEvents'),
                function () {
                    $event = $this->irrigationEvents()
                        ->whereNotNull('end_time')
                        ->latest('end_time')
                        ->first();
                    return $event ? $event->end_time->toIso8601String() : null;
                }
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted_at' => $this->whenNotNull(
                $this->deleted_at?->toIso8601String()
            ),
            'metadata' => $this->when(
                $this->metadata && is_array($this->metadata) && count($this->metadata) > 0,
                $this->metadata,
                (object)[]
            ),
        ];
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'status' => 'success',
                'version' => config('app.version', '1.0.0'),
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }
}
