@extends('layouts.app')

@section('title', 'Sensors')

@section('content')
<div class="container-fluid">
    <!-- Header with Create Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Sensors</h1>
            <p class="text-muted mb-0">Manage your irrigation system sensors</p>
        </div>
        <a href="{{ route('sensors.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Sensor
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Sensors Table -->
    <div class="card">
        <div class="card-body p-0">
            @if($sensors->isEmpty())
                <div class="text-center p-5">
                    <i class="bi bi-thermometer-sun fs-1 text-muted"></i>
                    <p class="h5 mt-3">No sensors found</p>
                    <p class="text-muted">Get started by adding your first sensor</p>
                    <a href="{{ route('sensors.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Sensor
                    </a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Last Reading</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sensors as $sensor)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $icon = match($sensor->type) {
                                                    'water_level' => 'droplet',
                                                    'soil_moisture' => 'moisture',
                                                    'temperature' => 'thermometer-half',
                                                    'humidity' => 'cloud-rain',
                                                    'flow' => 'water',
                                                    'pressure' => 'speedometer2',
                                                    'ph' => 'droplet-half',
                                                    'ec' => 'lightning-charge',
                                                    'light' => 'brightness-high',
                                                    default => 'thermometer'
                                                };
                                                
                                                $iconColor = match($sensor->status) {
                                                    'active' => 'text-success',
                                                    'inactive' => 'text-secondary',
                                                    'error' => 'text-danger',
                                                    default => 'text-primary'
                                                };
                                            @endphp
                                            <div class="avatar-sm bg-{{ $iconColor }} bg-opacity-10 rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <i class="bi bi-{{ $icon }} {{ $iconColor }}"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $sensor->name }}</h6>
                                                <small class="text-muted">{{ $sensor->external_id ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $typeClass = match($sensor->type) {
                                                'water_level' => 'bg-primary',
                                                'soil_moisture' => 'bg-success',
                                                'temperature' => 'bg-danger',
                                                'humidity' => 'bg-info',
                                                'flow' => 'bg-primary',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $typeClass }}-subtle text-{{ $typeClass }} text-capitalize">
                                            {{ str_replace('_', ' ', $sensor->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($sensor->location)
                                            <a href="{{ route($sensor->location_type . 's.show', $sensor->location) }}" class="text-decoration-none">
                                                {{ $sensor->location->name }}
                                            </a>
                                        @else
                                            <span class="text-muted">Not assigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($sensor->status) {
                                                'active' => 'success',
                                                'inactive' => 'secondary',
                                                'error' => 'danger',
                                                default => 'secondary'
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                            {{ ucfirst($sensor->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($sensor->last_reading_at)
                                            {{ $sensor->last_reading_at->diffForHumans() }}
                                        @else
                                            <span class="text-muted">No readings</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('sensors.show', $sensor) }}">
                                                        <i class="bi bi-eye me-2"></i>View Details
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('sensors.edit', $sensor) }}">
                                                        <i class="bi bi-pencil me-2"></i>Edit
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('sensors.destroy', $sensor) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" 
                                                            onclick="return confirm('Are you sure you want to delete this sensor? This action cannot be undone.')">
                                                            <i class="bi bi-trash me-2"></i>Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                @if($sensors->hasPages())
                    <div class="card-footer bg-transparent">
                        {{ $sensors->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Add any JavaScript for the sensors index page here
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
