<?php

namespace Database\Seeders;

use App\Models\Pump;
use App\Models\Sensor;
use App\Models\Tank;
use App\Models\SensorReading;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DashboardDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create tanks
        $tank1 = Tank::create([
            'name' => 'Main Water Tank',
            'capacity' => 5000, // 5000 liters
            'current_level' => 3200,
            'min_threshold' => 500,
            'max_threshold' => 4500,
            'status' => 'active',
            'is_active' => true,
        ]);

        $tank2 = Tank::create([
            'name' => 'Secondary Water Tank',
            'capacity' => 3000, // 3000 liters
            'current_level' => 2800,
            'min_threshold' => 300,
            'max_threshold' => 2800,
            'status' => 'active',
            'is_active' => true,
        ]);

        // Create water level sensors for tanks
        $sensor1 = Sensor::create([
            'name' => 'Tank 1 Water Level Sensor',
            'type' => 'water_level',
            'location_type' => 'tank',
            'location_id' => $tank1->id,
            'reading_interval' => 5, // minutes
            'status' => 'active',
        ]);

        $sensor2 = Sensor::create([
            'name' => 'Tank 2 Water Level Sensor',
            'type' => 'water_level',
            'location_type' => 'tank',
            'location_id' => $tank2->id,
            'reading_interval' => 5, // minutes
            'status' => 'active',
        ]);

        // Create sensor readings
        SensorReading::create([
            'sensor_id' => $sensor1->id,
            'value' => 64.0, // 64% full
            'water_level' => 64.0,
            'unit' => '%',
            'recorded_at' => now(),
        ]);

        SensorReading::create([
            'sensor_id' => $sensor2->id,
            'value' => 42.0, // 42% full
            'water_level' => 42.0,
            'unit' => '%',
            'recorded_at' => now()->subMinutes(10),
        ]);

        // Create pumps
        Pump::create([
            'name' => 'Main Irrigation Pump',
            'status' => 'running',
            'flow_rate' => 50.0, // L/min
            'power_consumption' => 1200.0, // Watts
            'total_runtime' => 3600, // 1 hour in seconds
            'last_maintenance' => now()->subDays(30),
        ]);

        Pump::create([
            'name' => 'Backup Pump',
            'status' => 'stopped',
            'flow_rate' => 30.0, // L/min
            'power_consumption' => 800.0, // Watts
            'total_runtime' => 1800, // 30 minutes in seconds
            'last_maintenance' => now()->subDays(60),
        ]);
    }
}
