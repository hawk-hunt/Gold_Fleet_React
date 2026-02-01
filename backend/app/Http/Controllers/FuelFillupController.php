<?php

namespace App\Http\Controllers;

use App\Models\FuelFillup;
use Illuminate\Http\Request;

class FuelFillupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fuelFillups = FuelFillup::with('vehicle')->get();
        return response()->json(['data' => $fuelFillups]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Provide fuel fill-up details to create.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'gallons' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'fillup_date' => 'required|date',
            'odometer_reading' => 'required|numeric|min:0',
        ]);

        $validated['company_id'] = auth()->user()->company_id ?? 1;
        $validated['driver_id'] = auth()->user()->driver_id ?? null;
        $validated['cost_per_gallon'] = $validated['cost'] / $validated['gallons'];

        $fuelFillup = FuelFillup::create($validated);
        return response()->json(['data' => $fuelFillup->load('vehicle')], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(FuelFillup $fuelFillup)
    {
        return response()->json(['data' => $fuelFillup->load('vehicle')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FuelFillup $fuelFillup)
    {
        return response()->json($fuelFillup->load('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FuelFillup $fuelFillup)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'gallons' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'fillup_date' => 'required|date',
            'odometer_reading' => 'required|numeric|min:0',
        ]);

        $validated['cost_per_gallon'] = $validated['cost'] / $validated['gallons'];

        $fuelFillup->update($validated);
        return response()->json(['data' => $fuelFillup->load('vehicle')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FuelFillup $fuelFillup)
    {
        $fuelFillup->delete();
        return response()->json(['success' => true, 'message' => 'Fuel fill-up deleted successfully.']);
    }
}
