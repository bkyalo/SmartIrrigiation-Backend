@extends('layouts.app')

@section('title', 'Valves')

@section('content')
<div class="container-fluid">
    <!-- Header with Create Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Valves</h1>
            <p class="text-muted mb-0">Manage your irrigation system valves</p>
        </div>
        <a href="{{ route('valves.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Valve
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Valves Table -->
    <div class="card">
        <div class="card-body p-0">
            @if($valves->isEmpty())
                <div class="text-center p-5">
                    <i class="bi bi-valve fs-1 text-muted"></i>
                    <p class="h5 mt-3">No valves found</p>
                    <p class="text-muted">Get started by adding your first valve</p>
                    <a href="{{ route('valves.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add Valve
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
                                <th>Direction</th>
                                <th>Status</th>
                                <th>State</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($valves as $valve)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-valve-{{ $valve->is_open ? 'open' : 'closed' }} text-{{ $valve->is_open ? 'success' : 'secondary' }} me-2"></i>
                                            <a href="{{ route('valves.show', $valve) }}" class="text-decoration-none">
                                                {{ $valve->name }}
                                            </a>
                                        </div>
                                    </td>
                                    <td>{{ ucfirst($valve->type) }} Valve</td>
                                    <td>
                                        @if($valve->tank)
                                            <i class="bi bi-droplet me-1"></i> {{ $valve->tank->name }}
                                        @elseif($valve->plot)
                                            <i class="bi bi-flower1 me-1"></i> {{ $valve->plot->name }}
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($valve->type === 'tank' && $valve->valve_direction)
                                            <span class="badge bg-{{ $valve->valve_direction === 'inlet' ? 'info' : 'primary' }}">
                                                {{ ucfirst($valve->valve_direction) }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusBadges = [
                                                'operational' => 'bg-success',
                                                'stuck_open' => 'bg-warning',
                                                'stuck_closed' => 'bg-danger',
                                                'error' => 'bg-danger'
                                            ];
                                            $statusClass = $statusBadges[$valve->status] ?? 'bg-secondary';
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $valve->status)) }}</span>
                                    </td>
                                    <td>
                                        @if($valve->status === 'operational')
                                            <div class="d-flex align-items-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" 
                                                            class="btn btn-sm {{ $valve->is_open ? 'btn-outline-secondary' : 'btn-success' }} valve-action"
                                                            data-action="close"
                                                            data-valve-id="{{ $valve->id }}"
                                                            {{ $valve->is_open ? '' : 'disabled' }}>
                                                        <i class="bi bi-x-circle"></i> Close
                                                    </button>
                                                    <button type="button" 
                                                            class="btn btn-sm {{ $valve->is_open ? 'btn-danger' : 'btn-outline-secondary' }} valve-action"
                                                            data-action="open"
                                                            data-valve-id="{{ $valve->id }}"
                                                            {{ $valve->is_open ? 'disabled' : '' }}>
                                                        <i class="bi bi-play-circle"></i> Open
                                                    </button>
                                                </div>
                                                <div class="form-check form-switch ms-3 d-none d-md-block">
                                                    <input class="form-check-input valve-toggle" 
                                                           type="checkbox" 
                                                           data-valve-id="{{ $valve->id }}"
                                                           {{ $valve->is_open ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('valves.show', $valve) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('valves.edit', $valve) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('valves.destroy', $valve) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this valve?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                @if($valves->hasPages())
                    <div class="card-footer bg-transparent">
                        {{ $valves->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle valve action buttons (Open/Close)
    document.querySelectorAll('.valve-action').forEach(button => {
        button.addEventListener('click', function() {
            const valveId = this.dataset.valveId;
            const action = this.dataset.action; // 'open' or 'close'
            const row = this.closest('tr');
            const buttons = row.querySelectorAll('.valve-action');
            const toggle = row.querySelector('.valve-toggle');
            
            // Disable all buttons during request
            buttons.forEach(btn => btn.disabled = true);
            
            // Show loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Send AJAX request to update valve state
            fetch(`/valves/${valveId}/${action}`, {
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
                
                // Update UI
                const isOpen = action === 'open';
                
                // Update buttons
                buttons.forEach(btn => {
                    if (btn.dataset.action === action) {
                        btn.disabled = true;
                        btn.classList.remove(isOpen ? 'btn-outline-secondary' : 'btn-danger');
                        btn.classList.add(isOpen ? 'btn-danger' : 'btn-outline-secondary');
                    } else {
                        btn.disabled = false;
                        btn.classList.remove(isOpen ? 'btn-success' : 'btn-outline-secondary');
                        btn.classList.add(isOpen ? 'btn-outline-secondary' : 'btn-success');
                    }
                });
                
                // Update toggle if it exists
                if (toggle) {
                    toggle.checked = isOpen;
                }
                
                // Show success message
                showAlert('success', data.message || 'Valve state updated successfully');
            })
            .catch(error => {
                console.error('Error updating valve state:', error);
                showAlert('danger', error.message || 'Failed to update valve state');
            })
            .finally(() => {
                // Re-enable buttons and restore text
                buttons.forEach(btn => {
                    btn.disabled = false;
                    if (btn.dataset.action === action) {
                        btn.innerHTML = originalText;
                    }
                });
            });
        });
    });
    
    // Handle valve toggle switches (kept for backward compatibility)
    document.querySelectorAll('.valve-toggle').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const valveId = this.dataset.valveId;
            const isOpen = this.checked;
            const row = this.closest('tr');
            const icon = row.querySelector('.bi-valve-open, .bi-valve-closed');
            // The state text is now in a different location, so we'll update the button states instead
            const actionButtons = row.querySelectorAll('.valve-action');
            
            // Show loading state
            this.disabled = true;
            
            // Get CSRF token from meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Log the request details
            console.log('Sending toggle request to:', `/valves/${valveId}/toggle`);
            console.log('CSRF Token:', csrfToken);
            
            if (!csrfToken) {
                console.error('CSRF token not found');
                throw new Error('CSRF token not found. Please refresh the page and try again.');
            }
            
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
                credentials: 'same-origin' // Ensure cookies are sent with the request
            })
            .then(async response => {
                console.log('Response status:', response.status, response.statusText);
                
                // Get the response text for debugging
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                if (!response.ok) {
                    let errorMessage = `HTTP error! status: ${response.status}`;
                    try {
                        const errorData = JSON.parse(responseText);
                        errorMessage = errorData.message || errorMessage;
                    } catch (e) {
                        errorMessage = responseText || errorMessage;
                    }
                    throw new Error(errorMessage);
                }
                
                return JSON.parse(responseText);
            })
            .then(data => {
                if (data.success) {
                    // Update UI on success
                    if (icon) {
                        if (data.is_open) {
                            icon.classList.remove('bi-valve-closed', 'text-secondary');
                            icon.classList.add('bi-valve-open', 'text-success');
                        } else {
                            icon.classList.remove('bi-valve-open', 'text-success');
                            icon.classList.add('bi-valve-closed', 'text-secondary');
                        }
                    }
                    
                    // Update action buttons state
                    actionButtons.forEach(btn => {
                        const action = btn.dataset.action;
                        if (action === 'open') {
                            btn.disabled = data.is_open;
                            btn.classList.toggle('btn-success', !data.is_open);
                            btn.classList.toggle('btn-outline-secondary', data.is_open);
                        } else if (action === 'close') {
                            btn.disabled = !data.is_open;
                            btn.classList.toggle('btn-danger', data.is_open);
                            btn.classList.toggle('btn-outline-secondary', !data.is_open);
                        }
                    });
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.role = 'alert';
                    alert.innerHTML = `
                        <i class="bi bi-check-circle me-2"></i>
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    
                    const container = document.querySelector('.container-fluid');
                    container.insertBefore(alert, container.firstChild);
                    
                    // Auto-dismiss alert after 3 seconds
                    setTimeout(() => {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error toggling valve state:', error);
                // Revert the toggle on error
                this.checked = !isOpen;
                
                // Re-enable the toggle
                this.disabled = false;
                
                // Show error message
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.role = 'alert';
                alert.innerHTML = `
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Failed to update valve state. Please try again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                const container = document.querySelector('.container-fluid');
                container.insertBefore(alert, container.firstChild);
            })
            .finally(() => {
                // Re-enable the toggle
                this.disabled = false;
            });
        });
    });
    
    // Helper function to show alerts
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        alertDiv.role = 'alert';
        alertDiv.style.zIndex = '1050';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = new bootstrap.Alert(alertDiv);
            alert.close();
        }, 5000);
    }
});
</script>
@endpush
