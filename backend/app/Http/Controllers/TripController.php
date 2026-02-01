<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trips = Trip::with('vehicle', 'driver')->get();
        return response()->json(['data' => $trips]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Provide trip details to create.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:drivers,id',
            'start_location' => 'required|string|max:255',
            'end_location' => 'required|string|max:255',
            'distance' => 'nullable|numeric|min:0',
            'trip_date' => 'required|date',
            'status' => 'nullable|in:planned,in_progress,completed,cancelled',
        ]);

        $validated['company_id'] = auth()->user()->company_id ?? 1;
        $validated['status'] = $validated['status'] ?? 'planned';
        
        $trip = Trip::create($validated);
        return response()->json(['data' => $trip->load('vehicle', 'driver')], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip)
    {
        return response()->json(['data' => $trip->load('vehicle', 'driver')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trip $trip)
    {
        return response()->json($trip->load('vehicle', 'driver'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'driver_id' => 'required|exists:drivers,id',
            'start_location' => 'required|string|max:255',
            'end_location' => 'required|string|max:255',
            'distance' => 'nullable|numeric|min:0',
            'trip_date' => 'required|date',
            'status' => 'nullable|in:planned,in_progress,completed,cancelled',
        ]);

        $trip->update($validated);
        return response()->json(['data' => $trip->load('vehicle', 'driver')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        $trip->delete();
        return response()->json(['success' => true, 'message' => 'Trip deleted successfully.']);
    }
}
