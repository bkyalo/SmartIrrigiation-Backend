<?php

namespace App\Http\Controllers;

use App\Models\Tank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TankController extends Controller
{
    /**
     * Display a listing of the tanks.
     */
    public function index()
    {
        $tanks = Tank::latest()->paginate(10);
        return view('tanks.index', compact('tanks'));
    }

    /**
     * Show the form for creating a new tank.
     */
    public function create()
    {
        return view('tanks.create');
    }

    /**
     * Store a newly created tank in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $tank = Tank::create($validated);

        return redirect()->route('tanks.show', $tank)
            ->with('success', 'Tank created successfully!');
    }

    /**
     * Display the specified tank.
     */
    public function show(Tank $tank)
    {
        return view('tanks.show', compact('tank'));
    }

    /**
     * Show the form for editing the specified tank.
     */
    public function edit(Tank $tank)
    {
        return view('tanks.edit', compact('tank'));
    }

    /**
     * Update the specified tank in storage.
     */
    public function update(Request $request, Tank $tank)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|numeric|min:1',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
        ]);

        $tank->update($validated);

        return redirect()->route('tanks.show', $tank)
            ->with('success', 'Tank updated successfully!');
    }

    /**
     * Remove the specified tank from storage.
     */
    public function destroy(Tank $tank)
    {
        $tank->delete();
        
        return redirect()->route('tanks.index')
            ->with('success', 'Tank deleted successfully!');
    }
}
