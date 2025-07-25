<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SensorResource extends JsonResource
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
            'type' => $this->type,
            'type_display' => $this->getTypeName(),
            'location_type' => $this->location_type,
            'location_id' => $this->location_id,
            'status' => $this->status,
            'last_reading' => $this->when(
                $this->relationLoaded('latestReading'),
                fn () => $this->latestReading ? [
                    'value' => $this->latestReading->value,
                    'unit' => $this->latestReading->getUnit(),
                    'timestamp' => $this->latestReading->created_at->toIso8601String(),
                ] : null,
                null
            ),
            'calibration' => $this->when(
                $this->calibration_offset !== null || $this->calibration_factor !== null,
                [
                    'offset' => $this->calibration_offset,
                    'factor' => $this->calibration_factor,
                ],
                null
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
}
