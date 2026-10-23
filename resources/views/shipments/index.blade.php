@extends('layouts.app')

@section('title', 'Purchase Orders & Shipments | Logistics System')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/orders.css') }}">
@endpush

@section('content')
<div class="page-content">
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="header-right">
                <div class="divider"></div>
                <div class="user-info-header">
                    <span class="user-name-header">{{ auth()->user()->name }}</span>
                    <div class="avatar-small">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Header -->
    <div class="page-header">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1>Procurement & Sourcing Management</h1>
                <p>Manage purchase orders, suppliers, and dispatches</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="document.getElementById('createShipmentModal').style.display='flex'">
                    <i class="fas fa-plus"></i> Create Purchase Order / Shipment
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
            <i class="fas fa-check-circle" style="margin-right: 6px;"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Statistics Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon blue">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <span class="stat-badge green">Total orders</span>
            </div>
            <p class="stat-label">Total Shipments</p>
            <p class="stat-value">{{ $stats['total'] }}</p>
            <div class="stat-trend">
                <i class="fas fa-boxes"></i> All time dispatches
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon emerald">
                    <i class="fas fa-truck"></i>
                </div>
                <span class="stat-badge green">{{ $stats['in_transit'] }} active</span>
            </div>
            <p class="stat-label">In Transit</p>
            <p class="stat-value">{{ $stats['in_transit'] }}</p>
            <div class="stat-trend">
                <i class="fas fa-route"></i> On the road now
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon amber">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="stat-badge amber">{{ $stats['pending'] }} pending</span>
            </div>
            <p class="stat-label">Pending Orders</p>
            <p class="stat-value">{{ $stats['pending'] }}</p>
            <div class="stat-trend down">
                <i class="fas fa-hourglass-half"></i> Awaiting pickup
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon purple">
                    <i class="fas fa-check-double"></i>
                </div>
                <span class="stat-badge green">{{ $stats['delivered'] }} delivered</span>
            </div>
            <p class="stat-label">Completed Deliveries</p>
            <p class="stat-value">{{ $stats['delivered'] }}</p>
            <div class="stat-trend">
                <i class="fas fa-arrow-up"></i> Successfully completed
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card" style="margin-top: 24px; padding: 16px;">
        <form method="GET" action="{{ route('shipments.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search client, destination, tracking..." style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; min-width: 260px;">
            <select name="status" style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                <option value="">All Statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="delayed" {{ request('status') === 'delayed' ? 'selected' : '' }}>Delayed</option>
            </select>
            <button type="submit" class="btn btn-primary" style="padding: 10px 18px; border-radius: 8px;">
                <i class="fas fa-search"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('shipments.index') }}" class="btn btn-outline" style="padding: 10px 18px; border-radius: 8px;">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Shipments Table -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2><i class="fas fa-file-invoice"></i> Orders & Shipments Directory</h2>
            <span class="card-badge">{{ $shipments->total() }} records</span>
        </div>
        <div class="card-body">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px; text-align: left;">Tracking #</th>
                        <th style="padding: 12px; text-align: left;">Customer & Destination</th>
                        <th style="padding: 12px; text-align: left;">Assigned Vehicle</th>
                        <th style="padding: 12px; text-align: left;">Driver</th>
                        <th style="padding: 12px; text-align: left;">Status</th>
                        <th style="padding: 12px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shipments as $s)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px; font-family: monospace; font-weight: 700; color: #2563eb;">
                            #SHP-{{ str_pad($s->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td style="padding: 12px;">
                            <div style="font-weight: 600; color: #1e293b;">{{ $s->customer_name }}</div>
                            <div style="font-size: 12px; color: #64748b;"><i class="fas fa-map-marker-alt" style="color: #ef4444; margin-right: 4px;"></i> {{ $s->delivery_address }}</div>
                        </td>
                        <td style="padding: 12px; color: #475569;">
                            {{ $s->vehicle->asset_name ?? 'Not Assigned' }}
                        </td>
                        <td style="padding: 12px; color: #475569;">
                            {{ $s->driver->name ?? 'Not Assigned' }}
                        </td>
                        <td style="padding: 12px;">
                            @php
                                $badgeColor = match($s->shipment_status) {
                                    'delivered' => 'background: #d1fae5; color: #047857;',
                                    'in_transit' => 'background: #e0f2fe; color: #0284c7;',
                                    'delayed' => 'background: #fee2e2; color: #b91c1c;',
                                    default => 'background: #fef3c7; color: #b45309;'
                                };
                            @endphp
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; {{ $badgeColor }}">
                                {{ ucfirst(str_replace('_', ' ', $s->shipment_status)) }}
                            </span>
                        </td>
                        <td style="padding: 12px;">
                            <form method="POST" action="{{ route('shipments.updateStatus', $s->id) }}" style="display: inline-flex; gap: 6px; align-items: center; margin: 0;">
                                @csrf
                                @method('PUT')
                                <select name="shipment_status" onchange="this.form.submit()" style="padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                                    <option value="pending" {{ $s->shipment_status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="in_transit" {{ $s->shipment_status === 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                    <option value="delivered" {{ $s->shipment_status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                                    <option value="delayed" {{ $s->shipment_status === 'delayed' ? 'selected' : '' }}>Delayed</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">No orders found. Click "Create Purchase Order / Shipment" to add one.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 20px;">
                {{ $shipments->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Create Shipment Modal -->
<div id="createShipmentModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-content" style="background: white; border-radius: 16px; width: 90%; max-width: 520px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div class="modal-header" style="padding: 18px 24px; background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px;"><i class="fas fa-box"></i> New Purchase Order / Shipment</h3>
            <button onclick="document.getElementById('createShipmentModal').style.display='none'" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="{{ route('shipments.store') }}" style="padding: 24px;">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Client / Customer Name *</label>
                <input type="text" name="customer_name" required placeholder="e.g. Grand Vista Hotel Manila" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Delivery Destination *</label>
                <input type="text" name="delivery_address" required placeholder="e.g. 102 Roxas Blvd, Pasay City" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Assigned Vehicle</label>
                    <select name="vehicle_id" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        <option value="">-- Select Vehicle --</option>
                        @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->asset_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Assigned Driver</label>
                    <select name="driver_id" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        <option value="">-- Select Driver --</option>
                        @foreach($drivers as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Current / Initial Location</label>
                <input type="text" name="current_location" value="Main Warehouse Depot" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('createShipmentModal').style.display='none'" style="padding: 10px 18px; border: none; border-radius: 8px; background: #e2e8f0; color: #475569; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border: none; border-radius: 8px; background: #2563eb; color: white; font-weight: 600; cursor: pointer;">Create Order</button>
            </div>
        </form>
    </div>
</div>
@endsection
