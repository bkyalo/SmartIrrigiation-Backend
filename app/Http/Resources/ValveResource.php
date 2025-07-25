<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValveResource extends JsonResource
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
            'is_open' => $this->is_open,
            'flow_rate' => $this->whenNotNull($this->flow_rate, fn () => (float) $this->flow_rate),
            'tank_id' => $this->when($this->tank_id, fn () => (int) $this->tank_id),
            'plot_id' => $this->when($this->plot_id, fn () => (int) $this->plot_id),
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
