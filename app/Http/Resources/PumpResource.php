<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PumpResource extends JsonResource
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
            'tank_id' => $this->tank_id,
            'flow_rate' => $this->flow_rate,
            'max_flow_rate' => $this->max_flow_rate,
            'pressure' => $this->pressure,
            'max_pressure' => $this->max_pressure,
            'power_rating' => $this->power_rating,
            'power_consumption' => $this->power_consumption,
            'voltage' => $this->voltage,
            'current' => $this->current,
            'efficiency' => $this->efficiency,
            'total_runtime' => $this->total_runtime,
            'is_running' => (bool) $this->is_running,
            'status' => $this->status,
            'status_display' => $this->getStatusName(),
            'last_started_at' => $this->last_started_at?->toIso8601String(),
            'last_stopped_at' => $this->last_stopped_at?->toIso8601String(),
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'installation_date' => $this->installation_date?->toDateString(),
            'last_maintenance_date' => $this->last_maintenance_date?->toDateString(),
            'next_maintenance_date' => $this->next_maintenance_date?->toDateString(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Relationships
            'tank' => $this->whenLoaded('tank', function () {
                return [
                    'id' => $this->tank->id,
                    'name' => $this->tank->name,
                    'current_level' => $this->tank->current_level,
                    'capacity' => $this->tank->capacity,
                    'status' => $this->tank->status,
                ];
            }),
            
            // Computed attributes
            'is_active' => $this->is_running,
            'runtime_this_week' => $this->calculateRuntimeThisWeek(),
            'runtime_this_month' => $this->calculateRuntimeThisMonth(),
            'uptime_percentage' => $this->calculateUptimePercentage(),
            'maintenance_due_soon' => $this->isMaintenanceDueSoon(),
            'maintenance_overdue' => $this->isMaintenanceOverdue(),
            'estimated_energy_consumption' => $this->calculateEstimatedEnergyConsumption(),
        ];
    }
    
    /**
     * Get the status display name.
     *
     * @return string
     */
    protected function getStatusName(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'maintenance' => 'Maintenance',
            'error' => 'Error',
            default => ucfirst($this->status ?? 'Unknown')
        };
    }
    
    /**
     * Calculate the pump's runtime for the current week.
     *
     * @return int
     */
    protected function calculateRuntimeThisWeek(): int
    {
        // This is a placeholder. Implement actual calculation based on your requirements.
        return 0;
    }
    
    /**
     * Calculate the pump's runtime for the current month.
     *
     * @return int
     */
    protected function calculateRuntimeThisMonth(): int
    {
        // This is a placeholder. Implement actual calculation based on your requirements.
        return 0;
    }
    
    /**
     * Calculate the pump's uptime percentage.
     *
     * @return float
     */
    protected function calculateUptimePercentage(): float
    {
        // This is a placeholder. Implement actual calculation based on your requirements.
        return 100.0;
    }
    
    /**
     * Check if maintenance is due soon.
     *
     * @return bool
     */
    protected function isMaintenanceDueSoon(): bool
    {
        if (!$this->next_maintenance_date) {
            return false;
        }
        
        $daysUntilMaintenance = now()->diffInDays($this->next_maintenance_date, false);
        return $daysUntilMaintenance >= 0 && $daysUntilMaintenance <= 7;
    }
    
    /**
     * Check if maintenance is overdue.
     *
     * @return bool
     */
    protected function isMaintenanceOverdue(): bool
    {
        if (!$this->next_maintenance_date) {
            return false;
        }
        
        return now()->gt($this->next_maintenance_date);
    }
    
    /**
     * Calculate estimated energy consumption.
     *
     * @return float
     */
    protected function calculateEstimatedEnergyConsumption(): float
    {
        // This is a placeholder. Implement actual calculation based on your requirements.
        if (!$this->power_rating || !$this->total_runtime) {
            return 0.0;
        }
        
        // Convert power rating from W to kW and multiply by hours
        return ($this->power_rating / 1000) * ($this->total_runtime / 3600);
    }
}
