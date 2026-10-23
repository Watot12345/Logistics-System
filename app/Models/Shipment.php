<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $primaryKey = 'shipment_id';

    protected $fillable = [
        'customer_name',
        'delivery_address',
        'order_id',
        'vehicle_id',
        'driver_id',
        'shipment_status',
        'departure_time',
        'estimated_arrival',
        'actual_arrival',
        'current_location',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'estimated_arrival' => 'datetime',
        'actual_arrival' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Asset::class, 'vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function tracking()
    {
        return $this->hasMany(ShipmentTracking::class, 'shipment_id', 'shipment_id');
    }

    public function delays()
    {
        return $this->hasMany(ShipmentDelay::class, 'shipment_id', 'shipment_id');
    }
}
