@extends('layouts.main')

@section('title')
    View Hotel Apartment Type
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ trans('labels.dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('hotel-apartment-types.index') }}">Hotel Apartment Types</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Hotel Apartment Type Details</h4>
                <div class="card-header-action">
                    <a href="{{ route('hotel-apartment-types.edit', $hotelApartmentType->id) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">ID:</label>
                            <p>{{ $hotelApartmentType->id }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Name:</label>
                            <p>{{ $hotelApartmentType->name }}</p>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold">Description:</label>
                            <p>{{ $hotelApartmentType->description ?? 'No description available' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Created At:</label>
                            <p>{{ $hotelApartmentType->created_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold">Updated At:</label>
                            <p>{{ $hotelApartmentType->updated_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="{{ route('hotel-apartment-types.index') }}" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
        </div>
    </section>
@endsection
