<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Plot\StorePlotRequest;
use App\Http\Requests\Plot\UpdatePlotRequest;
use App\Http\Resources\PlotResource;
use App\Models\Plot;
use App\Models\Sensor;
use App\Models\Valve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PlotController extends BaseController
{
    /**
     * Display a listing of the plots.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $plots = Plot::with(['valves', 'sensors', 'schedules', 'irrigationEvents'])->get();
            return $this->sendResponse(PlotResource::collection($plots), 'Plots retrieved successfully.');
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve plots');
        }
    }

    /**
     * Store a newly created plot in storage.
     *
     * @param  \App\Http\Requests\Plot\StorePlotRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePlotRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $plot = Plot::create($validated);
            
            // If valves are provided, attach them to the plot
            if ($request->has('valve_ids')) {
                $valveIds = $request->input('valve_ids');
                $plot->valves()->sync($valveIds);
            }
            
            DB::commit();
            
            return $this->sendResponse(
                new PlotResource($plot->load(['valves', 'sensors', 'schedules', 'irrigationEvents'])),
                'Plot created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create plot', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->handleException($e, 'Failed to create plot');
        }
    }

    /**
     * Display the specified plot.
     *
     * @param  \App\Models\Plot  $plot
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Plot $plot): JsonResponse
    {
        try {
            return $this->sendResponse(
                new PlotResource($plot->load(['valves', 'sensors', 'schedules', 'irrigationEvents'])),
                'Plot retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve plot');
        }
    }

    /**
     * Update the specified plot in storage.
     *
     * @param  \App\Http\Requests\Plot\UpdatePlotRequest  $request
     * @param  \App\Models\Plot  $plot
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePlotRequest $request, Plot $plot): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $plot->update($validated);
            
            // Update valves if provided
            if ($request->has('valve_ids')) {
                $valveIds = $request->input('valve_ids');
                $plot->valves()->sync($valveIds);
            }
            
            DB::commit();
            
            return $this->sendResponse(
                new PlotResource($plot->load(['valves', 'sensors', 'schedules', 'irrigationEvents'])),
                'Plot updated successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update plot');
        }
    }

    /**
     * Remove the specified plot from storage.
     *
     * @param  \App\Models\Plot  $plot
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Plot $plot): JsonResponse
    {
        try {
            // Check if plot has any associated records before deleting
            if ($plot->irrigationEvents()->exists() || $plot->schedules()->exists()) {
                return $this->sendError(
                    'Cannot delete plot with associated irrigation events or schedules. Please remove them first.',
                    [],
                    Response::HTTP_CONFLICT
                );
            }
            
            DB::beginTransaction();
            
            // Detach all valves and sensors
            $plot->valves()->detach();
            $plot->sensors()->delete();
            
            // Delete the plot
            $plot->delete();
            
            DB::commit();
            
            return $this->sendResponse(
                null,
                'Plot deleted successfully.',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to delete plot');
        }
    }
}
