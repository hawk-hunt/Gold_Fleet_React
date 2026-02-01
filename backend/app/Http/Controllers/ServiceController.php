<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = Service::with('vehicle')->get();
        return response()->json(['data' => $services]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Provide service details to create.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_type' => 'required|string|max:255',
            'service_date' => 'required|date',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
        ]);

        $validated['company_id'] = auth()->user()->company_id ?? 1;
        $validated['description'] = $validated['notes'] ?? '';
        $validated['status'] = $validated['status'] ?? 'pending';

        $service = Service::create($validated);
        return response()->json(['data' => $service->load('vehicle')], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        return response()->json(['data' => $service->load('vehicle')]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Service $service)
    {
        return response()->json($service->load('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_type' => 'required|string|max:255',
            'service_date' => 'required|date',
            'cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
        ]);

        $validated['description'] = $validated['notes'] ?? '';

        $service->update($validated);
        return response()->json(['data' => $service->load('vehicle')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(['success' => true, 'message' => 'Service deleted successfully.']);
    }
}
