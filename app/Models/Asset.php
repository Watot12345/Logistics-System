<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_name',
        'asset_type',
        'status',
        'asset_condition',
    ];

    public function reservations()
    {
        return $this->hasMany(VehicleReservation::class, 'vehicle_id');
    }

    public function dispatchSchedules()
    {
        return $this->hasMany(DispatchSchedule::class, 'vehicle_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'vehicle_id');
    }

    public function maintenanceAlerts()
    {
        return $this->hasMany(MaintenanceAlert::class, 'asset_name', 'asset_name');
    }
}
