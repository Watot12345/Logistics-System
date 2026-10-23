@extends('layouts.app')

@section('title', 'Fleet & Transportation Operations | Logistics System')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/fleet.css') }}">
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
                <h1>Fleet & Transportation Operations</h1>
                <p>Monitor vehicles, drivers, and fleet operations</p>
            </div>
            <div class="header-actions" style="display: flex; gap: 10px;">
                <button class="btn btn-primary" onclick="document.getElementById('addVehicleModal').style.display='flex'">
                    <i class="fas fa-plus"></i> Add Vehicle
                </button>
                <button class="btn btn-outline" style="border-color: #ef4444; color: #ef4444;" onclick="document.getElementById('reportAlertModal').style.display='flex'">
                    <i class="fas fa-wrench"></i> Report Breakdown
                </button>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 500;">
            <i class="fas fa-check-circle" style="margin-right: 6px;"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon blue">
                    <i class="fas fa-truck"></i>
                </div>
                <span class="stat-badge green">Total fleet</span>
            </div>
            <p class="stat-label">Total Vehicles</p>
            <p class="stat-value">{{ $stats['total'] }}</p>
            <div class="stat-trend">
                <i class="fas fa-arrow-up"></i> Registered vehicles
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon emerald">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="stat-badge green">{{ $stats['available'] }} ready</span>
            </div>
            <p class="stat-label">Available Now</p>
            <p class="stat-value">{{ $stats['available'] }}</p>
            <div class="stat-trend">
                <i class="fas fa-check"></i> Operational condition
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon amber">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <span class="stat-badge amber">{{ $stats['warning'] }} attention</span>
            </div>
            <p class="stat-label">Needs Inspection</p>
            <p class="stat-value">{{ $stats['warning'] }}</p>
            <div class="stat-trend down">
                <i class="fas fa-wrench"></i> Scheduled review
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon red">
                    <i class="fas fa-tools"></i>
                </div>
                <span class="stat-badge red">{{ $stats['bad'] }} in shop</span>
            </div>
            <p class="stat-label">In Maintenance</p>
            <p class="stat-value">{{ $stats['bad'] }}</p>
            <div class="stat-trend down">
                <i class="fas fa-times-circle"></i> Service required
            </div>
        </div>
    </div>

    <!-- Vehicles List Card -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2><i class="fas fa-truck-moving"></i> Vehicle Inventory & Condition</h2>
            <span class="card-badge">{{ count($vehicles) }} Vehicles</span>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px;">
                @forelse($vehicles as $v)
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: all 0.3s ease;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div>
                                <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 4px 0;">{{ $v->asset_name }}</h3>
                                <span style="font-size: 12px; color: #64748b;">Asset #{{ $v->id }} &bull; Vehicle</span>
                            </div>
                            <span style="font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 20px; {{ $v->status === 'good' ? 'background: #d1fae5; color: #047857;' : ($v->status === 'warning' ? 'background: #fef3c7; color: #b45309;' : 'background: #fee2e2; color: #b91c1c;') }}">
                                {{ ucfirst($v->status) }}
                            </span>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin-bottom: 4px;">
                                <span>Health Condition</span>
                                <span style="font-weight: 700; color: #1e293b;">{{ $v->asset_condition ?? 100 }}%</span>
                            </div>
                            <div style="height: 6px; background: #f1f5f9; border-radius: 999px; overflow: hidden;">
                                <div style="height: 100%; width: {{ $v->asset_condition ?? 100 }}%; background: {{ ($v->asset_condition ?? 100) >= 70 ? '#10b981' : (($v->asset_condition ?? 100) >= 40 ? '#f59e0b' : '#ef4444') }}; border-radius: 999px;"></div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 10px;">
                            <span><i class="far fa-calendar"></i> Added {{ $v->created_at ? $v->created_at->format('M d, Y') : 'Active' }}</span>
                            <span style="color: #2563eb; font-weight: 500;">Ready for Dispatch</span>
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8;">
                        <i class="fas fa-truck" style="font-size: 40px; margin-bottom: 12px; opacity: 0.5;"></i>
                        <p>No vehicles in fleet. Click "Add Vehicle" to register one.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Active Breakdown Alerts Table -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2><i class="fas fa-wrench"></i> Active Maintenance Logs & Alerts</h2>
            <span class="card-badge">{{ count($alerts) }} active issues</span>
        </div>
        <div class="card-body">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px; text-align: left;">Vehicle</th>
                        <th style="padding: 12px; text-align: left;">Issue Description</th>
                        <th style="padding: 12px; text-align: left;">Priority</th>
                        <th style="padding: 12px; text-align: left;">Assigned Mechanic</th>
                        <th style="padding: 12px; text-align: left;">Due Date</th>
                        <th style="padding: 12px; text-align: left;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($alerts as $alert)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px; font-weight: 600;">{{ $alert->asset_name }}</td>
                        <td style="padding: 12px; color: #475569;">{{ $alert->issue }}</td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; {{ $alert->priority === 'high' ? 'background: #fee2e2; color: #b91c1c;' : ($alert->priority === 'medium' ? 'background: #fef3c7; color: #b45309;' : 'background: #d1fae5; color: #047857;') }}">
                                {{ ucfirst($alert->priority) }}
                            </span>
                        </td>
                        <td style="padding: 12px; color: #64748b;">
                            {{ $alert->mechanic->name ?? 'Unassigned' }}
                        </td>
                        <td style="padding: 12px; color: #64748b;">{{ $alert->due_date ? date('M d, Y', strtotime($alert->due_date)) : 'N/A' }}</td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: #f1f5f9; color: #475569;">
                                {{ ucfirst(str_replace('_', ' ', $alert->status)) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 24px; text-align: center; color: #94a3b8;">No maintenance alerts. All vehicles running smoothly!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Vehicle Modal -->
<div id="addVehicleModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-content" style="background: white; border-radius: 16px; width: 90%; max-width: 500px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div class="modal-header" style="padding: 18px 24px; background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px;"><i class="fas fa-truck"></i> Add New Vehicle</h3>
            <button onclick="document.getElementById('addVehicleModal').style.display='none'" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="{{ route('fleet.store') }}" style="padding: 24px;">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Vehicle Name / Model *</label>
                <input type="text" name="asset_name" required placeholder="e.g. Isuzu Elf Truck #4" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Initial Status *</label>
                <select name="status" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    <option value="good">Good (Ready for Dispatch)</option>
                    <option value="warning">Warning (Needs Inspection)</option>
                    <option value="bad">Bad (In Maintenance)</option>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Initial Health Condition (%)</label>
                <input type="number" name="asset_condition" value="100" min="0" max="100" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addVehicleModal').style.display='none'" style="padding: 10px 18px; border: none; border-radius: 8px; background: #e2e8f0; color: #475569; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border: none; border-radius: 8px; background: #2563eb; color: white; font-weight: 600; cursor: pointer;">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- Report Breakdown Modal -->
<div id="reportAlertModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-content" style="background: white; border-radius: 16px; width: 90%; max-width: 500px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div class="modal-header" style="padding: 18px 24px; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px;"><i class="fas fa-wrench"></i> Report Vehicle Breakdown</h3>
            <button onclick="document.getElementById('reportAlertModal').style.display='none'" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="{{ route('fleet.alerts.store') }}" style="padding: 24px;">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Select Vehicle *</label>
                <select name="asset_name" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    <option value="">-- Choose a Vehicle --</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->asset_name }}">{{ $v->asset_name }} (Current: {{ $v->status }})</option>
                    @endforeach
                </select>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Issue Description *</label>
                <input type="text" name="issue" required placeholder="e.g. Brake overheating, flat tire, oil leak" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Priority *</label>
                    <select name="priority" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Target Completion Date</label>
                    <input type="date" name="due_date" value="{{ date('Y-m-d', strtotime('+3 days')) }}" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Assign Mechanic (Optional)</label>
                <select name="assigned_mechanic" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                    <option value="">-- Any Available Mechanic --</option>
                    @foreach($mechanics as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('reportAlertModal').style.display='none'" style="padding: 10px 18px; border: none; border-radius: 8px; background: #e2e8f0; color: #475569; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-danger" style="padding: 10px 20px; border: none; border-radius: 8px; background: #ef4444; color: white; font-weight: 600; cursor: pointer;">Submit Report</button>
            </div>
        </form>
    </div>
</div>
@endsection
