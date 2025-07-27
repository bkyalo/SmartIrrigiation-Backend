@extends('layouts.app')

@section('title', 'Edit Valve: ' . $valve->name)

@section('content')
<div class="container-fluid">
    <!-- Header with Back Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('valves.index') }}">Valves</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('valves.show', $valve) }}">{{ $valve->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">Edit Valve: {{ $valve->name }}</h1>
        </div>
        <div>
            <a href="{{ route('valves.show', $valve) }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card">
        <div class="card-body">
            <form action="{{ route('valves.update', $valve) }}" method="POST">
                @csrf
                @method('PUT')
                @include('valves._form')
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-danger" 
                            onclick="if(confirm('Are you sure you want to delete this valve?')) { document.getElementById('delete-form').submit(); }">
                        <i class="bi bi-trash me-1"></i> Delete Valve
                    </button>
                    
                    <div>
                        <a href="{{ route('valves.show', $valve) }}" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-x-lg me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Delete Form (Hidden) -->
            <form id="delete-form" action="{{ route('valves.destroy', $valve) }}" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>
@endsection
