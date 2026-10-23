<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryItem;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryItem::with(['category', 'supplier'])->orderBy('item_name', 'asc');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate(15);
        $categories = Category::all();
        $suppliers = Supplier::all();

        $stats = [
            'total_items' => InventoryItem::count(),
            'in_stock' => InventoryItem::where('status', 'in_stock')->count(),
            'low_stock' => InventoryItem::where('status', 'low_stock')->count(),
            'out_of_stock' => InventoryItem::where('status', 'out_of_stock')->count(),
            'total_value' => InventoryItem::sum(\DB::raw('quantity * price')),
        ];

        return view('inventory.index', compact('items', 'categories', 'suppliers', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:inventory_items,sku',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        $status = 'in_stock';
        if ($validated['quantity'] <= 0) {
            $status = 'out_of_stock';
        } elseif ($validated['quantity'] <= $validated['reorder_level']) {
            $status = 'low_stock';
        }

        $item = InventoryItem::create([
            'item_name' => $validated['item_name'],
            'sku' => $validated['sku'],
            'category_id' => $validated['category_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'quantity' => $validated['quantity'],
            'price' => $validated['price'],
            'reorder_level' => $validated['reorder_level'],
            'description' => $validated['description'] ?? null,
            'status' => $status,
        ]);

        // Record initial movement
        StockMovement::create([
            'item_id' => $item->id,
            'movement_type' => 'in',
            'quantity_change' => $validated['quantity'],
            'previous_quantity' => 0,
            'new_quantity' => $validated['quantity'],
            'notes' => 'Initial stock intake',
            'user_id' => Auth::id(),
        ]);

        return redirect()->route('inventory.index')->with('success', 'Inventory item added successfully.');
    }
}
