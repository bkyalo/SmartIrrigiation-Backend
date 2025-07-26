<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f8f9fa;
            color: #212529;
        }
        
        /* Navigation */
        .navbar-custom {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 0;
        }
        
        .navbar-custom .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: white !important;
            display: flex;
            align-items: center;
        }
        
        .navbar-custom .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            margin: 0 0.2rem;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }
        
        .navbar-custom .nav-link:hover,
        .navbar-custom .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
        }
        
        .navbar-custom .nav-link i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* Main content */
        .main-content {
            margin-top: 76px; /* Height of navbar */
            padding: 2rem 0;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        /* Utility classes */
        .cursor-pointer {
            cursor: pointer;
        }
        
        .transition-all {
            transition: all 0.3s ease;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: white;
                border-radius: 0.5rem;
                padding: 1rem;
                margin-top: 0.5rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            }
            
            .navbar-custom .nav-link {
                color: #212529 !important;
                padding: 0.5rem 0.75rem !important;
                margin: 0.25rem 0;
            }
            
            .navbar-custom .nav-link:hover,
            .navbar-custom .nav-link.active {
                background-color: rgba(13, 110, 253, 0.1);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top" x-data="{ isScrolled: false }" @scroll.window="isScrolled = window.scrollY > 10">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="bi bi-droplet-fill me-2"></i>
                SmartIrrigate
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-speedometer2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('tanks.*') ? 'active' : '' }}" href="{{ route('tanks.index') }}">
                            <i class="bi bi-droplet"></i>
                            Water Tanks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('pumps.*') ? 'active' : '' }}" href="{{ route('pumps.index') }}">
                            <i class="bi bi-tools"></i>
                            Pumps
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#sensors">
                            <i class="bi bi-hdd-rack"></i>
                            Sensors
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#analytics">
                            <i class="bi bi-graph-up"></i>
                            Analytics
                        </a>
                    </li>
                </ul>



                <!-- User Menu -->
                <div class="dropdown ms-3 d-none d-lg-block" x-data="{ open: false }">
                    <button class="btn btn-link text-decoration-none text-white d-flex align-items-center p-0" 
                            @click="open = !open" 
                            aria-expanded="false">
                        <div class="user-avatar me-2">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <span class="d-none d-lg-inline">{{ Auth::user()->name }}</span>
                        <i class="bi bi-chevron-down ms-1"></i>
                    </button>
                    
                    <ul class="dropdown-menu dropdown-menu-end mt-2" :class="{ 'show': open }" 
                        @click.away="open = false"
                        x-show="open"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-95">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                
                <!-- Mobile User Menu -->
                <div class="d-lg-none mt-3 pt-3 border-top">
                    <div class="d-flex align-items-center mb-3">
                        <div class="user-avatar me-3">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-bold text-white">{{ Auth::user()->name }}</div>
                            <div class="text-white-50 small">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#">
                                <i class="bi bi-person me-2"></i>Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="nav-link text-danger p-0 bg-transparent border-0">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                </div>
            </div>

            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Alpine.js -->
    <script src="//unpkg.com/alpinejs" defer></script>
    
    <!-- Custom Scripts -->
    @stack('scripts')
    
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-hide dropdowns when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.matches('.dropdown-toggle')) {
                    var dropdowns = document.getElementsByClassName('dropdown-menu');
                    for (var i = 0; i < dropdowns.length; i++) {
                        var openDropdown = dropdowns[i];
                        if (openDropdown.classList.contains('show')) {
                            openDropdown.classList.remove('show');
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
