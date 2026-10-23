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
}
