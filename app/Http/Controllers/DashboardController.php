<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Asset;
use App\Models\Shipment;
use App\Models\InventoryItem;
use App\Models\MaintenanceAlert;
use App\Models\DispatchSchedule;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Fleet Stats
        $totalVehicles = Asset::where('asset_type', 'vehicle')->count();
        $goodVehicles = Asset::where('asset_type', 'vehicle')->where('status', 'good')->count();
        $maintenanceVehicles = Asset::where('asset_type', 'vehicle')->where('status', 'bad')->count();
        $warningVehicles = Asset::where('asset_type', 'vehicle')->where('status', 'warning')->count();

        // 2. Shipment Stats
        $totalShipments = Shipment::count();
        $inTransitShipments = Shipment::where('shipment_status', 'in_transit')->count();
        $deliveredShipments = Shipment::where('shipment_status', 'delivered')->count();
        $pendingShipments = Shipment::where('shipment_status', 'pending')->count();

        // 3. Inventory Stats
        $totalInventoryItems = InventoryItem::count();
        $lowStockItems = InventoryItem::where('status', 'low_stock')->count();
        $outOfStockItems = InventoryItem::where('status', 'out_of_stock')->count();
        $inventoryValue = InventoryItem::sum(\DB::raw('quantity * price'));

        // 4. Alerts
        $pendingMaintenance = MaintenanceAlert::whereIn('status', ['pending', 'in_progress'])
            ->with('mechanic')
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        // 5. Recent Shipments
        $recentShipments = Shipment::with(['vehicle', 'driver'])
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // 6. Drivers & Vehicles available
        $availableDrivers = User::where('role', 'driver')->where('status', 'active')->count();

        return view('dashboard.index', compact(
            'user',
            'totalVehicles',
            'goodVehicles',
            'maintenanceVehicles',
            'warningVehicles',
            'totalShipments',
            'inTransitShipments',
            'deliveredShipments',
            'pendingShipments',
            'totalInventoryItems',
            'lowStockItems',
            'outOfStockItems',
            'inventoryValue',
            'pendingMaintenance',
            'recentShipments',
            'availableDrivers'
        ));
    }

    public function lifecycle(Request $request)
    {
        $user = Auth::user();
        $userRole = $user->role ?? 'employee';

        $assets = Asset::orderBy('created_at', 'desc')->get();
        $alerts = MaintenanceAlert::whereIn('status', ['pending', 'in_progress'])
            ->orderBy('due_date', 'asc')
            ->get();

        $lifecycleCounts = [
            'Operational (70-100%)' => 0,
            'Maintenance Needed (40-69%)' => 0,
            'Critical (10-39%)' => 0,
            'End-of-Life (0-9%)' => 0,
            'Retired' => 0
        ];

        foreach ($assets as $asset) {
            $condition = intval($asset->asset_condition ?? 0);
            if ($condition >= 70) {
                $lifecycleCounts['Operational (70-100%)']++;
            } elseif ($condition >= 40) {
                $lifecycleCounts['Maintenance Needed (40-69%)']++;
            } elseif ($condition >= 10) {
                $lifecycleCounts['Critical (10-39%)']++;
            } elseif ($condition > 0) {
                $lifecycleCounts['End-of-Life (0-9%)']++;
            } else {
                $lifecycleCounts['Retired']++;
            }
        }

        $lifecycleData = [];
        $totalItems = $assets->count();
        foreach ($lifecycleCounts as $stage => $count) {
            $percentage = ($totalItems > 0) ? round(($count / $totalItems) * 100) : 0;
            $lifecycleData[] = [
                'stage' => $stage,
                'count' => $count,
                'percentage' => $percentage
            ];
        }

        $activeShipments = DispatchSchedule::with(['driver', 'vehicle', 'reservation'])
            ->whereIn('status', ['scheduled', 'in-progress', 'delivered', 'pending'])
            ->orderBy('scheduled_date', 'asc')
            ->take(10)
            ->get();

        return view('dashboard.lifecycle', compact(
            'user',
            'userRole',
            'assets',
            'alerts',
            'lifecycleData',
            'activeShipments'
        ));
    }
}

