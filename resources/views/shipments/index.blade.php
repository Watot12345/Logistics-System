@extends('layouts.app')

@section('title', 'Shipments & Orders - Logistics System')
@section('page-title', 'Shipment Orders & Tracking')

@section('content')
<div class="space-y-6">

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
            <span class="text-[11px] text-slate-500 font-semibold uppercase">Total Orders</span>
            <p class="text-xl font-bold text-slate-900 mt-0.5">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
            <span class="text-[11px] text-sky-600 font-semibold uppercase">In Transit</span>
            <p class="text-xl font-bold text-slate-900 mt-0.5">{{ $stats['in_transit'] }}</p>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
            <span class="text-[11px] text-amber-600 font-semibold uppercase">Pending</span>
            <p class="text-xl font-bold text-slate-900 mt-0.5">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
            <span class="text-[11px] text-emerald-600 font-semibold uppercase">Delivered</span>
            <p class="text-xl font-bold text-slate-900 mt-0.5">{{ $stats['delivered'] }}</p>
        </div>
        <div class="bg-white p-3.5 rounded-xl border border-slate-200">
            <span class="text-[11px] text-rose-600 font-semibold uppercase">Delayed</span>
            <p class="text-xl font-bold text-slate-900 mt-0.5">{{ $stats['delayed'] }}</p>
        </div>
    </div>

    <!-- Filter & Action Controls -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <form method="GET" action="{{ route('shipments.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customer, location..." class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs w-64 focus:ring-2 focus:ring-sky-500">
            <select name="status" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="delayed" {{ request('status') === 'delayed' ? 'selected' : '' }}>Delayed</option>
            </select>
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-xs font-semibold hover:bg-slate-700">Filter</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('shipments.index') }}" class="text-xs text-slate-500 hover:text-slate-700">Clear</a>
            @endif
        </form>

        <button onclick="document.getElementById('addShipmentModal').classList.remove('hidden')" class="px-3.5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-sm">
            <i class="fas fa-plus"></i>
            <span>Create Shipment</span>
        </button>
    </div>

    <!-- Shipments Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3">Shipment #</th>
                        <th class="px-4 py-3">Client & Delivery Destination</th>
                        <th class="px-4 py-3">Assigned Fleet</th>
                        <th class="px-4 py-3">Current Location</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($shipments as $s)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-bold text-slate-800">#SHP-{{ $s->shipment_id }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $s->customer_name ?? 'Client Request' }}</p>
                                <span class="text-slate-400 block truncate max-w-sm">{{ $s->delivery_address }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $s->vehicle->asset_name ?? 'No vehicle' }}</p>
                                <span class="text-slate-400 text-[11px]">Driver: {{ $s->driver->name ?? 'Unassigned' }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                <i class="fas fa-location-dot text-rose-500 mr-1"></i>
                                {{ $s->current_location ?? 'At DC' }}
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('shipments.updateStatus', $s->shipment_id) }}">
                                    @csrf
                                    @method('PUT')
                                    <select name="shipment_status" onchange="this.form.submit()" class="text-xs font-semibold rounded-full px-2.5 py-1 border {{ $s->shipment_status === 'in_transit' ? 'bg-sky-50 text-sky-700 border-sky-200' : ($s->shipment_status === 'delivered' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($s->shipment_status === 'delayed' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-amber-50 text-amber-700 border-amber-200')) }}">
                                        <option value="pending" {{ $s->shipment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="in_transit" {{ $s->shipment_status === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                        <option value="delivered" {{ $s->shipment_status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                        <option value="delayed" {{ $s->shipment_status === 'delayed' ? 'selected' : '' }}>Delayed</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('shipments.destroy', $s->shipment_id) }}" onsubmit="return confirm('Delete this shipment?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-slate-400 hover:text-rose-600 p-1 transition" title="Delete">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-slate-400">No shipments found. Click "Create Shipment" above to register an order.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($shipments->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $shipments->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal: Create Shipment -->
<div id="addShipmentModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="font-bold text-slate-900 text-sm">Create New Delivery Shipment</h3>
            <button onclick="document.getElementById('addShipmentModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form method="POST" action="{{ route('shipments.store') }}" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Customer / Recipient Name</label>
                <input type="text" name="customer_name" required placeholder="e.g. San Miguel Distribution Center" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Complete Delivery Address</label>
                <textarea name="delivery_address" rows="2" required placeholder="Enter full address details..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Assign Vehicle</label>
                    <select name="vehicle_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        <option value="">-- Optional --</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->asset_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Assign Driver</label>
                    <select name="driver_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        <option value="">-- Optional --</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Starting Origin / Current Location</label>
                <input type="text" name="current_location" value="Central Distribution Warehouse" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addShipmentModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg font-semibold hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded-lg font-semibold hover:bg-sky-700">Dispatch Order</button>
            </div>
        </form>
    </div>
</div>
@endsection
