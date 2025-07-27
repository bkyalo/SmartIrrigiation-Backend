@extends('layouts.app')

@section('title', 'Smart Irrigation Dashboard')

@push('styles')
<style>
    .status-badge { font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.5rem; }
    .tank-level { height: 8px; border-radius: 0.25rem; overflow: hidden; }
    .card { height: 100%; margin-bottom: 1.5rem; }
    .card-title { font-weight: 600; color: #2d3748; }
    .card-value { font-size: 1.75rem; font-weight: 700; margin: 0.5rem 0; }
    .card-icon { font-size: 2rem; opacity: 0.8; margin-bottom: 1rem; }
    .system-status-card { color: white; border: none; }
    .system-status-card .card-body { padding: 1.5rem; }
    .system-status-card .card-title { color: white; margin-bottom: 0.5rem; }
    .system-status-card .card-text { opacity: 0.9; margin-bottom: 0; }
    .icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <div class="py-6">
        <!-- System Status -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card system-status-card {{ $systemStatus['status'] === 'warning' ? 'bg-danger' : 'bg-success' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title">System Status: {{ ucfirst($systemStatus['status']) }}</h5>
                                <p class="card-text mb-0">{{ $systemStatus['message'] }}</p>
                            </div>
                            <div class="display-4">
                                <i class="bi {{ $systemStatus['status'] === 'warning' ? 'bi-exclamation-triangle' : 'bi-check-circle' }}"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold">System Status: {{ ucfirst($systemStatus['status']) }}</h3>
                            <p class="text-sm">{{ $systemStatus['message'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tanks Overview -->
            <h3 class="text-lg font-semibold mb-4">Water Tanks</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                @forelse($tanks as $tank)
                    @php
                        $waterLevel = $tank->water_level ?? 0;
                        $bgColor = $waterLevel < 20 ? 'bg-red-100' : 'bg-blue-100';
                        $textColor = $waterLevel < 20 ? 'text-red-800' : 'text-blue-800';
                        
                        // Find the latest reading timestamp
                        $latestReading = null;
                        foreach ($tank->sensors as $sensor) {
                            if ($sensor->readings->isNotEmpty()) {
                                $latestReading = $sensor->readings->first();
                                break;
                            }
                        }
                    @endphp
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="font-semibold text-lg">{{ $tank->name }}</h4>
                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $bgColor }} {{ $textColor }}">
                                    {{ $waterLevel }}% full
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                                <div class="h-4 rounded-full {{ $waterLevel < 20 ? 'bg-red-500' : 'bg-blue-500' }}" 
                                     style="width: {{ $waterLevel }}%">
                                </div>
                            </div>
                            @if($latestReading)
                                <p class="text-sm text-gray-600 mt-2">
                                    Last updated: {{ $latestReading->created_at->diffForHumans() }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-8">
                        <p class="text-gray-500">No tanks configured. Add your first tank to get started.</p>
                    </div>
                @endforelse
            </div>

            <!-- Plots Overview -->
            <h3 class="text-lg font-semibold mb-4">Irrigation Plots</h3>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-4">
                @forelse($plots as $plot)
                    @php
                        $statusColor = [
                            'active' => 'bg-success',
                            'inactive' => 'bg-secondary',
                            'maintenance' => 'bg-warning',
                        ][$plot->status] ?? 'bg-secondary';
                    @endphp
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">{{ $plot->name }}</h5>
                                    <span class="badge {{ $statusColor }}">{{ ucfirst($plot->status) }}</span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Crop Type</span>
                                        <span>{{ $plot->crop_type ?? 'N/A' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Area</span>
                                        <span>{{ number_format($plot->area, 2) }} m²</span>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Moisture Threshold</span>
                                        <span>{{ $plot->moisture_threshold }}%</span>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('plots.show', $plot) }}" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                    <small class="text-muted">
                                        {{ $plot->valves_count ?? 0 }} valve(s)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center py-4">
                            <i class="bi bi-grid-3x3-gap text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">No plots configured yet</p>
                            <a href="{{ route('plots.create') }}" class="btn btn-sm btn-outline-primary mt-2">
                                <i class="bi bi-plus-lg"></i> Add Plot
                            </a>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Sensor Readings -->
            <div class="row mb-4">
                <div class="col-12 mb-3">
                    <h2 class="h4 fw-bold">Sensor Readings</h2>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-muted small mb-1">Temperature</h6>
                                    <h4 class="mb-0">{{ $latestReadings['temperature'] }}°C</h4>
                                </div>
                                <div class="icon-shape bg-primary bg-opacity-10 text-primary rounded-3">
                                    <i class="bi bi-thermometer-half fs-3"></i>
                                </div>
                            </div>
                            <div class="mt-3 small">
                                <span class="text-muted">Last updated: Just now</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-muted small mb-1">Humidity</h6>
                                    <h4 class="mb-0">{{ $latestReadings['humidity'] }}%</h4>
                                </div>
                                <div class="icon-shape bg-success bg-opacity-10 text-success rounded-3">
                                    <i class="bi bi-droplet-half fs-3"></i>
                                </div>
                            </div>
                            <div class="mt-3 small">
                                <span class="text-muted">Last updated: Just now</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-muted small mb-1">Soil Moisture</h6>
                                    <h4 class="mb-0">{{ $latestReadings['soil_moisture'] }}%</h4>
                                </div>
                                <div class="icon-shape bg-warning bg-opacity-10 text-warning rounded-3">
                                    <i class="bi bi-moisture fs-3"></i>
                                </div>
                            </div>
                            <div class="mt-3 small">
                                <span class="text-muted">Last updated: Just now</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 col-sm-6 col-xl-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-muted small mb-1">Water Flow</h6>
                                    <h4 class="mb-0">{{ $latestReadings['water_flow'] }} L/min</h4>
                                </div>
                                <div class="icon-shape bg-info bg-opacity-10 text-info rounded-3">
                                    <i class="bi bi-water fs-3"></i>
                                </div>
                            </div>
                            <div class="mt-3 small">
                                <span class="text-muted">Last updated: Just now</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pumps Status -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Pump Status</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Pump</th>
                                            <th>Status</th>
                                            <th>Last Run</th>
                                            <th>Uptime Today</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pumps as $pump)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0 me-3">
                                                            <div class="avatar-sm {{ $pump->status === 'running' ? 'bg-success' : 'bg-secondary' }} bg-opacity-10 rounded p-2">
                                                                <i class="bi {{ $pump->status === 'running' ? 'bi-play-fill text-success' : 'bi-pause-fill text-secondary' }} fs-5"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ $pump->name }}</h6>
                                                            <small class="text-muted">{{ $pump->location }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $pump->status === 'running' ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ ucfirst($pump->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $pump->last_run_at ? $pump->last_run_at->diffForHumans() : 'Never' }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-clock-history text-muted me-2"></i>
                                                        <span>{{ $pump->uptime_today }} min</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Valves Status -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Valves Status</h5>
                                <a href="{{ route('valves.index') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-list-ul me-1"></i> View All Valves
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Valve</th>
                                            <th>Type</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                            <th>Flow Rate</th>
                                            <th>Last Actuated</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($valves as $valve)
                                            @php
                                                $statusClass = [
                                                    'operational' => 'success',
                                                    'open' => 'success',
                                                    'closed' => 'secondary',
                                                    'error' => 'danger',
                                                    'maintenance' => 'warning',
                                                    'leaking' => 'danger',
                                                    'obstructed' => 'warning',
                                                ][$valve->status] ?? 'secondary';
                                                
                                                $statusText = ucfirst(str_replace('_', ' ', $valve->status));
                                                
                                                // Determine location name based on type
                                                $locationName = '';
                                                if ($valve->type === 'tank' && $valve->tank) {
                                                    $locationName = $valve->tank->name;
                                                } elseif ($valve->type === 'plot' && $valve->plot) {
                                                    $locationName = $valve->plot->name;
                                                } elseif ($valve->type === 'main') {
                                                    $locationName = 'Main Line';
                                                }
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-shrink-0 me-3">
                                                            <div class="avatar-sm bg-{{ $statusClass }} bg-opacity-10 rounded p-2">
                                                                <i class="bi bi-valve text-{{ $statusClass }} fs-5"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ $valve->name }}</h6>
                                                            <small class="text-muted">#{{ $valve->external_device_id ?? 'N/A' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-{{ $valve->type === 'main' ? 'primary' : ($valve->type === 'tank' ? 'info' : 'success') }}">
                                                        {{ ucfirst($valve->type) }}
                                                    </span>
                                                </td>
                                                <td>{{ $locationName ?: 'N/A' }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                                        {{ $statusText }}
                                                    </span>
                                                </td>
                                                <td>{{ $valve->flow_rate ? $valve->flow_rate . ' L/min' : 'N/A' }}</td>
                                                <td>{{ $valve->last_actuated ? $valve->last_actuated->diffForHumans() : 'Never' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="text-muted">No valves configured yet.</div>
                                                    <a href="{{ route('valves.create') }}" class="btn btn-sm btn-outline-primary mt-2">
                                                        <i class="bi bi-plus-circle me-1"></i> Add Valve
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @forelse($pumps as $pump)
                @empty
                    <div class="col-span-3 text-center py-8">
                        <p class="text-gray-500">No pumps configured. Add your first pump to get started.</p>
                    </div>
            @endforelse
        </div>
    </div>

    @push('styles')
        <style>
            .progress-bar {
                transition: width 0.5s ease-in-out;
            }
        </style>
    @endpush
</div>
@endsection

@push('scripts')
    <script>
        // Auto-refresh the page every 30 seconds
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
@endpush
