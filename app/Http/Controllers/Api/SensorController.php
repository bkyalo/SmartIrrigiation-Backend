<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Sensor\StoreSensorRequest;
use App\Http\Requests\Sensor\UpdateSensorRequest;
use App\Http\Resources\SensorResource;
use App\Models\Sensor;
use App\Models\Plot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SensorController extends BaseController
{
    /**
     * Display a listing of the sensors.
     */
    public function index(): JsonResponse
    {
        try {
            $sensors = Sensor::with(['plot', 'sensorType'])->get();
            return $this->sendResponse(
                SensorResource::collection($sensors),
                'Sensors retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve sensors');
        }
    }

    /**
     * Store a newly created sensor in storage.
     */
    public function store(StoreSensorRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $sensor = Sensor::create($validated);
            
            if ($request->has('plot_id')) {
                $sensor->plot()->associate($request->plot_id);
                $sensor->save();
            }
            
            DB::commit();
            
            return $this->sendResponse(
                new SensorResource($sensor->load(['plot', 'sensorType'])),
                'Sensor created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to create sensor');
        }
    }

    /**
     * Display the specified sensor.
     */
    public function show(Sensor $sensor): JsonResponse
    {
        try {
            return $this->sendResponse(
                new SensorResource($sensor->load(['plot', 'sensorType', 'readings'])),
                'Sensor retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve sensor');
        }
    }

    /**
     * Update the specified sensor in storage.
     */
    public function update(UpdateSensorRequest $request, Sensor $sensor): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $sensor->update($validated);
            
            if ($request->has('plot_id')) {
                $sensor->plot()->associate($request->plot_id);
                $sensor->save();
            } elseif ($request->has('plot_id') && $request->plot_id === null) {
                $sensor->plot()->dissociate();
                $sensor->save();
            }
            
            DB::commit();
            
            return $this->sendResponse(
                new SensorResource($sensor->load(['plot', 'sensorType'])),
                'Sensor updated successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update sensor');
        }
    }
