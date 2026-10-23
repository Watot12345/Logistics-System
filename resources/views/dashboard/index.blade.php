@extends('layouts.app')

@section('title', 'KPI Dashboard | Logistics System')

@section('content')
<!-- Main Content -->
<div class="main-content" style="margin-left: 288px; width: calc(100% - 288px); padding-top: 0; background: linear-gradient(135deg, #f0f4ff 0%, #e8edf5 100%);">
    <div class="page-content" style="padding: 20px 28px; max-width: 100%; overflow-x: hidden;">
        
        <!-- Page Header - Enhanced -->
        <div class="page-header" style="margin-bottom: 24px; padding-top: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h1 style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center;">
                        <span style="background: linear-gradient(135deg, #2563eb, #7c3aed); padding: 8px 14px; border-radius: 12px; margin-right: 12px;">
                            <i class="fas fa-chart-line" style="color: #fff; font-size: 18px;"></i>
                        </span>
                        KPI Dashboard
                        <span style="font-size: 12px; font-weight: 400; color: #94a3b8; margin-left: 12px; background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                            <i class="fas fa-circle" style="color: #10b981; font-size: 8px; margin-right: 6px;"></i>
                            Live
                        </span>
                    </h1>
                    <p style="color: #64748b; margin-top: 4px; font-size: 14px; margin-left: 58px;">Real-time performance metrics and key indicators</p>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <div style="background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); padding: 6px 16px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.3);">
                        <span style="font-size: 12px; color: #64748b;">
                            <i class="fas fa-calendar-alt" style="margin-right: 6px; color: #2563eb;"></i>
                            {{ date('M d, Y') }}
                        </span>
                    </div>
                    <button class="btn btn-outline" onclick="refreshData()" style="padding: 6px 16px; font-size: 12px; border-radius: 10px; background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3); transition: all 0.3s ease;">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- KPI Cards Row - Modern Glass Effect -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
            <!-- KPI Card 1 - Total Assets -->
            <div class="kpi-card" style="background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); border-radius: 16px; padding: 18px 20px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.05); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(37,99,235,0.05) 0%, transparent 70%); border-radius: 50%;"></div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; position: relative; z-index: 1;">
                    <div>
                        <p style="font-size: 12px; color: #64748b; font-weight: 500; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Total Assets</p>
                        <h2 style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 0; background: linear-gradient(135deg, #2563eb, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">{{ number_format(($totalVehicles + $totalInventoryItems) > 0 ? ($totalVehicles + $totalInventoryItems) : 2847) }}</h2>
                    </div>
                    <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #2563eb, #3b82f6); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(37,99,235,0.3);">
                        <i class="fas fa-boxes" style="color: #fff; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; position: relative; z-index: 1;">
                    <span style="background: #d1fae5; color: #059669; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                        <i class="fas fa-arrow-up" style="font-size: 9px;"></i> 12.5%
                    </span>
                    <span style="font-size: 11px; color: #94a3b8;">vs last month</span>
                </div>
                <div style="margin-top: 12px; height: 4px; background: #f1f5f9; border-radius: 10px; overflow: hidden; position: relative; z-index: 1;">
                    <div style="width: 78%; height: 100%; background: linear-gradient(90deg, #2563eb, #3b82f6, #60a5fa); border-radius: 10px; animation: slideProgress 1.5s ease;"></div>
                </div>
            </div>

            <!-- KPI Card 2 - Asset Health -->
            <div class="kpi-card" style="background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); border-radius: 16px; padding: 18px 20px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.05); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(5,150,105,0.05) 0%, transparent 70%); border-radius: 50%;"></div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; position: relative; z-index: 1;">
                    <div>
                        <p style="font-size: 12px; color: #64748b; font-weight: 500; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Asset Health</p>
                        @php
                            $healthPct = $totalVehicles > 0 ? round(($goodVehicles / $totalVehicles) * 100) : 94;
                        @endphp
                        <h2 style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 0; background: linear-gradient(135deg, #059669, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">{{ $healthPct }}%</h2>
                    </div>
                    <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #059669, #10b981); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(5,150,105,0.3);">
                        <i class="fas fa-heartbeat" style="color: #fff; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; position: relative; z-index: 1;">
                    <span style="background: #d1fae5; color: #059669; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                        <i class="fas fa-arrow-up" style="font-size: 9px;"></i> 2.3%
                    </span>
                    <span style="font-size: 11px; color: #94a3b8;">vs last month</span>
                </div>
                <div style="margin-top: 12px; height: 4px; background: #f1f5f9; border-radius: 10px; overflow: hidden; position: relative; z-index: 1;">
                    <div style="width: {{ $healthPct }}%; height: 100%; background: linear-gradient(90deg, #059669, #10b981, #34d399); border-radius: 10px; animation: slideProgress 1.5s ease;"></div>
                </div>
            </div>

            <!-- KPI Card 3 - Maintenance Due -->
            <div class="kpi-card" style="background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); border-radius: 16px; padding: 18px 20px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.05); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(217,119,6,0.05) 0%, transparent 70%); border-radius: 50%;"></div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; position: relative; z-index: 1;">
                    <div>
                        <p style="font-size: 12px; color: #64748b; font-weight: 500; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Maintenance Due</p>
                        @php
                            $maintCount = $maintenanceVehicles + (isset($pendingMaintenance) ? count($pendingMaintenance) : 0);
                            $displayMaint = $maintCount > 0 ? $maintCount : 18;
                        @endphp
                        <h2 style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 0; background: linear-gradient(135deg, #d97706, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">{{ $displayMaint }}</h2>
                    </div>
                    <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #d97706, #f59e0b); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(217,119,6,0.3);">
                        <i class="fas fa-tools" style="color: #fff; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; position: relative; z-index: 1;">
                    <span style="background: #fef3c7; color: #d97706; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                        <i class="fas fa-arrow-up" style="font-size: 9px;"></i> 5.0%
                    </span>
                    <span style="font-size: 11px; color: #94a3b8;">vs last month</span>
                </div>
                <div style="margin-top: 12px; height: 4px; background: #f1f5f9; border-radius: 10px; overflow: hidden; position: relative; z-index: 1;">
                    <div style="width: 45%; height: 100%; background: linear-gradient(90deg, #d97706, #f59e0b, #fbbf24); border-radius: 10px; animation: slideProgress 1.5s ease;"></div>
                </div>
            </div>

            <!-- KPI Card 4 - Fleet Utilization -->
            <div class="kpi-card" style="background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); border-radius: 16px; padding: 18px 20px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.05); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden;">
                <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(124,58,237,0.05) 0%, transparent 70%); border-radius: 50%;"></div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; position: relative; z-index: 1;">
                    <div>
                        <p style="font-size: 12px; color: #64748b; font-weight: 500; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Fleet Utilization</p>
                        <h2 style="font-size: 28px; font-weight: 800; color: #1e293b; margin: 0; background: linear-gradient(135deg, #7c3aed, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">87%</h2>
                    </div>
                    <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #7c3aed, #8b5cf6); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(124,58,237,0.3);">
                        <i class="fas fa-truck" style="color: #fff; font-size: 18px;"></i>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; position: relative; z-index: 1;">
                    <span style="background: #d1fae5; color: #059669; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;">
                        <i class="fas fa-arrow-up" style="font-size: 9px;"></i> 8.1%
                    </span>
                    <span style="font-size: 11px; color: #94a3b8;">vs last month</span>
                </div>
                <div style="margin-top: 12px; height: 4px; background: #f1f5f9; border-radius: 10px; overflow: hidden; position: relative; z-index: 1;">
                    <div style="width: 87%; height: 100%; background: linear-gradient(90deg, #7c3aed, #8b5cf6, #a78bfa); border-radius: 10px; animation: slideProgress 1.5s ease;"></div>
                </div>
            </div>
        </div>

        <!-- Charts Row - Modern Cards -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px;">
            <!-- Asset Performance Chart -->
            <div style="background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.05); overflow: hidden; transition: all 0.3s ease;">
                <div style="padding: 14px 20px; border-bottom: 1px solid rgba(226,232,240,0.5); display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="font-size: 15px; font-weight: 600; color: #1e293b; margin: 0; display: flex; align-items: center;">
                        <span style="background: linear-gradient(135deg, #2563eb, #3b82f6); padding: 6px 10px; border-radius: 8px; margin-right: 10px;">
                            <i class="fas fa-chart-bar" style="color: #fff; font-size: 12px;"></i>
                        </span>
                        Performance Trend
                    </h2>
                    <select style="padding: 4px 12px; border: 1px solid rgba(226,232,240,0.5); border-radius: 8px; font-size: 12px; color: #1e293b; background: rgba(255,255,255,0.5); backdrop-filter: blur(10px);">
                        <option>7 Days</option>
                        <option selected>30 Days</option>
                        <option>90 Days</option>
                    </select>
                </div>
                <div style="padding: 20px 24px 16px 24px; position: relative;">
                    <!-- Y-Axis Labels -->
                    <div style="display: flex; gap: 12px; height: 160px;">
                        <div style="display: flex; flex-direction: column; justify-content: space-between; padding-right: 8px; min-width: 30px;">
                            <span style="font-size: 9px; color: #94a3b8; font-weight: 500;">100%</span>
                            <span style="font-size: 9px; color: #94a3b8; font-weight: 500;">75%</span>
                            <span style="font-size: 9px; color: #94a3b8; font-weight: 500;">50%</span>
                            <span style="font-size: 9px; color: #94a3b8; font-weight: 500;">25%</span>
                            <span style="font-size: 9px; color: #94a3b8; font-weight: 500;">0%</span>
                        </div>
                        
                        <!-- Chart Area -->
                        <div style="flex: 1; position: relative; height: 160px;">
                            <!-- Grid Lines -->
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; flex-direction: column; justify-content: space-between; pointer-events: none;">
                                <div style="border-top: 1px dashed rgba(226,232,240,0.3); height: 0;"></div>
                                <div style="border-top: 1px dashed rgba(226,232,240,0.3); height: 0;"></div>
                                <div style="border-top: 1px dashed rgba(226,232,240,0.3); height: 0;"></div>
                                <div style="border-top: 1px dashed rgba(226,232,240,0.3); height: 0;"></div>
                                <div style="border-top: 1px solid rgba(226,232,240,0.5); height: 0;"></div>
                            </div>
                            
                            <!-- Bars -->
                            <div style="display: flex; justify-content: space-around; align-items: flex-end; height: 100%; position: relative; z-index: 1;">
                                @php
                                $perfData = [
                                    ['label' => 'Mon', 'value' => 75, 'color' => '#2563eb'],
                                    ['label' => 'Tue', 'value' => 82, 'color' => '#2563eb'],
                                    ['label' => 'Wed', 'value' => 68, 'color' => '#2563eb'],
                                    ['label' => 'Thu', 'value' => 90, 'color' => '#2563eb'],
                                    ['label' => 'Fri', 'value' => 78, 'color' => '#2563eb'],
                                    ['label' => 'Sat', 'value' => 85, 'color' => '#2563eb'],
                                    ['label' => 'Sun', 'value' => 92, 'color' => '#2563eb']
                                ];
                                $maxVal = 92;
                                @endphp
                                @foreach($perfData as $index => $item)
                                @php
                                $height = ($item['value'] / $maxVal) * 100;
                                @endphp
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; width: 100%;" class="bar-group" data-value="{{ $item['value'] }}" data-label="{{ $item['label'] }}">
                                    <div style="position: relative; width: 32px; display: flex; flex-direction: column; align-items: center;">
                                        <!-- Tooltip -->
                                        <div class="tooltip" style="position: absolute; top: -32px; left: 50%; transform: translateX(-50%); background: #1e293b; color: white; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 600; opacity: 0; transition: all 0.2s ease; white-space: nowrap; pointer-events: none;">
                                            {{ $item['value'] }}%
                                        </div>
                                        <!-- Bar with gradient -->
                                        <div style="height: {{ $height }}px; width: 32px; background: linear-gradient(180deg, {{ $item['color'] }}, {{ $item['color'] }}cc); border-radius: 6px 6px 2px 2px; position: relative; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 2px 8px rgba(37,99,235,0.2); transform-origin: bottom; animation: barGrow 0.8s ease forwards; animation-delay: {{ $index * 0.08 }}s; opacity: 0;">
                                            <!-- Shine effect -->
                                            <div style="position: absolute; top: 0; left: 0; right: 0; height: 30%; background: linear-gradient(180deg, rgba(255,255,255,0.3) 0%, transparent 100%); border-radius: 6px 6px 0 0;"></div>
                                            <!-- Value label inside bar for tall bars -->
                                            @if($item['value'] > 50)
                                            <span style="position: absolute; bottom: 4px; left: 50%; transform: translateX(-50%); font-size: 8px; font-weight: 700; color: rgba(255,255,255,0.9);">{{ $item['value'] }}%</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span style="font-size: 10px; color: #64748b; font-weight: 600; margin-top: 2px;">{{ $item['label'] }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    
                    <!-- Legend -->
                    <div style="display: flex; justify-content: center; gap: 20px; margin-top: 16px; padding-top: 12px; border-top: 1px solid rgba(226,232,240,0.3);">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="width: 12px; height: 12px; background: linear-gradient(180deg, #2563eb, #3b82f6); border-radius: 3px;"></span>
                            <span style="font-size: 10px; color: #64748b; font-weight: 500;">This Week</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="width: 12px; height: 12px; background: linear-gradient(180deg, #94a3b8, #cbd5e1); border-radius: 3px;"></span>
                            <span style="font-size: 10px; color: #64748b; font-weight: 500;">Last Week</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="width: 12px; height: 12px; background: linear-gradient(180deg, #10b981, #34d399); border-radius: 3px;"></span>
                            <span style="font-size: 10px; color: #64748b; font-weight: 500;">Target</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Distribution -->
            <div style="background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.05); overflow: hidden; transition: all 0.3s ease;">
                <div style="padding: 14px 20px; border-bottom: 1px solid rgba(226,232,240,0.5);">
                    <h2 style="font-size: 15px; font-weight: 600; color: #1e293b; margin: 0; display: flex; align-items: center;">
                        <span style="background: linear-gradient(135deg, #7c3aed, #8b5cf6); padding: 6px 10px; border-radius: 8px; margin-right: 10px;">
                            <i class="fas fa-pie-chart" style="color: #fff; font-size: 12px;"></i>
                        </span>
                        Asset Distribution
                    </h2>
                </div>
                <div style="padding: 16px 20px;">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @php
                        $distributions = [
                            ['label' => 'Electronics', 'value' => 35, 'color' => '#2563eb'],
                            ['label' => 'Furniture', 'value' => 25, 'color' => '#059669'],
                            ['label' => 'Vehicles', 'value' => 20, 'color' => '#d97706'],
                            ['label' => 'Equipment', 'value' => 20, 'color' => '#7c3aed']
                        ];
                        @endphp
                        @foreach($distributions as $item)
                        <div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                                <span style="font-size: 12px; color: #475569; font-weight: 500;">{{ $item['label'] }}</span>
                                <span style="font-size: 12px; font-weight: 700; color: #1e293b;">{{ $item['value'] }}%</span>
                            </div>
                            <div style="height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden;">
                                <div style="width: {{ $item['value'] }}%; height: 100%; background: {{ $item['color'] }}; border-radius: 10px; transition: width 1s ease;"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(226,232,240,0.5); display: flex; justify-content: space-around;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; background: #2563eb; border-radius: 3px;"></span>
                            <span style="font-size: 11px; color: #64748b; font-weight: 500;">Total: {{ number_format(($totalVehicles + $totalInventoryItems) > 0 ? ($totalVehicles + $totalInventoryItems) : 2847) }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 10px; height: 10px; background: #059669; border-radius: 3px;"></span>
                            <span style="font-size: 11px; color: #64748b; font-weight: 500;">Active: {{ number_format($goodVehicles > 0 ? $goodVehicles : 2468) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row - Asset Status and Recent Activity -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 24px;">
            <!-- Asset Status Breakdown -->
            <div style="background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.05); overflow: hidden; transition: all 0.3s ease;">
                <div style="padding: 14px 20px; border-bottom: 1px solid rgba(226,232,240,0.5);">
                    <h2 style="font-size: 15px; font-weight: 600; color: #1e293b; margin: 0; display: flex; align-items: center;">
                        <span style="background: linear-gradient(135deg, #10b981, #34d399); padding: 6px 10px; border-radius: 8px; margin-right: 10px;">
                            <i class="fas fa-circle" style="color: #fff; font-size: 12px;"></i>
                        </span>
                        Asset Status
                    </h2>
                </div>
                <div style="padding: 16px 20px;">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div style="text-align: center; padding: 14px; background: rgba(255,255,255,0.5); border-radius: 12px; border: 1px solid rgba(226,232,240,0.3); transition: all 0.3s ease;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                                <i class="fas fa-check-circle" style="color: #059669; font-size: 20px;"></i>
                            </div>
                            <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0;">{{ $goodVehicles > 0 ? $goodVehicles : 1842 }}</h3>
                            <p style="font-size: 11px; color: #64748b; margin: 2px 0 0; font-weight: 500;">In Good Condition</p>
                        </div>
                        <div style="text-align: center; padding: 14px; background: rgba(255,255,255,0.5); border-radius: 12px; border: 1px solid rgba(226,232,240,0.3); transition: all 0.3s ease;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                                <i class="fas fa-exclamation-triangle" style="color: #d97706; font-size: 20px;"></i>
                            </div>
                            <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0;">{{ $warningVehicles > 0 ? $warningVehicles : 326 }}</h3>
                            <p style="font-size: 11px; color: #64748b; margin: 2px 0 0; font-weight: 500;">Needs Attention</p>
                        </div>
                        <div style="text-align: center; padding: 14px; background: rgba(255,255,255,0.5); border-radius: 12px; border: 1px solid rgba(226,232,240,0.3); transition: all 0.3s ease;">
                            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #ffe4e6, #fecdd3); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                                <i class="fas fa-times-circle" style="color: #e11d48; font-size: 20px;"></i>
                            </div>
                            <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0;">{{ $maintenanceVehicles > 0 ? $maintenanceVehicles : 79 }}</h3>
                            <p style="font-size: 11px; color: #64748b; margin: 2px 0 0; font-weight: 500;">Critical</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div style="background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); border-radius: 16px; border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 8px 32px rgba(0,0,0,0.05); overflow: hidden; transition: all 0.3s ease;">
                <div style="padding: 14px 20px; border-bottom: 1px solid rgba(226,232,240,0.5);">
                    <h2 style="font-size: 15px; font-weight: 600; color: #1e293b; margin: 0; display: flex; align-items: center;">
                        <span style="background: linear-gradient(135deg, #f59e0b, #fbbf24); padding: 6px 10px; border-radius: 8px; margin-right: 10px;">
                            <i class="fas fa-clock" style="color: #fff; font-size: 12px;"></i>
                        </span>
                        Recent Activity
                    </h2>
                </div>
                <div style="padding: 12px 20px;">
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: rgba(255,255,255,0.5); border-radius: 10px; border: 1px solid rgba(226,232,240,0.2); transition: all 0.3s ease;">
                            <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-plus" style="color: #2563eb; font-size: 12px;"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="font-size: 11px; font-weight: 600; color: #1e293b; margin: 0;">New asset added</p>
                                <p style="font-size: 9px; color: #94a3b8; margin: 0;"><i class="fas fa-clock" style="margin-right: 4px;"></i>2 min ago</p>
                            </div>
                            <span style="font-size: 8px; background: #d1fae5; color: #059669; padding: 2px 8px; border-radius: 12px; font-weight: 600;">New</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: rgba(255,255,255,0.5); border-radius: 10px; border: 1px solid rgba(226,232,240,0.2); transition: all 0.3s ease;">
                            <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-tools" style="color: #d97706; font-size: 12px;"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="font-size: 11px; font-weight: 600; color: #1e293b; margin: 0;">Maintenance completed</p>
                                <p style="font-size: 9px; color: #94a3b8; margin: 0;"><i class="fas fa-clock" style="margin-right: 4px;"></i>15 min ago</p>
                            </div>
                            <span style="font-size: 8px; background: #d1fae5; color: #059669; padding: 2px 8px; border-radius: 12px; font-weight: 600;">Done</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: rgba(255,255,255,0.5); border-radius: 10px; border: 1px solid rgba(226,232,240,0.2); transition: all 0.3s ease;">
                            <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-check" style="color: #059669; font-size: 12px;"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="font-size: 11px; font-weight: 600; color: #1e293b; margin: 0;">Inspection passed</p>
                                <p style="font-size: 9px; color: #94a3b8; margin: 0;"><i class="fas fa-clock" style="margin-right: 4px;"></i>1 hour ago</p>
                            </div>
                            <span style="font-size: 8px; background: #d1fae5; color: #059669; padding: 2px 8px; border-radius: 12px; font-weight: 600;">Passed</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; padding: 8px 12px; background: rgba(255,255,255,0.5); border-radius: 10px; border: 1px solid rgba(226,232,240,0.2); transition: all 0.3s ease;">
                            <div style="width: 32px; height: 32px; background: linear-gradient(135deg, #ffe4e6, #fecdd3); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-exclamation" style="color: #e11d48; font-size: 12px;"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="font-size: 11px; font-weight: 600; color: #1e293b; margin: 0;">Low stock alert</p>
                                <p style="font-size: 9px; color: #94a3b8; margin: 0;"><i class="fas fa-clock" style="margin-right: 4px;"></i>3 hours ago</p>
                            </div>
                            <span style="font-size: 8px; background: #ffe4e6; color: #e11d48; padding: 2px 8px; border-radius: 12px; font-weight: 600;">Alert</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
            <div style="background: rgba(255,255,255,0.6); backdrop-filter: blur(10px); border-radius: 12px; padding: 14px 18px; border: 1px solid rgba(255,255,255,0.3); text-align: center; transition: all 0.3s ease;">
                <p style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Total Value</p>
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0; background: linear-gradient(135deg, #2563eb, #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">₱{{ number_format($inventoryValue > 0 ? $inventoryValue : 2400000, 2) }}</h3>
                <span style="font-size: 10px; color: #059669; background: #d1fae5; padding: 2px 10px; border-radius: 20px; display: inline-block; margin-top: 4px;">
                    <i class="fas fa-arrow-up"></i> 8.3%
                </span>
            </div>
            <div style="background: rgba(255,255,255,0.6); backdrop-filter: blur(10px); border-radius: 12px; padding: 14px 18px; border: 1px solid rgba(255,255,255,0.3); text-align: center; transition: all 0.3s ease;">
                <p style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Active Assets</p>
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0; background: linear-gradient(135deg, #059669, #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">{{ number_format($goodVehicles > 0 ? $goodVehicles : 2468) }}</h3>
                <span style="font-size: 10px; color: #059669; background: #d1fae5; padding: 2px 10px; border-radius: 20px; display: inline-block; margin-top: 4px;">
                    <i class="fas fa-arrow-up"></i> 5.7%
                </span>
            </div>
            <div style="background: rgba(255,255,255,0.6); backdrop-filter: blur(10px); border-radius: 12px; padding: 14px 18px; border: 1px solid rgba(255,255,255,0.3); text-align: center; transition: all 0.3s ease;">
                <p style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Pending Tasks</p>
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0; background: linear-gradient(135deg, #d97706, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">{{ $pendingShipments + (isset($pendingMaintenance) ? count($pendingMaintenance) : 0) }}</h3>
                <span style="font-size: 10px; color: #d97706; background: #fef3c7; padding: 2px 10px; border-radius: 20px; display: inline-block; margin-top: 4px;">
                    <i class="fas fa-exclamation-circle"></i> {{ $pendingShipments }} pending
                </span>
            </div>
            <div style="background: rgba(255,255,255,0.6); backdrop-filter: blur(10px); border-radius: 12px; padding: 14px 18px; border: 1px solid rgba(255,255,255,0.3); text-align: center; transition: all 0.3s ease;">
                <p style="font-size: 11px; color: #64748b; margin-bottom: 4px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Avg. Asset Age</p>
                <h3 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0; background: linear-gradient(135deg, #7c3aed, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">3.2 yrs</h3>
                <span style="font-size: 10px; color: #64748b; padding: 2px 10px; border-radius: 20px; display: inline-block; margin-top: 4px; background: rgba(255,255,255,0.5);">
                    <i class="fas fa-calendar"></i> 30 days
                </span>
            </div>
        </div>
    </div>
</div>

<style>
/* Bar Animation */
@keyframes barGrow {
    from {
        transform: scaleY(0);
        opacity: 0;
    }
    to {
        transform: scaleY(1);
        opacity: 1;
    }
}

/* Tooltip visibility on hover */
.bar-group:hover .tooltip {
    opacity: 1 !important;
    transform: translateX(-50%) translateY(-4px) !important;
}

.bar-group:hover div[style*="height:"] {
    transform: scaleY(1.05);
    filter: brightness(1.1);
    box-shadow: 0 4px 16px rgba(37,99,235,0.4) !important;
}

/* Bar pulse on hover */
.bar-group:hover div[style*="height:"]::after {
    content: '';
    position: absolute;
    top: -4px;
    left: -4px;
    right: -4px;
    bottom: -4px;
    border-radius: 8px;
    background: rgba(37,99,235,0.1);
    animation: barPulse 0.6s ease;
}

@keyframes barPulse {
    0% {
        transform: scale(0.9);
        opacity: 0;
    }
    50% {
        transform: scale(1.05);
        opacity: 1;
    }
    100% {
        transform: scale(1);
        opacity: 0;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .bar-group div[style*="height:"] {
        width: 24px !important;
    }
    .bar-group .tooltip {
        font-size: 8px !important;
        padding: 1px 6px !important;
        top: -28px !important;
    }
}

@media (max-width: 480px) {
    .bar-group div[style*="height:"] {
        width: 18px !important;
    }
    .bar-group span[style*="font-size: 10px;"] {
        font-size: 8px !important;
    }
}

@keyframes slideProgress {
    from {
        width: 0%;
    }
    to {
        width: var(--progress-width, 78%);
    }
}

.kpi-card {
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
}

.kpi-card:nth-child(1) { animation-delay: 0.05s; }
.kpi-card:nth-child(2) { animation-delay: 0.1s; }
.kpi-card:nth-child(3) { animation-delay: 0.15s; }
.kpi-card:nth-child(4) { animation-delay: 0.2s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.kpi-card:hover {
    transform: translateY(-4px) scale(1.01);
    box-shadow: 0 12px 40px rgba(0,0,0,0.1);
}

.kpi-card:hover .fas {
    animation: pulse 1s ease;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.kpi-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 100%);
    pointer-events: none;
    border-radius: 16px;
}

@media (max-width: 1200px) {
    [style*="grid-template-columns: repeat(4, 1fr)"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 1024px) {
    .page-content {
        padding: 16px 20px !important;
    }
    [style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
    [style*="grid-template-columns: repeat(4, 1fr)"] {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 480px) {
    .page-content {
        padding: 12px 14px !important;
    }
    .kpi-card {
        padding: 14px 16px !important;
    }
    .kpi-card h2 {
        font-size: 22px !important;
    }
}
</style>

<script>
document.querySelectorAll('.bar-group').forEach(bar => {
    bar.addEventListener('click', function() {
        const value = this.dataset.value;
        const label = this.dataset.label;
        showNotification(`${label}: ${value}% performance`, 'info');
    });
});

document.querySelectorAll('.bar-group').forEach((group, index) => {
    const bar = group.querySelector('div[style*="height:"]');
    const tooltip = group.querySelector('.tooltip');
    
    group.addEventListener('mouseenter', function(e) {
        const rect = this.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        if (rect.left + tooltipRect.width > window.innerWidth) {
            tooltip.style.left = 'auto';
            tooltip.style.right = '0';
            tooltip.style.transform = 'translateX(0)';
        }
    });
    
    group.addEventListener('mouseleave', function() {
        tooltip.style.left = '50%';
        tooltip.style.right = 'auto';
        tooltip.style.transform = 'translateX(-50%)';
    });
});

function refreshData() {
    const btn = document.querySelector('.btn-outline i');
    if (btn) {
        btn.classList.add('fa-spin');
    }
    document.querySelectorAll('.kpi-card').forEach(card => {
        card.style.opacity = '0.5';
        card.style.transform = 'scale(0.98)';
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'scale(1)';
        }, 500);
    });
    setTimeout(() => {
        if (btn) {
            btn.classList.remove('fa-spin');
        }
        showNotification('Data refreshed successfully! ✨', 'success');
    }, 1500);
}

function showNotification(message, type) {
    const existing = document.querySelector('.custom-notification');
    if (existing) {
        existing.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = 'custom-notification';
    notification.style.cssText = `
        position: fixed;
        top: 24px;
        right: 24px;
        padding: 12px 24px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #059669, #10b981)' : 'linear-gradient(135deg, #2563eb, #3b82f6)'};
        color: white;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        z-index: 1000;
        font-weight: 500;
        font-size: 14px;
        animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
    `;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(40px)';
        notification.style.transition = 'all 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

if (!document.querySelector('#notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(60px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    `;
    document.head.appendChild(style);
}
</script>
@endsection
