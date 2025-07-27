<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Valve extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'tank_id',
        'plot_id',
        'external_device_id',
        'valve_direction',
        'is_open',
        'flow_rate',
        'last_actuated',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_open' => 'boolean',
        'flow_rate' => 'decimal:2',
        'last_actuated' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    
    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'is_open' => false,
        'status' => 'operational',
        'valve_direction' => 'outlet', // Default to outlet for backward compatibility
    ];
    
    /**
     * Get the direction options for the valve.
     *
     * @return array
     */
    public static function getDirectionOptions(): array
    {
        return [
            'inlet' => 'Inlet Valve',
            'outlet' => 'Outlet Valve',
        ];
    }
    
    /**
     * Check if the valve is an inlet valve.
     *
     * @return bool
     */
    public function isInlet(): bool
    {
        return $this->valve_direction === 'inlet';
    }
    
    /**
     * Check if the valve is an outlet valve.
     *
     * @return bool
     */
    public function isOutlet(): bool
    {
        return $this->valve_direction === 'outlet';
    }

    // Removed duplicate $attributes property

    /**
     * Get the tank that owns the valve.
     */
    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    /**
     * Get the plot that owns the valve.
     */
    public function plot(): BelongsTo
    {
        return $this->belongsTo(Plot::class)->withDefault();
    }
    
    /**
     * Get all state history records for the valve.
     */
    public function stateHistories(): HasMany
    {
        return $this->hasMany(ValveStateHistory::class)->latest();
    }
    
    /**
     * Get the latest state history record.
     */
    public function latestState()
    {
        return $this->hasOne(ValveStateHistory::class)->latestOfMany();
    }
    
    /**
     * Change the valve state and record the change.
     *
     * @param bool $isOpen Whether to open (true) or close (false) the valve
     * @param string $triggerSource What triggered the state change (manual, schedule, api, system)
     * @param int|null $userId ID of the user who triggered the change, if applicable
     * @param string|null $notes Additional notes about the state change
     * @param array $metadata Additional metadata about the state change
     * @return bool Whether the state change was successful
     */
    public function changeState(bool $isOpen, string $triggerSource = 'manual', ?int $userId = null, ?string $notes = null, array $metadata = []): bool
    {
        // Don't do anything if the state isn't changing
        if ($this->is_open === $isOpen) {
            return true;
        }
        
        try {
            return DB::transaction(function () use ($isOpen, $triggerSource, $userId, $notes, $metadata) {
                // Update the valve state
                $this->is_open = $isOpen;
                $this->last_actuated = now();
                
                if (!$this->save()) {
                    throw new \RuntimeException('Failed to update valve state');
                }
                
                // Record the state change
                $history = $this->stateHistories()->create([
                    'is_open' => $isOpen,
                    'flow_rate' => $this->flow_rate ?? 0,
                    'trigger_source' => $triggerSource,
                    'user_id' => $userId,
                    'notes' => $notes,
                    'metadata' => !empty($metadata) ? $metadata : null,
                ]);
                
                if (!$history) {
                    throw new \RuntimeException('Failed to record valve state history');
                }
                
                // Log the state change for debugging
                \Log::info('Valve state changed', [
                    'valve_id' => $this->id,
                    'is_open' => $isOpen,
                    'trigger_source' => $triggerSource,
                    'user_id' => $userId,
                ]);
                
                return true;
            });
        } catch (\Exception $e) {
            \Log::error('Error changing valve state: ' . $e->getMessage(), [
                'valve_id' => $this->id,
                'is_open' => $isOpen,
                'exception' => $e
            ]);
            
            return false;
        }
    }
    
    /**
     * Open the valve.
     * 
     * @param string $triggerSource What triggered the state change
     * @param int|null $userId ID of the user who triggered the change, if applicable
     * @param string|null $notes Additional notes about the state change
     * @param array $metadata Additional metadata about the state change
     * @return bool Whether the valve was successfully opened
     */
    public function openValve(string $triggerSource = 'manual', ?int $userId = null, ?string $notes = null, array $metadata = []): bool
    {
        if ($this->is_open) {
            return true; // Already open
        }
        return $this->changeState(true, $triggerSource, $userId, $notes, $metadata);
    }
    
    /**
     * Close the valve.
     * 
     * @param string $triggerSource What triggered the state change
     * @param int|null $userId ID of the user who triggered the change, if applicable
     * @param string|null $notes Additional notes about the state change
     * @param array $metadata Additional metadata about the state change
     * @return bool Whether the valve was successfully closed
     */
    public function closeValve(string $triggerSource = 'manual', ?int $userId = null, ?string $notes = null, array $metadata = []): bool
    {
        if (!$this->is_open) {
            return true; // Already closed
        }
        return $this->changeState(false, $triggerSource, $userId, $notes, $metadata);
    }
    
    /**
     * Alias for closeValve() for backward compatibility.
     * 
     * @deprecated Use closeValve() instead
     */
    public function close(string $triggerSource = 'manual', ?int $userId = null, ?string $notes = null, array $metadata = []): bool
    {
        return $this->closeValve($triggerSource, $userId, $notes, $metadata);
    }
    
    /**
     * Toggle the valve state.
     * 
     * @param string $triggerSource What triggered the state change
     * @param int|null $userId ID of the user who triggered the change, if applicable
     * @param string|null $notes Additional notes about the state change
     * @param array $metadata Additional metadata about the state change
     * @return bool Whether the valve state was successfully toggled
     */
    public function toggle(string $triggerSource = 'manual', ?int $userId = null, ?string $notes = null, array $metadata = []): bool
    {
        return $this->changeState(!$this->is_open, $triggerSource, $userId, $notes, $metadata);
    }

    /**
     * Get the irrigation events for the valve.
     */
    public function irrigationEvents(): HasMany
    {
        return $this->hasMany(IrrigationEvent::class);
    }

    // Using openValve() instead of open() to avoid method name conflicts
    // close() and toggle() methods are kept for backward compatibility

    /**
     * Get all main valves.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMain($query)
    {
        return $query->where('type', 'main');
    }

    /**
     * Get all tank valves.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTankValves($query)
    {
        return $query->where('type', 'tank');
    }

    /**
     * Get all plot valves.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePlotValves($query)
    {
        return $query->where('type', 'plot');
    }

    /**
     * Get all open valves.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    /**
     * Get all closed valves.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeClosed($query)
    {
        return $query->where('is_open', false);
    }

    /**
     * Get all operational valves.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOperational($query)
    {
        return $query->where('status', 'operational');
    }
}
