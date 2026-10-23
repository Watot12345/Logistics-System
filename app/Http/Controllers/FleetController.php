<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\MaintenanceAlert;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FleetController extends Controller
{
    public function index()
    {
        $vehicles = Asset::where('asset_type', 'vehicle')
            ->orderBy('id', 'desc')
            ->get();

        $mechanics = User::where('role', 'mechanic')->where('status', 'active')->get();
        $drivers = User::where('role', 'driver')->where('status', 'active')->get();

        $alerts = MaintenanceAlert::with('mechanic')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $stats = [
            'total' => $vehicles->count(),
            'available' => $vehicles->where('status', 'good')->count(),
            'warning' => $vehicles->where('status', 'warning')->count(),
            'bad' => $vehicles->where('status', 'bad')->count(),
            'avg_condition' => round($vehicles->avg('asset_condition') ?? 100),
        ];

        return view('fleet.index', compact('vehicles', 'mechanics', 'drivers', 'alerts', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_name' => 'required|string|max:255',
            'status' => 'required|in:good,warning,bad',
            'asset_condition' => 'nullable|integer|min:0|max:100',
        ]);

        Asset::create([
            'asset_name' => $validated['asset_name'],
            'asset_type' => 'vehicle',
            'status' => $validated['status'],
            'asset_condition' => $validated['asset_condition'] ?? 100,
        ]);

        return redirect()->route('fleet.index')->with('success', 'Vehicle successfully added to fleet.');
    }

    public function createAlert(Request $request)
    {
        $validated = $request->validate([
            'asset_name' => 'required|string',
            'issue' => 'required|string|max:255',
            'issue_type' => 'required|in:minor,major,critical',
            'priority' => 'required|in:low,medium,high',
            'assigned_mechanic' => 'nullable|exists:users,id',
            'estimated_hours' => 'nullable|numeric|min:0.5',
            'due_date' => 'required|date',
        ]);

        MaintenanceAlert::create([
            'asset_name' => $validated['asset_name'],
            'issue' => $validated['issue'],
            'issue_type' => $validated['issue_type'],
            'priority' => $validated['priority'],
            'assigned_mechanic' => $validated['assigned_mechanic'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? 1.0,
            'due_date' => $validated['due_date'],
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('fleet.index')->with('success', 'Maintenance alert created successfully.');
    }
}
