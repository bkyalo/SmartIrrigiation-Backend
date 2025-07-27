@extends('layouts.app')

@section('title', $valve->name)

@section('content')
<div class="container-fluid">
    <!-- Header with Back Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('valves.index') }}">Valves</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $valve->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ $valve->name }}</h1>
        </div>
        <div>
            <a href="{{ route('valves.edit', $valve) }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-pencil me-1"></i> Edit
            </a>
            <a href="{{ route('valves.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Valves
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Left Column: Valve Details -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Valve Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Name:</div>
                        <div class="col-sm-8">{{ $valve->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Type:</div>
                        <div class="col-sm-8">
                            @php
                                $typeBadges = [
                                    'tank' => ['badge' => 'bg-primary', 'text' => 'Tank Valve'],
                                    'plot' => ['badge' => 'bg-success', 'text' => 'Plot Valve'],
                                    'main' => ['badge' => 'bg-info', 'text' => 'Main Valve']
                                ];
                                $type = $typeBadges[$valve->type] ?? ['badge' => 'bg-secondary', 'text' => ucfirst($valve->type)];
                            @endphp
                            <span class="badge {{ $type['badge'] }}">{{ $type['text'] }}</span>
                        </div>
                    </div>
                    @if($valve->type === 'tank')
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Valve Direction:</div>
                        <div class="col-sm-8">
                            @if($valve->valve_direction)
                                <span class="badge bg-{{ $valve->valve_direction === 'inlet' ? 'info' : 'primary' }}">
                                    {{ ucfirst($valve->valve_direction) }} Valve
                                </span>
                            @else
                                <span class="text-muted">Not specified</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Location:</div>
                        <div class="col-sm-8">
                            @if($valve->tank)
                                <i class="bi bi-droplet me-1"></i> {{ $valve->tank->name }}
                            @elseif($valve->plot)
                                <i class="bi bi-flower1 me-1"></i> {{ $valve->plot->name }}
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Status:</div>
                        <div class="col-sm-8">
                            @php
                                $statusBadges = [
                                    'operational' => ['badge' => 'bg-success', 'text' => 'Operational'],
                                    'stuck_open' => ['badge' => 'bg-warning', 'text' => 'Stuck Open'],
                                    'stuck_closed' => ['badge' => 'bg-danger', 'text' => 'Stuck Closed'],
                                    'error' => ['badge' => 'bg-danger', 'text' => 'Error']
                                ];
                                $status = $statusBadges[$valve->status] ?? ['badge' => 'bg-secondary', 'text' => ucfirst($valve->status)];
                            @endphp
                            <span class="badge {{ $status['badge'] }}">{{ $status['text'] }}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Current State:</div>
                        <div class="col-sm-8">
                            @if($valve->status === 'operational')
                                <div class="d-flex align-items-center">
                                    <span id="valve-state-text" class="me-2">{{ $valve->is_open ? 'Open' : 'Closed' }}</span>
                                    <button type="button" 
                                            id="toggle-valve-btn" 
                                            class="btn btn-sm {{ $valve->is_open ? 'btn-danger' : 'btn-success' }}"
                                            data-valve-id="{{ $valve->id }}"
                                            data-is-open="{{ $valve->is_open ? '1' : '0' }}">
                                        {{ $valve->is_open ? 'Close Valve' : 'Open Valve' }}
                                        <span id="toggle-spinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                            @else
                                <span class="text-muted">N/A ({{ ucfirst(str_replace('_', ' ', $valve->status)) }})</span>
                            @endif
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Flow Rate:</div>
                        <div class="col-sm-8">{{ $valve->flow_rate }} L/min</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-bold">Last Actuated:</div>
                        <div class="col-sm-8">
                            {{ $valve->last_actuated ? $valve->last_actuated->diffForHumans() : 'Never' }}
                            @if($valve->last_actuated)
                                <small class="text-muted d-block">{{ $valve->last_actuated->format('M j, Y g:i A') }}</small>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4 fw-bold">Created:</div>
                        <div class="col-sm-8">
                            {{ $valve->created_at->diffForHumans() }}
                            <small class="text-muted d-block">{{ $valve->created_at->format('M j, Y g:i A') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Valve State History -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Valve State History</h5>
                </div>
                <div class="card-body">
                    @if($valve->stateHistories->isEmpty())
                        <div class="text-center py-4">
                            <i class="bi bi-activity fs-1 text-muted"></i>
                            <p class="mt-2 mb-0">No state history recorded</p>
                        </div>
                    @else
                        <div class="timeline">
                            @foreach($valve->stateHistories->sortByDesc('created_at')->take(5) as $history)
                                <div class="timeline-item">
                                    <div class="timeline-point {{ $history->is_open ? 'bg-success' : 'bg-secondary' }}"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">
                                                @if($history->is_open)
                                                    <i class="bi bi-valve-open text-success"></i> Valve Opened
                                                @else
                                                    <i class="bi bi-valve-closed text-secondary"></i> Valve Closed
                                                @endif
                                            </h6>
                                            <span class="badge bg-light text-dark">{{ $history->created_at->diffForHumans() }}</span>
                                        </div>
                                        <p class="text-muted small mb-1">
                                            Trigger: {{ ucfirst($history->trigger_source) }}
                                            @if($history->user)
                                                by {{ $history->user->name }}
                                            @endif
                                        </p>
                                        @if($history->notes)
                                            <p class="mb-0">{{ $history->notes }}</p>
                                        @endif
                                        @if($history->flow_rate)
                                            <p class="mb-0 small">Flow rate: {{ $history->flow_rate }} L/min</p>
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

<style>
.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
    padding-left: 1.5rem;
    border-left: 2px solid #e9ecef;
}

.timeline-item:last-child {
    border-left-color: transparent;
}

.timeline-point {
    position: absolute;
    left: -8px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #6c757d;
}

.timeline-content {
    padding: 0.5rem 0;
}

.timeline-content h6 {
    margin-bottom: 0.25rem;
}

.timeline-content p {
    margin-bottom: 0.25rem;
}
.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
    padding-left: 2rem;
    border-left: 1px solid #e9ecef;
}
.timeline-item:last-child {
    padding-bottom: 0;
    border-left-color: transparent;
}
.timeline-point {
    position: absolute;
    left: -0.75rem;
    top: 0;
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 50%;
    background-color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #e9ecef;
}
.timeline-point i {
    font-size: 0.75rem;
}
.timeline-content {
    padding-bottom: 1rem;
}
</style>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-valve-btn');
    const stateText = document.getElementById('valve-state-text');
    const spinner = document.getElementById('toggle-spinner');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            const valveId = this.dataset.valveId;
            const isOpen = this.dataset.isOpen === '1';
            const newState = !isOpen;
            
            // Show loading state
            this.disabled = true;
            spinner.classList.remove('d-none');
            
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Send AJAX request to toggle valve state
            fetch(`/valves/${valveId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    _method: 'PATCH'
                }),
                credentials: 'same-origin'
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Failed to update valve state');
                }
                
                if (data.success) {
                    // Update button and state text
                    const newIsOpen = data.is_open;
                    stateText.textContent = newIsOpen ? 'Open' : 'Closed';
                    toggleBtn.textContent = newIsOpen ? 'Close Valve ' : 'Open Valve ';
                    toggleBtn.className = `btn btn-sm ${newIsOpen ? 'btn-danger' : 'btn-success'}`;
                    toggleBtn.dataset.isOpen = newIsOpen ? '1' : '0';
                    
                    // Add spinner back to the button
                    toggleBtn.appendChild(spinner);
                    
                    // Show success message
                    showAlert('success', data.message);
                    
                    // Refresh the page after 1 second to show updated state history
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Error toggling valve:', error);
                showAlert('danger', error.message || 'Failed to update valve state');
            })
            .finally(() => {
                // Re-enable button and hide spinner
                toggleBtn.disabled = false;
                spinner.classList.add('d-none');
            });
        });
    }
    
    // Helper function to show alerts
    function showAlert(type, message) {
        // Remove any existing alerts
        const existingAlert = document.querySelector('.alert-dynamic');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-dynamic`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const container = document.querySelector('.container-fluid');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-dismiss after 3 seconds
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 3000);
    }
});
</script>
@endpush

@endsection
