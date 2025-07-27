@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('plots.index') }}">Plots</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add New Plot</li>
                </ol>
            </nav>
            <h1 class="mb-0">Add New Plot</h1>
        </div>
        <div>
            <a href="{{ route('plots.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Plots
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('plots.store') }}" method="POST">
                @include('plots._form')
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Plot
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
