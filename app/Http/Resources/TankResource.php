<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TankResource extends JsonResource
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
            'capacity' => (float) $this->capacity,
            'current_level' => (float) $this->current_level,
            'fill_percentage' => $this->when(
                $this->capacity > 0,
                fn () => round(($this->current_level / $this->capacity) * 100, 2)
            ),
            'min_threshold' => $this->whenNotNull($this->min_threshold, fn () => (float) $this->min_threshold),
            'max_threshold' => $this->whenNotNull($this->max_threshold, fn () => (float) $this->max_threshold),
            'status' => $this->status,
            'status_display' => $this->getStatus(),
            'needs_refill' => $this->needsRefill(),
            'is_full' => $this->isFull(),
            'location' => $this->location,
            'last_refilled' => $this->when(
                $this->last_refilled,
                fn () => $this->last_refilled->toIso8601String(),
                null
            ),
            'valves_count' => $this->when(
                $this->relationLoaded('valves'),
                fn () => $this->valves->count()
            ),
            'sensors_count' => $this->when(
                $this->relationLoaded('sensors'),
                fn () => $this->sensors->count()
            ),
            'valves' => ValveResource::collection(
                $this->whenLoaded('valves')
            ),
            'sensors' => SensorResource::collection(
                $this->whenLoaded('sensors')
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
