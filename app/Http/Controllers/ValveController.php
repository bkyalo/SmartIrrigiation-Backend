<?php

namespace App\Http\Controllers;

use App\Models\Valve;
use App\Models\Tank;
use App\Models\Plot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValveController extends Controller
{
    /**
     * Display a listing of the valves.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $valves = Valve::with(['tank', 'plot'])
            ->latest()
            ->paginate(10);
            
        return view('valves.index', compact('valves'));
    }

    /**
     * Show the form for creating a new valve.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $tanks = Tank::all();
        $plots = Plot::all();
        $types = [
            'tank' => 'Tank Valve',
            'plot' => 'Plot Valve',
            'main' => 'Main Valve'
        ];
        
        $statuses = [
            'operational' => 'Operational',
            'stuck_open' => 'Stuck Open',
            'stuck_closed' => 'Stuck Closed',
            'error' => 'Error'
        ];
        
        $valveDirections = Valve::getDirectionOptions();
        
        return view('valves.create', compact('tanks', 'plots', 'types', 'statuses', 'valveDirections'));
    }

    /**
     * Store a newly created valve in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:tank,plot,main',
            'tank_id' => 'nullable|required_if:type,tank|exists:tanks,id',
            'plot_id' => 'nullable|required_if:type,plot|exists:plots,id',
            'valve_direction' => 'nullable|required_if:type,tank|in:inlet,outlet',
            'flow_rate' => 'required|numeric|min:0',
            'status' => 'required|in:operational,stuck_open,stuck_closed,error',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Only set the appropriate ID based on the valve type
            if ($validated['type'] === 'tank') {
                $validated['plot_id'] = null;
            } elseif ($validated['type'] === 'plot') {
                $validated['tank_id'] = null;
            } else {
                // For main valves, unset both
                $validated['tank_id'] = null;
                $validated['plot_id'] = null;
            }
            
            $valve = Valve::create($validated);
            
            DB::commit();
            
            return redirect()
                ->route('valves.show', $valve)
                ->with('success', 'Valve created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating valve: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to create valve. Please try again.');
        }
    }

    /**
     * Display the specified valve.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\View\View
     */
    public function show(Valve $valve)
    {
        $valve->load(['tank', 'plot', 'irrigationEvents' => function($query) {
            $query->latest()->take(10);
        }]);
        
        return view('valves.show', compact('valve'));
    }

    /**
     * Show the form for editing the specified valve.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\View\View
     */
    public function edit(Valve $valve)
    {
        $tanks = Tank::all();
        $plots = Plot::all();
        $types = [
            'tank' => 'Tank Valve',
            'plot' => 'Plot Valve',
            'main' => 'Main Valve'
        ];
        
        $statuses = [
            'operational' => 'Operational',
            'stuck_open' => 'Stuck Open',
            'stuck_closed' => 'Stuck Closed',
            'error' => 'Error'
        ];
        
        $valveDirections = Valve::getDirectionOptions();
        
        return view('valves.edit', compact('valve', 'tanks', 'plots', 'types', 'statuses', 'valveDirections'));
    }

    /**
     * Update the specified valve in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Valve $valve)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:tank,plot,main',
            'tank_id' => 'nullable|required_if:type,tank|exists:tanks,id',
            'plot_id' => 'nullable|required_if:type,plot|exists:plots,id',
            'valve_direction' => 'nullable|required_if:type,tank|in:inlet,outlet',
            'flow_rate' => 'required|numeric|min:0',
            'status' => 'required|in:operational,stuck_open,stuck_closed,error',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Only set the appropriate ID based on the valve type
            if ($validated['type'] === 'tank') {
                $validated['plot_id'] = null;
            } elseif ($validated['type'] === 'plot') {
                $validated['tank_id'] = null;
            } else {
                // For main valves, unset both
                $validated['tank_id'] = null;
                $validated['plot_id'] = null;
            }
            
            $valve->update($validated);
            
            DB::commit();
            
            return redirect()
                ->route('valves.show', $valve)
                ->with('success', 'Valve updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating valve: ' . $e->getMessage());
            
            return back()
                ->withInput()
                ->with('error', 'Failed to update valve. Please try again.');
        }
    }

    /**
     * Remove the specified valve from storage.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Valve $valve)
    {
        try {
            $valve->delete();
            
            return redirect()
                ->route('valves.index')
                ->with('success', 'Valve deleted successfully.');
                
        } catch (\Exception $e) {
            Log::error('Error deleting valve: ' . $e->getMessage());
            
            return back()
                ->with('error', 'Failed to delete valve. Please try again.');
        }
    }
    
    /**
     * Open the specified valve.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function open(Valve $valve)
    {
        try {
            // Get the authenticated user ID if available
            $userId = auth()->id();
            
            // Open the valve and record the change
            $success = $valve->openValve('manual', $userId, 'Opened via web interface');
            
            if ($success) {
                // Reload the valve to get the updated state
                $valve->refresh();
                
                return response()->json([
                    'success' => true,
                    'is_open' => $valve->is_open,
                    'message' => 'Valve opened successfully.'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Valve could not be opened. It may already be open or in an error state.'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Error opening valve: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to open valve. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Close the specified valve.
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function close(Valve $valve)
    {
        try {
            // Get the authenticated user ID if available
            $userId = auth()->id();
            
            // Close the valve and record the change
            $success = $valve->closeValve('manual', $userId, 'Closed via web interface');
            
            if ($success) {
                // Reload the valve to get the updated state
                $valve->refresh();
                
                return response()->json([
                    'success' => true,
                    'is_open' => $valve->is_open,
                    'message' => 'Valve closed successfully.'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Valve could not be closed. It may already be closed or in an error state.'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Error closing valve: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to close valve. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Toggle the valve state (open/close).
     *
     * @param  \App\Models\Valve  $valve
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle(Valve $valve)
    {
        try {
            // Get the authenticated user ID if available
            $userId = auth()->id();
            
            // Toggle the valve state and record the change
            $success = $valve->toggle('manual', $userId, 'Toggled via web interface');
            
            if ($success) {
                // Reload the valve to get the updated state
                $valve->refresh();
                
                return response()->json([
                    'success' => true,
                    'is_open' => $valve->is_open,
                    'message' => $valve->is_open ? 'Valve opened successfully.' : 'Valve closed successfully.'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Valve is not in an operational state.'
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Error toggling valve: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle valve. Please try again.'
            ], 500);
        }
    }
}
