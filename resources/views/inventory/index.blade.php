@extends('layouts.app')

@section('title', 'Warehouse Inventory - Logistics System')
@section('page-title', 'Warehouse & Stock Management')

@section('content')
<div class="space-y-6">

    <!-- Inventory Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <span class="text-xs text-slate-500 font-semibold uppercase">Total SKUs</span>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_items'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <span class="text-xs text-emerald-600 font-semibold uppercase">In Stock</span>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['in_stock'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <span class="text-xs text-amber-600 font-semibold uppercase">Low Stock Alert</span>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['low_stock'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200">
            <span class="text-xs text-rose-600 font-semibold uppercase">Out of Stock</span>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['out_of_stock'] }}</p>
        </div>
    </div>

    <!-- Actions & Filter -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
        <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-wrap items-center gap-2 flex-1">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search item, SKU..." class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs w-64 focus:ring-2 focus:ring-sky-500">
            <select name="category_id" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                @endforeach
            </select>
            <select name="status" class="px-3 py-1.5 border border-slate-300 rounded-lg text-xs">
                <option value="">All Levels</option>
                <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-xs font-semibold hover:bg-slate-700">Filter</button>
            @if(request()->hasAny(['search', 'category_id', 'status']))
                <a href="{{ route('inventory.index') }}" class="text-xs text-slate-500 hover:text-slate-700">Clear</a>
            @endif
        </form>

        <button onclick="document.getElementById('addItemModal').classList.remove('hidden')" class="px-3.5 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition shadow-sm">
            <i class="fas fa-plus"></i>
            <span>Add Stock Item</span>
        </button>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase text-[10px] tracking-wider font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3">SKU</th>
                        <th class="px-4 py-3">Item Description</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Supplier</th>
                        <th class="px-4 py-3 text-right">Unit Price</th>
                        <th class="px-4 py-3 text-center">Quantity</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-mono font-bold text-slate-800">{{ $item->sku }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $item->item_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->category->category_name ?? 'Uncategorized' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->supplier->supplier_name ?? 'General' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-800">₱{{ number_format($item->price, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-bold {{ $item->quantity <= 0 ? 'text-rose-600' : ($item->quantity <= $item->reorder_level ? 'text-amber-600' : 'text-slate-800') }}">
                                    {{ $item->quantity }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($item->quantity <= 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Out of Stock</span>
                                @elseif($item->quantity <= $item->reorder_level)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Low Stock (≤{{ $item->reorder_level }})</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">In Stock</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">No inventory items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $items->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Modal: Add Item -->
<div id="addItemModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="font-bold text-slate-900 text-sm">Add New Warehouse Item</h3>
            <button onclick="document.getElementById('addItemModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form method="POST" action="{{ route('inventory.store') }}" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Item Name</label>
                <input type="text" name="item_name" required placeholder="e.g. Engine Oil (5L)" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block font-semibold text-slate-700 mb-1">SKU / Code</label>
                <input type="text" name="sku" required placeholder="e.g. OIL-ENG-5L" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-sky-500 uppercase">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Category</label>
                    <select name="category_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        <option value="">-- Select --</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Supplier</label>
                    <select name="supplier_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                        <option value="">-- Select --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->supplier_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Initial Qty</label>
                    <input type="number" name="quantity" required min="0" value="10" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Unit Price (₱)</label>
                    <input type="number" step="0.01" name="price" required min="0" value="100.00" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Reorder Level</label>
                    <input type="number" name="reorder_level" required min="1" value="10" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs">
                </div>
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('addItemModal').classList.add('hidden')" class="px-4 py-2 border border-slate-300 text-slate-600 rounded-lg font-semibold hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-sky-600 text-white rounded-lg font-semibold hover:bg-sky-700">Save Item</button>
            </div>
        </form>
    </div>
</div>
@endsection
