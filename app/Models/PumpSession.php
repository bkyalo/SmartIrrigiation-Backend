<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PumpSession extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pump_id',
        'started_at',
        'stopped_at',
        'duration_seconds',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    /**
     * Get the pump that owns the session.
     */
    public function pump(): BelongsTo
    {
        return $this->belongsTo(Pump::class);
    }

    /**
     * Scope a query to only include running sessions.
     */
    public function scopeRunning($query)
    {
        return $query->whereNull('stopped_at');
    }

    /**
     * Stop the session and calculate duration.
     */
    public function stop(): void
    {
        if (!$this->stopped_at) {
            $this->stopped_at = now();
            $this->duration_seconds = $this->started_at->diffInSeconds($this->stopped_at);
            $this->save();
        }
    }
}
