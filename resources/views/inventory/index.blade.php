@extends('layouts.app')

@section('title', 'Smart Warehousing & Inventory | Logistics System')

@section('content')
<div class="page-content" style="flex: 1; margin-left: 288px; overflow-y: auto; min-height: 100vh; padding: 24px 32px; box-sizing: border-box;">
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
    <div class="page-header" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1>Smart Warehousing & Inventory</h1>
                <p>Real-time stock control, inventory tracking, and warehouse management</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="document.getElementById('addStockModal').style.display='flex'">
                    <i class="fas fa-plus"></i> Add Stock Item
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
    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
        <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="stat-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <div class="stat-icon blue" style="width: 40px; height: 40px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fas fa-boxes"></i>
                </div>
                <span class="stat-badge green" style="background: #d1fae5; color: #047857; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px;">Total SKUs</span>
            </div>
            <p class="stat-label" style="font-size: 12px; color: #64748b; font-weight: 500; margin: 0;">Total Products</p>
            <p class="stat-value" style="font-size: 26px; font-weight: 800; color: #1e293b; margin: 4px 0;">{{ $stats['total_items'] }}</p>
            <div class="stat-trend" style="font-size: 11px; color: #10b981;">
                <i class="fas fa-check"></i> Monitored in warehouse
            </div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="stat-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <div class="stat-icon emerald" style="width: 40px; height: 40px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fas fa-warehouse"></i>
                </div>
                <span class="stat-badge green" style="background: #d1fae5; color: #047857; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px;">In Stock</span>
            </div>
            <p class="stat-label" style="font-size: 12px; color: #64748b; font-weight: 500; margin: 0;">Optimal Level</p>
            <p class="stat-value" style="font-size: 26px; font-weight: 800; color: #1e293b; margin: 4px 0;">{{ $stats['in_stock'] }}</p>
            <div class="stat-trend" style="font-size: 11px; color: #10b981;">
                <i class="fas fa-arrow-up"></i> Healthy inventory
            </div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="stat-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <div class="stat-icon amber" style="width: 40px; height: 40px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <span class="stat-badge amber" style="background: #fef3c7; color: #b45309; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px;">Low Stock</span>
            </div>
            <p class="stat-label" style="font-size: 12px; color: #64748b; font-weight: 500; margin: 0;">Reorder Needed</p>
            <p class="stat-value" style="font-size: 26px; font-weight: 800; color: #1e293b; margin: 4px 0;">{{ $stats['low_stock'] }}</p>
            <div class="stat-trend down" style="font-size: 11px; color: #d97706;">
                <i class="fas fa-arrow-down"></i> Under threshold
            </div>
        </div>

        <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div class="stat-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <div class="stat-icon purple" style="width: 40px; height: 40px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="fas fa-coins"></i>
                </div>
                <span class="stat-badge green" style="background: #d1fae5; color: #047857; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px;">Total Value</span>
            </div>
            <p class="stat-label" style="font-size: 12px; color: #64748b; font-weight: 500; margin: 0;">Valuation</p>
            <p class="stat-value" style="font-size: 26px; font-weight: 800; color: #1e293b; margin: 4px 0;">₱{{ number_format($stats['total_value'], 2) }}</p>
            <div class="stat-trend" style="font-size: 11px; color: #10b981;">
                <i class="fas fa-chart-line"></i> Warehouse assets
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card" style="margin-bottom: 24px; padding: 16px; background: white; border-radius: 12px; border: 1px solid #e2e8f0;">
        <form method="GET" action="{{ route('inventory.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search item, SKU, code..." style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; min-width: 260px;">
            <select name="category_id" style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                @endforeach
            </select>
            <select name="status" style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                <option value="">All Stock Levels</option>
                <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>
            <button type="submit" class="btn btn-primary" style="padding: 10px 18px; border-radius: 8px;">
                <i class="fas fa-filter"></i> Filter
            </button>
            @if(request()->hasAny(['search', 'category_id', 'status']))
                <a href="{{ route('inventory.index') }}" class="btn btn-outline" style="padding: 10px 18px; border-radius: 8px;">Reset</a>
            @endif
        </form>
    </div>

    <!-- Inventory Table Card -->
    <div class="card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
        <div class="card-header" style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0;"><i class="fas fa-boxes-stacked"></i> Warehouse Inventory Items</h2>
            <span class="card-badge" style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;">{{ $items->total() }} items</span>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; font-weight: 600;">Item Name & SKU</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; font-weight: 600;">Category</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; font-weight: 600;">Supplier</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; font-weight: 600;">Quantity</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; font-weight: 600;">Unit Price</th>
                        <th style="padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; font-weight: 600;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 16px;">
                            <div style="font-weight: 600; color: #1e293b;">{{ $item->item_name }}</div>
                            <div style="font-size: 11px; color: #64748b; font-family: monospace;">SKU: {{ $item->sku }}</div>
                        </td>
                        <td style="padding: 12px 16px; color: #475569; font-size: 13px;">
                            {{ $item->category->category_name ?? 'General' }}
                        </td>
                        <td style="padding: 12px 16px; color: #475569; font-size: 13px;">
                            {{ $item->supplier->supplier_name ?? 'Direct' }}
                        </td>
                        <td style="padding: 12px 16px; font-weight: 700; color: #1e293b;">
                            {{ $item->quantity }}
                        </td>
                        <td style="padding: 12px 16px; color: #1e293b; font-weight: 600;">
                            ₱{{ number_format($item->price, 2) }}
                        </td>
                        <td style="padding: 12px 16px;">
                            @php
                                $statusStyle = match($item->status) {
                                    'in_stock' => 'background: #d1fae5; color: #047857;',
                                    'low_stock' => 'background: #fef3c7; color: #b45309;',
                                    default => 'background: #fee2e2; color: #b91c1c;'
                                };
                            @endphp
                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; {{ $statusStyle }}">
                                {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: #94a3b8;">No items in inventory. Click "Add Stock Item" to register one.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div style="padding: 16px 20px;">
                {{ $items->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Add Stock Modal -->
<div id="addStockModal" class="modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-content" style="background: white; border-radius: 16px; width: 90%; max-width: 520px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <div class="modal-header" style="padding: 18px 24px; background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px;"><i class="fas fa-boxes"></i> Add Inventory Stock Item</h3>
            <button onclick="document.getElementById('addStockModal').style.display='none'" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="{{ route('inventory.store') }}" style="padding: 24px;">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Product / Item Name *</label>
                <input type="text" name="item_name" required placeholder="e.g. Industrial Pallet Straps" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Category</label>
                    <select name="category_id" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        <option value="">-- Choose Category --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Supplier</label>
                    <select name="supplier_id" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                        <option value="">-- Choose Supplier --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->supplier_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Initial Quantity *</label>
                    <input type="number" name="quantity" value="10" min="0" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Unit Price (₱) *</label>
                    <input type="number" step="0.01" name="price" value="100.00" min="0" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
                </div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #1e293b;">Reorder Level Threshold</label>
                <input type="number" name="reorder_level" value="5" min="1" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addStockModal').style.display='none'" style="padding: 10px 18px; border: none; border-radius: 8px; background: #e2e8f0; color: #475569; cursor: pointer;">Cancel</button>
                <button type="submit" class="btn btn-primary" style="padding: 10px 20px; border: none; border-radius: 8px; background: #2563eb; color: white; font-weight: 600; cursor: pointer;">Save Item</button>
            </div>
        </form>
    </div>
</div>
@endsection
