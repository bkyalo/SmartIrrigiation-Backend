<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ScheduleController extends BaseController
{
    /**
     * Display a listing of schedules.
     * 
     * @queryParam plot_id int Filter schedules by plot ID.
     * @queryParam status string Filter by status (active, paused, completed).
     * @queryParam frequency string Filter by frequency (daily, weekly, monthly, custom).
     * @queryParam is_active boolean Filter by active status.
     * @queryParam due boolean Only show schedules that are due to run.
     */
    public function index(): JsonResponse
    {
        try {
            $query = Schedule::with(['plot', 'irrigationEvents']);
            
            // Apply filters
            if (request()->has('plot_id')) {
                $query->where('plot_id', request('plot_id'));
            }
            
            if (request()->has('status')) {
                $query->where('status', request('status'));
            }
            
            if (request()->has('frequency')) {
                $query->where('frequency', request('frequency'));
            }
            
            if (request()->has('is_active')) {
                $query->where('is_active', filter_var(request('is_active'), FILTER_VALIDATE_BOOLEAN));
            }
            
            if (request()->boolean('due')) {
                $query->due();
            } else {
                $query->latest('next_run');
            }
            
            $schedules = $query->paginate(min(request('limit', 50), 100));
            
            return $this->sendResponse(
                ScheduleResource::collection($schedules),
                'Schedules retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve schedules');
        }
    }
    
    /**
     * Store a newly created schedule in storage.
     */
    public function store(StoreScheduleRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            // Create the schedule
            $schedule = Schedule::create($validated);
            
            // Calculate next run time
            $schedule->calculateNextRun()->save();
            
            DB::commit();
            
            return $this->sendResponse(
                new ScheduleResource($schedule->load(['plot'])),
                'Schedule created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to create schedule');
        }
    }
    
    /**
     * Display the specified schedule.
     */
    public function show(Schedule $schedule): JsonResponse
    {
        try {
            return $this->sendResponse(
                new ScheduleResource($schedule->load(['plot', 'irrigationEvents'])),
                'Schedule retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve schedule');
        }
    }
    
    /**
     * Update the specified schedule in storage.
     */
    public function update(UpdateScheduleRequest $request, Schedule $schedule): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            // Update the schedule
            $schedule->update($validated);
            
            // Recalculate next run if relevant fields were updated
            if ($request->hasAny(['start_time', 'frequency', 'frequency_params', 'end_date', 'is_active'])) {
                $schedule->calculateNextRun()->save();
            }
            
            DB::commit();
            
            return $this->sendResponse(
                new ScheduleResource($schedule->load(['plot', 'irrigationEvents'])),
                'Schedule updated successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update schedule');
        }
    }
    
    /**
     * Remove the specified schedule from storage.
     */
    public function destroy(Schedule $schedule): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            // Delete the schedule
            $schedule->delete();
            
            DB::commit();
            
            return $this->sendResponse(
                null,
                'Schedule deleted successfully.',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to delete schedule');
        }
    }
    
    /**
     * Pause the specified schedule.
     */
    public function pause(Schedule $schedule): JsonResponse
    {
        try {
            if ($schedule->status === Schedule::STATUS_PAUSED) {
                return $this->sendResponse(
                    new ScheduleResource($schedule),
                    'Schedule is already paused.'
                );
            }
            
            $schedule->pause();
            
            return $this->sendResponse(
                new ScheduleResource($schedule->load(['plot'])),
                'Schedule paused successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to pause schedule');
        }
    }
    
    /**
     * Resume the specified schedule.
     */
    public function resume(Schedule $schedule): JsonResponse
    {
        try {
            if ($schedule->status !== Schedule::STATUS_PAUSED) {
                return $this->sendError(
                    'Only paused schedules can be resumed.',
                    [],
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            $schedule->resume();
            
            return $this->sendResponse(
                new ScheduleResource($schedule->load(['plot'])),
                'Schedule resumed successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to resume schedule');
        }
    }
    
    /**
     * Mark the specified schedule as completed.
     */
    public function complete(Schedule $schedule): JsonResponse
    {
        try {
            if ($schedule->status === Schedule::STATUS_COMPLETED) {
                return $this->sendResponse(
                    new ScheduleResource($schedule),
                    'Schedule is already completed.'
                );
            }
            
            $schedule->complete();
            
            return $this->sendResponse(
                new ScheduleResource($schedule->load(['plot'])),
                'Schedule marked as completed successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to complete schedule');
        }
    }
    
    /**
     * Get the next run time for the specified schedule.
     */
    public function nextRun(Schedule $schedule): JsonResponse
    {
        try {
            $nextRun = $schedule->calculateNextRun();
            
            return $this->sendResponse([
                'next_run' => $nextRun->next_run?->toIso8601String(),
                'next_run_for_humans' => $nextRun->next_run?->diffForHumans(),
                'is_due' => $schedule->isDue(),
                'is_active' => $schedule->isActive(),
            ], 'Next run time calculated successfully.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to calculate next run time');
        }
    }
}