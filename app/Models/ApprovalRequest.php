<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class ApprovalRequest extends Model
{
    use SoftDeletes;

    // Action types that require approval
    public const ACTION_IRRIGATION = 'irrigation';
    public const ACTION_VALVE_CONTROL = 'valve_control';
    public const ACTION_PUMP_CONTROL = 'pump_control';
    public const ACTION_SCHEDULE_UPDATE = 'schedule_update';
    public const ACTION_SYSTEM_CONFIG = 'system_config';
    public const ACTION_MAINTENANCE = 'maintenance';
    public const ACTION_OTHER = 'other';

    // Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    // Priority levels
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'requested_by',
        'action_type',
        'action_parameters',
        'status',
        'priority',
        'request_notes',
        'response_notes',
        'approved_by',
        'approved_at',
        'expires_at',
        'metadata',
        'approvable_id',
        'approvable_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'action_parameters' => 'array',
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'priority' => self::PRIORITY_NORMAL,
        'action_parameters' => '{}',
        'metadata' => '{}',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'approved_at',
        'expires_at',
        'deleted_at',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default expires_at if not provided
        static::creating(function ($approvalRequest) {
            if (empty($approvalRequest->expires_at)) {
                $approvalRequest->expires_at = now()->addHours(24); // Default to 24 hours
            }
        });

        // Handle status changes
        static::updating(function ($approvalRequest) {
            // If status is being updated to approved/rejected, set the approved_at timestamp
            if ($approvalRequest->isDirty('status') && 
                in_array($approvalRequest->status, [self::STATUS_APPROVED, self::STATUS_REJECTED]) && 
                empty($approvalRequest->approved_at)) {
                $approvalRequest->approved_at = now();
            }

            // If status is being updated to expired, clear the approved_by
            if ($approvalRequest->isDirty('status') && $approvalRequest->status === self::STATUS_EXPIRED) {
                $approvalRequest->approved_by = null;
            }
        });
    }

    /**
     * Get the user who requested the approval.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved/rejected the request.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the parent approvable model (polymorphic).
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include pending approval requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope a query to only include approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope a query to only include rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope a query to only include expired requests.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where('expires_at', '<=', now());
    }

    /**
     * Check if the approval request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if the approval request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the approval request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if the approval request is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_PENDING && 
               $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the approval request is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Approve the request.
     */
    public function approve(User $approver, ?string $notes = null): bool
    {
        if ($this->isPending()) {
            $this->status = self::STATUS_APPROVED;
            $this->approved_by = $approver->id;
            $this->approved_at = now();
            $this->response_notes = $notes;
            
            return $this->save();
        }
        
        return false;
    }

    /**
     * Reject the request.
     */
    public function reject(User $approver, ?string $notes = null): bool
    {
        if ($this->isPending()) {
            $this->status = self::STATUS_REJECTED;
            $this->approved_by = $approver->id;
            $this->approved_at = now();
            $this->response_notes = $notes;
            
            return $this->save();
        }
        
        return false;
    }

    /**
     * Cancel the request.
     */
    public function cancel(User $user, ?string $notes = null): bool
    {
        if ($this->isPending()) {
            $this->status = self::STATUS_CANCELLED;
            $this->response_notes = $notes;
            
            // If the requester is cancelling, keep the original requester
            // Otherwise, if an admin is cancelling, note who did it
            if ($this->requested_by !== $user->id) {
                $this->approved_by = $user->id;
                $this->approved_at = now();
            }
            
            return $this->save();
        }
        
        return false;
    }

    /**
     * Get the status in a human-readable format.
     */
    public function getStatusNameAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get the priority in a human-readable format.
     */
    public function getPriorityNameAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
            default => ucfirst($this->priority),
        };
    }

    /**
     * Get the action type in a human-readable format.
     */
    public function getActionTypeNameAttribute(): string
    {
        return match($this->action_type) {
            self::ACTION_IRRIGATION => 'Irrigation',
            self::ACTION_VALVE_CONTROL => 'Valve Control',
            self::ACTION_PUMP_CONTROL => 'Pump Control',
            self::ACTION_SCHEDULE_UPDATE => 'Schedule Update',
            self::ACTION_SYSTEM_CONFIG => 'System Configuration',
            self::ACTION_MAINTENANCE => 'Maintenance',
            self::ACTION_OTHER => 'Other',
            default => ucfirst($this->action_type),
        };
    }

    /**
     * Get the time remaining before expiration in a human-readable format.
     */
    public function getTimeRemainingAttribute(): ?string
    {
        if (!$this->expires_at) {
            return 'No expiration';
        }

        if ($this->expires_at->isPast()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans(now(), [
            'syntax' => Carbon::DIFF_ABSOLUTE,
            'parts' => 2,
        ]);
    }

    /**
     * Get the time since the request was created in a human-readable format.
     */
    public function getTimeSinceCreatedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
