@extends('layouts.app')

@section('title', $tank->name)

@push('styles')
<style>
    .tank-stats-card {
        transition: all 0.3s ease;
    }
    .tank-stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('tanks.index') }}">Water Tanks</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $tank->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ $tank->name }}</h1>
            <p class="text-muted mb-0">{{ $tank->location ?? 'No location specified' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('tanks.edit', $tank) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ route('tanks.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Tanks
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <!-- Tank Overview -->
        <div class="col-12 col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Tank Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-muted small mb-1">Status</h6>
                                <div class="d-flex align-items-center">
                                    @php
                                        $statusClass = 'success';
                                        $statusIcon = 'check-circle';
                                        $statusText = 'Operational';
                                        
                                        if ($tank->water_level < 20) {
                                            $statusClass = 'danger';
                                            $statusIcon = 'exclamation-triangle';
                                            $statusText = 'Low Water Level';
                                        } elseif ($tank->water_level < 50) {
                                            $statusClass = 'warning';
                                            $statusIcon = 'exclamation-circle';
                                            $statusText = 'Medium Water Level';
                                        }
                                    @endphp
                                    <div class="avatar-sm bg-{{ $statusClass }}-subtle rounded-circle me-2">
                                        <i class="bi bi-{{ $statusIcon }} text-{{ $statusClass }} fs-4"></i>
                                    </div>
                                    <span class="fw-medium">{{ $statusText }}</span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="text-muted small mb-1">Description</h6>
                                <p class="mb-0">{{ $tank->description ?? 'No description provided.' }}</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6 class="text-muted small mb-1">Water Level</h6>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 me-3">
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: {{ $tank->water_level ?? 0 }}%" 
                                                 aria-valuenow="{{ $tank->water_level ?? 0 }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fw-bold">{{ $tank->water_level ?? 0 }}%</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <h6 class="text-muted small mb-1">Capacity</h6>
                                        <p class="mb-0">{{ number_format($tank->capacity) }} Liters</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <h6 class="text-muted small mb-1">Current Volume</h6>
                                        <p class="mb-0">{{ number_format(($tank->capacity * $tank->water_level) / 100) }} Liters</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tank Sensors -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sensors</h5>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Sensor
                    </button>
                </div>
                <div class="card-body">
                    @if($tank->sensors->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-thermometer-snow text-muted" style="font-size: 2.5rem;"></i>
                            <p class="mt-2 mb-0">No sensors connected to this tank</p>
                            <p class="text-muted small">Add sensors to monitor water level and quality</p>
                            <button class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i> Add Sensor
                            </button>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Sensor</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Last Reading</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tank->sensors as $sensor)
                                        <tr>
                                            <td>{{ $sensor->name }}</td>
                                            <td>
                                                @php
                                                    $typeBadges = [
                                                        'water_level' => ['bg-primary', 'Water Level'],
                                                        'temperature' => ['bg-danger', 'Temperature'],
                                                        'humidity' => ['bg-info', 'Humidity'],
                                                        'ph' => ['bg-success', 'pH'],
                                                        'ec' => ['bg-warning', 'EC'],
                                                    ];
                                                    $typeBadge = $typeBadges[$sensor->type] ?? ['bg-secondary', ucfirst($sensor->type)];
                                                @endphp
                                                <span class="badge {{ $typeBadge[0] }}">{{ $typeBadge[1] }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i> Active
                                                </span>
                                            </td>
                                            <td>
                                                {{ $sensor->last_reading_at ? $sensor->last_reading_at->diffForHumans() : 'Never' }}
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="#">
                                                                <i class="bi bi-eye me-2"></i>View Data
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="#">
                                                                <i class="bi bi-pencil me-2"></i>Edit
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#">
                                                                <i class="bi bi-trash me-2"></i>Remove
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-12 col-xl-4">
            <!-- Quick Stats -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="card tank-stats-card bg-light">
                                <div class="card-body text-center">
                                    <div class="avatar-sm bg-primary bg-opacity-10 text-primary rounded-circle mx-auto mb-2">
                                        <i class="bi bi-droplet fs-4"></i>
                                    </div>
                                    <h4 class="mb-0">{{ $tank->water_level ?? 0 }}%</h4>
                                    <p class="text-muted small mb-0">Water Level</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card tank-stats-card bg-light">
                                <div class="card-body text-center">
                                    <div class="avatar-sm bg-success bg-opacity-10 text-success rounded-circle mx-auto mb-2">
                                        <i class="bi bi-thermometer-half fs-4"></i>
                                    </div>
                                    <h4 class="mb-0">24°C</h4>
                                    <p class="text-muted small mb-0">Temperature</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card tank-stats-card bg-light">
                                <div class="card-body text-center">
                                    <div class="avatar-sm bg-info bg-opacity-10 text-info rounded-circle mx-auto mb-2">
                                        <i class="bi bi-moisture fs-4"></i>
                                    </div>
                                    <h4 class="mb-0">65%</h4>
                                    <p class="text-muted small mb-0">Humidity</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card tank-stats-card bg-light">
                                <div class="card-body text-center">
                                    <div class="avatar-sm bg-warning bg-opacity-10 text-warning rounded-circle mx-auto mb-2">
                                        <i class="bi bi-water fs-4"></i>
                                    </div>
                                    <h4 class="mb-0">7.2</h4>
                                    <p class="text-muted small mb-0">pH Level</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex">
                                <div class="avatar-sm bg-light rounded-circle me-3">
                                    <i class="bi bi-droplet text-primary fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Water level updated</h6>
                                    <p class="small text-muted mb-0">Water level is now at {{ $tank->water_level ?? 0 }}%</p>
                                    <small class="text-muted">5 minutes ago</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex">
                                <div class="avatar-sm bg-light rounded-circle me-3">
                                    <i class="bi bi-thermometer-half text-danger fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Temperature alert</h6>
                                    <p class="small text-muted mb-0">High temperature detected (28°C)</p>
                                    <small class="text-muted">2 hours ago</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item border-0 px-0">
                            <div class="d-flex">
                                <div class="avatar-sm bg-light rounded-circle me-3">
                                    <i class="bi bi-pencil-square text-success fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">Tank details updated</h6>
                                    <p class="small text-muted mb-0">Location changed to "Backyard"</p>
                                    <small class="text-muted">Yesterday</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-3">
                        <a href="#" class="btn btn-sm btn-outline-primary">View All Activity</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
