<?php

namespace App\Http\Controllers;

use App\Models\Plot;
use App\Models\Valve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlotController extends Controller
{
    /**
     * Display a listing of the plots.
     */
    public function index()
    {
        $plots = Plot::with('valve')
            ->latest()
            ->paginate(10);

        return view('plots.index', compact('plots'));
    }

    /**
     * Show the form for creating a new plot.
     */
    public function create()
    {
        return view('plots.create', [
            'plot' => new Plot(),
            'statuses' => $this->getStatusOptions(),
            'soilTypes' => $this->getSoilTypeOptions()
        ]);
    }

    /**
     * Store a newly created plot in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'crop_type' => 'nullable|string|max:255',
            'soil_type' => 'nullable|string|max:255',
            'area' => 'required|numeric|min:0.01',
            'moisture_threshold' => 'required|numeric|min:0|max:100',
            'irrigation_duration' => 'required|integer|min:1',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            
            $plot = Plot::create($validated);
            
            DB::commit();
            
            return redirect()
                ->route('plots.show', $plot)
                ->with('success', 'Plot created successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating plot: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to create plot. Please try again.');
        }
    }

    /**
     * Display the specified plot.
     */
    public function show(Plot $plot)
    {
        $plot->load([
            'valve',
            'irrigationEvents' => function ($query) {
                $query->latest()->take(5);
            }
        ]);

        return view('plots.show', compact('plot'));
    }

    /**
     * Show the form for editing the specified plot.
     */
    public function edit(Plot $plot)
    {
        return view('plots.edit', [
            'plot' => $plot,
            'statuses' => $this->getStatusOptions(),
            'soilTypes' => $this->getSoilTypeOptions()
        ]);
    }

    /**
     * Update the specified plot in storage.
     */
    public function update(Request $request, Plot $plot)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'crop_type' => 'nullable|string|max:255',
            'soil_type' => 'nullable|string|max:255',
            'area' => 'required|numeric|min:0.01',
            'moisture_threshold' => 'required|numeric|min:0|max:100',
            'irrigation_duration' => 'required|integer|min:1',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            
            $plot->update($validated);
            
            DB::commit();
            
            return redirect()
                ->route('plots.show', $plot)
                ->with('success', 'Plot updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating plot: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Failed to update plot. Please try again.');
        }
    }

    /**
     * Remove the specified plot from storage.
     */
    public function destroy(Plot $plot)
    {
        try {
            DB::beginTransaction();
            
            // Detach all valves before deleting
            $plot->valves()->update(['plot_id' => null]);
            
            $plot->delete();
            
            DB::commit();
            
            return redirect()
                ->route('plots.index')
                ->with('success', 'Plot deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting plot: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete plot. Please try again.');
        }
    }
    
    /**
     * Get available status options for plots.
     */
    protected function getStatusOptions()
    {
        return [
            'idle' => 'Idle',
            'irrigating' => 'Irrigating',
            'scheduled' => 'Scheduled',
            'error' => 'Error',
        ];
    }
    
    /**
     * Get available soil type options.
     */
    protected function getSoilTypeOptions()
    {
        return [
            'clay' => 'Clay',
            'sandy' => 'Sandy',
            'loamy' => 'Loamy',
            'silty' => 'Silty',
            'peaty' => 'Peaty',
            'chalky' => 'Chalky',
            'clay_loam' => 'Clay Loam',
            'sandy_loam' => 'Sandy Loam',
            'silt_loam' => 'Silt Loam',
        ];
    }
}
