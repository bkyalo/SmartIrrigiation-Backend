@extends('layouts.app')

@section('title', 'Add New Water Tank')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card
                <div class="card-header">
                    <h5 class="mb-0">Add New Water Tank</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('tanks.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Tank Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="capacity" class="form-label">Capacity (Liters) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control @error('capacity') is-invalid @enderror" 
                                               id="capacity" name="capacity" value="{{ old('capacity') }}" required>
                                        <span class="input-group-text">L</span>
                                        @error('capacity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control @error('location') is-invalid @enderror" 
                                           id="location" name="location" value="{{ old('location') }}">
                                    @error('location')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('tanks.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Save Tank
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
