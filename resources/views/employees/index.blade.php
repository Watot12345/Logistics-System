@extends('layouts.app')

@section('title', 'Staff & Drivers - Logistics System')
@section('page-title', 'Staff & Drivers Directory')

@section('content')
<div class="space-y-6">

    <!-- Filter Control -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <form method="GET" action="{{ route('employees.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee name, ID..." class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs w-64 focus:ring-2 focus:ring-sky-500">
            <select name="role" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs capitalize">
                <option value="">All Roles</option>
                @foreach($roles as $r)
                    <option value="{{ $r }}" {{ request('role') === $r ? 'selected' : '' }}>{{ str_replace('_', ' ', $r) }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-xs font-semibold hover:bg-slate-700">Filter</button>
            @if(request()->hasAny(['search', 'role']))
                <a href="{{ route('employees.index') }}" class="text-xs text-slate-500 hover:text-slate-700">Clear</a>
            @endif
        </form>
    </div>

    <!-- Staff Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($employees as $emp)
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4 hover:border-slate-300 transition">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 font-bold text-base">
                        {{ strtoupper(substr($emp->name ?? 'E', 0, 1)) }}
                    </div>
                    <div class="overflow-hidden flex-1">
                        <h4 class="font-bold text-slate-900 text-sm truncate">{{ $emp->name }}</h4>
                        <span class="text-xs text-slate-400 font-mono block">{{ $emp->employee_id ?? 'ID: #'.$emp->id }}</span>
                    </div>
                    <span class="capitalize px-2 py-0.5 rounded-full text-[10px] font-bold {{ $emp->role === 'admin' ? 'bg-purple-50 text-purple-700 border border-purple-200' : ($emp->role === 'fleet_manager' ? 'bg-sky-50 text-sky-700 border border-sky-200' : ($emp->role === 'driver' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-700')) }}">
                        {{ str_replace('_', ' ', $emp->role) }}
                    </span>
                </div>

                <div class="space-y-1.5 text-xs text-slate-600 pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Username:</span>
                        <span class="font-medium text-slate-800">{{ $emp->username ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Email:</span>
                        <span class="font-medium text-slate-800 truncate max-w-[160px]">{{ $emp->email }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Status:</span>
                        <span class="capitalize font-semibold {{ $emp->status === 'active' ? 'text-emerald-600' : 'text-slate-500' }}">{{ $emp->status }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-slate-400 bg-white rounded-xl border border-slate-200">
                <i class="fas fa-users text-4xl text-slate-300 mb-2"></i>
                <p>No staff or drivers found matching filters.</p>
            </div>
        @endforelse
    </div>

    @if($employees->hasPages())
        <div class="bg-white p-3 rounded-xl border border-slate-200">
            {{ $employees->links() }}
        </div>
    @endif

</div>
@endsection
