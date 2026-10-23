<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = User::orderBy('name', 'asc');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate(15);
        $roles = ['admin', 'fleet_manager', 'dispatcher', 'driver', 'mechanic', 'employee'];

        return view('employees.index', compact('employees', 'roles'));
    }

    public function profile()
    {
        $user = Auth::user();
        return view('employees.profile', compact('user'));
    }
}
