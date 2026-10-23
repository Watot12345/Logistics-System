@extends('layouts.app')

@section('title', 'Operations Dashboard - Logistics System')
@section('page-title', 'Operations Overview')

@section('content')
<div class="space-y-6">

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Fleet Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Fleet Vehicles</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-bold text-slate-900">{{ $totalVehicles }}</span>
                    <span class="text-xs text-emerald-600 font-medium">{{ $goodVehicles }} Ready</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">{{ $warningVehicles + $maintenanceVehicles }} In Maintenance/Alert</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl">
                <i class="fas fa-truck-moving"></i>
            </div>
        </div>

        <!-- Shipments In Transit -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Shipments</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-bold text-slate-900">{{ $inTransitShipments }}</span>
                    <span class="text-xs text-sky-600 font-medium">In Transit</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">{{ $totalShipments }} Total recorded</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fas fa-route"></i>
            </div>
        </div>

        <!-- Inventory Stock Alerts -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Inventory SKUs</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-bold text-slate-900">{{ $totalInventoryItems }}</span>
                    <span class="text-xs text-amber-600 font-medium">{{ $lowStockItems }} Low Stock</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">₱{{ number_format($inventoryValue, 2) }} total value</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fas fa-boxes-stacked"></i>
            </div>
        </div>

        <!-- Pending Maintenance -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Maintenance Alerts</p>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-2xl font-bold text-slate-900">{{ $pendingMaintenance->count() }}</span>
                    <span class="text-xs text-rose-600 font-medium">Open Tasks</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">{{ $availableDrivers }} Active Drivers Ready</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl">
                <i class="fas fa-wrench"></i>
            </div>
        </div>
    </div>

    <!-- Quick Actions Banner -->
    <div class="bg-gradient-to-r from-slate-900 to-sky-900 text-white rounded-xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold">Welcome back, {{ auth()->user()->name }}!</h3>
            <p class="text-xs text-slate-300 mt-0.5">Role: <span class="capitalize font-semibold text-sky-300">{{ str_replace('_', ' ', auth()->user()->role) }}</span> | System running in production-ready container.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('shipments.index') }}" class="px-3.5 py-2 bg-sky-500 hover:bg-sky-600 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 shadow-sm">
                <i class="fas fa-plus"></i>
                <span>New Shipment</span>
            </a>
            <a href="{{ route('fleet.index') }}" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 border border-slate-700">
                <i class="fas fa-truck"></i>
                <span>Manage Fleet</span>
            </a>
        </div>
    </div>

    <!-- Tables Grid: Recent Shipments & Maintenance Alerts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Shipments -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fas fa-shipping-fast text-sky-500"></i>
                    <span>Recent Shipments</span>
                </h3>
                <a href="{{ route('shipments.index') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-700">View All</a>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Customer / Destination</th>
                            <th class="px-4 py-3">Vehicle</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentShipments as $s)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-3 font-semibold text-slate-700">#{{ $s->shipment_id }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ $s->customer_name ?? 'N/A' }}</p>
                                    <span class="text-[11px] text-slate-400 block truncate max-w-xs">{{ $s->delivery_address }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $s->vehicle->asset_name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3">
                                    @if($s->shipment_status === 'in_transit')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-50 text-sky-700 border border-sky-200">In Transit</span>
                                    @elseif($s->shipment_status === 'delivered')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Delivered</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400">No shipments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Maintenance Alerts -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fas fa-triangle-exclamation text-amber-500"></i>
                    <span>Maintenance & Breakdown Alerts</span>
                </h3>
                <a href="{{ route('fleet.index') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-700">View Fleet</a>
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-4 py-3">Asset</th>
                            <th class="px-4 py-3">Issue</th>
                            <th class="px-4 py-3">Mechanic</th>
                            <th class="px-4 py-3">Priority</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pendingMaintenance as $m)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $m->asset_name }}</td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-700">{{ $m->issue }}</p>
                                    <span class="text-[11px] text-slate-400">Due: {{ $m->due_date ? $m->due_date->format('M d, Y') : 'N/A' }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $m->mechanic->name ?? 'Unassigned' }}</td>
                                <td class="px-4 py-3">
                                    @if($m->priority === 'high' || $m->issue_type === 'critical')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">High</span>
                                    @elseif($m->priority === 'medium')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Medium</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">Low</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-slate-400">All vehicles in healthy operating condition!</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
