@csrf

<div class="row mb-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Plot Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" 
               value="{{ old('name', $plot->name ?? '') }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <!-- Status is auto-computed and not user-editable -->
    <input type="hidden" name="status" value="idle">
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="crop_type" class="form-label">Crop Type</label>
        <input type="text" class="form-control @error('crop_type') is-invalid @enderror" id="crop_type" name="crop_type"
               value="{{ old('crop_type', $plot->crop_type ?? '') }}">
        @error('crop_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="col-md-6">
        <label for="soil_type" class="form-label">Soil Type</label>
        <input type="text" class="form-control @error('soil_type') is-invalid @enderror" id="soil_type" name="soil_type"
               value="{{ old('soil_type', $plot->soil_type ?? '') }}">
        @error('soil_type')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="area" class="form-label">Area (m²) <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="number" step="0.01" min="0" class="form-control @error('area') is-invalid @enderror" 
                   id="area" name="area" value="{{ old('area', $plot->area ?? '') }}" required>
            <span class="input-group-text">m²</span>
            @error('area')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
    
    <div class="col-md-6">
        <label for="moisture_threshold" class="form-label">Moisture Threshold (%) <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="number" step="0.1" min="0" max="100" class="form-control @error('moisture_threshold') is-invalid @enderror" 
                   id="moisture_threshold" name="moisture_threshold" 
                   value="{{ old('moisture_threshold', $plot->moisture_threshold ?? '60') }}" required>
            <span class="input-group-text">%</span>
            @error('moisture_threshold')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <label for="irrigation_duration" class="form-label">Irrigation Duration (minutes) <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="number" min="1" class="form-control @error('irrigation_duration') is-invalid @enderror" 
                   id="irrigation_duration" name="irrigation_duration" 
                   value="{{ old('irrigation_duration', $plot->irrigation_duration ?? '30') }}" required>
            <span class="input-group-text">minutes</span>
            @error('irrigation_duration')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Location</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="latitude" class="form-label">Latitude</label>
                        <input type="number" step="0.000001" class="form-control @error('latitude') is-invalid @enderror" 
                               id="latitude" name="latitude" 
                               value="{{ old('latitude', $plot->latitude ?? '') }}">
                        @error('latitude')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="longitude" class="form-label">Longitude</label>
                        <input type="number" step="0.000001" class="form-control @error('longitude') is-invalid @enderror" 
                               id="longitude" name="longitude" 
                               value="{{ old('longitude', $plot->longitude ?? '') }}">
                        @error('longitude')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-text">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="getLocationBtn">
                        <i class="bi bi-geo-alt"></i> Use Current Location
                    </button>
                    <span class="ms-2 text-muted">Leave blank if not applicable</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get current location button
        const getLocationBtn = document.getElementById('getLocationBtn');
        if (getLocationBtn) {
            getLocationBtn.addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
                        document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
                    }, function(error) {
                        alert('Unable to retrieve your location. Please enter coordinates manually.');
                        console.error('Geolocation error:', error);
                    });
                } else {
                    alert('Geolocation is not supported by your browser. Please enter coordinates manually.');
                }
            });
        }
    });
</script>
@endpush
