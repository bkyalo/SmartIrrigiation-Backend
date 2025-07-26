@extends('layouts.app')

@section('title', 'My Water Tanks')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">My Water Tanks</h1>
        <a href="{{ route('tanks.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add New Tank
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($tanks->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-droplet text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3">No water tanks found</h5>
                <p class="text-muted">Get started by adding your first water tank</p>
                <a href="{{ route('tanks.create') }}" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-circle me-1"></i> Add Tank
                </a>
            </div>
        </div>
    @else
        <div class="row">
            @foreach ($tanks as $tank)
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title mb-1">{{ $tank->name }}</h5>
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-geo-alt me-1"></i> {{ $tank->location ?? 'No location set' }}
                                    </p>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('tanks.show', $tank) }}">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('tanks.edit', $tank) }}">
                                                <i class="bi bi-pencil me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('tanks.destroy', $tank) }}" method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this tank? This action cannot be undone.');">
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
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Water Level</span>
                                    <span>{{ $tank->water_level ?? 0 }}%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: {{ $tank->water_level ?? 0 }}%" 
                                         aria-valuenow="{{ $tank->water_level ?? 0 }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row small">
                                <div class="col-6">
                                    <div class="text-muted">Capacity</div>
                                    <div class="fw-medium">{{ number_format($tank->capacity) }}L</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted">Last Updated</div>
                                    <div class="fw-medium">
                                        {{ $tank->updated_at ? $tank->updated_at->diffForHumans() : 'Never' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="{{ route('tanks.show', $tank) }}" class="btn btn-sm btn-outline-primary w-100">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $tanks->links() }}
        </div>
    @endif
</div>
@endsection
