<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SensorReading\StoreSensorReadingRequest;
use App\Http\Requests\SensorReading\UpdateSensorReadingRequest;
use App\Http\Resources\SensorReadingResource;
use App\Models\SensorReading;
use App\Models\Sensor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SensorReadingController extends BaseController
{
    /**
     * Display a listing of the sensor readings.
     */
    public function index(): JsonResponse
    {
        try {
            $readings = SensorReading::with(['sensor'])->get();
            return $this->sendResponse(
                SensorReadingResource::collection($readings),
                'Sensor readings retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve sensor readings');
        }
    }

    /**
     * Store a newly created sensor reading in storage.
     */
    public function store(StoreSensorReadingRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $sensor = Sensor::findOrFail($validated['sensor_id']);
            $reading = $sensor->readings()->create($validated);
            
            DB::commit();
            
            return $this->sendResponse(
                new SensorReadingResource($reading->load('sensor')),
                'Sensor reading created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to create sensor reading');
        }
    }

    /**
     * Display the specified sensor reading.
     */
    public function show(SensorReading $sensorReading): JsonResponse
    {
        try {
            return $this->sendResponse(
                new SensorReadingResource($sensorReading->load('sensor')),
                'Sensor reading retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to retrieve sensor reading');
        }
    }

    /**
     * Update the specified sensor reading in storage.
     */
    public function update(UpdateSensorReadingRequest $request, SensorReading $sensorReading): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            DB::beginTransaction();
            
            $sensorReading->update($validated);
            
            DB::commit();
            
            return $this->sendResponse(
                new SensorReadingResource($sensorReading->load('sensor')),
                'Sensor reading updated successfully.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to update sensor reading');
        }
    }

    /**
     * Remove the specified sensor reading from storage.
     */
    public function destroy(SensorReading $sensorReading): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $sensorReading->delete();
            
            DB::commit();
            
            return $this->sendResponse(
                null,
                'Sensor reading deleted successfully.',
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->handleException($e, 'Failed to delete sensor reading');
        }
    }
}
