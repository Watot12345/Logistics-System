<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_name',
        'issue',
        'issue_type',
        'priority',
        'assigned_mechanic',
        'estimated_hours',
        'due_date',
        'started_at',
        'completed_date',
        'completed_notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_date' => 'date',
    ];

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'assigned_mechanic');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
