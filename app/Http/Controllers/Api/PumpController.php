<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Pump\StorePumpRequest;
use App\Http\Requests\Pump\UpdatePumpRequest;
use App\Http\Resources\PumpResource;
use App\Models\Pump;
use App\Models\Tank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PumpController extends BaseController
{
    /**
     * Display a listing of the pumps.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $pumps = Pump::with(['tank'])->get();
            return $this->sendResponse(
                PumpResource::collection($pumps),
                'Pumps retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve pumps');
        }
    }

    /**
     * Store a newly created pump in storage.
     *
     * @param  \App\Http\Requests\Pump\StorePumpRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePumpRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $pump = Pump::create($validated);
            
            DB::commit();
            
            return $this->sendResponse(
                new PumpResource($pump->load(['tank'])),
                'Pump created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to create pump');
        }
    }

    /**
     * Display the specified pump.
     *
     * @param  \App\Models\Pump  $pump
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Pump $pump): JsonResponse
    {
        try {
            return $this->sendResponse(
                new PumpResource($pump->load(['tank'])),
                'Pump retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve pump');
        }
    }

    /**
     * Update the specified pump in storage.
     *
     * @param  \App\Http\Requests\Pump\UpdatePumpRequest  $request
     * @param  \App\Models\Pump  $pump
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePumpRequest $request, Pump $pump): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $pump->update($validated);
            
            DB::commit();
            
            return $this->sendResponse(
                new PumpResource($pump->load(['tank'])),
                'Pump updated successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update pump');
        }
    }

    /**
     * Remove the specified pump from storage.
     *
     * @param  \App\Models\Pump  $pump
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Pump $pump): JsonResponse
    {
        try {
            // Check if pump is currently in use
            if ($pump->is_running) {
                return $this->sendError(
                    'Cannot delete a pump that is currently running. Please stop the pump first.',
                    [],
                    Response::HTTP_CONFLICT
                );
            }
            
            DB::beginTransaction();
            
            $pump->delete();
            
            DB::commit();
            
            return $this->sendResponse(
                null,
                'Pump deleted successfully.',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to delete pump');
        }
    }
    
    /**
     * Start the specified pump.
     *
     * @param  \App\Models\Pump  $pump
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(Pump $pump): JsonResponse
    {
        try {
            if ($pump->is_running) {
                return $this->sendResponse(
                    new PumpResource($pump->load(['tank'])),
                    'Pump is already running.'
                );
            }
            
            // Check if the tank has enough water
            if ($pump->tank && $pump->tank->current_level <= 0) {
                return $this->sendError(
                    'Cannot start pump. The associated tank is empty.',
                    [],
                    Response::HTTP_CONFLICT
                );
            }
            
            DB::beginTransaction();
            
            $pump->update([
                'is_running' => true,
                'last_started_at' => now(),
                'status' => 'active'
            ]);
            
            DB::commit();
            
            return $this->sendResponse(
                new PumpResource($pump->load(['tank'])),
                'Pump started successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to start pump');
        }
    }
    
    /**
     * Stop the specified pump.
     *
     * @param  \App\Models\Pump  $pump
     * @return \Illuminate\Http\JsonResponse
     */
    public function stop(Pump $pump): JsonResponse
    {
        try {
            if (!$pump->is_running) {
                return $this->sendResponse(
                    new PumpResource($pump->load(['tank'])),
                    'Pump is already stopped.'
                );
            }
            
            DB::beginTransaction();
            
            $pump->update([
                'is_running' => false,
                'last_stopped_at' => now(),
                'status' => 'inactive'
            ]);
            
            DB::commit();
            
            return $this->sendResponse(
                new PumpResource($pump->load(['tank'])),
                'Pump stopped successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to stop pump');
        }
    }
    
    /**
     * Toggle the pump state (start/stop).
     *
     * @param  \App\Models\Pump  $pump
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Pump $pump): JsonResponse
    {
        return $pump->is_running ? $this->stop($pump) : $this->start($pump);
    }
    
    /**
     * Get the status of the pump.
     *
     * @param  \App\Models\Pump  $pump
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Pump $pump): JsonResponse
    {
        try {
            return $this->sendResponse([
                'id' => $pump->id,
                'name' => $pump->name,
                'is_running' => $pump->is_running,
                'status' => $pump->status,
                'status_display' => $pump->getStatusName(),
                'flow_rate' => $pump->flow_rate,
                'pressure' => $pump->pressure,
                'last_started_at' => $pump->last_started_at?->toIso8601String(),
                'last_stopped_at' => $pump->last_stopped_at?->toIso8601String(),
                'tank' => $pump->tank ? [
                    'id' => $pump->tank->id,
                    'name' => $pump->tank->name,
                    'current_level' => $pump->tank->current_level,
                    'capacity' => $pump->tank->capacity,
                ] : null,
                'total_runtime' => $pump->total_runtime,
                'power_consumption' => $pump->power_consumption,
                'efficiency' => $pump->efficiency,
            ], 'Pump status retrieved successfully.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to get pump status');
        }
    }
}
