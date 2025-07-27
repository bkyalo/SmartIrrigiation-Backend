@extends('layouts.app')

@section('title', 'Add New Valve')

@section('content')
<div class="container-fluid">
    <!-- Header with Back Button -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('valves.index') }}">Valves</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add New</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">Add New Valve</h1>
        </div>
        <div>
            <a href="{{ route('valves.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Cancel
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="card">
        <div class="card-body">
            <form action="{{ route('valves.store') }}" method="POST">
                @include('valves._form')
                
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Save Valve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
