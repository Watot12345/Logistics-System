<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_name',
        'sku',
        'category_id',
        'quantity',
        'price',
        'reorder_level',
        'description',
        'supplier_id',
        'status',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'item_id');
    }
}
