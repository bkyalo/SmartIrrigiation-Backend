<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use App\Models\Plot;
use App\Models\Tank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SensorController extends Controller
{
    /**
     * Display a listing of sensors.
     */
    public function index()
    {
        $sensors = Sensor::with(['location'])->latest()->paginate(10);
        return view('sensors.index', compact('sensors'));
    }

    /**
     * Show the form for creating a new sensor.
     */
    public function create()
    {
        $types = [
            'soil_moisture' => 'Soil Moisture',
            'temperature' => 'Temperature',
            'humidity' => 'Humidity',
            'rainfall' => 'Rainfall',
            'water_level' => 'Water Level',
            'flow' => 'Flow',
            'pressure' => 'Pressure',
            'ph' => 'pH',
            'ec' => 'EC',
            'light' => 'Light',
        ];

        $locationTypes = [
            'plot' => 'Plot',
            'tank' => 'Tank',
        ];

        $plots = Plot::all();
        $tanks = Tank::all();

        return view('sensors.create', compact('types', 'locationTypes', 'plots', 'tanks'));
    }

    /**
     * Store a newly created sensor in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'external_device_id' => 'required|string|max:255|unique:sensors,external_device_id',
            'type' => 'required|string|in:soil_moisture,temperature,humidity,rainfall,water_level,flow,pressure,ph,ec,light',
            'location_type' => 'required|string|in:plot,tank',
            'location_id' => 'required|integer',
            'reading_interval' => 'required|integer|min:30',
            'status' => 'required|string|in:active,inactive,error',
            'metadata' => 'nullable|json',
        ]);

        try {
            DB::beginTransaction();
            
            $sensor = Sensor::create($validated);
            
            DB::commit();
            
            return redirect()->route('sensors.show', $sensor)
                ->with('success', 'Sensor created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating sensor: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create sensor. Please try again.');
        }
    }

    /**
     * Display the specified sensor.
     */
    public function show(Sensor $sensor)
    {
        $sensor->load('location');
        $readings = $sensor->readings()
            ->latest('recorded_at')
            ->paginate(20);
            
        return view('sensors.show', compact('sensor', 'readings'));
    }

    /**
     * Show the form for editing the specified sensor.
     */
    public function edit(Sensor $sensor)
    {
        $types = [
            'soil_moisture' => 'Soil Moisture',
            'temperature' => 'Temperature',
            'humidity' => 'Humidity',
            'rainfall' => 'Rainfall',
            'water_level' => 'Water Level',
            'flow' => 'Flow',
            'pressure' => 'Pressure',
            'ph' => 'pH',
            'ec' => 'EC',
            'light' => 'Light',
        ];

        $locationTypes = [
            'plot' => 'Plot',
            'tank' => 'Tank',
        ];

        $plots = Plot::all();
        $tanks = Tank::all();
        
        $sensor->load('location');
        
        return view('sensors.edit', compact('sensor', 'types', 'locationTypes', 'plots', 'tanks'));
    }

    /**
     * Update the specified sensor in storage.
     */
    public function update(Request $request, Sensor $sensor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'external_device_id' => 'required|string|max:255|unique:sensors,external_device_id,' . $sensor->id,
            'type' => 'required|string|in:soil_moisture,temperature,humidity,rainfall,water_level,flow,pressure,ph,ec,light',
            'location_type' => 'required|string|in:plot,tank',
            'location_id' => 'required|integer',
            'reading_interval' => 'required|integer|min:30',
            'status' => 'required|string|in:active,inactive,error',
            'metadata' => 'nullable|json',
        ]);

        try {
            DB::beginTransaction();
            
            $sensor->update($validated);
            
            DB::commit();
            
            return redirect()->route('sensors.show', $sensor)
                ->with('success', 'Sensor updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating sensor: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to update sensor. Please try again.');
        }
    }

    /**
     * Remove the specified sensor from storage.
     */
    public function destroy(Sensor $sensor)
    {
        try {
            DB::beginTransaction();
            
            $sensor->delete();
            
            DB::commit();
            
            return redirect()->route('sensors.index')
                ->with('success', 'Sensor deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting sensor: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete sensor. Please try again.');
        }
    }
}
