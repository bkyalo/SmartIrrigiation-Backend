<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SensorReadingResource extends JsonResource
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
            'sensor' => $this->whenLoaded('sensor', [
                'id' => $this->sensor->id,
                'name' => $this->sensor->name,
                'type' => $this->sensor->sensorType->name ?? null,
                'unit' => $this->sensor->unit ?? $this->unit,
            ]),
            'value' => (float) $this->value,
            'unit' => $this->unit,
            'recorded_at' => $this->recorded_at->toIso8601String(),
            'location' => $this->when($this->latitude && $this->longitude, [
                'latitude' => (float) $this->latitude,
                'longitude' => (float) $this->longitude,
                'elevation' => $this->elevation ? (float) $this->elevation : null,
                'accuracy' => $this->accuracy ? (float) $this->accuracy : null,
            ]),
            'device_info' => [
                'battery_level' => $this->battery_level ? (int) $this->battery_level : null,
                'signal_strength' => $this->signal_strength ? (int) $this->signal_strength : null,
            ],
            'metadata' => $this->metadata ?? (object) [],
            'flags' => [
                'is_anomaly' => (bool) $this->is_anomaly,
                'is_manually_entered' => (bool) $this->is_manually_entered,
            ],
            'timestamps' => [
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
        ];
    }
}
