<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'requester_id',
        'customer_name',
        'delivery_address',
        'department',
        'purpose',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Asset::class, 'vehicle_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function dispatchSchedules()
    {
        return $this->hasMany(DispatchSchedule::class, 'reservation_id');
    }
}
