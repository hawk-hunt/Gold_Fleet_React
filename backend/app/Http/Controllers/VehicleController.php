<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companyId = auth()->user()->company_id;

        $vehicles = Vehicle::where('company_id', $companyId)
            ->with(['trips' => function($query) {
                $query->latest()->limit(1);
            }])
            ->get();

        return response()->json(['data' => $vehicles]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Provide vehicle data to create.']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'license_plate' => 'required|string|max:255|unique:vehicles,license_plate',
            'type' => 'required|in:Car,Bus,Truck,Van',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'vin' => 'required|string|max:255|unique:vehicles,vin',
            'status' => 'required|in:active,inactive,maintenance',
            'fuel_capacity' => 'nullable|numeric|min:0',
            'fuel_type' => 'required|in:diesel,gasoline,electric,hybrid',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('vehicles', 'public');
        }

        Vehicle::create([
            'company_id' => $companyId,
            'image' => $imagePath,
            'name' => $request->name,
            'license_plate' => $request->license_plate,
            'type' => $request->type,
            'make' => $request->make,
            'model' => $request->model,
            'year' => $request->year,
            'vin' => $request->vin,
            'status' => $request->status,
            'fuel_capacity' => $request->fuel_capacity,
            'fuel_type' => $request->fuel_type,
            'notes' => $request->notes,
        ]);

        return response()->json(['success' => true, 'message' => 'Vehicle created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vehicle $vehicle)
    {
        // Ensure vehicle belongs to user's company
        if ($vehicle->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $vehicle->load([
            'trips' => function($query) {
                $query->latest()->limit(10);
            },
            'services' => function($query) {
                $query->latest()->limit(10);
            },
            'issues' => function($query) {
                $query->latest()->limit(10);
            },
            'expenses' => function($query) {
                $query->latest()->limit(10);
            },
            'fuelFillups' => function($query) {
                $query->latest()->limit(10);
            },
            'reminders' => function($query) {
                $query->where('status', 'pending')->latest()->limit(5);
            }
        ]);

        return response()->json($vehicle);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vehicle $vehicle)
    {
        // Ensure vehicle belongs to user's company
        if ($vehicle->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        return response()->json($vehicle);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        // Ensure vehicle belongs to user's company
        if ($vehicle->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'license_plate' => 'required|string|max:255|unique:vehicles,license_plate,' . $vehicle->id,
            'type' => 'required|in:Car,Bus,Truck,Van',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'vin' => 'required|string|max:255|unique:vehicles,vin,' . $vehicle->id,
            'status' => 'required|in:active,inactive,maintenance',
            'fuel_capacity' => 'nullable|numeric|min:0',
            'fuel_type' => 'required|in:diesel,gasoline,electric,hybrid',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Handle image upload
        $imagePath = $vehicle->image;
        if ($request->hasFile('image')) {
            // Delete old image
            if ($vehicle->image && \Storage::disk('public')->exists($vehicle->image)) {
                \Storage::disk('public')->delete($vehicle->image);
            }
            $imagePath = $request->file('image')->store('vehicles', 'public');
        }

        $vehicle->update([
            'image' => $imagePath,
            'name' => $request->name,
            'license_plate' => $request->license_plate,
            'type' => $request->type,
            'make' => $request->make,
            'model' => $request->model,
            'year' => $request->year,
            'vin' => $request->vin,
            'status' => $request->status,
            'fuel_capacity' => $request->fuel_capacity,
            'fuel_type' => $request->fuel_type,
            'notes' => $request->notes,
        ]);

        return response()->json(['success' => true, 'message' => 'Vehicle updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        // Ensure vehicle belongs to user's company
        if ($vehicle->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Admin-only access
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only admins can delete vehicles.');
        }

        // Delete image from storage
        if ($vehicle->image && \Storage::disk('public')->exists($vehicle->image)) {
            \Storage::disk('public')->delete($vehicle->image);
        }

        $vehicle->delete();

        return response()->json(['success' => true, 'message' => 'Vehicle deleted successfully.']);
    }
}
