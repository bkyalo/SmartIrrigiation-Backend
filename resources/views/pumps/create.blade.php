@extends('layouts.app')

@section('title', 'Add New Water Pump')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add New Water Pump</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('pumps.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Pump Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="external_device_id" class="form-label">Pump ID (External Device ID)</label>
                                    <input type="text" class="form-control @error('external_device_id') is-invalid @enderror" 
                                           id="external_device_id" name="external_device_id" value="{{ old('external_device_id') }}"
                                           placeholder="e.g., pump-001" aria-describedby="externalDeviceIdHelp">
                                    <div id="externalDeviceIdHelp" class="form-text">Unique identifier for external device integration</div>
                                    @error('external_device_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="error_code" class="form-label">Error Code (if any)</label>
                                    <input type="text" class="form-control @error('error_code') is-invalid @enderror" 
                                           id="error_code" name="error_code" value="{{ old('error_code') }}">
                                    @error('error_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="flow_rate" class="form-label">Flow Rate (L/min) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('flow_rate') is-invalid @enderror" 
                                               id="flow_rate" name="flow_rate" value="{{ old('flow_rate') }}" required>
                                        <span class="input-group-text">L/min</span>
                                        @error('flow_rate')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="power_consumption" class="form-label">Power Consumption (W) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('power_consumption') is-invalid @enderror" 
                                               id="power_consumption" name="power_consumption" value="{{ old('power_consumption') }}" required>
                                        <span class="input-group-text">W</span>
                                        @error('power_consumption')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        

                        
                        <div class="mb-4">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('pumps.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Pump
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
