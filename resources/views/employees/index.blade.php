@extends('layouts.app')

@section('title', 'Employee Management | Logistics System')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/employee.css') }}">
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
                <h1>Employee Management</h1>
                <p>Manage users, organizational roles, and system permissions</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="alert('User invitation functionality active for system admins.');">
                    <i class="fas fa-user-plus"></i> Add Employee
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
                    <i class="fas fa-users"></i>
                </div>
                <span class="stat-badge green">Total</span>
            </div>
            <p class="stat-label">Total Employees</p>
            <p class="stat-value">{{ $stats['total'] }}</p>
            <div class="stat-trend">
                <i class="fas fa-id-card"></i> Registered personnel
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon emerald">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span class="stat-badge green">Active</span>
            </div>
            <p class="stat-label">Active Employees</p>
            <p class="stat-value">{{ $stats['active'] }}</p>
            <div class="stat-trend">
                <i class="fas fa-check"></i> {{ $stats['total'] > 0 ? round(($stats['active'] / $stats['total']) * 100) : 100 }}% of workforce
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon amber">
                    <i class="fas fa-truck"></i>
                </div>
                <span class="stat-badge blue">Drivers</span>
            </div>
            <p class="stat-label">Fleet Drivers</p>
            <p class="stat-value">{{ $stats['drivers'] }}</p>
            <div class="stat-trend">
                <i class="fas fa-route"></i> Licensed drivers
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon purple">
                    <i class="fas fa-headset"></i>
                </div>
                <span class="stat-badge amber">Dispatchers</span>
            </div>
            <p class="stat-label">Dispatchers</p>
            <p class="stat-value">{{ $stats['dispatchers'] }}</p>
            <div class="stat-trend">
                <i class="fas fa-clock"></i> Active dispatch duty
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card" style="margin-top: 24px; padding: 16px;">
        <form method="GET" action="{{ route('employees.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, email, employee ID..." style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; min-width: 260px;">
            <select name="role" style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                <option value="">All Roles</option>
                @foreach($roles as $r)
                    <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary" style="padding: 10px 18px; border-radius: 8px;">
                <i class="fas fa-search"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'role']))
                <a href="{{ route('employees.index') }}" class="btn btn-outline" style="padding: 10px 18px; border-radius: 8px;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Employee Table Card -->
    <div class="card" style="margin-top: 24px;">
        <div class="card-header">
            <h2><i class="fas fa-user-tie"></i> Employee Directory</h2>
            <span class="card-badge">{{ $employees->total() }} Employees</span>
        </div>
        <div class="card-body">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px; text-align: left;">Employee</th>
                        <th style="padding: 12px; text-align: left;">Email Address</th>
                        <th style="padding: 12px; text-align: left;">Role</th>
                        <th style="padding: 12px; text-align: left;">Status</th>
                        <th style="padding: 12px; text-align: left;">Registered Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                                    {{ strtoupper(substr($emp->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #1e293b;">{{ $emp->name }}</div>
                                    <div style="font-size: 11px; color: #64748b;">ID: {{ $emp->employee_id ?? 'EMP-'.str_pad($emp->id, 4, '0', STR_PAD_LEFT) }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 12px; color: #475569; font-size: 13px;">
                            {{ $emp->email }}
                        </td>
                        <td style="padding: 12px;">
                            @php
                                $roleBadge = match($emp->role) {
                                    'admin' => 'background: #ede9fe; color: #6d28d9;',
                                    'fleet_manager' => 'background: #e0f2fe; color: #0369a1;',
                                    'driver' => 'background: #fef3c7; color: #b45309;',
                                    'mechanic' => 'background: #fee2e2; color: #b91c1c;',
                                    'dispatcher' => 'background: #ccfbf1; color: #0f766e;',
                                    default => 'background: #f1f5f9; color: #475569;'
                                };
                            @endphp
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; {{ $roleBadge }}">
                                {{ ucfirst(str_replace('_', ' ', $emp->role)) }}
                            </span>
                        </td>
                        <td style="padding: 12px;">
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; {{ $emp->status === 'active' ? 'background: #d1fae5; color: #047857;' : 'background: #f1f5f9; color: #64748b;' }}">
                                {{ ucfirst($emp->status ?? 'active') }}
                            </span>
                        </td>
                        <td style="padding: 12px; color: #64748b; font-size: 13px;">
                            {{ $emp->created_at ? $emp->created_at->format('M d, Y') : 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8;">No employees matching criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="margin-top: 20px;">
                {{ $employees->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
