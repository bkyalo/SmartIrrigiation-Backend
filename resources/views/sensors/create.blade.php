@extends('layouts.app')

@section('title', 'Add New Sensor')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('sensors.index') }}">Sensors</a></li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">Add New Sensor</h1>
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
            <form action="{{ route('sensors.store') }}" method="POST">
                @csrf
                
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3">Basic Information</h5>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Sensor Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="external_device_id" class="form-label">Device ID</label>
                            <input type="text" class="form-control @error('external_device_id') is-invalid @enderror" 
                                   id="external_device_id" name="external_device_id" 
                                   value="{{ old('external_device_id') }}" required
                                   placeholder="e.g., SENSOR-001">
                            <div class="form-text small">Unique identifier for the physical device</div>
                            @error('external_device_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3">Sensor Configuration</h5>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="type" class="form-label">Sensor Type</label>
                            <select class="form-select @error('type') is-invalid @enderror" 
                                    id="type" name="type" required>
                                <option value="" disabled selected>Select sensor type</option>
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
                                    <option value="{{ $value }}" {{ old('type') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                    id="status" name="status" required>
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="error" {{ old('status') == 'error' ? 'selected' : '' }}>Error</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3">Location</h5>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="location_type" class="form-label">Location Type</label>
                            <select class="form-select @error('location_type') is-invalid @enderror" 
                                    id="location_type" name="location_type" required>
                                <option value="" disabled selected>Select location type</option>
                                <option value="plot" {{ old('location_type') == 'plot' ? 'selected' : '' }}>Plot</option>
                                <option value="tank" {{ old('location_type') == 'tank' ? 'selected' : '' }}>Tank</option>
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
                                <option value="" disabled selected>Select location type first</option>
                            </select>
                            @error('location_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3">Additional Settings</h5>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="reading_interval" class="form-label">Reading Interval (seconds)</label>
                            <input type="number" class="form-control @error('reading_interval') is-invalid @enderror" 
                                   id="reading_interval" name="reading_interval" 
                                   value="{{ old('reading_interval', 300) }}" min="30" required>
                            <div class="form-text small">Minimum 30 seconds</div>
                            @error('reading_interval')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="metadata" class="form-label">Additional Information (JSON)</label>
                    <textarea class="form-control font-monospace @error('metadata') is-invalid @enderror" 
                              id="metadata" name="metadata" rows="4">{{ old('metadata', '{}') }}</textarea>
                    <div class="form-text">Enter any additional sensor metadata as valid JSON</div>
                    @error('metadata')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('sensors.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Sensor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const locationTypeSelect = document.getElementById('location_type');
        const locationIdContainer = document.getElementById('location_id_container');
        
        function loadLocations(locationType) {
            if (!locationType) {
                document.getElementById('location_id').innerHTML = '<option value="" disabled selected>Select location</option>';
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
                const select = document.getElementById('location_id');
                let options = '<option value="" disabled selected>Select ' + locationType + '</option>';
                
                if (data && Array.isArray(data.data)) {
                    data.data.forEach(item => {
                        options += `<option value="${item.id}">${item.name}</option>`;
                    });
                } else {
                    console.error('Unexpected data format:', data);
                    throw new Error('Invalid data format received from server');
                }
                
                select.innerHTML = options;
                
                // Set the previously selected value if it exists
                const oldValue = '{{ old('location_id') }}';
                if (oldValue) {
                    select.value = oldValue;
                }
            })
            .catch(error => {
                console.error('Error loading locations:', error);
                const errorMessage = error.message || 'Error loading locations';
                document.getElementById('location_id').innerHTML = 
                    `<option value="" disabled>${errorMessage}</option>`;
            });
        }
        
        // Load locations when the page loads if a type is already selected
        if (locationTypeSelect.value) {
            loadLocations(locationTypeSelect.value);
        }
        
        // Load locations when the type changes
        locationTypeSelect.addEventListener('change', function() {
            loadLocations(this.value);
        });
    });
</script>
@endpush
@endsection
