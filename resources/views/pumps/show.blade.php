@extends('layouts.app')

@section('title', $pump->name)

@push('styles')
<style>
    .pump-stats-card { transition: all 0.3s ease; }
    .pump-stats-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; }
    .status-badge { font-size: 0.9rem; padding: 0.5rem 1rem; }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header with Breadcrumbs and Status -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('pumps.index') }}">Pumps</a></li>
                    <li class="breadcrumb-item active">{{ $pump->name }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <h1 class="h3 mb-0">{{ $pump->name }}</h1>
                @php
                    $statusClasses = [
                        'running' => ['success', 'play-circle'],
                        'stopped' => ['secondary', 'stop-circle'],
                        'error' => ['danger', 'exclamation-triangle']
                    ];
                    $statusClass = $statusClasses[$pump->status] ?? ['secondary', 'question-circle'];
                @endphp
                <span class="badge bg-{{ $statusClass[0] }}-subtle text-{{ $statusClass[0] }} status-badge">
                    <i class="bi bi-{{ $statusClass[1] }} me-1"></i>
                    {{ ucfirst($pump->status) }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('pumps.toggle-status', $pump) }}" method="POST" class="d-inline">
                @csrf @method('PATCH')
                <button type="submit" class="btn @if($pump->status === 'running') btn-outline-danger @else btn-outline-success @endif">
                    <i class="bi bi-power me-1"></i>
                    {{ $pump->status === 'running' ? 'Stop' : 'Start' }} Pump
                </button>
            </form>
            <a href="{{ route('pumps.edit', $pump) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <!-- Main Content -->
        <div class="col-12 col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Pump Details</h5>
                </div>
                <div class="card-body">
                    @if($pump->notes)
                        <div class="alert alert-light border mb-4">
                            <p class="mb-0">{{ $pump->notes }}</p>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-5">Pump ID</dt>
                                <dd class="col-7">
                                    @if($pump->external_device_id)
                                        <code>{{ $pump->external_device_id }}</code>
                                        <button type="button" class="btn btn-sm btn-link p-0 ms-2" onclick="navigator.clipboard.writeText('{{ $pump->external_device_id }}')">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </dd>

                                <dt class="col-5">Status</dt>
                                <dd class="col-7">
                                    <span class="badge bg-{{ $statusClass[0] }}-subtle text-{{ $statusClass[0] }}">
                                        <i class="bi bi-{{ $statusClass[1] }} me-1"></i>
                                        {{ ucfirst($pump->status) }}
                                    </span>
                                </dd>

                                <dt class="col-5">Flow Rate</dt>
                                <dd class="col-7">{{ number_format($pump->flow_rate, 2) }} L/min</dd>

                                <dt class="col-5">Power</dt>
                                <dd class="col-7">{{ number_format($pump->power_consumption, 2) }} W</dd>

                                <dt class="col-5">Total Runtime</dt>
                                <dd class="col-7">
                                    {{ floor($totalRuntime / 60) }}h {{ $totalRuntime % 60 }}m
                                    @if($pump->status === 'running')
                                        <span class="text-success small ms-2">
                                            <i class="bi bi-clock-history"></i> Running
                                            @if($pump->currentSession())
                                                ({{ now()->diffInMinutes($pump->currentSession()->started_at) }}m)
                                            @endif
                                        </span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row">
                                <dt class="col-5">Error Code</dt>
                                <dd class="col-7">
                                    @if($pump->error_code)
                                        <span class="badge bg-danger-subtle text-danger">
                                            {{ $pump->error_code }}
                                        </span>
                                    @else
                                        None
                                    @endif
                                </dd>

                                <dt class="col-5">Created</dt>
                                <dd class="col-7">{{ $pump->created_at->format('M j, Y') }}</dd>

                                <dt class="col-5">Last Updated</dt>
                                <dd class="col-7">{{ $pump->updated_at->diffForHumans() }}</dd>
                            </dl>
                        </div>
                    </div>
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
                            <div class="card pump-stats-card bg-light">
                                <div class="card-body text-center">
                                    <div class="avatar-sm bg-{{ $statusClass[0] }}-subtle text-{{ $statusClass[0] }} rounded-circle mx-auto mb-2">
                                        <i class="bi bi-{{ $statusClass[1] }} fs-4"></i>
                                    </div>
                                    <h4 class="mb-0">{{ ucfirst($pump->status) }}</h4>
                                    <p class="text-muted small mb-0">Status</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card pump-stats-card bg-light">
                                <div class="card-body text-center">
                                    <div class="avatar-sm bg-primary-subtle text-primary rounded-circle mx-auto mb-2">
                                        <i class="bi bi-speedometer2 fs-4"></i>
                                    </div>
                                    <h4 class="mb-0">{{ $pump->flow_rate }}</h4>
                                    <p class="text-muted small mb-0">L/min</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card pump-stats-card bg-light">
                                <div class="card-body text-center">
                                    <div class="avatar-sm bg-info-subtle text-info rounded-circle mx-auto mb-2">
                                        <i class="bi bi-clock-history fs-4"></i>
                                    </div>
                                    <h4 class="mb-0">{{ floor($todayRuntime / 60) }}<small class="fs-6">h</small> {{ $todayRuntime % 60 }}<small class="fs-6">m</small></h4>
                                    <p class="text-muted small mb-0">Today's Runtime</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Sessions -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Sessions</h5>
                </div>
                <div class="card-body p-0">
                    @if($pump->sessions->isEmpty())
                        <div class="text-center p-4 text-muted">
                            <i class="bi bi-clock-history fs-1"></i>
                            <p class="mt-2 mb-0">No sessions recorded yet</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($pump->sessions as $session)
                                <div class="list-group-item {{ $loop->first ? 'border-top-0' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium">
                                                {{ $session->started_at->format('M j, Y g:i A') }}
                                                @if($session->stopped_at)
                                                    - {{ $session->stopped_at->format('g:i A') }}
                                                @else
                                                    <span class="badge bg-success-subtle text-success ms-2">
                                                        <i class="bi bi-play-fill"></i> Running
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="text-muted small">
                                                @if($session->stopped_at)
                                                    {{ floor($session->duration_seconds / 60) }}m {{ $session->duration_seconds % 60 }}s
                                                @else
                                                    {{ now()->diffInMinutes($session->started_at) }}m so far
                                                @endif
                                            </div>
                                        </div>
                                        @if($session->stopped_at)
                                            <span class="badge bg-light text-dark">
                                                {{ number_format($session->duration_seconds / 60, 1) }} min
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
