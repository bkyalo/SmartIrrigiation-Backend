@extends('layouts.app')

@section('title', 'Edit Sensor: ' . $sensor->name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('sensors.index') }}">Sensors</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('sensors.show', $sensor) }}">{{ $sensor->name }}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">Edit Sensor: {{ $sensor->name }}</h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('sensors.update', $sensor) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Sensor Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $sensor->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="external_device_id" class="form-label">Device ID</label>
                            <input type="text" class="form-control @error('external_device_id') is-invalid @enderror" 
                                   id="external_device_id" name="external_device_id" 
                                   value="{{ old('external_device_id', $sensor->external_device_id) }}" required
                                   placeholder="e.g., SENSOR-001">
                            <div class="form-text">Unique identifier for the physical device (required)</div>
                            @error('external_device_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="type" class="form-label">Sensor Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" 
                                    id="type" name="type" required>
                                <option value="" disabled>Select sensor type</option>
                                @foreach([
                                    'water_level' => 'Water Level',
                                    'soil_moisture' => 'Soil Moisture',
                                    'temperature' => 'Temperature',
                                    'humidity' => 'Humidity',
                                    'flow' => 'Flow Rate',
                                    'pressure' => 'Pressure',
                                    'ph' => 'pH',
                                    'ec' => 'Electrical Conductivity',
                                    'light' => 'Light'
                                ] as $value => $label)
                                    <option value="{{ $value }}" {{ old('type', $sensor->type) == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="location_type" class="form-label">Location Type</label>
                            <select class="form-select @error('location_type') is-invalid @enderror" 
                                    id="location_type" name="location_type" required>
                                <option value="" disabled>Select location type</option>
                                <option value="plot" {{ old('location_type', $sensor->location_type) == 'plot' ? 'selected' : '' }}>Plot</option>
                                <option value="tank" {{ old('location_type', $sensor->location_type) == 'tank' ? 'selected' : '' }}>Tank</option>
                            </select>
                            @error('location_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3" id="location_id_container">
                            <label for="location_id" class="form-label">Location</label>
                            <select class="form-select @error('location_id') is-invalid @enderror" 
                                    id="location_id" name="location_id" required>
                                <option value="" disabled>Select location</option>
                            </select>
                            @error('location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="reading_interval" class="form-label">Reading Interval (seconds)</label>
                            <input type="number" class="form-control @error('reading_interval') is-invalid @enderror" 
                                   id="reading_interval" name="reading_interval" 
                                   value="{{ old('reading_interval', $sensor->reading_interval) }}" min="30" required>
                            <div class="form-text">Minimum 30 seconds</div>
                            @error('reading_interval')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="active" {{ old('status', $sensor->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $sensor->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="error" {{ old('status', $sensor->status) == 'error' ? 'selected' : '' }}>Error</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="metadata" class="form-label">Additional Information (JSON)</label>
                    <textarea class="form-control font-monospace @error('metadata') is-invalid @enderror" 
                              id="metadata" name="metadata" rows="4">{{ old('metadata', $sensor->metadata ? json_encode($sensor->metadata, JSON_PRETTY_PRINT) : '{}') }}</textarea>
                    <div class="form-text">Enter any additional sensor metadata as valid JSON</div>
                    @error('metadata')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('sensors.show', $sensor) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger" 
                                onclick="if(confirm('Are you sure you want to delete this sensor?')) { document.getElementById('delete-form').submit(); }">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Update Sensor
                        </button>
                    </div>
                </div>
            </form>

            <!-- Delete Form (Hidden) -->
            <form id="delete-form" action="{{ route('sensors.destroy', $sensor) }}" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const locationTypeSelect = document.getElementById('location_type');
        const locationIdSelect = document.getElementById('location_id');
        const sensorLocationId = '{{ $sensor->location_id }}';
        
        function loadLocations(locationType, selectedId = null) {
            if (!locationType) {
                locationIdSelect.innerHTML = '<option value="" disabled selected>Select location</option>';
                return;
            }
            
            // Get the CSRF token from the meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`/api/v1/${locationType}s`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                let options = '<option value="" disabled>Select ' + locationType + '</option>';
                
                if (data && Array.isArray(data.data)) {
                    data.data.forEach(item => {
                        const selected = (selectedId && item.id == selectedId) || 
                                       (!selectedId && item.id == '{{ old('location_id') }}') ? 'selected' : '';
                        options += `<option value="${item.id}" ${selected}>${item.name}</option>`;
                    });
                } else {
                    console.error('Unexpected data format:', data);
                    throw new Error('Invalid data format received from server');
                }
                
                locationIdSelect.innerHTML = options;
            })
            .catch(error => {
                console.error('Error loading locations:', error);
                const errorMessage = error.message || 'Error loading locations';
                locationIdSelect.innerHTML = 
                        '<option value="" disabled>Error loading locations</option>';
                });
        }
        
        // Load locations when the page loads
        const locationType = '{{ $sensor->location_type }}';
        if (locationType) {
            loadLocations(locationType, '{{ $sensor->location_id }}');
        }
        
        // Load locations when the type changes
        locationTypeSelect.addEventListener('change', function() {
            loadLocations(this.value);
        });
    });
</script>
@endpush
@endsection
