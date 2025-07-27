@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between align-items-center mb-4">
        <div class="col-md-6">
            <h1>Plots</h1>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('plots.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add New Plot
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($plots->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Crop Type</th>
                                <th>Area (m²)</th>
                                <th>Moisture Threshold</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plots as $plot)
                                <tr>
                                    <td>
                                        <a href="{{ route('plots.show', $plot) }}" class="text-decoration-none">
                                            {{ $plot->name }}
                                        </a>
                                    </td>
                                    <td>{{ $plot->crop_type ?? 'N/A' }}</td>
                                    <td>{{ number_format($plot->area, 2) }}</td>
                                    <td>{{ $plot->moisture_threshold }}%</td>
                                    <td>
                                        <span class="badge bg-{{ $plot->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($plot->status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('plots.show', $plot) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                            <a href="{{ route('plots.edit', $plot) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form action="{{ route('plots.destroy', $plot) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this plot?')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $plots->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted"></i>
                    <h3 class="mt-3">No Plots Found</h3>
                    <p class="text-muted">Get started by adding your first plot.</p>
                    <a href="{{ route('plots.create') }}" class="btn btn-primary mt-2">
                        <i class="bi bi-plus-lg"></i> Add Plot
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
