@extends('layouts.app')

@section('title', 'Water Pumps')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Water Pumps</h1>
        <a href="{{ route('pumps.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add New Pump
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($pumps->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-tools text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3">No water pumps found</h5>
                <p class="text-muted">Get started by adding your first water pump</p>
                <a href="{{ route('pumps.create') }}" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-circle me-1"></i> Add Pump
                </a>
            </div>
        </div>
    @else
        <div class="row">
            @foreach ($pumps as $pump)
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">
                                        {{ $pump->name }}
                                        @if($pump->external_device_id)
                                            <small class="text-muted ms-2">
                                                <code class="text-dark">{{ $pump->external_device_id }}</code>
                                            </small>
                                        @endif
                                    </h5>
                                    <div class="d-flex align-items-center">
                                        @php
                                            $statusClasses = [
                                                'running' => ['success', 'play-circle'],
                                                'stopped' => ['secondary', 'stop-circle'],
                                                'error' => ['danger', 'exclamation-triangle']
                                            ];
                                            $statusClass = $statusClasses[$pump->status] ?? ['secondary', 'question-circle'];
                                        @endphp
                                        <span class="badge bg-{{ $statusClass[0] }}-subtle text-{{ $statusClass[0] }} me-2">
                                            <i class="bi bi-{{ $statusClass[1] }} me-1"></i>
                                            {{ ucfirst($pump->status) }}
                                        </span>
                                        @if($pump->error_code)
                                            <span class="badge bg-danger-subtle text-danger">
                                                Error: {{ $pump->error_code }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('pumps.show', $pump) }}">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('pumps.edit', $pump) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('pumps.toggle-status', $pump) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-power me-2"></i>
                                                    {{ $pump->status === 'running' ? 'Stop' : 'Start' }} Pump
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('pumps.destroy', $pump) }}" method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this pump? This action cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="row small g-3 mb-3">
                                <div class="col-6">
                                    <div class="text-muted">Flow Rate</div>
                                    <div class="fw-medium">{{ $pump->flow_rate }} L/min</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted">Power</div>
                                    <div class="fw-medium">{{ $pump->power_consumption }} W</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted">Runtime</div>
                                    <div class="fw-medium">
                                        {{ $pump->total_runtime ? round($pump->total_runtime / 60, 1) . ' hours' : 'N/A' }}
                                    </div>
                                </div>

                            </div>
                            
                            @if($pump->notes)
                                <div class="alert alert-light border small p-2 mb-0">
                                    <i class="bi bi-info-circle me-1"></i> {{ Str::limit($pump->notes, 100) }}
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-transparent
                            @if($pump->status === 'running') bg-success-subtle
                            @elseif($pump->status === 'error') bg-danger-subtle
                            @else bg-light @endif">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">
                                    @if($pump->status === 'running')
                                        <i class="bi bi-play-circle-fill text-success me-1"></i> Running
                                    @elseif($pump->status === 'error')
                                        <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i> Error

                                    @else
                                        <i class="bi bi-stop-circle-fill text-secondary me-1"></i> Stopped
                                    @endif
                                </span>
                                <form action="{{ route('pumps.toggle-status', $pump) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm @if($pump->status === 'running') btn-outline-danger @else btn-outline-success @endif">
                                        <i class="bi bi-power me-1"></i>
                                        {{ $pump->status === 'running' ? 'Stop' : 'Start' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $pumps->links() }}
        </div>
    @endif
</div>
@endsection
