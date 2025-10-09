@extends('layouts.main')

@section('title')
    {{ __('Add Property') }}
@endsection
<!-- add before </body> -->

{{-- <script src="https://unpkg.com/filepond/dist/filepond.js"></script> --}}
@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>

            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('property.index') }}" id="subURL">{{ __('View Property') }}</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ __('Add') }}
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
@endsection
@section('content')
    {!! Form::open(['route' => 'property.store', 'data-parsley-validate', 'id' => 'myForm', 'files' => true]) !!}
    <div class='row'>
        <div class='col-md-6'>
            <div class="card">
                <h3 class="card-header"> {{ __('Details') }}</h3>
                <hr>
                <input type="hidden" id="default-latitude" value="{{ system_setting('latitude') }}">
                <input type="hidden" id="default-longitude" value="{{ system_setting('longitude') }}">

                {{-- Property Classification --}}
                <div class="card-body">
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('property_classification', __('Property Classification'), ['class' => 'form-label col-12 ']) }}
                        <select name="property_classification" id="property_classification" class="form-select form-control-sm" data-parsley-minSelect='1' required>
                            <option value="" selected>{{ __('Choose Classification') }}</option>
                            <option value="1">{{ __('Sell/Long Term Rent') }}</option>
                            <option value="2">{{ __('Commercial') }}</option>
                            <option value="3">{{ __('New Project') }}</option>
                            <option value="4">{{ __('Vacation Homes') }}</option>
                            <option value="5">{{ __('Hotel Booking') }}</option>
                        </select>
                    </div>

                    {{-- Category --}}
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('category', __('Category'), ['class' => 'form-label col-12 ']) }}
                        <select name="category" class="form-select form-control-sm" data-parsley-minSelect='1' id="category" required>
                            <option value="" selected>{{ __('Choose Category') }}</option>
                            @foreach ($category as $row)
                                <option value="{{ $row->id }}" data-parametertypes='{{ $row->parameter_types }}' data-classification='{{ $row->property_classification }}'>
                                    {{ $row->category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Title --}}
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('title', __('Title'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('title', '', [ 'class' => 'form-control ', 'placeholder' =>  __('Title'), 'required' => 'true', 'id' => 'title', ]) }}
                    </div>

                    {{-- Title Arabic --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('title_ar', __('Title (Arabic)'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('title_ar', '', [ 'class' => 'form-control ', 'placeholder' =>  __('Title in Arabic'), 'id' => 'title_ar', ]) }}
                    </div>

                    {{-- Slug --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('slug', __('Slug'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('slug', '', [ 'class' => 'form-control ', 'placeholder' =>  __('Slug'), 'id' => 'slug', ]) }}
                        <small class="text-danger text-sm">{{ __("Only Small English Characters, Numbers And Hypens Allowed") }}</small>
                    </div>

                    {{-- Description --}}
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('description', __('Description'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('description', '', [ 'class' => 'form-control mb-3', 'rows' => '5', 'id' => '', 'required' => 'true', 'placeholder' => __('Description') ]) }}
                    </div>

                    {{-- Description Arabic --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('description_ar', __('Description (Arabic)'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('description_ar', '', [ 'class' => 'form-control mb-3', 'rows' => '5', 'id' => 'description_ar', 'placeholder' => __('Description in Arabic') ]) }}
                    </div>

                    {{-- Area Description --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('area_description', __('Area Description'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('area_description', '', [ 'class' => 'form-control mb-3', 'rows' => '3', 'id' => 'area_description', 'placeholder' => __('Area Description') ]) }}
                    </div>

                    {{-- Area Description Arabic --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('area_description_ar', __('Area Description (Arabic)'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('area_description_ar', '', [ 'class' => 'form-control mb-3', 'rows' => '3', 'id' => 'area_description_ar', 'placeholder' => __('Area Description in Arabic') ]) }}
                    </div>

                    {{-- Company Employee Information --}}
                    <div class="col-md-12 col-12 form-group">
                        <h6 class="form-label col-12">{{ __('Company Employee Information') }}</h6>
                    </div>

                    {{-- Company Employee Username --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('company_employee_username', __('Company Employee Username'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('company_employee_username', '', [ 'class' => 'form-control ', 'placeholder' =>  __('Company Employee Username'), 'id' => 'company_employee_username', ]) }}
                    </div>

                    {{-- Company Employee Email --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('company_employee_email', __('Company Employee Email'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::email('company_employee_email', '', [ 'class' => 'form-control ', 'placeholder' =>  __('Company Employee Email'), 'id' => 'company_employee_email', ]) }}
                    </div>

                    {{-- Company Employee Phone Number --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('company_employee_phone_number', __('Company Employee Phone Number'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('company_employee_phone_number', '', [ 'class' => 'form-control ', 'placeholder' =>  __('Company Employee Phone Number'), 'id' => 'company_employee_phone_number', ]) }}
                    </div>

                    {{-- Property Type --}}
                    <div class="col-md-12 col-12  form-group  mandatory">
                        <div class="row">
                            {{ Form::label('', __('Property Type'), ['class' => 'form-label col-12 ']) }}

                            {{-- For Sell --}}
                            <div class="col-md-6">
                                {{ Form::radio('property_type', 0, null, [ 'class' => 'form-check-input', 'id' => 'property_type', 'required' => true, 'checked' => true ]) }}
                                {{ Form::label('property_type', __('For Sell'), ['class' => 'form-check-label']) }}
                            </div>
                            {{-- For Rent --}}
                            <div class="col-md-6">
                                {{ Form::radio('property_type', 1, null, [ 'class' => 'form-check-input', 'id' => 'property_type', 'required' => true, ]) }}
                                {{ Form::label('property_type', __('For Rent'), ['class' => 'form-check-label']) }}
                            </div>
                        </div>
                    </div>



                                {{-- Hotel Specific Fields --}}
            <div class="col-md-12 hotel-fields" style="display: none;">
                <div class="form-group">
                    {{ Form::label('refund_policy', __('Refund Policy'), ['class' => 'form-label col-12']) }}
                    {{ Form::select('refund_policy', ['flexible' => __('Flexible'), 'non-refundable' => __('Non-Refundable')], null, ['class' => 'form-control select2', 'placeholder' => __('Select Refund Policy')]) }}
                </div>
                <div class="form-group">
                    {{ Form::label('hotel_apartment_type_id', __('Hotel Apartment Type'), ['class' => 'form-label col-12']) }}
                    {{ Form::select('hotel_apartment_type_id', App\Models\HotelApartmentType::pluck('name', 'id'), null, ['class' => 'form-control select2', 'placeholder' => __('Select Apartment Type')]) }}
                </div>
                <div class="form-group">
                    {{ Form::label('check_in', __('Check-in Time'), ['class' => 'form-label col-12']) }}
                    {{ Form::time('check_in', null, ['class' => 'form-control']) }}
                </div>
                <div class="form-group">
                    {{ Form::label('check_out', __('Check-out Time'), ['class' => 'form-label col-12']) }}
                    {{ Form::time('check_out', null, ['class' => 'form-control']) }}
                </div>
                <div class="form-group">
                    {{ Form::label('available_rooms', __('Total Available Rooms'), ['class' => 'form-label col-12']) }}
                    {{ Form::number('available_rooms', null, ['class' => 'form-control', 'min' => '0']) }}
                </div>

                {{-- Revenue Information --}}
                <div class="form-group">
                    <h6 class="form-label col-12">{{ __('Revenue Information') }}</h6>
                </div>
                <div class="form-group">
                    {{ Form::label('revenue_user_name', __('Revenue User Name'), ['class' => 'form-label col-12']) }}
                    {{ Form::text('revenue_user_name', null, ['class' => 'form-control', 'placeholder' => __('Revenue User Name')]) }}
                </div>
                <div class="form-group">
                    {{ Form::label('revenue_phone_number', __('Revenue Phone Number'), ['class' => 'form-label col-12']) }}
                    {{ Form::text('revenue_phone_number', null, ['class' => 'form-control', 'placeholder' => __('Revenue Phone Number')]) }}
                </div>
                <div class="form-group">
                    {{ Form::label('revenue_email', __('Revenue Email'), ['class' => 'form-label col-12']) }}
                    {{ Form::email('revenue_email', null, ['class' => 'form-control', 'placeholder' => __('Revenue Email')]) }}
                </div>

                {{-- Reservation Information --}}
                <div class="form-group">
                    <h6 class="form-label col-12">{{ __('Reservation Information') }}</h6>
                </div>
                <div class="form-group">
                    {{ Form::label('reservation_user_name', __('Reservation User Name'), ['class' => 'form-label col-12']) }}
                    {{ Form::text('reservation_user_name', null, ['class' => 'form-control', 'placeholder' => __('Reservation User Name')]) }}
                </div>
                <div class="form-group">
                    {{ Form::label('reservation_phone_number', __('Reservation Phone Number'), ['class' => 'form-label col-12']) }}
                    {{ Form::text('reservation_phone_number', null, ['class' => 'form-control', 'placeholder' => __('Reservation Phone Number')]) }}
                </div>
                <div class="form-group">
                    {{ Form::label('reservation_email', __('Reservation Email'), ['class' => 'form-label col-12']) }}
                    {{ Form::email('reservation_email', null, ['class' => 'form-control', 'placeholder' => __('Reservation Email')]) }}
                </div>

                {{-- Rent Package --}}
                <div class="form-group">
                    {{ Form::label('rent_package', __('Rent Package'), ['class' => 'form-label col-12']) }}
                    {{ Form::select('rent_package', ['basic' => __('Basic'), 'premium' => __('Premium')], null, ['class' => 'form-control select2', 'placeholder' => __('Select Rent Package')]) }}
                </div>
            </div>

            {{-- Hotel Rooms Section --}}
            <div class="col-md-12 hotel-fields" style="display: none;">
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5>{{ __('Hotel Rooms') }}</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="add-room-btn">
                            <i class="bi bi-plus"></i> {{ __('Add Room') }}
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Room Number') }}</th>
                                    <th>{{ __('Room Type') }}</th>
                                    <th>{{ __('Price/Night') }}</th>
                                    <th>{{ __('Discount %') }}</th>
                                    <th>{{ __('Non-Refundable %') }}</th>
                                    <th>{{ __('Refund Policy') }}</th>
                                    <th>{{ __('Weekend Commission') }}</th>
                                    <th>{{ __('Availability Type') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="rooms-container">
                                <!-- Room rows will be added here dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Addons Packages Section --}}
            <div class="col-md-12 hotel-fields" style="display: none;">
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5>{{ __('Addons Packages') }}</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="add-package-btn">
                            <i class="bi bi-plus"></i> {{ __('Add Package') }}
                        </button>
                    </div>
                    <div id="packages-container">
                        <!-- Package forms will be added here dynamically -->
                    </div>
                </div>
            </div>

            {{-- Certificates Section --}}
            <div class="col-md-12 hotel-fields" style="display: none;">
                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5>{{ __('Certificates') }}</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="add-certificate-btn">
                            <i class="bi bi-plus"></i> {{ __('Add Certificate') }}
                        </button>
                    </div>
                    <div id="certificates-container">
                        <!-- Certificate forms will be added here dynamically -->
                    </div>
                </div>
            </div>

                    {{-- Duration --}}
                    <div class="col-md-12 col-12 form-group mandatory" id='duration'>
                        {{ Form::label('Duration', __('Duration For Price'), ['class' => 'form-label col-12 ']) }}
                        <select name="price_duration" id="price_duration" class="choosen-select form-select form-control-sm" data-parsley-minSelect='1'>
                            <option value="">{{ __("Select Duration") }}</option>
                            <option value="Daily">{{ __("Daily") }}</option>
                            <option value="Monthly">{{ __("Monthly") }}</option>
                            <option value="Yearly">{{ __("Yearly") }}</option>
                            <option value="Quarterly">{{ __("Quarterly") }}</option>
                        </select>
                    </div>

                    {{-- Vacation Home Specific Fields --}}
                    <div class="col-md-12 vacation-fields" style="display: none;">
                        <div class="form-group">
                            {{ Form::label('availability_type', __('Availability Type'), ['class' => 'form-label col-12']) }}
                            {{ Form::select('availability_type', [
                                '1' => __('Available Days'),
                                '2' => __('Busy Days')
                            ], null, ['class' => 'form-control select2', 'placeholder' => __('Select Availability Type')]) }}
                        </div>
                        <div class="form-group">
                            {{ Form::label('available_dates', __('Available Dates'), ['class' => 'form-label col-12']) }}
                            <div id="availability-calendar"></div>
                            {{ Form::hidden('available_dates', '[]', ['id' => 'available-dates-json']) }}
                        </div>
                    </div>

                    {{-- Price --}}
                    <div class="control-label col-12 form-group mandatory price-field">
                        {{ Form::label('price', __('Price') . '(' . $currency_symbol . ')', ['class' => 'form-label col-12 ']) }}
                        {{ Form::number('price', '', ['class' => 'form-control ', 'placeholder' => __('Price'), 'min' => '1', 'max' => '9223372036854775807', 'id' => 'price']) }}
                    </div>

                    {{-- Weekend Commission --}}
                    <div class="control-label col-12 form-group price-field">
                        {{ Form::label('weekend_commission', __('Weekend Commission (%)'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::number('weekend_commission', '', ['class' => 'form-control', 'placeholder' => __('Weekend Commission'), 'min' => '0', 'max' => '100', 'step' => '0.01', 'id' => 'weekend_commission']) }}
                    </div>

                    {{-- Corresponding Day --}}
                    <div class="control-label col-12 form-group">
                        {{ Form::label('corresponding_day', __('Corresponding Day'), ['class' => 'form-label col-12 ']) }}
                        <div class="corresponding-day-container">
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <label>{{ __('Day') }}</label>
                                    <select class="form-control day-select">
                                        <option value="monday">{{ __('Monday') }}</option>
                                        <option value="tuesday">{{ __('Tuesday') }}</option>
                                        <option value="wednesday">{{ __('Wednesday') }}</option>
                                        <option value="thursday">{{ __('Thursday') }}</option>
                                        <option value="friday">{{ __('Friday') }}</option>
                                        <option value="saturday">{{ __('Saturday') }}</option>
                                        <option value="sunday">{{ __('Sunday') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>{{ __('From Time') }}</label>
                                    <input type="time" class="form-control from-time" value="10:00">
                                </div>
                                <div class="col-md-3">
                                    <label>{{ __('To Time') }}</label>
                                    <input type="time" class="form-control to-time" value="14:00">
                                </div>
                                <div class="col-md-3">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-primary btn-sm add-corresponding-day">
                                        <i class="bi bi-plus"></i> {{ __('Add') }}
                                    </button>
                                </div>
                            </div>
                            <div class="corresponding-day-list">
                                <!-- Added days will appear here -->
                            </div>
                            {{ Form::hidden('corresponding_day', '', ['id' => 'corresponding_day_json']) }}
                        </div>
                        <small class="text-muted">{{ __('Format: [{"from": "10:00AM", "to": "02:00PM", "day": "saturday"}]') }}</small>
                    </div>

                    {{-- Agent Addons --}}
                    <div class="form-group">
                        {{ Form::label('agent_addons', __('Agent Addons'), ['class' => 'form-label col-12']) }}
                        {{ Form::textarea('agent_addons', null, ['class' => 'form-control', 'rows' => '5', 'placeholder' => __('Agent Addons (JSON format)'), 'id' => 'agent_addons_field']) }}
                        <small class="text-muted">{{ __('Enter valid JSON format. Example: [{"name": "Breakfast", "price": 15.00}, {"name": "WiFi", "price": 5.00}]') }}</small>
                        <div class="invalid-feedback" id="agent_addons_error"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class='col-md-6'>
            <div class="card">
                <h3 class="card-header">{{ __('SEO Details') }}</h3>
                <hr>
                <div class="row card-body">

                    {{-- SEO Title --}}
                    <div class="col-md-6 col-sm-12 form-group">
                        {{ Form::label('title', __('Title'), ['class' => 'form-label text-center']) }}
                        <textarea id="meta_title" name="meta_title" class="form-control" oninput="getWordCount('meta_title','meta_title_count','12.9px arial')" rows="2" style="height: 75px" placeholder="{{ __('Title') }}"></textarea>
                        <br>
                        <h6 id="meta_title_count">0</h6>
                    </div>

                    {{-- SEO Image --}}
                    <div class="col-md-6 col-sm-12 form-group card">
                        {{ Form::label('image', __('Image'), ['class' => 'form-label']) }}
                        <input type="file" name="meta_image" id="meta_image" class="from-control" placeholder="{{ __('Image') }}">
                        <div class="img_error"></div>
                    </div>

                    {{-- SEO Description --}}
                    <div class="col-md-12 col-sm-12 form-group">
                        {{ Form::label('description', __('Description'), ['class' => 'form-label text-center']) }}
                        <textarea id="meta_description" name="meta_description" class="form-control" oninput="getWordCount('meta_description','meta_description_count','12.9px arial')" rows="3" placeholder="{{ __('Description') }}"></textarea>
                        <br>
                        <h6 id="meta_description_count">0</h6>
                    </div>

                    {{-- SEO Keywords --}}
                    <div class="col-md-12 col-sm-12 form-group">
                        {{ Form::label('keywords', __('Keywords'), ['class' => 'form-label']) }}
                        <textarea name="keywords" id="" class="form-control" rows="3" placeholder="{{ __('Keywords') }}"></textarea>
                        (add comma separated keywords)
                    </div>

                </div>
            </div>

        </div>

        <div class="col-md-12" id="outdoor_facility">
            <div class="card">
                <h3 class="card-header">{{ __('Near By Places') }}</h3>
                <hr>
                <div class="card-body">
                    <div class="row">
                        @foreach ($facility as $key => $value)
                            <div class='col-md-3  form-group'>
                                {{ Form::checkbox($value->id, $value->name, false, ['class' => 'form-check-input', 'id' => 'chk' . $value->id]) }}
                                {{ Form::label('description', $value->name, ['class' => 'form-check-label']) }}
                                {{ Form::number('facility' . $value->id, '', [ 'class' => 'form-control mt-3', 'placeholder' => trans('Distance').' ('.$distanceValue.')', 'id' => 'dist' . $value->id, 'min' => 0, 'max' => 99999999.9, 'step' => '0.1' ]) }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12" id="facility">
            <div class="card">

                <h3 class="card-header"> {{ __('Facilities') }}</h3>
                <hr>
                {{ Form::hidden('category_count[]', $category, ['id' => 'category_count']) }}
                {{ Form::hidden('parameter_count[]', $parameters, ['id' => 'parameter_count']) }}
                {{ Form::hidden('facilities[]', $facility, ['id' => 'facilities']) }}

                {{ Form::hidden('parameter_add', '', ['id' => 'parameter_add']) }}
                <div id="parameter_type" class="row card-body"></div>

            </div>
        </div>
        <div class='col-md-12'>

            <div class="card">

                <h3 class="card-header">{{ __('Location') }}</h3>
                <hr>
                <div class="card-body">

                    <div class="row">
                        <div class='col-md-6'>
                            <div class="card col-md-12" id="map" style="height: 90%">
                                <!-- Google map -->
                            </div>
                        </div>
                        <div class='col-md-6'>
                            <div class="row">
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('city', __('City'), ['class' => 'form-label col-12 ']) }}
                                    {!! Form::hidden('city', '', ['class' => 'form-control ', 'id' => 'city']) !!}
                                    <input id="searchInput" class="controls form-control" type="text" placeholder="{{ __('City') }}" required>
                                    {{-- {{ Form::text('city', '', ['class' => 'form-control ', 'placeholder' => 'City', 'id' => 'city', 'required' => true]) }} --}}
                                </div>
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('country', __('Country'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::text('country', '', ['class' => 'form-control ', 'placeholder' => 'Country', 'id' => 'country', 'required' => true]) }}
                                </div>
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('state', __('State'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::text('state', '', ['class' => 'form-control ', 'placeholder' => 'State', 'id' => 'state', 'required' => true]) }}
                                </div>
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('latitude', __('Latitude'), ['class' => 'form-label col-12 ']) }}
                                    {!! Form::text('latitude', '', ['class' => 'form-control', 'id' => 'latitude', 'step' => 'any', 'readonly' => true, 'required' => true, 'placeholder' => trans('Latitude')]) !!}
                                </div>
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('longitude', __('Longitude'), ['class' => 'form-label col-12 ']) }}
                                    {!! Form::text('longitude', '', ['class' => 'form-control', 'id' => 'longitude', 'step' => 'any', 'readonly' => true, 'required' => true, 'placeholder' => trans('Longitude')]) !!}
                                </div>
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', 'Client Address', ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('client_address', system_setting('company_address') ?? "", [
                                        'class' => 'form-control ',
                                        'placeholder' => 'Client Address',
                                        'rows' => '4',
                                        'id' => 'client-address',
                                        'autocomplete' => 'off',
                                        'required' => 'true',
                                    ]) }}
                                </div>
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('Address'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('address', '', [
                                        'class' => 'form-control ',
                                        'placeholder' => 'Address',
                                        'rows' => '4',
                                        'id' => 'address',
                                        'autocomplete' => 'off',
                                        'required' => 'true',
                                    ]) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <h3 class="card-header">{{ __('Images') }}</h3>
                <hr>
                <div class="card-body">
                    <div class="row">
                        {{-- Title Image --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3  form-group mandatory">
                            {{ Form::label('title-image', __('Title Image'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="title-image" name="title_image" accept="image/jpg,image/png,image/jpeg" required>
                        </div>

                        {{-- 3D Image --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('three-d-images', __('3D Image'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="three-d-images" name="3d_image">
                        </div>

                        {{-- Gallery Images --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('gallary-images', __('Gallery Images'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="gallary-images" name="gallery_images[]" multiple accept="image/jpg,image/png,image/jpeg">
                        </div>

                        {{-- Documents --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('documents', __('Documents'), ['class' => 'form-label ']) }}
                            <input type="file" class="filepond" id="documents" name="documents[]" multiple accept="application/pdf,application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                        </div>

                        {{-- Policy Data (hidden for hotel properties) --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3 policy-data-field">
                            {{ Form::label('policy_data', __('Policy Data'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="policy_data" name="policy_data" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain">
                        </div>

                        {{-- Identity Proof --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('identity_proof', __('Identity Proof'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="identity_proof" name="identity_proof" accept="image/jpg,image/png,image/jpeg">
                        </div>

                        {{-- National ID/Passport --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('national_id_passport', __('National ID/Passport'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="national_id_passport" name="national_id_passport" accept="image/jpg,image/png,image/jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                        </div>

                        {{-- Utilities Bills --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('utilities_bills', __('Utilities Bills'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="utilities_bills" name="utilities_bills" accept="image/jpg,image/png,image/jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                        </div>

                        {{-- Power of Attorney --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('power_of_attorney', __('Power of Attorney'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="power_of_attorney" name="power_of_attorney" accept="image/jpg,image/png,image/jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                        </div>

                        {{-- Video Link --}}
                        <div class="col-md-3">
                            {{ Form::label('video_link', __('Video Link'), ['class' => 'form-label']) }}
                            {{ Form::text('video_link', isset($list->video_link) ? $list->video_link : '', [ 'class' => 'form-control ', 'placeholder' => 'Video Link', 'id' => 'address', 'autocomplete' => 'off', ]) }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <h3 class="card-header">{{ __('accessibility') }}</h3>
                <hr>
                <div class="card-body">
                    <div class="col-sm-12 col-md-12  col-xs-12 d-flex">
                        <label class="col-sm-1 form-check-label mandatory mt-3 ">{{ __('Is Private?') }}</label>

                        <div class="form-check form-switch mt-3">

                            <input type="hidden" name="is_premium" id="is_premium" value="0">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_premium_switch">

                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class='col-md-12 d-flex justify-content-end mb-3'>
            <input type="submit" class="btn btn-primary" value="save">
            &nbsp;
            &nbsp;

            <button class="btn btn-secondary" type="button" onclick="myForm.reset();">{{ __('Reset') }}</button>
        </div>
    </div>

    {!! Form::close() !!}
@endsection
@section('script')
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places&key={{ env('PLACE_API_KEY') }}&callback=initMap" async defer></script>
    <script type="text/javascript">
        $(document).ready(function() {
            // $("#category").val($("#category option:first").val()).trigger('change');

            $('#facility').hide();
            $('#duration').hide();
            $('#price_duration').removeAttr('required');

            // Store all categories for filtering
            var allCategories = $('#category option').clone();

            // Filter categories based on selected classification
            $('#property_classification').on('change', function() {
                var selectedClassification = $(this).val();
                console.log("Selected classification:", selectedClassification);

                // Reset categories
                $('#category').empty().append('<option value="" selected>{{ __("Choose Category") }}</option>');

                // If no classification selected, show all categories
                if (!selectedClassification) {
                    $('#category').append(allCategories);
                    return;
                }

                // Use AJAX to get categories by classification
                console.log("Fetching categories for classification:", selectedClassification);
                $.ajax({
                    url: '{{ url("api/get_categories_by_classification") }}',
                    type: 'GET',
                    data: {
                        classification: selectedClassification
                    },
                    success: function(response) {
                        console.log("API response:", response);
                        if (!response.error && response.data && response.data.length > 0) {
                            console.log("Found " + response.data.length + " categories");
                            $.each(response.data, function(index, category) {
                                $('#category').append(
                                    $('<option></option>')
                                        .attr('value', category.id)
                                        .attr('data-parametertypes', category.parameter_types)
                                        .attr('data-classification', category.property_classification)
                                        .text(category.category)
                                );
                            });
                        } else {
                            console.log("No categories found in API response, using fallback");
                            // Fallback to client-side filtering if API returns no data
                            allCategories.each(function() {
                                if ($(this).val() === "" || $(this).data('classification') == selectedClassification) {
                                    $('#category').append($(this).clone());
                                }
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching categories:', error);
                        // Fallback to client-side filtering on error
                        allCategories.each(function() {
                            if ($(this).val() === "" || $(this).data('classification') == selectedClassification) {
                                $('#category').append($(this).clone());
                            }
                        });
                    }
                });
            });

            // Event handler for radio button change
            $('input[name="property_type"]').change(function() {
                // Get the selected value
                var selectedType = $('input[name="property_type"]:checked').val();

                // Perform actions based on the selected value

                if (selectedType == 1) {
                    $('#duration').show();
                    $('#price_duration').val('Monthly');
                    $('#price_duration').attr('required', 'true');
                } else {
                    $('#price_duration').val('');
                    $('#duration').hide();
                    $('#price_duration').removeAttr('required');
                }
            });
        });

        function initMap() {
            // Properly parse latitude and longitude as floats, with fallback values
            let defaultLatitude = parseFloat($("#default-latitude").val()) || 20.593684;
            let defaultLongitude = parseFloat($("#default-longitude").val()) || 78.96288;

            console.log("Map initialization with coordinates:", defaultLatitude, defaultLongitude);

            var map = new google.maps.Map(document.getElementById('map'), {
                center: {
                    lat: defaultLatitude,
                    lng: defaultLongitude
                },
                zoom: 8
            });
            var input = document.getElementById('searchInput');

            var autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', map);

            var infowindow = new google.maps.InfoWindow();
            var marker = new google.maps.Marker({
                draggable: true,
                position: {
                    lat: defaultLatitude,
                    lng: defaultLongitude
                },
                map: map,
                anchorPoint: new google.maps.Point(0, -29)
            });
            google.maps.event.addListener(marker, 'dragend', function(event) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({
                    'latLng': event.latLng
                }, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                        if (results[0]) {
                            var address_components = results[0].address_components;
                            var city, state, country, full_address;

                            for (var i = 0; i < address_components.length; i++) {
                                var types = address_components[i].types;
                                if (types.indexOf('locality') != -1) {
                                    city = address_components[i].long_name;
                                } else if (types.indexOf('administrative_area_level_1') != -1) {
                                    state = address_components[i].long_name;
                                } else if (types.indexOf('country') != -1) {
                                    country = address_components[i].long_name;
                                }
                            }

                            full_address = results[0].formatted_address;

                            // Do something with the city, state, country, and full address
                            $('#searchInput').val(city);
                            $('#city').val(city);
                            $('#country').val(country);
                            $('#state').val(state);
                            $('#address').val(full_address);
                            $('#latitude').val(event.latLng.lat());
                            $('#longitude').val(event.latLng.lng());

                        } else {
                            console.log('No results found');
                        }
                    } else {
                        console.log('Geocoder failed due to: ' + status);
                    }
                });
            });
            autocomplete.addListener('place_changed', function() {
                infowindow.close();
                marker.setVisible(false);
                var place = autocomplete.getPlace();
                if (!place.geometry) {
                    window.alert("Autocomplete's returned place contains no geometry");
                    return;
                }


                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }
                marker.setIcon(({
                    url: place.icon,
                    size: new google.maps.Size(71, 71),
                    origin: new google.maps.Point(0, 0),
                    anchor: new google.maps.Point(17, 34),
                    scaledSize: new google.maps.Size(35, 35)
                }));
                marker.setPosition(place.geometry.location);
                marker.setVisible(true);

                var address = '';
                if (place.address_components) {
                    address = [
                        (place.address_components[0] && place.address_components[0].short_name || ''),
                        (place.address_components[1] && place.address_components[1].short_name || ''),
                        (place.address_components[2] && place.address_components[2].short_name || '')
                    ].join(' ');
                }

                infowindow.setContent('<div><strong>' + place.name + '</strong><br>' + address);
                infowindow.open(map, marker);

                // Location details
                for (var i = 0; i < place.address_components.length; i++) {

                    if (place.address_components[i].types[0] == 'locality') {
                        $('#city').val(place.address_components[i].long_name);


                    }
                    if (place.address_components[i].types[0] == 'country') {
                        $('#country').val(place.address_components[i].long_name);


                    }
                    if (place.address_components[i].types[0] == 'administrative_area_level_1') {
                        $('#state').val(place.address_components[i].long_name);


                    }
                }

                var latitude = place.geometry.location.lat();
                var longitude = place.geometry.location.lng();
                $('#address').val(place.formatted_address);


                $('#latitude').val(place.geometry.location.lat());
                $('#longitude').val(place.geometry.location.lng());

            });
        }

        // Remove duplicate initMap call and iframe append
        $(document).ready(function() {
            // Don't add the iframe here - it's causing conflicts with the Google Maps API
            // The map will be initialized by the callback in the script tag

            $('.select2').prepend('<option value="" selected></option>');
            $facility = $.parseJSON($('#facilities').val());

            $.each($facility, function(key, value) {

                $('#dist' + value.id).hide();
                $('#chk' + value.id).on('click', function() {

                    if ($('#chk' + value.id).is(':checked')) {
                        $('#dist' + value.id).show();

                    } else {
                        $('#dist' + value.id).hide();

                    }
                });
            });

            // Add back the is_premium_switch functionality
            $("#is_premium_switch").on('change', function() {
                $("#is_premium_switch").is(':checked') ? $("#is_premium").val(1) : $("#is_premium").val(0);
            });

            getWordCount("meta_title", "meta_title_count", "19.9px arial");
            getWordCount("meta_description", "meta_description_count", "12.9px arial");
        });
        $(document).ready(function() {

            FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateSize,
                FilePondPluginFileValidateType);

            $('#meta_image').filepond({
                credits: null,
                allowFileSizeValidation: "true",
                maxFileSize: '5000KB',
                labelMaxFileSizeExceeded: 'File is too large',
                labelMaxFileSize: 'Maximum file size is {filesize}',
                allowFileTypeValidation: true,
                acceptedFileTypes: ['image/*'],
                labelFileTypeNotAllowed: 'File of invalid type',
                fileValidateTypeLabelExpectedTypes: 'Expects {allButLastType} or {lastType}',
                storeAsFile: true,
            });
        });

        $("#title").on('keyup',function(e){
            let title = $(this).val();
            let slugElement = $("#slug");
            if(title){
                $.ajax({
                    type: 'POST',
                    url: "{{ route('property.generate-slug') }}",
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        title: title
                    },
                    beforeSend: function() {
                        slugElement.attr('readonly', true).val('Please wait....')
                    },
                    success: function(response) {
                        if(!response.error){
                            if(response.data){
                                slugElement.removeAttr('readonly').val(response.data);
                            }else{
                                slugElement.removeAttr('readonly').val("")
                            }
                        }
                    }
                });
            }else{
                slugElement.removeAttr('readonly', true).val("")
            }
        });

        // Handle property classification change
        function handlePropertyClassification() {
            var propertyClassification = $('#property_classification').val();

            // Hide all specific fields first
            $('.vacation-home-fields').hide();
            $('.hotel-fields').hide();
            $('.hotel-rooms').hide();

            // Show fields based on classification
            if (propertyClassification == 4) { // Vacation Homes
                $('.vacation-home-fields').show();
            } else if (propertyClassification == 5) { // Hotel Booking
                $('.hotel-fields').show();
                $('.hotel-rooms').show();
                $('.policy-data-field').hide(); // Hide policy data for hotels
                $('.price-field').hide(); // Hide price for hotels
                $('#price').removeAttr('required');
            } else {
                $('.policy-data-field').show(); // Show policy data for non-hotels
                $('.price-field').show(); // Show price for non-hotels
                $('#price').attr('required', 'true');
            }
        }

        // Call when classification changes
        $('#property_classification').on('change', function() {
            handlePropertyClassification();
        });

        // Call on page load
        $(document).ready(function() {
            handlePropertyClassification();

            // If classification is already selected, trigger the change event to load categories
            var initialClassification = $('#property_classification').val();
            if (initialClassification) {
                $('#property_classification').trigger('change');
            }
        });

        // Room management
        var roomIndex = 0;
        var packageIndex = 0;
        var certificateIndex = 0;

        // Add new room
        $('#add-room-btn').on('click', function() {
            var newRow = `
                <tr class="room-row">
                    <td>
                        <input type="text" class="form-control" name="hotel_rooms[${roomIndex}][room_number]" required>
                    </td>
                    <td>
                        <select class="form-control" name="hotel_rooms[${roomIndex}][room_type_id]" required>
                            @foreach(App\Models\HotelRoomType::where('status', 1)->get() as $roomType)
                                <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][price_per_night]" min="0" step="0.01" required>
                    </td>
                    <td>
                        <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][discount_percentage]" value="0" min="0" max="100" step="0.01">
                    </td>
                    <td>
                        <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][nonrefundable_percentage]" value="0" min="0" max="100" step="0.01">
                    </td>
                    <td>
                        <select class="form-control" name="hotel_rooms[${roomIndex}][refund_policy]">
                            <option value="flexible">{{ __('Flexible') }}</option>
                            <option value="non-refundable">{{ __('Non-Refundable') }}</option>
                        </select>
                    </td>
                    <td>
                        <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][weekend_commission]" value="" min="0" max="100" step="0.01">
                    </td>
                    <td>
                        <select class="form-control availability-type-select" data-index="${roomIndex}">
                            <option value="">{{ __('None') }}</option>
                            <option value="1">{{ __('Available Days') }}</option>
                            <option value="2">{{ __('Busy Days') }}</option>
                        </select>
                        <input type="hidden" name="hotel_rooms[${roomIndex}][availability_type]" class="availability-type-value">
                        <input type="hidden" name="hotel_rooms[${roomIndex}][available_dates]" value="[]" class="available-dates-value">
                        <button type="button" class="btn btn-sm btn-info mt-2 select-dates-btn" data-index="${roomIndex}" style="display:none;">
                            <i class="bi bi-calendar"></i> {{ __('Select Dates') }}
                        </button>
                    </td>
                    <td>
                        <textarea class="form-control" name="hotel_rooms[${roomIndex}][description]" rows="2"></textarea>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm remove-room">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#rooms-container').append(newRow);
            roomIndex++;
        });

        // Remove room
        $(document).on('click', '.remove-room', function() {
            $(this).closest('tr').remove();
        });

        // Add new addon package
        $('#add-package-btn').on('click', function() {
            var packageHtml = `
                <div class="card mb-3 addon-package" data-index="${packageIndex}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>{{ __('Package') }} #${packageIndex + 1}</h5>
                        <button type="button" class="btn btn-danger btn-sm remove-package">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Package Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="addons_packages[${packageIndex}][name]" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Room Type') }}</label>
                                    <select class="form-control" name="addons_packages[${packageIndex}][room_type_id]">
                                        <option value="">{{ __('Select Room Type') }}</option>
                                        @foreach(App\Models\HotelRoomType::where('status', 1)->get() as $roomType)
                                            <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Description') }}</label>
                                    <textarea class="form-control" name="addons_packages[${packageIndex}][description]" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ __('Price') }}</label>
                                    <input type="number" class="form-control" name="addons_packages[${packageIndex}][price]" min="0" step="0.01">
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <select class="form-control" name="addons_packages[${packageIndex}][status]">
                                        <option value="active">{{ __('Active') }}</option>
                                        <option value="inactive">{{ __('Inactive') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h6>{{ __('Addon Values') }}</h6>
                            <div class="addon-values-container" data-package-index="${packageIndex}">
                                <button type="button" class="btn btn-sm btn-primary add-addon-value" data-package-index="${packageIndex}">
                                    <i class="bi bi-plus"></i> {{ __('Add Addon Value') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#packages-container').append(packageHtml);
            packageIndex++;
        });

        // Remove addon package
        $(document).on('click', '.remove-package', function() {
            $(this).closest('.addon-package').remove();
        });

        // Add addon value to package
        $(document).on('click', '.add-addon-value', function() {
            var packageIndex = $(this).data('package-index');
            var addonValueIndex = $(this).closest('.addon-values-container').find('.addon-value-row').length;

            var addonValueHtml = `
                <div class="row mt-2 addon-value-row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('Addon Field') }} <span class="text-danger">*</span></label>
                                                                        <select class="form-control" name="addons_packages[${packageIndex}][addon_values][${addonValueIndex}][hotel_addon_field_id]" required>
                                                <option value="">{{ __('Select Addon Field') }}</option>
                                                @foreach(App\Models\HotelAddonField::where('status', 'active')->get() as $addonField)
                                                    <option value="{{ $addonField->id }}">{{ $addonField->name }}</option>
                                                @endforeach
                                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('Value') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="addons_packages[${packageIndex}][addon_values][${addonValueIndex}][value]" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('Static Price') }}</label>
                            <input type="number" class="form-control" name="addons_packages[${packageIndex}][addon_values][${addonValueIndex}][static_price]" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-addon-value">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            $(this).before(addonValueHtml);
        });

        // Remove addon value
        $(document).on('click', '.remove-addon-value', function() {
            $(this).closest('.addon-value-row').remove();
        });

        // Add certificate
        $('#add-certificate-btn').on('click', function() {
            var certificateHtml = `
                <div class="row mt-2 certificate-row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('Title') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="certificates[${certificateIndex}][title]" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('Description') }}</label>
                            <textarea class="form-control" name="certificates[${certificateIndex}][description]" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('File') }} <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="certificates[${certificateIndex}][file]" required>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-certificate">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `;

            $('#certificates-container').append(certificateHtml);
            certificateIndex++;
        });

        // Remove certificate
        $(document).on('click', '.remove-certificate', function() {
            $(this).closest('.certificate-row').remove();
        });

        // Handle availability type change for hotel rooms
        $(document).on('change', '.availability-type-select', function() {
            var index = $(this).data('index');
            var value = $(this).val();

            $(this).siblings('.availability-type-value').val(value);

            if (value === '1' || value === '2') {
                $(this).siblings('.select-dates-btn').show();
            } else {
                $(this).siblings('.select-dates-btn').hide();
            }
        });

        // Handle select dates button click
        $(document).on('click', '.select-dates-btn', function() {
            var index = $(this).data('index');
            // Here you would open a date picker or calendar modal
            // For now, we'll just set a sample date range
            var sampleDates = JSON.stringify([
                {
                    from: '2023-01-01',
                    to: '2023-01-15'
                }
            ]);
            $(this).siblings('.available-dates-value').val(sampleDates);
            alert('Date selection would open here. A sample date range has been set.');
        });

        // Initialize availability calendar for vacation homes
        function initAvailabilityCalendar() {
            // This would be implemented with a date picker library like flatpickr
            // For now, we'll just set a sample date range
            $('#available-dates-json').val(JSON.stringify([
                {
                    from: '2023-01-01',
                    to: '2023-01-15'
                }
            ]));
            $('#availability-calendar').html('<div class="alert alert-info">Calendar would be displayed here. A sample date range has been set.</div>');
        }

        // Handle property classification change
        $('#property_classification').on('change', function() {
            var classification = $(this).val();

            // Hide all classification-specific fields first
            $('.vacation-fields, .hotel-fields').hide();

            // Show fields based on classification
            if (classification == 4) { // Vacation Homes
                $('.vacation-fields').show();
                initAvailabilityCalendar();
                $('.policy-data-field').show();
                $('.price-field').show();
                $('#price').attr('required', 'true');
            } else if (classification == 5) { // Hotel Booking
                $('.hotel-fields').show();
                $('.policy-data-field').hide(); // Hide policy data for hotels
                $('.price-field').hide(); // Hide price for hotels
                $('#price').removeAttr('required');
            } else {
                $('.policy-data-field').show(); // Show policy data for non-hotels
                $('.price-field').show(); // Show price for non-hotels
                $('#price').attr('required', 'true');
            }
        });

        // Validate YouTube URL
        $('#video_link').on('blur', function() {
            var url = $(this).val();
            if (url) {
                var youtubePattern = /^(https?\:\/\/)?(www\.youtube\.com|youtu\.be)\/.+$/;
                if (!youtubePattern.test(url)) {
                    alert('Please enter a valid YouTube URL');
                    $(this).val('');
                }
            }
        });

        // Corresponding Day Management
        var correspondingDays = [];

        // Add corresponding day
        $('.add-corresponding-day').on('click', function() {
            var day = $('.day-select').val();
            var fromTime = $('.from-time').val();
            var toTime = $('.to-time').val();

            if (!fromTime || !toTime) {
                alert('Please select both from and to times');
                return;
            }

            // Convert 24-hour format to 12-hour format
            var fromTime12 = convertTo12HourFormat(fromTime);
            var toTime12 = convertTo12HourFormat(toTime);

            var dayData = {
                from: fromTime12,
                to: toTime12,
                day: day
            };

            // Check if day already exists
            var existingIndex = correspondingDays.findIndex(function(item) {
                return item.day === day;
            });

            if (existingIndex !== -1) {
                correspondingDays[existingIndex] = dayData;
            } else {
                correspondingDays.push(dayData);
            }

            updateCorrespondingDayList();
            updateCorrespondingDayJson();
        });

        // Remove corresponding day
        $(document).on('click', '.remove-corresponding-day', function() {
            var day = $(this).data('day');
            correspondingDays = correspondingDays.filter(function(item) {
                return item.day !== day;
            });
            updateCorrespondingDayList();
            updateCorrespondingDayJson();
        });

        // Update the display list
        function updateCorrespondingDayList() {
            var html = '';
            correspondingDays.forEach(function(dayData) {
                html += `
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <span><strong>${dayData.day.charAt(0).toUpperCase() + dayData.day.slice(1)}</strong>: ${dayData.from} - ${dayData.to}</span>
                        <button type="button" class="btn btn-danger btn-sm remove-corresponding-day" data-day="${dayData.day}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
            });
            $('.corresponding-day-list').html(html);
        }

        // Update the hidden JSON field
        function updateCorrespondingDayJson() {
            $('#corresponding_day_json').val(JSON.stringify(correspondingDays));
        }

        // Convert 24-hour format to 12-hour format
        function convertTo12HourFormat(time24) {
            var time = time24.split(':');
            var hours = parseInt(time[0]);
            var minutes = time[1];
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            return hours.toString().padStart(2, '0') + ':' + minutes + ampm;
        }

        // Validate JSON format for agent_addons field
        $('#agent_addons_field').on('blur', function() {
            var value = $(this).val().trim();
            if (value) {
                try {
                    JSON.parse(value);
                    $(this).removeClass('is-invalid').addClass('is-valid');
                    $('#agent_addons_error').text('');
                } catch (e) {
                    $(this).removeClass('is-valid').addClass('is-invalid');
                    $('#agent_addons_error').text('Invalid JSON format. Please check your syntax.');
                }
            } else {
                $(this).removeClass('is-invalid is-valid');
                $('#agent_addons_error').text('');
            }
        });

        // Form validation before submit
        $('#myForm').on('submit', function(e) {
            var agentAddonsValue = $('#agent_addons_field').val().trim();
            if (agentAddonsValue) {
                try {
                    JSON.parse(agentAddonsValue);
                } catch (e) {
                    e.preventDefault();
                    $('#agent_addons_field').addClass('is-invalid');
                    $('#agent_addons_error').text('Invalid JSON format. Please fix the syntax before submitting.');
                    $('#agent_addons_field').focus();
                    return false;
                }
            }
        });

        // Trigger change event on page load to set initial state
        $('#property_classification').trigger('change');
    </script>
@endsection
