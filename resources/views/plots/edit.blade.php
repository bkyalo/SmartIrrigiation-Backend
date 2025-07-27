@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('plots.index') }}">Plots</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('plots.show', $plot) }}">{{ $plot->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
            <h1 class="mb-0">Edit Plot</h1>
            <p class="text-muted">{{ $plot->name }}</p>
        </div>
        <div>
            <a href="{{ route('plots.show', $plot) }}" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i> Cancel
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('plots.update', $plot) }}" method="POST">
                @csrf
                @method('PUT')
                @include('plots._form')
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePlotModal">
                        <i class="bi bi-trash"></i> Delete Plot
                    </button>
                    <div>
                        <a href="{{ route('plots.show', $plot) }}" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-x-lg"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deletePlotModal" tabindex="-1" aria-labelledby="deletePlotModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePlotModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this plot? This action cannot be undone.</p>
                <p class="mb-0"><strong>Plot:</strong> {{ $plot->name }}</p>
                @if($plot->valves->count() > 0)
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        This plot has {{ $plot->valves->count() }} valve(s) associated with it. Deleting this plot will remove these associations.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('plots.destroy', $plot) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete Plot
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
