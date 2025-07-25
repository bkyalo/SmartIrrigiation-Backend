<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Valve\StoreValveRequest;
use App\Http\Requests\Valve\UpdateValveRequest;
use App\Http\Resources\ValveResource;
use App\Models\Valve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ValveController extends BaseController
{
    /**
     * Display a listing of the valves.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $valves = Valve::with(['tank', 'plot'])->get();
            return $this->sendResponse(ValveResource::collection($valves), 'Valves retrieved successfully.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve valves');
        }
    }

    /**
     * Store a newly created valve in storage.
     *
     * @param  \App\Http\Requests\Valve\StoreValveRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreValveRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $valve = Valve::create($validated);
            
            // If plot_id is provided, attach the valve to the plot
            if ($request->has('plot_id')) {
                $valve->plot()->associate($request->plot_id);
                $valve->save();
            }
            
            DB::commit();
            
            return $this->sendResponse(
                new ValveResource($valve->load(['tank', 'plot'])),
                'Valve created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to create valve');
        }
    }

    /**
     * Display the specified valve.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Valve $valve): JsonResponse
    {
        try {
            return $this->sendResponse(
                new ValveResource($valve->load(['tank', 'plot'])),
                'Valve retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve valve');
        }
    }

    /**
     * Update the specified valve in storage.
     *
     * @param  \App\Http\Requests\Valve\UpdateValveRequest  $request
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateValveRequest $request, Valve $valve): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $valve->update($validated);
            
            // Update plot association if provided
            if ($request->has('plot_id')) {
                $valve->plot()->associate($request->plot_id);
                $valve->save();
            } elseif ($request->has('plot_id') && $request->plot_id === null) {
                $valve->plot()->dissociate();
                $valve->save();
            }
            
            DB::commit();
            
            return $this->sendResponse(
                new ValveResource($valve->load(['tank', 'plot'])),
                'Valve updated successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update valve');
        }
    }

    /**
     * Remove the specified valve from storage.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Valve $valve): JsonResponse
    {
        try {
            // Check if valve is currently in use
            if ($valve->irrigationEvents()->whereNull('end_time')->exists()) {
                return $this->sendError(
                    'Cannot delete valve that is currently in use by an active irrigation event.',
                    [],
                    Response::HTTP_CONFLICT
                );
            }
            
            DB::beginTransaction();
            
            // Detach from plot if attached
            if ($valve->plot) {
                $valve->plot()->dissociate();
                $valve->save();
            }
            
            // Delete the valve
            $valve->delete();
            
            DB::commit();
            
            return $this->sendResponse(
                null,
                'Valve deleted successfully.',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to delete valve');
        }
    }
    
    /**
     * Open the specified valve.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function open(Valve $valve): JsonResponse
    {
        try {
            if ($valve->is_open) {
                return $this->sendResponse(
                    new ValveResource($valve->load(['tank', 'plot'])),
                    'Valve is already open.'
                );
            }
            
            // Check if the tank has enough water
            if ($valve->tank && $valve->tank->current_level <= 0) {
                return $this->sendError(
                    'Cannot open valve. The associated tank is empty.',
                    [],
                    Response::HTTP_CONFLICT
                );
            }
            
            DB::beginTransaction();
            
            $valve->update([
                'is_open' => true,
                'last_opened_at' => now(),
                'status' => 'active'
            ]);
            
            DB::commit();
            
            return $this->sendResponse(
                new ValveResource($valve->load(['tank', 'plot'])),
                'Valve opened successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to open valve');
        }
    }
    
    /**
     * Close the specified valve.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function close(Valve $valve): JsonResponse
    {
        try {
            if (!$valve->is_open) {
                return $this->sendResponse(
                    new ValveResource($valve->load(['tank', 'plot'])),
                    'Valve is already closed.'
                );
            }
            
            DB::beginTransaction();
            
            $valve->update([
                'is_open' => false,
                'last_closed_at' => now(),
                'status' => 'inactive'
            ]);
            
            DB::commit();
            
            return $this->sendResponse(
                new ValveResource($valve->load(['tank', 'plot'])),
                'Valve closed successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to close valve');
        }
    }
    
    /**
     * Toggle the valve state (open/close).
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Valve $valve): JsonResponse
    {
        return $valve->is_open ? $this->close($valve) : $this->open($valve);
    }
    
    /**
     * Get the status of the valve.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function status(Valve $valve): JsonResponse
    {
        try {
            return $this->sendResponse([
                'id' => $valve->id,
                'name' => $valve->name,
                'is_open' => $valve->is_open,
                'status' => $valve->status,
                'status_display' => $valve->getStatusName(),
                'last_opened_at' => $valve->last_opened_at?->toIso8601String(),
                'last_closed_at' => $valve->last_closed_at?->toIso8601String(),
                'flow_rate' => $valve->flow_rate,
                'tank' => $valve->tank ? [
                    'id' => $valve->tank->id,
                    'name' => $valve->tank->name,
                    'current_level' => $valve->tank->current_level,
                    'capacity' => $valve->tank->capacity,
                ] : null,
                'plot' => $valve->plot ? [
                    'id' => $valve->plot->id,
                    'name' => $valve->plot->name,
                ] : null,
            ], 'Valve status retrieved successfully.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to get valve status');
        }
    }
}
