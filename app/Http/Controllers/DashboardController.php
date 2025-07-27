<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Models\Pump;
use App\Models\Valve;
use App\Models\Sensor;
use App\Models\SensorReading;
use App\Models\Plot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get all tanks with their latest sensor readings
        $tanks = Tank::with(['sensors' => function($query) {
            $query->with(['readings' => function($q) {
                $q->latest()->take(1);
            }]);
        }])->get();

        // Transform the tanks data to include the latest water level reading
        $tanks->each(function ($tank) {
            $tank->water_level = 0;
            
            if ($tank->sensors->isNotEmpty()) {
                foreach ($tank->sensors as $sensor) {
                    if ($sensor->type === Sensor::TYPE_WATER_LEVEL && $sensor->readings->isNotEmpty()) {
                        $tank->water_level = $sensor->readings->first()->water_level ?? 0;
                        break;
                    }
                }
            }
        });

        // Get all pumps with their latest status
        $pumps = Pump::all();
        
        // Get all valves with their latest status
        $valves = Valve::with(['tank', 'plot'])->get();
        
        // Get all plots with valve count
        $plots = Plot::withCount('valves')->get();

        // Get system status
        $systemStatus = $this->getSystemStatus($tanks, $pumps, $valves);
        
        // Get the latest sensor readings
        $latestReadings = $this->getLatestSensorReadings();

        return view('dashboard', [
            'tanks' => $tanks,
            'pumps' => $pumps,
            'valves' => $valves,
            'plots' => $plots,
            'systemStatus' => $systemStatus,
            'latestReadings' => $latestReadings
        ]);
    }

    private function getSystemStatus($tanks, $pumps, $valves)
    {
        // Default to operational
        $status = 'operational';
        $message = 'All systems are operating normally.';

        // Check tanks
        foreach ($tanks as $tank) {
            if ($tank->water_level < 20) {
                $status = 'warning';
                $message = "Low water level in {$tank->name}. Please check the water supply.";
                return [
                    'status' => $status,
                    'message' => $message
                ];
            }
        }

        // Check pumps
        foreach ($pumps as $pump) {
            if ($pump->status === 'error') {
                return [
                    'status' => 'warning',
                    'message' => "Issue detected with pump {$pump->name}. Please check the pump status."
                ];
            }
        }
        
        // Check valves
        foreach ($valves as $valve) {
            if ($valve->status !== 'operational') {
                $statusText = str_replace('_', ' ', $valve->status);
                return [
                    'status' => 'warning',
                    'message' => "Valve '{$valve->name}' is {$statusText}. Please check the valve status."
                ];
            }
        }

        return [
            'status' => $status,
            'message' => $message
        ];
    }
    
    private function getLatestSensorReadings()
    {
        // Get the latest reading for each sensor type
        $temperature = SensorReading::whereHas('sensor', function($query) {
                $query->where('type', Sensor::TYPE_TEMPERATURE);
            })
            ->latest()
            ->first();
            
        $humidity = SensorReading::whereHas('sensor', function($query) {
                $query->where('type', Sensor::TYPE_HUMIDITY);
            })
            ->latest()
            ->first();
            
        $soilMoisture = SensorReading::whereHas('sensor', function($query) {
                $query->where('type', Sensor::TYPE_SOIL_MOISTURE);
            })
            ->latest()
            ->first();
            
        $waterFlow = SensorReading::whereHas('sensor', function($query) {
                $query->where('type', Sensor::TYPE_FLOW);
            })
            ->latest()
            ->first();
        
        return [
            'temperature' => $temperature ? $temperature->value : 0,
            'humidity' => $humidity ? $humidity->value : 0,
            'soil_moisture' => $soilMoisture ? $soilMoisture->value : 0,
            'water_flow' => $waterFlow ? $waterFlow->value : 0
        ];
    }
}
