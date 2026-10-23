@extends('layouts.app')

@section('title', 'Asset Lifecycle | Logistics System')

@section('content')
<div class="main-content">
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

    <div class="page-content">
        <!-- Page Header -->
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Asset Lifecycle & Maintenance</h1>
                    <p>Monitor assets, maintenance lifecycle stages, and operations</p>
                </div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Asset Lifecycle Summary -->
            <div class="card card-full" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h2><i class="fas fa-chart-pie"></i> Asset Lifecycle Distribution</h2>
                    <span class="card-badge">{{ count($assets) }} Total Assets</span>
                </div>
                <div class="card-body">
                    @if(count($assets) === 0)
                        <div style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                            <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                            <p>No assets recorded yet in the system.</p>
                        </div>
                    @else
                        <div class="asset-lifecycle-list" style="display: flex; flex-direction: column; gap: 16px;">
                            @foreach($lifecycleData as $item)
                                @php
                                    $stage = $item['stage'];
                                    $count = $item['count'];
                                    $percentage = $item['percentage'];
                                    $barColor = '#2563eb';
                                    if (strpos($stage, 'Operational') !== false) {
                                        $barColor = '#10b981';
                                    } elseif (strpos($stage, 'Maintenance') !== false) {
                                        $barColor = '#f59e0b';
                                    } elseif (strpos($stage, 'Critical') !== false) {
                                        $barColor = '#ef4444';
                                    } elseif (strpos($stage, 'End-of-Life') !== false) {
                                        $barColor = '#6b7280';
                                    } elseif (strpos($stage, 'Retired') !== false) {
                                        $barColor = '#1e293b';
                                    }
                                @endphp
                                <div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                        <span style="font-size: 14px; font-weight: 500; color: #1e293b;">
                                            {{ $stage }}
                                        </span>
                                        <span style="font-size: 14px; font-weight: 600; color: #475569;">
                                            {{ $count }} assets ({{ $percentage }}%)
                                        </span>
                                    </div>
                                    <div style="height: 8px; background-color: #e2e8f0; border-radius: 999px; overflow: hidden;">
                                        <div style="height: 100%; width: {{ $percentage }}%; background: {{ $barColor }}; border-radius: 999px;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Asset List & Condition -->
            <div class="card card-full" style="margin-bottom: 24px;">
                <div class="card-header">
                    <h2><i class="fas fa-boxes"></i> Asset Registry & Condition</h2>
                    <span class="card-badge">{{ count($assets) }} assets</span>
                </div>
                <div class="card-body">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 12px; text-align: left;">Asset Name</th>
                                <th style="padding: 12px; text-align: left;">Type</th>
                                <th style="padding: 12px; text-align: left;">Status</th>
                                <th style="padding: 12px; text-align: left;">Condition</th>
                                <th style="padding: 12px; text-align: left;">Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $asset)
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 600; color: #1e293b;">{{ $asset->asset_name }}</td>
                                <td style="padding: 12px; text-transform: capitalize; color: #64748b;">{{ $asset->asset_type }}</td>
                                <td style="padding: 12px;">
                                    <span class="status-badge {{ $asset->status === 'good' ? 'status-good' : ($asset->status === 'warning' ? 'status-warning' : 'status-critical') }}" style="padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                        {{ ucfirst($asset->status) }}
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <span class="condition-badge" style="padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #e0f2fe; color: #0369a1;">
                                        {{ $asset->asset_condition ?? 90 }}%
                                    </span>
                                </td>
                                <td style="padding: 12px; color: #64748b;">{{ $asset->created_at ? $asset->created_at->format('M d, Y') : 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="padding: 24px; text-align: center; color: #94a3b8;">No assets found in the system.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Maintenance Alerts -->
            <div class="card card-full">
                <div class="card-header">
                    <h2><i class="fas fa-exclamation-triangle"></i> Maintenance Schedule & Alerts</h2>
                    <span class="card-badge">{{ count($alerts) }} active</span>
                </div>
                <div class="card-body">
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                <th style="padding: 12px; text-align: left;">Asset</th>
                                <th style="padding: 12px; text-align: left;">Reported Issue</th>
                                <th style="padding: 12px; text-align: left;">Priority</th>
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
                                    <span style="padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; {{ $alert->priority === 'high' ? 'background: #fee2e2; color: #b91c1c;' : ($alert->priority === 'medium' ? 'background: #fef3c7; color: #b45309;' : 'background: #d1fae5; color: #047857;') }}">
                                        {{ ucfirst($alert->priority) }}
                                    </span>
                                </td>
                                <td style="padding: 12px; color: #64748b;">{{ $alert->due_date ? date('M d, Y', strtotime($alert->due_date)) : 'N/A' }}</td>
                                <td style="padding: 12px;">
                                    <span style="padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #f1f5f9; color: #475569;">
                                        {{ ucfirst(str_replace('_', ' ', $alert->status)) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" style="padding: 24px; text-align: center; color: #94a3b8;">No pending maintenance alerts. All equipment operational!</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
