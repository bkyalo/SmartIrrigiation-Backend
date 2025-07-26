@extends('layouts.app')

@section('title', $sensor->name)

@push('styles')
<style>
    .sensor-stats-card { transition: all 0.3s ease; }
    .sensor-stats-card:hover { transform: translateY(-3px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.05) !important; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header with Breadcrumbs -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('sensors.index') }}">Sensors</a></li>
                    <li class="breadcrumb-item active">{{ $sensor->name }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <h1 class="h3 mb-0">{{ $sensor->name }}</h1>
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
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sensors.edit', $sensor) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-12 col-xl-8">
            <!-- Sensor Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Sensor Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-5">Sensor ID</dt>
                                <dd class="col-7">
                                    <code>{{ $sensor->id }}</code>
                                </dd>

                                <dt class="col-5">Device ID</dt>
                                <dd class="col-7">
                                    <code>{{ $sensor->external_device_id ?? 'Not set' }}</code>
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="navigator.clipboard.writeText('{{ $sensor->external_device_id }}')">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </dd>

                                <dt class="col-5">Type</dt>
                                <dd class="col-7">
                                    <span class="badge bg-primary-subtle text-primary text-capitalize">
                                        {{ str_replace('_', ' ', $sensor->type) }}
                                    </span>
                                </dd>

                                <dt class="col-5">Location</dt>
                                <dd class="col-7">
                                    @if($sensor->location)
                                        {{ $sensor->location->name }} ({{ ucfirst($sensor->location_type) }})
                                    @else
                                        <span class="text-muted">Not assigned</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-5">Status</dt>
                                <dd class="col-7">
                                    <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                        {{ ucfirst($sensor->status) }}
                                    </span>
                                </dd>

                                <dt class="col-5">Last Reading</dt>
                                <dd class="col-7">
                                    @if($sensor->last_reading_at)
                                        {{ $sensor->last_reading_at->diffForHumans() }}
                                    @else
                                        <span class="text-muted">No readings yet</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sensor Readings -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Readings</h5>
                </div>
                <div class="card-body p-0">
                    @if($readings->isEmpty())
                        <div class="text-center p-5 text-muted">
                            <i class="bi bi-database-slash fs-1"></i>
                            <p class="mt-3 mb-0">No readings available</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($readings as $reading)
                                        <tr>
                                            <td>{{ $reading->recorded_at->format('M j, Y H:i:s') }}</td>
                                            <td>
                                                {{ number_format($reading->value, 2) }} 
                                                <span class="text-muted">{{ $reading->unit }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($readings->hasPages())
                            <div class="card-footer bg-transparent">
                                {{ $readings->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
