<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $drivers = Driver::where('company_id', $companyId)->with('user')->get();
        return response()->json(['data' => $drivers]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $companyId = auth()->user()->company_id;
        $vehicles = Vehicle::where('company_id', $companyId)->get();

        return response()->json(['vehicles' => $vehicles]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'license_number' => 'required|string|unique:drivers,license_number',
            'license_expiry' => 'required|date',
            'status' => 'required|in:active,suspended',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'address' => 'nullable|string',
        ]);

        // Create user
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt('password'), // default password
            'company_id' => auth()->user()->company_id,
            'role' => 'driver',
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('drivers', 'public');
        }

        $driver = Driver::create([
            'company_id' => auth()->user()->company_id,
            'user_id' => $user->id,
            'license_number' => $validated['license_number'],
            'license_expiry' => $validated['license_expiry'],
            'phone' => $validated['phone'],
            'status' => $validated['status'],
            'image' => $imagePath,
            'address' => $validated['address'],
        ]);

        return response()->json(['success' => true, 'driver' => $driver], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Driver $driver)
    {
        // Ensure driver belongs to user's company
        if ($driver->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $driver->load(['user', 'vehicle', 'trips' => function($query) {
            $query->latest()->limit(10);
        }]);

        return response()->json($driver);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Driver $driver)
    {
        // Ensure driver belongs to user's company
        if ($driver->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $companyId = auth()->user()->company_id;
        $vehicles = Vehicle::where('company_id', $companyId)->get();

        return response()->json(['driver' => $driver, 'vehicles' => $vehicles]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Driver $driver)
    {
        // Ensure driver belongs to user's company
        if ($driver->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $driver->user_id,
            'phone' => 'required|string|max:20',
            'license_number' => 'required|string|unique:drivers,license_number,' . $driver->id,
            'license_expiry' => 'required|date',
            'status' => 'required|in:active,suspended',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'address' => 'nullable|string',
        ]);

        // Handle image upload
        $imagePath = $driver->image;
        if ($request->hasFile('image')) {
            // Delete old image
            if ($driver->image && \Storage::disk('public')->exists($driver->image)) {
                \Storage::disk('public')->delete($driver->image);
            }
            $imagePath = $request->file('image')->store('drivers', 'public');
        }

        // Update user
        $driver->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        // Update driver
        $driver->update([
            'license_number' => $validated['license_number'],
            'license_expiry' => $validated['license_expiry'],
            'phone' => $validated['phone'],
            'status' => $validated['status'],
            'image' => $imagePath,
            'address' => $validated['address'],
        ]);

        return response()->json(['success' => true, 'driver' => $driver]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Driver $driver)
    {
        // Ensure driver belongs to user's company
        if ($driver->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        // Admin-only access
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Only admins can delete drivers.');
        }

        // Unassign vehicle if assigned
        if ($driver->vehicle) {
            $driver->vehicle->update(['driver_id' => null]);
        }

        // Delete image from storage
        if ($driver->image && \Storage::disk('public')->exists($driver->image)) {
            \Storage::disk('public')->delete($driver->image);
        }

        $driver->delete();

        return response()->json(['success' => true, 'message' => 'Driver deleted successfully.']);
    }
}
