<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchSchedule extends Model
{
    use HasFactory;

    protected $table = 'dispatch_schedule';

    protected $fillable = [
        'reservation_id',
        'vehicle_id',
        'driver_id',
        'scheduled_date',
        'shift',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Asset::class, 'vehicle_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function reservation()
    {
        return $this->belongsTo(VehicleReservation::class, 'reservation_id');
    }
}
