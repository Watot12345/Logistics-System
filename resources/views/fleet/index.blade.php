@extends('layouts.app')

@section('title', 'Fleet Management - Logistics System')
@section('page-title', 'Fleet & Vehicle Management')

@section('content')
<div class="space-y-6">

    <!-- Fleet Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <span class="text-xs text-slate-500 font-semibold uppercase">Total Vehicles</span>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <span class="text-xs text-emerald-600 font-semibold uppercase">Good Condition</span>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['available'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <span class="text-xs text-amber-600 font-semibold uppercase">Warning Condition</span>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['warning'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <span class="text-xs text-rose-600 font-semibold uppercase">Maintenance Required</span>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['bad'] }}</p>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="flex items-center justify-between">
        <h3 class="font-bold text-slate-800 text-sm">Vehicle Fleet Directory</h3>
        <div class="flex items-center gap-2">
            <button onclick="document.getElementById('addVehicleModal').classList.remove('hidden')" class="px-3.5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-sm">
                <i class="fas fa-plus"></i>
                <span>Add Vehicle</span>
            </button>
            <button onclick="document.getElementById('reportAlertModal').classList.remove('hidden')" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-sm">
                <i class="fas fa-wrench"></i>
                <span>Report Breakdown</span>
            </button>
        </div>
    </div>

    <!-- Vehicles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($vehicles as $v)
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4 hover:border-slate-300 transition">
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="font-bold text-slate-900 text-base leading-tight">{{ $v->asset_name }}</h4>
                        <span class="text-xs text-slate-400 capitalize">Asset ID: #{{ $v->id }}</span>
                    </div>
                    @if($v->status === 'good')
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Ready</span>
                    @elseif($v->status === 'warning')
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">Warning</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">Maintenance</span>
                    @endif
                </div>

                <!-- Condition Bar -->
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-slate-500 font-medium">Condition Health</span>
                        <span class="font-bold text-slate-700">{{ $v->asset_condition }}%</span>
                    </div>
                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $v->asset_condition >= 75 ? 'bg-emerald-500' : ($v->asset_condition >= 50 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $v->asset_condition }}%"></div>
                    </div>
                </div>

                <!-- Quick specs -->
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                    <span>Type: <strong class="text-slate-700 capitalize">{{ $v->asset_type }}</strong></span>
                    <span>Updated: {{ $v->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 bg-white rounded-xl border border-slate-200">
                <i class="fas fa-truck text-4xl text-slate-300 mb-2"></i>
                <p>No vehicles in fleet database yet.</p>
            </div>
        @endforelse
    </div>

    <!-- Active Maintenance Tasks List -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <h3 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
            <i class="fas fa-clipboard-list text-sky-500"></i>
            <span>Active Maintenance & Alert Log</span>
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-2.5">Vehicle</th>
                        <th class="px-4 py-2.5">Issue & Scope</th>
                        <th class="px-4 py-2.5">Mechanic</th>
                        <th class="px-4 py-2.5">Target Due</th>
                        <th class="px-4 py-2.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($alerts as $a)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $a->asset_name }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $a->issue }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $a->mechanic->name ?? 'Unassigned' }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ $a->due_date ? $a->due_date->format('M d, Y') : '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="capitalize px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $a->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-slate-400">No open alerts.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Add Vehicle -->
<div id="addVehicleModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="font-bold text-slate-900 text-sm">Add New Fleet Vehicle</h3>
            <button onclick="document.getElementById('addVehicleModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form method="POST" action="{{ route('fleet.store') }}" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Vehicle Name / Plate Number</label>
                <input type="text" name="asset_name" required placeholder="e.g. Isuzu Forward (ABC-1234)" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500">
                    <option value="good">Good (Ready for Dispatch)</option>
                    <option value="warning">Warning (Scheduled Checkup)</option>
                    <option value="bad">Bad (In Repair)</option>
                </select>
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Condition Health (%)</label>
                <input type="number" name="asset_condition" min="0" max="100" value="95" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500">
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addVehicleModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg font-semibold hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded-lg font-semibold hover:bg-sky-700">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Report Alert -->
<div id="reportAlertModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="font-bold text-slate-900 text-sm">Report Breakdown / Maintenance Alert</h3>
            <button onclick="document.getElementById('reportAlertModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form method="POST" action="{{ route('fleet.alerts.store') }}" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Target Vehicle</label>
                <select name="asset_name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500">
                    @foreach($vehicles as $v)
                        <option value="{{ $v->asset_name }}">{{ $v->asset_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Issue Description</label>
                <input type="text" name="issue" required placeholder="e.g. Engine Overheating, Transmission Slip" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Severity</label>
                    <select name="issue_type" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        <option value="minor">Minor</option>
                        <option value="major">Major</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Priority</label>
                    <select name="priority" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Assign Mechanic</label>
                    <select name="assigned_mechanic" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        <option value="">-- Optional --</option>
                        @foreach($mechanics as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Target Due Date</label>
                    <input type="date" name="due_date" required value="{{ date('Y-m-d', strtotime('+2 days')) }}" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                </div>
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('reportAlertModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg font-semibold hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg font-semibold hover:bg-rose-700">Submit Alert</button>
            </div>
        </form>
    </div>
</div>
@endsection
