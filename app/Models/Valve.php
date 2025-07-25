<?php

namespace App\Models;

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
    ];

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
        return $this->belongsTo(Plot::class);
    }

    /**
     * Get the irrigation events for the valve.
     */
    public function irrigationEvents(): HasMany
    {
        return $this->hasMany(IrrigationEvent::class);
    }

    /**
     * Open the valve.
     *
     * @return bool
     */
    public function open(): bool
    {
        if ($this->status !== 'operational') {
            return false;
        }

        $this->is_open = true;
        $this->last_actuated = now();
        return $this->save();
    }

    /**
     * Close the valve.
     *
     * @return bool
     */
    public function close(): bool
    {
        if ($this->status !== 'operational') {
            return false;
        }

        $this->is_open = false;
        $this->last_actuated = now();
        return $this->save();
    }

    /**
     * Toggle the valve state.
     *
     * @return bool
     */
    public function toggle(): bool
    {
        return $this->is_open ? $this->close() : $this->open();
    }

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
