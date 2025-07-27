@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('plots.index') }}">Plots</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $plot->name }}</li>
                </ol>
            </nav>
            <h1 class="mb-0">{{ $plot->name }}</h1>
            <p class="text-muted">{{ $plot->crop_type ?? 'No crop type specified' }}</p>
        </div>
        <div>
            <a href="{{ route('plots.edit', $plot) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit Plot
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Plot Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Plot Name</h6>
                            <p>{{ $plot->name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Status</h6>
                            <span class="badge bg-{{ $plot->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($plot->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Crop Type</h6>
                            <p>{{ $plot->crop_type ?? 'Not specified' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Soil Type</h6>
                            <p>{{ $plot->soil_type ?? 'Not specified' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Area</h6>
                            <p>{{ number_format($plot->area, 2) }} m²</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Moisture Threshold</h6>
                            <p>{{ $plot->moisture_threshold }}%</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Irrigation Duration</h6>
                            <p>{{ $plot->irrigation_duration }} minutes</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">Location</h6>
                            @if($plot->latitude && $plot->longitude)
                                <p>
                                    <a href="https://www.google.com/maps?q={{ $plot->latitude }},{{ $plot->longitude }}" 
                                       target="_blank" class="text-decoration-none">
                                        <i class="bi bi-geo-alt"></i> View on Map
                                    </a>
                                </p>
                            @else
                                <p>Location not specified</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Valve Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Valve</h5>
                    @if($plot->valve)
                        <a href="{{ route('valves.edit', $plot->valve) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit Valve
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if($plot->valve)
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Valve Name</h6>
                                <p>{{ $plot->valve->name }}</p>
                                
                                <h6 class="text-muted mt-3">Type</h6>
                                <p>{{ ucfirst($plot->valve->type) }}</p>
                                
                                <h6 class="text-muted mt-3">Flow Rate</h6>
                                <p>{{ $plot->valve->flow_rate }} L/min</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Status</h6>
                                <p>
                                    <span class="badge bg-{{ $plot->valve->is_open ? 'success' : 'secondary' }}">
                                        {{ $plot->valve->is_open ? 'Open' : 'Closed' }}
                                    </span>
                                </p>
                                
                                <h6 class="text-muted mt-3">Last Actuated</h6>
                                <p>{{ $plot->valve->last_actuated ? $plot->valve->last_actuated->diffForHumans() : 'Never' }}</p>
                                
                                <div class="mt-4">
                                    <form action="{{ route('valves.destroy', $plot->valve) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('Are you sure you want to remove this valve from the plot?')">
                                            <i class="bi bi-trash"></i> Remove Valve
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="bi bi-droplet text-muted" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-3">No valve assigned to this plot</p>
                            <a href="{{ route('valves.create', ['plot_id' => $plot->id]) }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg"></i> Add Valve
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Irrigation Controls Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Irrigation Controls</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Manual Irrigation -->
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bi bi-droplet-fill text-primary" style="font-size: 2rem;"></i>
                                    </div>
                                    <h5 class="card-title">Manual Irrigation</h5>
                                    <p class="card-text text-muted small">Start irrigation immediately for this plot</p>
                                    <div class="input-group mb-3">
                                        <input type="number" id="manualDuration" class="form-control" 
                                               value="{{ $plot->irrigation_duration }}" min="1" max="120">
                                        <span class="input-group-text">minutes</span>
                                    </div>
                                    <button class="btn btn-primary w-100" onclick="startManualIrrigation({{ $plot->id }})">
                                        <i class="bi bi-play-fill"></i> Start Now
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Schedule One-Time Irrigation -->
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bi bi-calendar-event text-success" style="font-size: 2rem;"></i>
                                    </div>
                                    <h5 class="card-title">Schedule Irrigation</h5>
                                    <p class="card-text text-muted small">Set up a one-time irrigation event</p>
                                    <div class="mb-3">
                                        <input type="datetime-local" class="form-control mb-2" id="scheduleDateTime">
                                        <div class="input-group">
                                            <input type="number" id="scheduleDuration" class="form-control" 
                                                   value="{{ $plot->irrigation_duration }}" min="1" max="120">
                                            <span class="input-group-text">minutes</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-success w-100" onclick="scheduleOneTimeIrrigation({{ $plot->id }})">
                                        <i class="bi bi-calendar-plus"></i> Schedule
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recurring Schedule -->
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="bi bi-arrow-repeat text-info" style="font-size: 2rem;"></i>
                                    </div>
                                    <h5 class="card-title">Recurring Schedule</h5>
                                    <p class="card-text text-muted small">Set up automatic irrigation schedule</p>
                                    <div class="mb-3">
                                        <select class="form-select mb-2" id="recurrencePattern">
                                            <option value="DAILY">Daily</option>
                                            <option value="WEEKLY">Weekly</option>
                                            <option value="MONDAY,WEDNESDAY,FRIDAY">Mon, Wed, Fri</option>
                                            <option value="TUESDAY,THURSDAY">Tue, Thu</option>
                                            <option value="WEEKEND">Weekend</option>
                                        </select>
                                        <div class="input-group mb-2">
                                            <input type="time" class="form-control" id="recurrenceTime" value="06:00">
                                        </div>
                                        <div class="input-group">
                                            <input type="number" id="recurrenceDuration" class="form-control" 
                                                   value="{{ $plot->irrigation_duration }}" min="1" max="120">
                                            <span class="input-group-text">minutes</span>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-info w-100" onclick="setupRecurringIrrigation({{ $plot->id }})">
                                        <i class="bi bi-calendar2-plus"></i> Create Schedule
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Irrigation Events -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Upcoming Irrigation</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshIrrigationEvents()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="upcomingIrrigationEvents">
                        <div class="list-group-item text-center text-muted py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div> Loading...
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-md-4">
            <!-- Status Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status Overview</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 bg-{{ $plot->status === 'active' ? 'success' : 'secondary' }} bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-check2-circle fs-4 text-{{ $plot->status === 'active' ? 'success' : 'secondary' }}"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Plot Status</h6>
                            <p class="mb-0">{{ ucfirst($plot->status) }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-droplet fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Valve</h6>
                            <p class="mb-0">{{ $plot->valve ? $plot->valve->name : 'Not assigned' }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-info bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-moisture fs-4 text-info"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Moisture Threshold</h6>
                            <p class="mb-0">{{ $plot->moisture_threshold }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    @if($plot->irrigationEvents->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($plot->irrigationEvents->take(5) as $event)
                                <li class="list-group-item px-0">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            <i class="bi bi-droplet-half text-primary"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Irrigation {{ ucfirst($event->type) }}</h6>
                                            <p class="mb-0 small text-muted">
                                                {{ $event->created_at->diffForHumans() }}
                                            </p>
                                            @if($event->duration)
                                                <span class="badge bg-light text-dark">
                                                    {{ $event->duration }} minutes
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if($plot->irrigationEvents->count() > 5)
                            <div class="text-center mt-3">
                                <a href="#" class="btn btn-sm btn-outline-primary">View All Activity</a>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-3">
                            <i class="bi bi-info-circle text-muted d-block mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">No recent activity</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
// CSRF Token for AJAX requests
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Show toast notification
function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 show`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-remove the toast after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Format date for display
function formatDateTime(dateString) {
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Start manual irrigation
async function startManualIrrigation(plotId) {
    // Convert duration to number to prevent Carbon error
    const duration = parseInt(document.getElementById('manualDuration').value, 10);
    
    try {
        const response = await fetch(`/api/v1/irrigation/plots/${plotId}/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            },
            body: JSON.stringify({
                duration_minutes: duration
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Irrigation started successfully!');
            refreshIrrigationEvents();
        } else {
            throw new Error(data.message || 'Failed to start irrigation');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', error.message || 'Failed to start irrigation');
    }
}

// Schedule one-time irrigation
async function scheduleOneTimeIrrigation(plotId) {
    const dateTime = document.getElementById('scheduleDateTime').value;
    // Convert duration to number to prevent Carbon error
    const duration = parseInt(document.getElementById('scheduleDuration').value, 10);
    
    if (!dateTime) {
        showAlert('warning', 'Please select a date and time');
        return;
    }
    
    try {
        const response = await fetch(`/api/v1/irrigation/plots/${plotId}/schedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            },
            body: JSON.stringify({
                start_time: dateTime,
                duration_minutes: duration
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Irrigation scheduled successfully!');
            document.getElementById('scheduleDateTime').value = '';
            refreshIrrigationEvents();
        } else {
            throw new Error(data.message || 'Failed to schedule irrigation');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', error.message || 'Failed to schedule irrigation');
    }
}

// Set up recurring irrigation
async function setupRecurringIrrigation(plotId) {
    const pattern = document.getElementById('recurrencePattern').value;
    const time = document.getElementById('recurrenceTime').value;
    // Convert duration to number to prevent Carbon error
    const duration = parseInt(document.getElementById('recurrenceDuration').value, 10);
    
    // Combine current date with selected time
    const [hours, minutes] = time.split(':');
    const startDate = new Date();
    startDate.setHours(hours, minutes, 0, 0);
    
    try {
        const response = await fetch(`/api/v1/irrigation/plots/${plotId}/schedule-recurring`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            },
            body: JSON.stringify({
                recurrence_rule: pattern,
                start_time: startDate.toISOString(),
                duration_minutes: duration
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Recurring irrigation scheduled successfully!');
            refreshIrrigationEvents();
        } else {
            throw new Error(data.message || 'Failed to schedule recurring irrigation');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', error.message || 'Failed to schedule recurring irrigation');
    }
}

// Cancel a scheduled irrigation event
async function cancelIrrigationEvent(eventId) {
    if (!confirm('Are you sure you want to cancel this scheduled irrigation event?')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/v1/irrigation-events/${eventId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('success', 'Scheduled irrigation cancelled successfully');
            refreshIrrigationEvents();
            refreshPlotStatus();
        } else {
            throw new Error(data.message || 'Failed to cancel scheduled irrigation');
        }
    } catch (error) {
        console.error('Error cancelling scheduled irrigation:', error);
        showToast('error', error.message || 'Failed to cancel scheduled irrigation');
    }
}

// Stop an in-progress irrigation event
async function stopIrrigationEvent(eventId) {
    if (!confirm('Are you sure you want to stop this irrigation event?')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/v1/irrigation-events/${eventId}/stop`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('success', 'Irrigation stopped successfully');
            refreshIrrigationEvents();
            refreshPlotStatus();
        } else {
            throw new Error(data.message || 'Failed to stop irrigation');
        }
    } catch (error) {
        console.error('Error stopping irrigation:', error);
        showToast('error', error.message || 'Failed to stop irrigation');
    }
}

// Refresh the list of upcoming irrigation events
async function refreshIrrigationEvents() {
    const container = document.getElementById('upcomingIrrigationEvents');
    if (!container) return;
    
    container.innerHTML = `
        <div class="list-group-item text-center text-muted py-3">
            <div class="spinner-border spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div> Loading...
        </div>`;
    
    try {
        const response = await fetch(`/api/v1/irrigation/plots/{{ $plot->id }}/upcoming`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.data.length === 0) {
                container.innerHTML = `
                    <div class="list-group-item text-center text-muted py-4">
                        <i class="bi bi-calendar-x fs-4"></i>
                        <p class="mt-2 mb-0">No upcoming irrigation events</p>
                    </div>`;
            } else {
                container.innerHTML = data.data.map(event => `
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">${formatDateTime(event.timing.scheduled.start)}</h6>
                                <small class="text-muted">
                                    ${event.timing.scheduled.duration_minutes} minutes
                                    ${event.is_recurring ? '<span class="badge bg-info ms-2">Recurring</span>' : ''}
                                    ${event.status === 'in_progress' ? '<span class="badge bg-warning ms-2">In Progress</span>' : ''}
                                </small>
                            </div>
                            <button class="btn btn-sm ${event.status === 'in_progress' ? 'btn-warning' : 'btn-outline-danger'}" 
                                    onclick="${event.status === 'in_progress' ? 'stopIrrigationEvent' : 'cancelIrrigationEvent'}(${event.id})"
                                    title="${event.status === 'in_progress' ? 'Stop' : 'Cancel'} this event">
                                <i class="bi ${event.status === 'in_progress' ? 'bi-stop-fill' : 'bi-x-lg'}"></i>
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        } else {
            throw new Error(data.message || 'Failed to load irrigation events');
        }
    } catch (error) {
        console.error('Error:', error);
        container.innerHTML = `
            <div class="list-group-item text-center text-danger py-3">
                <i class="bi bi-exclamation-triangle"></i>
                <p class="mb-0">Failed to load irrigation events</p>
            </div>`;
    }
}

// Show alert message
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
    
    // Create a temporary container to parse the HTML
    const temp = document.createElement('div');
    temp.innerHTML = alertHtml;
    const alertElement = temp.firstElementChild;
    
    // Add to the alerts container or create one
    let alertsContainer = document.getElementById('alerts-container');
    if (!alertsContainer) {
        alertsContainer = document.createElement('div');
        alertsContainer.id = 'alerts-container';
        document.querySelector('.container').prepend(alertsContainer);
    }
    
    // Add the alert and auto-remove after 5 seconds
    alertsContainer.appendChild(alertElement);
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertElement);
        bsAlert.close();
    }, 5000);
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Set default datetime input to now + 1 hour
    const now = new Date();
    now.setHours(now.getHours() + 1);
    now.setMinutes(0);
    now.setSeconds(0);
    
    const dateTimeInput = document.getElementById('scheduleDateTime');
    if (dateTimeInput) {
        dateTimeInput.value = now.toISOString().slice(0, 16);
    }
    
    // Load upcoming irrigation events
    refreshIrrigationEvents();
    
    // Refresh events every 30 seconds
    setInterval(refreshIrrigationEvents, 30000);
});
</script>
@endpush

@endsection
