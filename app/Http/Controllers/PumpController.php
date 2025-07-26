<?php

namespace App\Http\Controllers;

use App\Models\Pump;
use Illuminate\Http\Request;

class PumpController extends Controller
{
    /**
     * Display a listing of the pumps.
     */
    public function index()
    {
        $pumps = Pump::latest()->paginate(10);
        return view('pumps.index', compact('pumps'));
    }

    /**
     * Show the form for creating a new pump.
     */
    public function create()
    {
        return view('pumps.create');
    }

    /**
     * Store a newly created pump in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'external_device_id' => 'nullable|string|max:100|unique:pumps,external_device_id',
            'power_consumption' => 'required|numeric|min:0',
            'flow_rate' => 'required|numeric|min:0',
            'error_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        // Set default values
        $pump = Pump::create(array_merge($validated, [
            'status' => 'stopped',
            'total_runtime' => 0,
        ]));

        return redirect()->route('pumps.show', $pump)
            ->with('success', 'Pump created successfully!');
    }

    /**
     * Display the specified pump.
     */
    public function show(Pump $pump)
    {
        // Load the pump with its latest sessions
        $pump->load(['sessions' => function ($query) {
            $query->latest()->take(10);
        }]);
        
        // Calculate session statistics
        $todaySessions = $pump->sessions()
            ->whereDate('created_at', today())
            ->whereNotNull('stopped_at')
            ->get();
            
        $todayRuntime = $todaySessions->sum('duration_seconds') / 60; // in minutes
        $totalRuntime = $pump->getTotalRuntimeInMinutes();
        
        return view('pumps.show', compact('pump', 'todayRuntime', 'totalRuntime'));
    }

    /**
     * Show the form for editing the specified pump.
     */
    public function edit(Pump $pump)
    {
        return view('pumps.edit', compact('pump'));
    }

    /**
     * Update the specified pump in storage.
     */
    public function update(Request $request, Pump $pump)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'external_device_id' => 'nullable|string|max:100|unique:pumps,external_device_id,' . $pump->id,
            'power_consumption' => 'required|numeric|min:0',
            'flow_rate' => 'required|numeric|min:0',
            'error_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        // Don't allow updating status or total_runtime directly through update
        $pump->update($validated);

        return redirect()->route('pumps.show', $pump)
            ->with('success', 'Pump updated successfully!');
    }

    /**
     * Toggle the pump status between running and stopped.
     */
    public function toggleStatus(Pump $pump)
    {
        try {
            if ($pump->status === 'running') {
                // Stop the pump
                $pump->stop();
                $message = 'Pump stopped successfully';
            } else {
                // Start the pump
                $pump->start();
                $message = 'Pump started successfully';
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to toggle pump status: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified pump from storage.
     */
    public function destroy(Pump $pump)
    {
        $pump->delete();
        
        return redirect()->route('pumps.index')
            ->with('success', 'Pump deleted successfully!');
    }
}
