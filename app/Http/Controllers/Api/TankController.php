<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Tank\StoreTankRequest;
use App\Http\Requests\Tank\UpdateTankRequest;
use App\Http\Resources\TankResource;
use App\Models\Tank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TankController extends BaseController
{
    /**
     * Display a listing of the tanks.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $tanks = Tank::with(['valves', 'sensors'])->get();
            return $this->sendResponse(TankResource::collection($tanks), 'Tanks retrieved successfully.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve tanks');
        }
    }

    /**
     * Store a newly created tank in storage.
     *
     * @param  \App\Http\Requests\Tank\StoreTankRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTankRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $tank = Tank::create($validated);
            
            return $this->sendResponse(
                new TankResource($tank->load(['valves', 'sensors'])),
                'Tank created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to create tank');
        }
    }

    /**
     * Display the specified tank.
     *
     * @param  \App\Models\Tank  $tank
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Tank $tank): JsonResponse
    {
        try {
            return $this->sendResponse(
                new TankResource($tank->load(['valves', 'sensors'])),
                'Tank retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve tank');
        }
    }

    /**
     * Update the specified tank in storage.
     *
     * @param  \App\Http\Requests\Tank\UpdateTankRequest  $request
     * @param  \App\Models\Tank  $tank
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTankRequest $request, Tank $tank): JsonResponse
    {
        try {
            $validated = $request->validated();
            $tank->update($validated);
            
            return $this->sendResponse(
                new TankResource($tank->load(['valves', 'sensors'])),
                'Tank updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to update tank');
        }
    }

    /**
     * Remove the specified tank from storage.
     *
     * @param  \App\Models\Tank  $tank
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Tank $tank): JsonResponse
    {
        try {
            // Check if tank has any valves or sensors before deleting
            if ($tank->valves()->exists() || $tank->sensors()->exists()) {
                return $this->sendError(
                    'Cannot delete tank with associated valves or sensors. Please remove them first.',
                    [],
                    Response::HTTP_CONFLICT
                );
            }
            
            $tank->delete();
            
            return $this->sendResponse(
                null,
                'Tank deleted successfully.',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to delete tank');
        }
    }
    
    /**
     * Get the current fill level of the tank.
     *
     * @param  \App\Models\Tank  $tank
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFillLevel(Tank $tank): JsonResponse
    {
        try {
            return $this->sendResponse([
                'current_level' => $tank->current_level,
                'capacity' => $tank->capacity,
                'fill_percentage' => $tank->getFillPercentage(),
                'status' => $tank->getStatus()
            ], 'Tank fill level retrieved successfully.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve tank fill level');
        }
    }
    
    /**
     * Update the tank's current level.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tank  $tank
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLevel(Request $request, Tank $tank): JsonResponse
    {
        try {
            $request->validate([
                'current_level' => 'required|numeric|min:0|max:' . $tank->capacity,
            ]);
            
            $tank->update(['current_level' => $request->current_level]);
            
            return $this->sendResponse(
                new TankResource($tank->load(['valves', 'sensors'])),
                'Tank level updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to update tank level');
        }
    }
    
    /**
     * Get the tank's status.
     *
     * @param  \App\Models\Tank  $tank
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(Tank $tank): JsonResponse
    {
        try {
            return $this->sendResponse([
                'status' => $tank->getStatus(),
                'needs_refill' => $tank->needsRefill(),
                'is_full' => $tank->isFull(),
                'last_refilled' => $tank->last_refilled,
                'current_level' => $tank->current_level,
                'capacity' => $tank->capacity
            ], 'Tank status retrieved successfully.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve tank status');
        }
    }
}
