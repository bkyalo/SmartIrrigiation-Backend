<?php

namespace App\Console\Commands;

use App\Models\Valve;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionMethod;

class TestValveModel extends Command
{
    protected $signature = 'test:valve-model';
    protected $description = 'Test the Valve model for duplicate methods';

    public function handle()
    {
        $this->info('Testing Valve model for duplicate methods...');
        
        $reflection = new ReflectionClass(Valve::class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        $methodNames = [];
        $duplicates = [];
        
        foreach ($methods as $method) {
            $name = $method->getName();
            if (in_array($name, $methodNames)) {
                $duplicates[] = $name;
            } else {
                $methodNames[] = $name;
            }
        }
        
        if (empty($duplicates)) {
            $this->info('No duplicate method names found in Valve model.');
        } else {
            $this->error('Found duplicate method names: ' . implode(', ', $duplicates));
        }
        
        // Test creating a valve
        try {
            $valve = new Valve();
            $valve->name = 'Test Valve';
            $valve->type = 'main'; // Using a valid valve type from enum: 'tank', 'plot', 'main'
            $valve->save();
            $this->info('Successfully created a test valve.');
            
            // Test open/close methods
            $valve->openValve();
            $this->info('Successfully opened the valve.');
            
            $valve->closeValve();
            $this->info('Successfully closed the valve.');
            
            // Clean up
            $valve->delete();
            $this->info('Test valve deleted.');
            
        } catch (\Exception $e) {
            $this->error('Error testing valve: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->error($e->getTraceAsString());
        }
        
        return 0;
    }
}
