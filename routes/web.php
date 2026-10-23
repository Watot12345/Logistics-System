<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\ShipmentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\EmployeeController;

// Public Root
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout', [AuthController::class, 'logout']); // Fallback GET logout

// Protected Application Routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Fleet Management
    Route::get('/fleet', [FleetController::class, 'index'])->name('fleet.index');
    Route::post('/fleet', [FleetController::class, 'store'])->name('fleet.store');
    Route::post('/fleet/alerts', [FleetController::class, 'createAlert'])->name('fleet.alerts.store');

    // Shipments / Orders
    Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
    Route::post('/shipments', [ShipmentController::class, 'store'])->name('shipments.store');
    Route::put('/shipments/{id}/status', [ShipmentController::class, 'updateStatus'])->name('shipments.updateStatus');
    Route::delete('/shipments/{id}', [ShipmentController::class, 'destroy'])->name('shipments.destroy');

    // Inventory & Warehousing
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');

    // Employees & Self-Service
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
});
