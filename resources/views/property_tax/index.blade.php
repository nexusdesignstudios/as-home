@extends('layouts.main')

@section('title', 'Property Taxes')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Property Taxes</h3>
                <p class="text-subtitle text-muted">Manage taxes for vacation homes and hotels</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Property Taxes</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Property Tax Settings</h4>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="taxTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="vacation-homes-tab" data-bs-toggle="tab" data-bs-target="#vacation-homes" type="button" role="tab" aria-controls="vacation-homes" aria-selected="true">Vacation Homes</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="hotels-tab" data-bs-toggle="tab" data-bs-target="#hotels" type="button" role="tab" aria-controls="hotels" aria-selected="false">Hotels</button>
                            </li>
                        </ul>
                        <div class="tab-content pt-4" id="taxTabContent">
                            <!-- Vacation Homes Tab -->
                            <div class="tab-pane fade show active" id="vacation-homes" role="tabpanel" aria-labelledby="vacation-homes-tab">
                                <form action="{{ route('property-taxes.store') }}" method="POST" id="vacation-homes-form">
                                    @csrf
                                    <input type="hidden" name="property_classification" value="4">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="vacation_sales_tax">Sales Tax (%)</label>
                                                <input type="number" class="form-control" id="vacation_sales_tax" name="sales_tax"
                                                    step="0.01" min="0" max="100"
                                                    value="{{ isset($taxes[4]) ? $taxes[4]->sales_tax : '' }}"
                                                    placeholder="Enter sales tax percentage">
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label for="vacation_city_tax">City Tax (%)</label>
                                                <input type="number" class="form-control" id="vacation_city_tax" name="city_tax"
                                                    step="0.01" min="0" max="100"
                                                    value="{{ isset($taxes[4]) ? $taxes[4]->city_tax : '' }}"
                                                    placeholder="Enter city tax percentage">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Save Vacation Homes Taxes</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Hotels Tab -->
                            <div class="tab-pane fade" id="hotels" role="tabpanel" aria-labelledby="hotels-tab">
                                <form action="{{ route('property-taxes.store') }}" method="POST" id="hotels-form">
                                    @csrf
                                    <input type="hidden" name="property_classification" value="5">

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="form-group">
                                                <label for="hotel_service_charge">Service Charge (%)</label>
                                                <input type="number" class="form-control" id="hotel_service_charge" name="service_charge"
                                                    step="0.01" min="0" max="100"
                                                    value="{{ isset($taxes[5]) ? $taxes[5]->service_charge : '' }}"
                                                    placeholder="Enter service charge percentage">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-group">
                                                <label for="hotel_sales_tax">Sales Tax (%)</label>
                                                <input type="number" class="form-control" id="hotel_sales_tax" name="sales_tax"
                                                    step="0.01" min="0" max="100"
                                                    value="{{ isset($taxes[5]) ? $taxes[5]->sales_tax : '' }}"
                                                    placeholder="Enter sales tax percentage">
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="form-group">
                                                <label for="hotel_city_tax">City Tax (%)</label>
                                                <input type="number" class="form-control" id="hotel_city_tax" name="city_tax"
                                                    step="0.01" min="0" max="100"
                                                    value="{{ isset($taxes[5]) ? $taxes[5]->city_tax : '' }}"
                                                    placeholder="Enter city tax percentage">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary">Save Hotel Taxes</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Form submission handling
        $('#vacation-homes-form, #hotels-form').submit(function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const originalBtnText = submitBtn.text();

            submitBtn.prop('disabled', true).text('Saving...');

            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message || 'Taxes updated successfully'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'An error occurred'
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage
                    });
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });
    });
</script>
@endsection
