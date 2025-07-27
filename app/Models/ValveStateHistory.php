<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValveStateHistory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'valve_id',
        'is_open',
        'flow_rate',
        'trigger_source',
        'user_id',
        'notes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_open' => 'boolean',
        'flow_rate' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the valve that owns the state history.
     */
    public function valve(): BelongsTo
    {
        return $this->belongsTo(Valve::class);
    }

    /**
     * Get the user who triggered the state change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
