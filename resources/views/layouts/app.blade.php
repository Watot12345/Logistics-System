<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    @stack('styles')
    <title>@yield('title', 'Logistics System')</title>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo-area">
                <div class="logo-container">
                    <div class="logo-icon">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="logo-text">
                        <h1>Logistics</h1>
                        <p>hotel & restaurant</p>
                    </div>
                </div>
            </div>
            
            <nav class="nav-menu">
                <div class="nav-section">
                    <p class="nav-section-title">Main Menu</p>
                    <ul class="nav-list">
                        @php
                            $userRole = auth()->user()->role ?? 'guest';
                            $showDashboard = in_array($userRole, ['admin', 'dispatcher', 'employee', 'fleet_manager']);
                        @endphp

                        @if($showDashboard)
                        <!-- FIRST: Main Dashboard -->
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" 
                               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        <!-- SECOND: Asset Lifecycle -->
                        <li class="nav-item">
                            <a href="{{ route('lifecycle') }}" 
                               class="nav-link {{ request()->routeIs('lifecycle') ? 'active' : '' }}">
                                <i class="fas fa-th-large"></i>
                                <span>Asset Lifecycle</span>
                            </a>
                        </li>
                        @endif

                        <!-- Smart warehousing -->
                        @if(in_array($userRole, ['admin', 'dispatcher', 'employee', 'fleet_manager']))
                        <li class="nav-item">
                            <a href="{{ route('inventory.index') }}" 
                               class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                                <i class="fas fa-boxes"></i>
                                <span>Smart warehousing</span>
                            </a>
                        </li>
                        @endif

                        <!-- Purchase Orders -->
                        @if(in_array($userRole, ['admin', 'employee', 'dispatcher', 'fleet_manager']))
                        <li class="nav-item">
                            <a href="{{ route('shipments.index') }}" 
                               class="nav-link {{ request()->routeIs('shipments.*') ? 'active' : '' }}">
                                <i class="fas fa-shopping-cart"></i>
                                <span>Purchase Orders</span>
                            </a>
                        </li>
                        @endif

                        <!-- Fleet Management -->
                        @if(in_array($userRole, ['admin', 'fleet_manager', 'driver', 'dispatcher', 'employee']))
                        <li class="nav-item">
                            <a href="{{ route('fleet.index') }}" 
                               class="nav-link {{ request()->routeIs('fleet.*') ? 'active' : '' }}">
                                <i class="fas fa-truck"></i>
                                <span>Fleet Operations</span>
                            </a>
                        </li>
                        @endif

                        <!-- Employee Management -->
                        @if(in_array($userRole, ['admin']))
                        <li class="nav-item">
                            <a href="{{ route('employees.index') }}" 
                               class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}">
                                <i class="fas fa-users"></i>
                                <span>Employee Management</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </nav>
            
            <div class="user-profile">
                <div class="profile-container">
                    <div class="avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? 'GU', 0, 2)) }}
                    </div>

                    <div class="user-info">
                        <p class="user-name">
                            {{ auth()->user()->name ?? 'Guest User' }}
                        </p>
                        <p class="user-role">
                            @php
                                $role = auth()->user()->role ?? 'Guest';
                                $displayRole = match($role) {
                                    'fleet_manager' => 'Fleet Manager',
                                    'admin' => 'Admin',
                                    'dispatcher' => 'Dispatcher',
                                    'driver' => 'Driver',
                                    'employee' => 'Employee',
                                    'mechanic' => 'Mechanic',
                                    default => ucfirst($role)
                                };
                            @endphp
                            {{ $displayRole }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                        @csrf
                        <button type="submit" class="logout-btn" title="Logout" style="background: none; border: none; cursor: pointer;">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        @yield('content')
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
                sidebar.classList.toggle('mobile-show');
            }
        }
    </script>
    @stack('scripts')
</body>
</html>
