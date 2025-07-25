<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ApprovalRequest\StoreApprovalRequestRequest;
use App\Http\Requests\ApprovalRequest\UpdateApprovalRequestRequest;
use App\Http\Resources\ApprovalRequestResource;
use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ApprovalRequestController extends BaseController
{
    /**
     * Display a listing of approval requests.
     */
    public function index(): JsonResponse
    {
        try {
            $query = ApprovalRequest::with(['requester', 'approver', 'approvable']);
            
            // Apply filters
            if (request()->has('status')) {
                $query->where('status', request('status'));
            }
            
            if (request()->has('action_type')) {
                $query->where('action_type', request('action_type'));
            }
            
            if (request()->has('priority')) {
                $query->where('priority', request('priority'));
            }
            
            // Default sorting
            $sortBy = request('sort_by', 'created_at');
            $sortOrder = request('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            $requests = $query->paginate(min(request('limit', 50), 100));
            
            return $this->sendResponse(
                ApprovalRequestResource::collection($requests),
                'Approval requests retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve approval requests');
        }
    }
    
    /**
     * Store a newly created approval request in storage.
     */
    public function store(StoreApprovalRequestRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            // Set the requested_by to the current user
            $user = auth()->user();
            if ($user) {
                $validated['requested_by'] = $user->id;
            }
            
            // Create the approval request
            $approvalRequest = ApprovalRequest::create($validated);
            
            DB::commit();
            
            return $this->sendResponse(
                new ApprovalRequestResource($approvalRequest->load(['requester', 'approver', 'approvable'])),
                'Approval request created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to create approval request');
        }
    }
    
    /**
     * Display the specified approval request.
     */
    public function show(ApprovalRequest $approvalRequest): JsonResponse
    {
        try {
            return $this->sendResponse(
                new ApprovalRequestResource($approvalRequest->load(['requester', 'approver', 'approvable'])),
                'Approval request retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve approval request');
        }
    }
    
    /**
     * Update the specified approval request in storage.
     */
    public function update(UpdateApprovalRequestRequest $request, ApprovalRequest $approvalRequest): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            // Update the approval request
            $approvalRequest->update($validated);
            
            DB::commit();
            
            return $this->sendResponse(
                new ApprovalRequestResource($approvalRequest->load(['requester', 'approver', 'approvable'])),
                'Approval request updated successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update approval request');
        }
    }
    
    /**
     * Remove the specified approval request from storage.
     */
    public function destroy(ApprovalRequest $approvalRequest): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            // Only allow deletion of pending requests
            if (!$approvalRequest->isPending()) {
                return $this->sendError(
                    'Only pending approval requests can be deleted.',
                    [],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            $approvalRequest->delete();
            
            DB::commit();
            
            return $this->sendResponse(
                null,
                'Approval request deleted successfully.',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to delete approval request');
        }
    }
    
    /**
     * Approve the specified approval request.
     */
    public function approve(ApprovalRequest $approvalRequest): JsonResponse
    {
        try {
            $user = auth()->user();
            $notes = request('notes');
            
            DB::beginTransaction();
            
            $approvalRequest->approve($user, $notes);
            
            DB::commit();
            
            return $this->sendResponse(
                new ApprovalRequestResource($approvalRequest->load(['requester', 'approver', 'approvable'])),
                'Approval request approved successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to approve request');
        }
    }
    
    /**
     * Reject the specified approval request.
     */
    public function reject(ApprovalRequest $approvalRequest): JsonResponse
    {
        try {
            $user = auth()->user();
            $notes = request('notes');
            
            DB::beginTransaction();
            
            $approvalRequest->reject($user, $notes);
            
            DB::commit();
            
            return $this->sendResponse(
                new ApprovalRequestResource($approvalRequest->load(['requester', 'approver', 'approvable'])),
                'Approval request rejected successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to reject request');
        }
    }
    
    /**
     * Cancel the specified approval request.
     */
    public function cancel(ApprovalRequest $approvalRequest): JsonResponse
    {
        try {
            $user = auth()->user();
            $notes = request('notes');
            
            DB::beginTransaction();
            
            // Only the requester can cancel their own request
            if ($user->id !== $approvalRequest->requested_by) {
                return $this->sendError(
                    'Only the requester can cancel this approval request.',
                    [],
                    Response::HTTP_FORBIDDEN
                );
            }
            
            $approvalRequest->cancel($user, $notes);
            
            DB::commit();
            
            return $this->sendResponse(
                new ApprovalRequestResource($approvalRequest->load(['requester', 'approver', 'approvable'])),
                'Approval request cancelled successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to cancel request');
        }
    }
    
    /**
     * Get statistics about approval requests.
     */
    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            $stats = [
                'total' => ApprovalRequest::count(),
                'pending' => ApprovalRequest::pending()->count(),
                'approved' => ApprovalRequest::approved()->count(),
                'rejected' => ApprovalRequest::rejected()->count(),
                'expired' => ApprovalRequest::expired()->count(),
                'cancelled' => ApprovalRequest::where('status', ApprovalRequest::STATUS_CANCELLED)->count(),
                'by_action_type' => ApprovalRequest::select('action_type', DB::raw('count(*) as count'))
                    ->groupBy('action_type')
                    ->pluck('count', 'action_type'),
                'by_priority' => ApprovalRequest::select('priority', DB::raw('count(*) as count'))
                    ->groupBy('priority')
                    ->pluck('count', 'priority'),
            ];
            
            // Add user-specific stats if user is authenticated
            if ($user) {
                $stats['my_pending_requests'] = ApprovalRequest::where('requested_by', $user->id)
                    ->pending()
                    ->count();
                    
                $stats['pending_approvals'] = ApprovalRequest::pending()
                    // This would be filtered by the user's approval permissions in a real app
                    ->count();
            }
            
            return $this->sendResponse(
                $stats,
                'Approval request statistics retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve approval request statistics');
        }
    }
    
    /**
     * Get the available action types for approval requests.
     */
    public function actionTypes(): JsonResponse
    {
        return $this->sendResponse(
            [
                'action_types' => [
                    ApprovalRequest::ACTION_IRRIGATION => 'Irrigation',
                    ApprovalRequest::ACTION_VALVE_CONTROL => 'Valve Control',
                    ApprovalRequest::ACTION_PUMP_CONTROL => 'Pump Control',
                    ApprovalRequest::ACTION_SCHEDULE_UPDATE => 'Schedule Update',
                    ApprovalRequest::ACTION_SYSTEM_CONFIG => 'System Configuration',
                    ApprovalRequest::ACTION_MAINTENANCE => 'Maintenance',
                    ApprovalRequest::ACTION_OTHER => 'Other',
                ],
                'statuses' => [
                    ApprovalRequest::STATUS_PENDING => 'Pending',
                    ApprovalRequest::STATUS_APPROVED => 'Approved',
                    ApprovalRequest::STATUS_REJECTED => 'Rejected',
                    ApprovalRequest::STATUS_EXPIRED => 'Expired',
                    ApprovalRequest::STATUS_CANCELLED => 'Cancelled',
                ],
                'priorities' => [
                    ApprovalRequest::PRIORITY_LOW => 'Low',
                    ApprovalRequest::PRIORITY_NORMAL => 'Normal',
                    ApprovalRequest::PRIORITY_HIGH => 'High',
                    ApprovalRequest::PRIORITY_CRITICAL => 'Critical',
                ],
            ],
            'Available action types, statuses, and priorities retrieved successfully.'
        );
    }
