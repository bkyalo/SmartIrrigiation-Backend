@csrf

<!-- Basic Information Section -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Basic Information</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Valve Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" 
                       value="{{ old('name', $valve->name ?? '') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="form-text text-muted">A descriptive name for this valve</small>
            </div>
            
            <div class="col-md-6">
                <label for="type" class="form-label">Valve Type <span class="text-danger">*</span></label>
                <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                    <option value="">Select valve type...</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}" 
                            {{ old('type', $valve->type ?? '') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Select the type of valve (tank, plot, or main)</small>
                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <label for="external_device_id" class="form-label">External Device ID</label>
                <input type="text" class="form-control @error('external_device_id') is-invalid @enderror" 
                       id="external_device_id" name="external_device_id" 
                       value="{{ old('external_device_id', $valve->external_device_id ?? '') }}"
                       placeholder="e.g., valve-001">
                <small class="form-text text-muted">Unique identifier for external system integration</small>
                @error('external_device_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<!-- Connection Settings Section -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Connection Settings</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6" id="tankField">
                <label for="tank_id" class="form-label">Connected Tank</label>
                <select class="form-select @error('tank_id') is-invalid @enderror" id="tank_id" name="tank_id">
                    <option value="">Select a tank...</option>
                    @foreach($tanks as $tank)
                        <option value="{{ $tank->id }}" 
                            {{ old('tank_id', $valve->tank_id ?? '') == $tank->id ? 'selected' : '' }}>
                            {{ $tank->name }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Required for tank valves</small>
                @error('tank_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            
            <div class="col-md-6" id="plotField">
                <label for="plot_id" class="form-label">Connected Plot</label>
                <select class="form-select @error('plot_id') is-invalid @enderror" id="plot_id" name="plot_id">
                    <option value="">Select a plot...</option>
                    @foreach($plots as $plot)
                        <option value="{{ $plot->id }}" 
                            {{ old('plot_id', $valve->plot_id ?? '') == $plot->id ? 'selected' : '' }}>
                            {{ $plot->name }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Required for plot valves</small>
                @error('plot_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Tank-specific settings -->
        <div class="row mb-3" id="tankSettings" style="display: none;">
            <div class="col-md-6">
                <label for="valve_direction" class="form-label">Valve Direction <span class="text-danger">*</span></label>
                <select class="form-select @error('valve_direction') is-invalid @enderror" id="valve_direction" name="valve_direction">
                    <option value="">Select direction...</option>
                    @foreach(\App\Models\Valve::getDirectionOptions() as $value => $label)
                        <option value="{{ $value }}" 
                            {{ old('valve_direction', $valve->valve_direction ?? '') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Controls whether this is an inlet or outlet valve</small>
                @error('valve_direction')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<!-- Operational Settings Section -->
<div class="card mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Operational Settings</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <label for="flow_rate" class="form-label">Flow Rate <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" step="0.01" min="0" class="form-control @error('flow_rate') is-invalid @enderror" 
                           id="flow_rate" name="flow_rate" 
                           value="{{ old('flow_rate', $valve->flow_rate ?? '0') }}" required>
                    <span class="input-group-text">L/min</span>
                    @error('flow_rate')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <small class="form-text text-muted">Maximum flow rate when valve is fully open</small>
            </div>
            
            <div class="col-md-6">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" 
                            {{ old('status', $valve->status ?? 'operational') == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Current operational status</small>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        const tankField = document.getElementById('tankField');
        const plotField = document.getElementById('plotField');
        const tankSettings = document.getElementById('tankSettings');
        
        // Function to update field visibility based on selected type
        function updateFieldVisibility() {
            const selectedType = typeSelect.value;
            
            // Reset required attributes and hide all fields initially
            document.getElementById('tank_id').required = false;
            document.getElementById('plot_id').required = false;
            tankField.style.display = 'none';
            plotField.style.display = 'none';
            tankSettings.style.display = 'none';
            
            // Show/hide fields based on selected type
            if (selectedType === 'tank') {
                tankField.style.display = 'block';
                tankSettings.style.display = 'block';
                document.getElementById('tank_id').required = true;
            } else if (selectedType === 'plot') {
                plotField.style.display = 'block';
                document.getElementById('plot_id').required = true;
            }
            // For 'main' type, no additional fields are shown
        }
        
        // Initial update
        updateFieldVisibility();
        
        // Update on type change
        typeSelect.addEventListener('change', updateFieldVisibility);
        
        // If editing an existing valve, make sure the correct fields are shown
        @if(isset($valve) && $valve->exists)
            updateFieldVisibility();
        @endif
    });
</script>
@endpush
