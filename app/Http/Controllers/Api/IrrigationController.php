<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IrrigationEventResource;
use App\Jobs\ProcessScheduledIrrigation;
use App\Models\IrrigationEvent;
use App\Models\Plot;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class IrrigationController extends Controller
{
    /**
     * Start manual irrigation for a plot.
     *
     * @param Request $request
     * @param int $plotId
     * @return JsonResponse
     */
    public function startManualIrrigation(Request $request, $plotId)
    {
        $plot = Plot::findOrFail($plotId);
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'duration_minutes' => 'nullable|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Start manual irrigation
            $event = $plot->startManualIrrigation(
                Auth::id(),
                $request->input('duration_minutes')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Irrigation started successfully',
                'data' => new IrrigationEventResource($event->load('valve', 'user'))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to start manual irrigation', [
                'plot_id' => $plotId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start irrigation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Schedule a one-time irrigation event.
     *
     * @param Request $request
     * @param int $plotId
     * @return JsonResponse
     */
    public function scheduleOneTimeIrrigation(Request $request, $plotId)
    {
        $plot = Plot::findOrFail($plotId);
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date|after:now',
            'duration_minutes' => 'nullable|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Schedule one-time irrigation
            $event = $plot->scheduleOneTimeIrrigation(
                new Carbon($request->input('start_time')),
                Auth::id(),
                $request->input('duration_minutes')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Irrigation scheduled successfully',
                'data' => new IrrigationEventResource($event->load('valve', 'user'))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to schedule one-time irrigation', [
                'plot_id' => $plotId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule irrigation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Schedule recurring irrigation.
     *
     * @param Request $request
     * @param int $plotId
     * @return JsonResponse
     */
    public function scheduleRecurringIrrigation(Request $request, $plotId)
    {
        $plot = Plot::findOrFail($plotId);
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'start_time' => 'required|date|after:now',
            'recurrence_rule' => 'required|string|in:DAILY,WEEKLY,MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY',
            'end_date' => 'nullable|date|after:start_time',
            'duration_minutes' => 'nullable|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Schedule recurring irrigation
            $event = $plot->scheduleRecurringIrrigation(
                $request->input('recurrence_rule'),
                new Carbon($request->input('start_time')),
                Auth::id(),
                $request->filled('end_date') ? new Carbon($request->input('end_date')) : null,
                $request->input('duration_minutes')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Recurring irrigation scheduled successfully',
                'data' => new IrrigationEventResource($event->load('valve', 'user'))
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to schedule recurring irrigation', [
                'plot_id' => $plotId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule recurring irrigation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get upcoming irrigation events for a plot.
     *
     * @param int $plotId
     * @return JsonResponse
     */
    public function getUpcomingEvents($plotId)
    {
        $plot = Plot::findOrFail($plotId);
        
        $events = $plot->upcomingIrrigationEvents()
            ->with('valve', 'user')
            ->orderBy('start_time')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => IrrigationEventResource::collection($events)
        ]);
    }
    
    /**
     * Get past irrigation events for a plot.
     *
     * @param int $plotId
     * @return JsonResponse
     */
    public function getPastEvents($plotId)
    {
        $plot = Plot::findOrFail($plotId);
        
        $events = $plot->pastIrrigationEvents()
            ->with('valve', 'user')
            ->orderBy('end_time', 'desc')
            ->paginate(10);
            
        return response()->json([
            'success' => true,
            'data' => IrrigationEventResource::collection($events),
            'pagination' => [
                'total' => $events->total(),
                'per_page' => $events->perPage(),
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
            ]
        ]);
    }
    
    /**
     * Cancel a scheduled irrigation event.
     *
     * @param int $eventId
     * @return JsonResponse
     */
    public function cancelEvent($eventId)
    {
        $event = IrrigationEvent::findOrFail($eventId);
        
        // Check if the event can be cancelled
        if (!in_array($event->status, [IrrigationEvent::STATUS_SCHEDULED, IrrigationEvent::STATUS_IN_PROGRESS])) {
            return response()->json([
                'success' => false,
                'message' => 'Only scheduled or in-progress events can be cancelled.'
            ], 400);
        }
        
        // Update the event status
        $event->update([
            'status' => IrrigationEvent::STATUS_CANCELLED,
            'end_time' => now(),
        ]);
        
        // If the event is in progress, close the valve
        if ($event->status === IrrigationEvent::STATUS_IN_PROGRESS && $event->valve) {
            $event->valve->close();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Irrigation event cancelled successfully',
            'data' => new IrrigationEventResource($event)
        ]);
    }
    
    /**
     * Stop an in-progress irrigation event.
     *
     * @param int $eventId
     * @return JsonResponse
     */
    public function stopEvent($eventId)
    {
        $event = IrrigationEvent::findOrFail($eventId);
        
        // Check if the event is in progress
        if ($event->status !== IrrigationEvent::STATUS_IN_PROGRESS) {
            return response()->json([
                'success' => false,
                'message' => 'Only in-progress events can be stopped.'
            ], 400);
        }
        
        // Calculate actual duration
        $actualDuration = now()->diffInMinutes($event->start_time);
        
        // Update the event status
        $event->update([
            'status' => IrrigationEvent::STATUS_COMPLETED,
            'end_time' => now(),
            'duration_minutes' => $actualDuration,
        ]);
        
        // Close the valve if it's open
        if ($event->valve && $event->valve->is_open) {
            $event->valve->close();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Irrigation stopped successfully',
            'data' => new IrrigationEventResource($event)
        ]);
    }
}
