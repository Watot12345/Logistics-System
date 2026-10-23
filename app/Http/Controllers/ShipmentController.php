<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipment;
use App\Models\Asset;
use App\Models\User;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with(['vehicle', 'driver'])->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('shipment_status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%")
                  ->orWhere('current_location', 'like', "%{$search}%");
            });
        }

        $shipments = $query->paginate(15);
        $vehicles = Asset::where('asset_type', 'vehicle')->where('status', 'good')->get();
        $drivers = User::where('role', 'driver')->where('status', 'active')->get();

        $stats = [
            'total' => Shipment::count(),
            'in_transit' => Shipment::where('shipment_status', 'in_transit')->count(),
            'delivered' => Shipment::where('shipment_status', 'delivered')->count(),
            'pending' => Shipment::where('shipment_status', 'pending')->count(),
            'delayed' => Shipment::where('shipment_status', 'delayed')->count(),
        ];

        return view('shipments.index', compact('shipments', 'vehicles', 'drivers', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'delivery_address' => 'required|string',
            'vehicle_id' => 'nullable|exists:assets,id',
            'driver_id' => 'nullable|exists:users,id',
            'estimated_arrival' => 'nullable|date',
            'current_location' => 'nullable|string|max:255',
        ]);

        Shipment::create([
            'customer_name' => $validated['customer_name'],
            'delivery_address' => $validated['delivery_address'],
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'driver_id' => $validated['driver_id'] ?? null,
            'shipment_status' => 'pending',
            'estimated_arrival' => $validated['estimated_arrival'] ?? null,
            'current_location' => $validated['current_location'] ?? 'Distribution Center',
        ]);

        return redirect()->route('shipments.index')->with('success', 'Shipment order successfully created.');
    }

    public function updateStatus(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);
        
        $validated = $request->validate([
            'shipment_status' => 'required|in:pending,in_transit,delivered,delayed',
            'current_location' => 'nullable|string|max:255',
        ]);

        $updateData = ['shipment_status' => $validated['shipment_status']];
        if (!empty($validated['current_location'])) {
            $updateData['current_location'] = $validated['current_location'];
        }

        if ($validated['shipment_status'] === 'in_transit' && !$shipment->departure_time) {
            $updateData['departure_time'] = now();
        }

        if ($validated['shipment_status'] === 'delivered') {
            $updateData['actual_arrival'] = now();
        }

        $shipment->update($updateData);

        return redirect()->route('shipments.index')->with('success', 'Shipment status updated.');
    }

    public function destroy($id)
    {
        $shipment = Shipment::findOrFail($id);
        $shipment->delete();

        return redirect()->route('shipments.index')->with('success', 'Shipment deleted.');
    }
}
