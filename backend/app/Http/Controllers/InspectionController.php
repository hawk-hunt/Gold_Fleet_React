<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inspections = Inspection::with('vehicle')->get();
        return response()->json(['data' => $inspections]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Provide inspection details to create.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'inspection_date' => 'required|date',
            'result' => 'nullable|in:pass,fail,conditional_pass',
            'notes' => 'nullable|string',
            'next_due_date' => 'nullable|date',
        ]);

        $validated['company_id'] = auth()->user()->company_id ?? 1;
        $validated['driver_id'] = $request->input('driver_id');
        $validated['result'] = $validated['result'] ?? 'pending';
        $validated['status'] = $request->input('status', 'pending');

        $inspection = Inspection::create($validated);
        return response()->json(['data' => $inspection->load('vehicle')], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Inspection $inspection)
    {
        return response()->json(['data' => $inspection->load('vehicle')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inspection $inspection)
    {
        return response()->json($inspection->load('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inspection $inspection)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'inspection_date' => 'required|date',
            'result' => 'nullable|in:pass,fail,conditional_pass',
            'notes' => 'nullable|string',
            'next_due_date' => 'nullable|date',
        ]);

        $validated['driver_id'] = $request->input('driver_id');
        $validated['status'] = $request->input('status', 'pending');

        $inspection->update($validated);
        return response()->json(['data' => $inspection->load('vehicle')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inspection $inspection)
    {
        $inspection->delete();
        return response()->json(['success' => true, 'message' => 'Inspection deleted successfully.']);
    }
}
