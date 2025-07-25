<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IrrigationEvent\StoreIrrigationEventRequest;
use App\Http\Requests\IrrigationEvent\UpdateIrrigationEventRequest;
use App\Http\Resources\IrrigationEventResource;
use App\Models\IrrigationEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class IrrigationEventController extends BaseController
{
    /**
     * Display a listing of irrigation events.
     */
    public function index(): JsonResponse
    {
        try {
            $query = IrrigationEvent::with([
                'plot',
                'valves',
                'schedules',
                'approvalRequests'
            ]);
            
            // Apply filters
            if (request()->has('plot_id')) {
                $query->where('plot_id', request('plot_id'));
            }
            
            if (request()->has('status')) {
                $query->where('status', request('status'));
            }
            
            if (request()->has('start_date')) {
                $query->where('start_time', '>=', Carbon::parse(request('start_date')));
            }
            
            if (request()->has('end_date')) {
                $query->where('end_time', '<=', Carbon::parse(request('end_date')));
            }
            
            // Order and paginate
            $events = $query->latest('start_time')
                           ->paginate(min(request('limit', 50), 100));
            
            return $this->sendResponse(
                IrrigationEventResource::collection($events),
                'Irrigation events retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve irrigation events');
        }
    }
    
    /**
     * Store a newly created irrigation event in storage.
     */
    public function store(StoreIrrigationEventRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            // Create the irrigation event
            $event = IrrigationEvent::create($validated);
            
            // Attach valves if provided
            if (isset($validated['valve_ids'])) {
                $event->valves()->sync($validated['valve_ids']);
            }
            
            DB::commit();
            
            return $this->sendResponse(
                new IrrigationEventResource($event->load(['plot', 'valves'])),
                'Irrigation event created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to create irrigation event');
        }
    }
    
    /**
     * Display the specified irrigation event.
     */
    public function show(IrrigationEvent $irrigationEvent): JsonResponse
    {
        try {
            return $this->sendResponse(
                new IrrigationEventResource($irrigationEvent->load([
                    'plot',
                    'valves',
                    'schedules',
                    'approvalRequests'
                ])),
                'Irrigation event retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve irrigation event');
        }
    }
    
    /**
     * Update the specified irrigation event in storage.
     */
    public function update(UpdateIrrigationEventRequest $request, IrrigationEvent $irrigationEvent): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Prevent updating completed or cancelled events
            if (in_array($irrigationEvent->status, ['completed', 'cancelled'])) {
                return $this->sendError(
                    'Cannot update a ' . $irrigationEvent->status . ' irrigation event.',
                    [],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            DB::beginTransaction();
            
            $irrigationEvent->update($validated);
            
            // Sync valves if provided
            if (isset($validated['valve_ids'])) {
                $irrigationEvent->valves()->sync($validated['valve_ids']);
            }
            
            DB::commit();
            
            return $this->sendResponse(
                new IrrigationEventResource($irrigationEvent->load(['plot', 'valves'])),
                'Irrigation event updated successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update irrigation event');
        }
    }
    
    /**
     * Remove the specified irrigation event from storage.
     */
    public function destroy(IrrigationEvent $irrigationEvent): JsonResponse
    {
        try {
            // Prevent deleting in-progress or completed events
            if (in_array($irrigationEvent->status, ['in_progress', 'completed'])) {
                return $this->sendError(
                    'Cannot delete an ' . str_replace('_', ' ', $irrigationEvent->status) . ' irrigation event.',
                    [],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            DB::beginTransaction();
            
            // Detach relationships
            $irrigationEvent->valves()->detach();
            
            // Delete the event
            $irrigationEvent->delete();
            
            DB::commit();
            
            return $this->sendResponse(
                null,
                'Irrigation event deleted successfully.',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to delete irrigation event');
        }
    }
    
    /**
     * Start an irrigation event.
     */
    public function start(IrrigationEvent $irrigationEvent): JsonResponse
    {
        try {
            if ($irrigationEvent->status !== 'scheduled') {
                return $this->sendError(
                    'Only scheduled irrigation events can be started.',
                    [],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            // Check if any valves are currently in use
            $activeValves = $irrigationEvent->valves()
                ->whereHas('irrigationEvents', function ($query) {
                    $query->where('status', 'in_progress');
                })
                ->exists();
                
            if ($activeValves) {
                return $this->sendError(
                    'One or more valves are currently in use by another irrigation event.',
                    [],
                    Response::HTTP_CONFLICT
                );
            }
            
            DB::beginTransaction();
            
            // Update the event status and start time
            $irrigationEvent->update([
                'status' => 'in_progress',
                'start_time' => now(), // Optionally update start time to now
                'actual_start_time' => now(),
            ]);
            
            // TODO: Trigger actual valve opening via MQTT or other service
            
            DB::commit();
            
            return $this->sendResponse(
                new IrrigationEventResource($irrigationEvent->load(['plot', 'valves'])),
                'Irrigation event started successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to start irrigation event');
        }
    }
    
    /**
     * Complete an irrigation event.
     */
    public function complete(IrrigationEvent $irrigationEvent): JsonResponse
    {
        try {
            if ($irrigationEvent->status !== 'in_progress') {
                return $this->sendError(
                    'Only in-progress irrigation events can be completed.',
                    [],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            DB::beginTransaction();
            
            // Calculate water usage if not provided
            $waterVolume = $irrigationEvent->water_volume;
            if (!$waterVolume && $irrigationEvent->water_flow_rate && $irrigationEvent->actual_start_time) {
                $durationMinutes = now()->diffInMinutes($irrigationEvent->actual_start_time);
                $waterVolume = $irrigationEvent->water_flow_rate * ($durationMinutes / 60); // Convert to hours
            }
            
            // Update the event status and end time
            $irrigationEvent->update([
                'status' => 'completed',
                'end_time' => now(),
                'actual_end_time' => now(),
                'water_volume' => $waterVolume,
            ]);
            
            // TODO: Trigger actual valve closing via MQTT or other service
            
            DB::commit();
            
            return $this->sendResponse(
                new IrrigationEventResource($irrigationEvent->load(['plot', 'valves'])),
                'Irrigation event completed successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to complete irrigation event');
        }
    }
    
    /**
     * Cancel an irrigation event.
     */
    public function cancel(IrrigationEvent $irrigationEvent): JsonResponse
    {
        try {
            if (!in_array($irrigationEvent->status, ['scheduled', 'in_progress'])) {
                return $this->sendError(
                    'Only scheduled or in-progress irrigation events can be cancelled.',
                    [],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            DB::beginTransaction();
            
            $updates = ['status' => 'cancelled'];
            
            // If in progress, set end time and close valves
            if ($irrigationEvent->status === 'in_progress') {
                $updates['end_time'] = now();
                $updates['actual_end_time'] = now();
                // TODO: Trigger valve closing via MQTT or other service
            }
            
            $irrigationEvent->update($updates);
            
            DB::commit();
            
            return $this->sendResponse(
                new IrrigationEventResource($irrigationEvent->load(['plot', 'valves'])),
                'Irrigation event cancelled successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to cancel irrigation event');
        }
    }
    
    /**
     * Get statistics for irrigation events.
     */
    public function stats(): JsonResponse
    {
        try {
            $query = IrrigationEvent::query();
            
            // Apply filters
            if (request()->has('plot_id')) {
                $query->where('plot_id', request('plot_id'));
            }
            
            if (request()->has('start_date')) {
                $query->where('start_time', '>=', Carbon::parse(request('start_date')));
            }
            
            if (request()->has('end_date')) {
                $query->where('end_time', '<=', Carbon::parse(request('end_date')));
            } else {
                // Default to last 30 days if no end date provided
                $query->where('end_time', '>=', now()->subDays(30));
            }
            
            // Get basic stats
            $stats = [
                'total_events' => (int) $query->count(),
                'total_water_volume' => (float) $query->sum('water_volume'),
                'avg_water_volume' => (float) $query->avg('water_volume'),
                'avg_duration_minutes' => (float) $query->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as avg_duration')
                    ->value('avg_duration'),
                'status_counts' => $query->select('status')
                    ->selectRaw('COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status'),
                'water_usage_by_plot' => $query->join('plots', 'irrigation_events.plot_id', '=', 'plots.id')
                    ->select('plots.name', DB::raw('SUM(water_volume) as total_water'))
                    ->groupBy('plots.id', 'plots.name')
                    ->pluck('total_water', 'name'),
                'recent_events' => IrrigationEventResource::collection(
                    $query->latest('end_time')
                        ->limit(5)
                        ->with(['plot', 'valves'])
                        ->get()
                ),
            ];
            
            return $this->sendResponse(
                $stats,
                'Irrigation event statistics retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve irrigation event statistics');
        }
    }
}