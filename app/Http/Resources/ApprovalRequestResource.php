<?php

namespace App\Http\Resources;

use App\Models\ApprovalRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRequestResource extends JsonResource
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
            'action_type' => $this->action_type,
            'action_type_name' => $this->action_type_name,
            'status' => $this->status,
            'status_name' => $this->status_name,
            'priority' => $this->priority,
            'priority_name' => $this->priority_name,
            'request_notes' => $this->request_notes,
            'response_notes' => $this->response_notes,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'expires_at_for_humans' => $this->expires_at?->diffForHumans(),
            'time_remaining' => $this->time_remaining,
            'is_expired' => $this->isExpired(),
            'is_pending' => $this->isPending(),
            'is_approved' => $this->isApproved(),
            'is_rejected' => $this->isRejected(),
            'is_cancelled' => $this->isCancelled(),
            'action_parameters' => $this->action_parameters ?? (object)[],
            'metadata' => $this->metadata ?? (object)[],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'time_since_created' => $this->time_since_created,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'approved_at_for_humans' => $this->approved_at?->diffForHumans(),
            
            // Relationships
            'requester' => $this->whenLoaded('requester', function () {
                return new UserResource($this->requester);
            }),
            'approver' => $this->whenLoaded('approver', function () {
                return $this->approver ? new UserResource($this->approver) : null;
            }),
            'approvable' => $this->whenLoaded('approvable', function () {
                // Return the approvable resource based on its type
                if (!$this->approvable) return null;
                
                $resourceClass = 'App\\Http\\Resources\\' . class_basename($this->approvable) . 'Resource';
                
                if (class_exists($resourceClass)) {
                    return new $resourceClass($this->approvable);
                }
                
                return $this->approvable;
            }),
            
            // Links
            'links' => [
                'self' => route('api.approval-requests.show', $this->id),
                'approve' => $this->isPending() ? route('api.approval-requests.approve', $this->id) : null,
                'reject' => $this->isPending() ? route('api.approval-requests.reject', $this->id) : null,
                'cancel' => $this->isPending() ? route('api.approval-requests.cancel', $this->id) : null,
                'requester' => $this->when($this->requester, function () {
                    return route('api.users.show', $this->requested_by);
                }),
                'approver' => $this->when($this->approver, function () {
                    return route('api.users.show', $this->approved_by);
                }),
                'approvable' => $this->when($this->approvable, function () {
                    $type = strtolower(class_basename($this->approvable_type));
                    $type = str_plural($type);
                    return route("api.{$type}.show", $this->approvable_id);
                }),
            ],
        ];
    }
    
    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'meta' => [
                'statuses' => [
                    ApprovalRequest::STATUS_PENDING => 'Pending',
                    ApprovalRequest::STATUS_APPROVED => 'Approved',
                    ApprovalRequest::STATUS_REJECTED => 'Rejected',
                    ApprovalRequest::STATUS_EXPIRED => 'Expired',
                    ApprovalRequest::STATUS_CANCELLED => 'Cancelled',
                ],
                'action_types' => [
                    ApprovalRequest::ACTION_IRRIGATION => 'Irrigation',
                    ApprovalRequest::ACTION_VALVE_CONTROL => 'Valve Control',
                    ApprovalRequest::ACTION_PUMP_CONTROL => 'Pump Control',
                    ApprovalRequest::ACTION_SCHEDULE_UPDATE => 'Schedule Update',
                    ApprovalRequest::ACTION_SYSTEM_CONFIG => 'System Configuration',
                    ApprovalRequest::ACTION_MAINTENANCE => 'Maintenance',
                    ApprovalRequest::ACTION_OTHER => 'Other',
                ],
                'priorities' => [
                    ApprovalRequest::PRIORITY_LOW => 'Low',
                    ApprovalRequest::PRIORITY_NORMAL => 'Normal',
                    ApprovalRequest::PRIORITY_HIGH => 'High',
                    ApprovalRequest::PRIORITY_CRITICAL => 'Critical',
                ],
            ],
        ];
    }
}
