<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use App\Models\Pump;
use App\Models\Sensor;
use App\Models\SensorReading;
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

        // Get system status
        $systemStatus = $this->getSystemStatus($tanks, $pumps);
        
        // Get the latest sensor readings
        $latestReadings = $this->getLatestSensorReadings();

        return view('dashboard', [
            'tanks' => $tanks,
            'pumps' => $pumps,
            'systemStatus' => $systemStatus,
            'latestReadings' => $latestReadings
        ]);
    }

    private function getSystemStatus($tanks, $pumps)
    {
        // Check if any tank has critical water level (below 20%)
        $criticalTank = $tanks->contains(function ($tank) {
            return $tank->water_level < 20;
        });

        // Check if any pump is not working (status is not 'running' or 'stopped')
        $pumpIssues = $pumps->contains(function($pump) {
            return !in_array($pump->status, ['running', 'stopped']);
        });

        return [
            'status' => ($criticalTank || $pumpIssues) ? 'warning' : 'operational',
            'message' => $criticalTank ? 'Low water level detected!' : 
                        ($pumpIssues ? 'Pump issues detected!' : 'All systems operational')
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
