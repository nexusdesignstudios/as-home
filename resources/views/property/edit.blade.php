@extends('layouts.main')
@section('title')
    {{ __('Update Property') }}
@endsection

@section('css')
<style>
    /* Enhanced document name display styling */
    .document-name-display {
        padding: 0.5rem;
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        border-left: 3px solid #0d6efd;
        margin-top: 0.5rem;
    }
    
    .document-name-display small {
        font-size: 0.875rem;
        line-height: 1.5;
        display: block;
    }
    
    .document-name-display strong {
        color: #495057;
        font-weight: 600;
    }
    
    .document-name-display i {
        color: #0d6efd;
        font-size: 1rem;
    }
    
    .document-name-display:hover {
        background-color: #e9ecef;
        transition: background-color 0.2s ease;
    }
    
    .text-truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
    }
    
    /* Fix FilePond label text display - ensure HTML renders properly */
    .filepond--label-action {
        text-decoration: underline;
        color: #0d6efd;
        cursor: pointer;
        font-weight: 600;
    }
    
    .filepond--root .filepond--label {
        font-size: 0.875rem;
        color: #6c757d;
        text-align: center;
    }
    
    /* Document Preview Modal Styles */
    .document-preview-modal .modal-dialog {
        max-width: 90%;
        height: 90vh;
    }

    .document-preview-modal .modal-content {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .document-preview-modal .modal-body {
        flex: 1;
        overflow: auto;
        padding: 0;
    }

    .document-preview-iframe {
        width: 100%;
        height: 100%;
        min-height: 600px;
        border: none;
    }

    .document-preview-image {
        max-width: 100%;
        height: auto;
        display: block;
        margin: 0 auto;
    }

    .document-preview-unsupported {
        padding: 2rem;
        text-align: center;
        color: #6c757d;
    }

    .document-preview-unsupported i {
        font-size: 4rem;
        margin-bottom: 1rem;
        color: #dee2e6;
    }
</style>
@endsection

<script src="https://unpkg.com/filepond/dist/filepond.js"></script>
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
                            {{ __('Update') }}
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
@endsection
@section('content')
    {!! Form::open([
        'route' => ['property.update', $id],
        'method' => 'PATCH',
        'data-parsley-validate',
        'files' => true,
        'id' => 'myForm',
    ]) !!}

    <div class='row'>
        <div class='col-md-6'>

            <div class="card">

                <h3 class="card-header">{{ __('Details') }}</h3>
                <hr>

                {{-- Category --}}
                <div class="card-body">
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('category', __('Category'), ['class' => 'form-label col-12 ']) }}
                        <select name="category" class="choosen-select form-select form-control-sm" data-parsley-minSelect='1' id="category">
                            <option value="">{{ __('Choose Category') }}</option>
                            @foreach ($category as $row)
                                <option value="{{ $row->id }}"
                                    {{ $list->category_id == $row->id ? ' selected=selected' : '' }}
                                    data-parametertypes='{{ $row->parameter_types }}'> {{ $row->category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Title --}}
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('title', __('Title'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('title', isset($list->title) ? $list->title : '', ['class' => 'form-control ', 'placeholder' => __('Title'), 'id' => 'title']) }}
                    </div>

                    {{-- Title Arabic --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('title_ar', __('Title (Arabic)'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('title_ar', isset($list->title_ar) ? $list->title_ar : '', ['class' => 'form-control ', 'placeholder' => __('Title in Arabic'), 'id' => 'title_ar']) }}
                    </div>

                    {{-- Slug --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('slug', __('Slug'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('slug', isset($list->slug_id) ? $list->slug_id : '', [ 'class' => 'form-control ', 'placeholder' =>  __('Slug'), 'id' => 'slug', ]) }}
                        <small class="text-danger text-sm">{{ __("Only Small English Characters, Numbers And Hypens Allowed") }}</small>
                    </div>

                    {{-- Description --}}
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('description', __('Description'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('description', isset($list->description) ? $list->description : '', ['class' => 'form-control mb-3', 'rows' => '3', 'id' => '', 'placeholder' => __('Description')]) }}
                    </div>

                    {{-- Description Arabic --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('description_ar', __('Description (Arabic)'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('description_ar', isset($list->description_ar) ? $list->description_ar : '', ['class' => 'form-control mb-3', 'rows' => '3', 'id' => 'description_ar', 'placeholder' => __('Description in Arabic')]) }}
                    </div>

                    {{-- Area Description --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('area_description', __('Area Description'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('area_description', isset($list->area_description) ? $list->area_description : '', ['class' => 'form-control mb-3', 'rows' => '3', 'id' => 'area_description', 'placeholder' => __('Area Description')]) }}
                    </div>

                    {{-- Area Description Arabic --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('area_description_ar', __('Area Description (Arabic)'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('area_description_ar', isset($list->area_description_ar) ? $list->area_description_ar : '', ['class' => 'form-control mb-3', 'rows' => '3', 'id' => 'area_description_ar', 'placeholder' => __('Area Description in Arabic')]) }}
                    </div>

                    {{-- Company Employee Information --}}
                    <div class="col-md-12 col-12 form-group">
                        <h6 class="form-label col-12">{{ __('Company Employee Information') }}</h6>
                    </div>

                    {{-- Company Employee Username --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('company_employee_username', __('Company Employee Username'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('company_employee_username', isset($list->company_employee_username) ? $list->company_employee_username : '', ['class' => 'form-control ', 'placeholder' =>  __('Company Employee Username'), 'id' => 'company_employee_username', ]) }}
                    </div>

                    {{-- Company Employee Email --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('company_employee_email', __('Company Employee Email'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::email('company_employee_email', isset($list->company_employee_email) ? $list->company_employee_email : '', ['class' => 'form-control ', 'placeholder' =>  __('Company Employee Email'), 'id' => 'company_employee_email', ]) }}
                    </div>

                    {{-- Company Employee Phone Number --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('company_employee_phone_number', __('Company Employee Phone Number'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('company_employee_phone_number', isset($list->company_employee_phone_number) ? $list->company_employee_phone_number : '', ['class' => 'form-control ', 'placeholder' =>  __('Company Employee Phone Number'), 'id' => 'company_employee_phone_number', ]) }}
                    </div>

                    {{-- Property Type --}}
                    <div class="col-md-12 col-12  form-group  mandatory">
                        <div class="row">
                            {{ Form::label('', __('Property Type'), ['class' => 'form-label col-12 ']) }}

                            {{-- For Sell --}}
                            <div class="col-md-6">
                                {{ Form::radio('property_type', 0, null, ['class' => 'form-check-input', 'id' => 'property_type', isset($list->propery_type) && $list->getRawOriginal('propery_type') == 0 ? 'checked' : '']) }}
                                {{ Form::label('property_type', __('For Sell'), ['class' => 'form-check-label']) }}
                            </div>

                            {{-- For Rent --}}
                            <div class="col-md-6">
                                {{ Form::radio('property_type', 1, null, ['class' => 'form-check-input', 'id' => 'property_type', isset($list->propery_type) && $list->getRawOriginal('propery_type') == 1 ? 'checked' : '']) }}
                                {{ Form::label('property_type', __('For Rent'), ['class' => 'form-check-label']) }}
                            </div>
                        </div>
                    </div>

                    {{-- Property Classification --}}
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('property_classification', __('Property Classification'), ['class' => 'form-label col-12 ']) }}
                        <select name="property_classification" id="property_classification" class="form-select form-control-sm" data-parsley-minSelect='1'>
                            <option value="">{{ __('Choose Classification') }}</option>
                            <option value="1" {{ $list->getRawOriginal('property_classification') == 1 ? 'selected' : '' }}>{{ __('Sell/Long Term Rent') }}</option>
                            <option value="2" {{ $list->getRawOriginal('property_classification') == 2 ? 'selected' : '' }}>{{ __('Commercial') }}</option>
                            <option value="3" {{ $list->getRawOriginal('property_classification') == 3 ? 'selected' : '' }}>{{ __('New Project') }}</option>
                            <option value="4" {{ $list->getRawOriginal('property_classification') == 4 ? 'selected' : '' }}>{{ __('Vacation Homes') }}</option>
                            <option value="5" {{ $list->getRawOriginal('property_classification') == 5 ? 'selected' : '' }}>{{ __('Hotel Booking') }}</option>
                        </select>
                    </div>

                    {{-- Hotel Specific Fields --}}
                    <div class="col-md-12 hotel-fields" style="display: none;">
                        <div class="form-group mandatory">
                            {{ Form::label('refund_policy_type', __('Refund Policy'), ['class' => 'form-label col-12']) }}
                            @php
                                $currentPolicy = isset($list->refund_policy) ? $list->refund_policy : 'flexible';
                                $refundPolicyType = 'both';
                                if ($currentPolicy == 'non-refundable') {
                                    $refundPolicyType = 'non-refundable';
                                } elseif ($currentPolicy == 'flexible') {
                                     $refundPolicyType = 'both';
                                }
                            @endphp
                            <select name="refund_policy_type" id="refund_policy_type" class="form-select form-control-sm">
                                <option value="both" {{ $refundPolicyType == 'both' ? 'selected' : '' }}>{{ __('Both (Flexible & Non-Refundable)') }}</option>
                                <option value="flexible" {{ $refundPolicyType == 'flexible' ? 'selected' : '' }}>{{ __('Flexible Booking') }}</option>
                                <option value="non-refundable" {{ $refundPolicyType == 'non-refundable' ? 'selected' : '' }}>{{ __('Non-Refundable') }}</option>
                            </select>
                            <input type="hidden" name="refund_policy" id="refund_policy_input" value="{{ $currentPolicy }}">
                        </div>
                        <div class="form-group">
                            {{ Form::label('hotel_apartment_type_id', __('Hotel Apartment Type'), ['class' => 'form-label col-12']) }}
                            {{ Form::select('hotel_apartment_type_id', App\Models\HotelApartmentType::pluck('name', 'id'), isset($list->hotel_apartment_type_id) ? $list->hotel_apartment_type_id : null, ['class' => 'form-control select2', 'placeholder' => __('Select Apartment Type')]) }}
                        </div>
                        <div class="form-group">
                            {{ Form::label('check_in', __('Check-in Time'), ['class' => 'form-label col-12']) }}
                            {{ Form::time('check_in', isset($list->check_in) ? $list->check_in : null, ['class' => 'form-control']) }}
                        </div>
                        <div class="form-group">
                            {{ Form::label('check_out', __('Check-out Time'), ['class' => 'form-label col-12']) }}
                            {{ Form::time('check_out', isset($list->check_out) ? $list->check_out : null, ['class' => 'form-control']) }}
                        </div>
                        <div class="form-group">
                            {{ Form::label('available_rooms', __('Total Available Rooms'), ['class' => 'form-label col-12']) }}
                            {{ Form::number('available_rooms', isset($list->available_rooms) ? $list->available_rooms : null, ['class' => 'form-control', 'min' => '0']) }}
                        </div>

                        {{-- Revenue Information --}}
                        <div class="form-group">
                            <h6 class="form-label col-12">{{ __('Revenue Information') }}</h6>
                        </div>
                        <div class="form-group">
                            {{ Form::label('revenue_user_name', __('Revenue User Name'), ['class' => 'form-label col-12']) }}
                            {{ Form::text('revenue_user_name', isset($list->revenue_user_name) ? $list->revenue_user_name : null, ['class' => 'form-control', 'placeholder' => __('Revenue User Name')]) }}
                        </div>
                        <div class="form-group">
                            {{ Form::label('revenue_phone_number', __('Revenue Phone Number'), ['class' => 'form-label col-12']) }}
                            {{ Form::text('revenue_phone_number', isset($list->revenue_phone_number) ? $list->revenue_phone_number : null, ['class' => 'form-control', 'placeholder' => __('Revenue Phone Number')]) }}
                        </div>
                        <div class="form-group">
                            {{ Form::label('revenue_email', __('Revenue Email'), ['class' => 'form-label col-12']) }}
                            {{ Form::email('revenue_email', isset($list->revenue_email) ? $list->revenue_email : null, ['class' => 'form-control', 'placeholder' => __('Revenue Email')]) }}
                        </div>

                        {{-- Reservation Information --}}
                        <div class="form-group">
                            <h6 class="form-label col-12">{{ __('Reservation Information') }}</h6>
                        </div>
                        <div class="form-group">
                            {{ Form::label('reservation_user_name', __('Reservation User Name'), ['class' => 'form-label col-12']) }}
                            {{ Form::text('reservation_user_name', isset($list->reservation_user_name) ? $list->reservation_user_name : null, ['class' => 'form-control', 'placeholder' => __('Reservation User Name')]) }}
                        </div>
                        <div class="form-group">
                            {{ Form::label('reservation_phone_number', __('Reservation Phone Number'), ['class' => 'form-label col-12']) }}
                            {{ Form::text('reservation_phone_number', isset($list->reservation_phone_number) ? $list->reservation_phone_number : null, ['class' => 'form-control', 'placeholder' => __('Reservation Phone Number')]) }}
                        </div>
                        <div class="form-group">
                            {{ Form::label('reservation_email', __('Reservation Email'), ['class' => 'form-label col-12']) }}
                            {{ Form::email('reservation_email', isset($list->reservation_email) ? $list->reservation_email : null, ['class' => 'form-control', 'placeholder' => __('Reservation Email')]) }}
                        </div>

                        {{-- Rent Package --}}
                        <div class="form-group">
                            {{ Form::label('rent_package', __('Rent Package'), ['class' => 'form-label col-12']) }}
                            {{ Form::select('rent_package', ['basic' => __('Basic'), 'premium' => __('Premium')], isset($list->rent_package) ? $list->rent_package : null, ['class' => 'form-control select2', 'placeholder' => __('Select Rent Package')]) }}
                        </div>
                        
                        {{-- Hotel VAT --}}
                        <div class="form-group">
                            {{ Form::label('hotel_vat', __('Hotel VAT (%)'), ['class' => 'form-label col-12 ']) }}
                            {{ Form::number('hotel_vat', isset($list->hotel_vat) ? $list->hotel_vat : '', ['class' => 'form-control', 'placeholder' => __('Hotel VAT'), 'min' => '0', 'max' => '100', 'step' => '0.01', 'id' => 'hotel_vat']) }}
                        </div>
                    </div>


                    {{-- Price --}}
                    <div class="control-label col-12 form-group mandatory price-field">
                        {{ Form::label('price', __('Price') . '(' . $currency_symbol . ')', ['class' => 'form-label col-12 ']) }}
                        {{ Form::number('price', isset($list->price) ? $list->price : '', ['class' => 'form-control ', 'placeholder' => __('Price'), 'min' => '1', 'max' => '9223372036854775807', 'id' => 'price']) }}
                    </div>

                    {{-- Weekend Commission --}}
                    <div class="control-label col-12 form-group price-field">
                        {{ Form::label('weekend_commission', __('Weekend Commission (%)'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::number('weekend_commission', isset($list->weekend_commission) ? $list->weekend_commission : '', ['class' => 'form-control', 'placeholder' => __('Weekend Commission'), 'min' => '0', 'max' => '100', 'step' => '0.01', 'id' => 'weekend_commission']) }}
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
                            {{ Form::hidden('corresponding_day', isset($list->corresponding_day) ? (is_string($list->corresponding_day) ? $list->corresponding_day : json_encode($list->corresponding_day)) : '', ['id' => 'corresponding_day_json']) }}
                        </div>
                        <small class="text-muted">{{ __('Format: [{"from": "10:00AM", "to": "02:00PM", "day": "saturday"}]') }}</small>
                    </div>

                    {{-- Agent Addons --}}
                    <div class="form-group">
                        {{ Form::label('agent_addons', __('Agent Addons'), ['class' => 'form-label col-12']) }}
                        {{ Form::textarea('agent_addons', isset($list->agent_addons) ? (is_string($list->agent_addons) ? $list->agent_addons : json_encode($list->agent_addons, JSON_PRETTY_PRINT)) : null, ['class' => 'form-control', 'rows' => '5', 'placeholder' => __('Agent Addons (JSON format)'), 'id' => 'agent_addons_field']) }}
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

                    {{-- Meta Title --}}
                    <div class="col-md-6 col-sm-12 form-group">
                        {{ Form::label('title', __('Meta Title'), ['class' => 'form-label text-center']) }}
                        <textarea id="edit_meta_title" name="edit_meta_title" class="form-control" oninput="getWordCount('edit_meta_title','edit_meta_title_count','12.9px arial')" rows="2" style="height: 75px" placeholder="{{ __('Meta Title') }}">{{ $list->meta_title }}</textarea>
                        <br>
                        <h6 id="edit_meta_title_count">0</h6>
                    </div>

                    {{-- Meta Image --}}
                    <div class="col-md-6 col-sm-12 form-group card">
                        {{ Form::label('title', __('Meta Image'), ['class' => 'form-label text-center']) }}
                        <input type="file" name="meta_image" id="meta_image">

                        {{-- Meta Image Show --}}
                        @if($list->meta_image != "")
                            <div class="col-md-2 col-sm-12 text-center">
                                <img src="{{ $list->meta_image }}" alt="" height="100px" width="100px">
                            </div>
                        @endif
                    </div>

                    {{-- Meta Description --}}
                    <div class="col-md-12 col-sm-12 form-group">
                        {{ Form::label('description', __('Meta Description'), ['class' => 'form-label text-center']) }}
                        <textarea id="edit_meta_description" name="edit_meta_description" class="form-control" oninput="getWordCount('edit_meta_description','edit_meta_description_count','12.9px arial')" rows="3" placeholder="{{ __('Meta Description') }}">{{ $list->meta_description }}</textarea>
                        <br>
                        <h6 id="edit_meta_description_count">0</h6>
                    </div>

                    {{-- Meta Keywords --}}
                    <div class="col-md-12 col-sm-12 form-group">
                        {{ Form::label('keywords', __('Meta Keywords'), ['class' => 'form-label']) }}
                        <textarea name="Keywords" id="" class="form-control" rows="3" placeholder="{{ __('Meta Keywords') }}">{{ $list->meta_keywords }}</textarea>
                        ({{ __('Add Comma Separated Keywords') }})
                    </div>
                </div>

            </div>
        </div>

        {{-- Outdoor Facility --}}
        <div class="col-md-12" id="outdoor_facility">
            <div class="card">
                <h3 class="card-header">{{ __('Near By Places') }}</h3>
                <hr>
                <div class="card-body">
                    <div class="row">
                        @foreach ($facility as $key => $value)
                            <div class='col-md-3  form-group'>
                                {{ Form::label('description', $value->name, ['class' => 'form-check-label']) }}
                                @if (count($value->assign_facilities))
                                    {{ Form::number('facility' . $value->id, $value->assign_facilities[0]['distance'], ['class' => 'form-control mt-3', 'placeholder' => trans('Distance').' ('.$distanceValue.')', 'id' => 'dist' . $value->id,'min' => 0, 'max' => 99999999.9,'step' => '0.1']) }}
                                @else
                                    {{ Form::number('facility' . $value->id, '', ['class' => 'form-control mt-3', 'placeholder' => trans('Distance').' ('.$distanceValue.')', 'id' => 'dist' . $value->id,'min' => 0, 'max' => 99999999.9 ,'step' => '0.1']) }}
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Facility --}}
        <div class="col-md-12" id="facility">
            <div class="card">
                <h3 class="card-header">{{ __('Facilities') }}</h3>
                <hr>
                {{ Form::hidden('category_count[]', $category, ['id' => 'category_count']) }}
                {{ Form::hidden('parameter_count[]', $parameters, ['id' => 'parameter_count']) }}
                {{ Form::hidden('parameter_add', '', ['id' => 'parameter_add']) }}
                <div id="parameter_type" name=parameter_type class="row card-body">
                    @foreach ($edit_parameters as $res)
                        @if($res->is_required == 1)
                            @if ($res->type_of_parameter == 'file')
                                @if (!empty($res->assigned_parameter->value))
                                @endif
                            @endif
                            <div class="col-md-3 form-group mandatory">
                        @else
                            <div class="col-md-3 form-group">
                        @endif
                            {{ Form::label($res->name, $res->name, ['class' => 'form-label col-12']) }}

                            {{-- DropDown --}}
                            @if ($res->type_of_parameter == 'dropdown')
                                <select name="{{ 'par_' . $res->id }}" class="choosen-select form-select form-control-sm" selected="false">
                                    <option value=""></option>
                                    @foreach ($res->type_values as $key => $value)
                                        <option value="{{ $value }}"
                                            {{ $res->assigned_parameter && $res->assigned_parameter->value == $value ? ' selected=selected' : '' }}>
                                            {{ $value }} </option>
                                    @endforeach
                                </select>
                            @endif

                            {{-- Radio Button --}}
                            @if ($res->type_of_parameter == 'radiobutton')
                                @foreach ($res->type_values as $key => $value)
                                    <input type="radio" name="{{ 'par_' . $res->id }}" id="" value={{ $value }} class="form-check-input" {{ $res->assigned_parameter && $res->assigned_parameter->value == $value ? 'checked' : '' }}>
                                    {{ $value }}
                                @endforeach
                            @endif

                            {{-- Number --}}
                            @if ($res->type_of_parameter == 'number')
                                <input type="number" name="{{ 'par_' . $res->id }}" id="" class="form-control" value="{{ $res->assigned_parameter  && $res->assigned_parameter != 'null' ? $res->assigned_parameter->value : '' }}">
                            @endif

                            {{-- TextBox --}}
                            @if ($res->type_of_parameter == 'textbox')
                                <input type="text" name="{{ 'par_' . $res->id }}" id="" class="form-control" value="{{ $res->assigned_parameter && $res->assigned_parameter->value != 'null' ? $res->assigned_parameter->value : '' }}">
                            @endif

                            {{-- TextArea --}}
                            @if ($res->type_of_parameter == 'textarea')
                                <textarea name="{{ 'par_' . $res->id }}" id="" class="form-control" cols="30" rows="3" value="{{ $res->assigned_parameter && $res->assigned_parameter->value != 'null' ? $res->assigned_parameter->value : '' }}">{{ $res->assigned_parameter && $res->assigned_parameter->value != 'null' ? $res->assigned_parameter->value : '' }}</textarea>
                            @endif

                            {{-- CheckBox --}}
                            @if ($res->type_of_parameter == 'checkbox')
                                @foreach ($res->type_values as $key => $value)
                                    <input type="checkbox" name="{{ 'par_' . $res->id . '[]' }}" id="" class="form-check-input" value={{ $value }} {{ !empty($res->assigned_parameter->value) && in_array($value, $res->assigned_parameter->value) ? 'Checked' : '' }}>{{ $value }}
                                @endforeach
                            @endif

                            {{-- FILE --}}
                            @if ($res->type_of_parameter == 'file')
                                @if (!empty($res->assigned_parameter->value))
                                    <a href="{{ url('') . config('global.IMG_PATH') . config('global.PARAMETER_IMG_PATH') . '/' . $res->assigned_parameter->value }}" class="text-center col-12" style="text-align: center"> Click here to View</a> OR
                                    <input type="file" class='form-control' name="{{ 'par_' . $res->id }}" id='edit_param_img'>
                                @else
                                    <input type="file" class='form-control' name="{{ 'par_' . $res->id }}" id='edit_param_img'>
                                @endif
                                <input type="hidden" name="{{ 'par_' . $res->id }}" value="{{ $res->assigned_parameter ? $res->assigned_parameter->value : '' }}">
                            @endif
                        </div>
                        {{-- @endforeach --}}
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Hotel Rooms Section --}}
        <div class="col-md-12 hotel-rooms" style="display: none;">
            <div class="card">
                <h3 class="card-header d-flex justify-content-between align-items-center">
                    {{ __('Hotel Rooms') }}
                    <button type="button" class="btn btn-primary btn-sm" id="add-room-btn">
                        <i class="bi bi-plus"></i> {{ __('Add Room') }}
                    </button>
                </h3>
                <hr>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="rooms-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Room Number') }}</th>
                                    <th>{{ __('Room Type') }}</th>
                                    <th>{{ __('Price/Night') }}</th>
                                    <th>{{ __('Discount %') }}</th>
                                    <th class="non-ref-col" style="{{ $refundPolicyType == 'both' ? '' : 'display:none;' }}">{{ __('Non-Refundable %') }}</th>
                                    <th>{{ __('Min Guests') }}</th>
                                    <th>{{ __('Max Guests') }}</th>
                                    <th>{{ __('Base Guests') }}</th>
                                    <th>{{ __('Guest Pricing') }}</th>
                                    <th>{{ __('Availability') }}</th>
                                    <th class="ref-policy-col" style="{{ $refundPolicyType == 'both' ? '' : 'display:none;' }}">{{ __('Refund Policy') }}</th>
                                    <th>{{ __('Active') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="rooms-container">
                                @if(isset($list->hotelRooms) && count($list->hotelRooms) > 0)
                                    @foreach($list->hotelRooms as $index => $room)
                                        <tr class="room-row">
                                            <td>
                                                <input type="hidden" name="hotel_rooms[{{ $index }}][id]" value="{{ $room->id }}">
                                                <input type="text" class="form-control" name="hotel_rooms[{{ $index }}][room_number]" value="{{ $room->room_number }}">
                                            </td>
                                            <td>
                                                <select class="form-control room-type-select" name="hotel_rooms[{{ $index }}][room_type_id]" data-index="{{ $index }}">
                                                    @foreach(App\Models\HotelRoomType::where('status', 1)->get() as $roomType)
                                                        <option value="{{ $roomType->id }}" {{ $room->room_type_id == $roomType->id ? 'selected' : '' }}>{{ $roomType->name }}</option>
                                                    @endforeach
                                                    <option value="other">{{ __('Other') }}</option>
                                                </select>
                                                <input type="text" class="form-control mt-2 custom-room-type-input" name="hotel_rooms[{{ $index }}][custom_room_type]" style="display:none;" placeholder="{{ __('Enter Room Type Name') }}">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="hotel_rooms[{{ $index }}][price_per_night]" value="{{ $room->price_per_night }}" min="0" step="0.01">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="hotel_rooms[{{ $index }}][discount_percentage]" value="{{ $room->discount_percentage }}" min="0" max="100" step="0.01">
                                            </td>
                                            <td class="non-ref-col" style="{{ $refundPolicyType == 'both' ? '' : 'display:none;' }}">
                                                <input type="number" class="form-control non-ref-input" name="hotel_rooms[{{ $index }}][nonrefundable_percentage]" value="{{ $room->nonrefundable_percentage }}" min="0" max="100" step="0.01">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="hotel_rooms[{{ $index }}][min_guests]" value="{{ $room->min_guests ?? 1 }}" min="1">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="hotel_rooms[{{ $index }}][max_guests]" value="{{ $room->max_guests ?? 2 }}" min="1">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="hotel_rooms[{{ $index }}][base_guests]" value="{{ $room->base_guests ?? 2 }}" min="1">
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info configure-pricing-btn" data-index="{{ $index }}">
                                                    <i class="bi bi-gear"></i> {{ __('Configure') }}
                                                </button>
                                                <input type="hidden" class="guest-pricing-rules-input" name="hotel_rooms[{{ $index }}][guest_pricing_rules]" value="{{ is_array($room->guest_pricing_rules) ? json_encode($room->guest_pricing_rules) : ($room->guest_pricing_rules ?? '{}') }}">
                                            </td>
                                            <td>
                                                <select class="form-control availability-type-select" data-index="{{ $index }}">
                                                    <option value="">{{ __('None') }}</option>
                                                    <option value="1" {{ $room->availability_type == 1 ? 'selected' : '' }}>{{ __('Available Days') }}</option>
                                                    <option value="2" {{ $room->availability_type == 2 ? 'selected' : '' }}>{{ __('Busy Days') }}</option>
                                                </select>
                                                <input type="hidden" name="hotel_rooms[{{ $index }}][availability_type]" class="availability-type-value" value="{{ $room->availability_type }}">
                                                <input type="hidden" name="hotel_rooms[{{ $index }}][available_dates]" value="{{ json_encode($room->available_dates) }}" class="available-dates-value">
                                                <button type="button" class="btn btn-sm btn-info mt-2 select-dates-btn" data-index="{{ $index }}" style="display:{{ $room->availability_type == 1 || $room->availability_type == 2 ? 'block' : 'none' }};">
                                                    <i class="bi bi-calendar"></i> {{ __('Select Dates') }}
                                                </button>
                                            </td>
                                            <td class="ref-policy-col" style="{{ $refundPolicyType == 'both' ? '' : 'display:none;' }}">
                                                <select class="form-control room-refund-policy" name="hotel_rooms[{{ $index }}][refund_policy]">
                                                    <option value="flexible" {{ $room->refund_policy == 'flexible' ? 'selected' : '' }}>{{ __('Flexible') }}</option>
                                                    <option value="non-refundable" {{ $room->refund_policy == 'non-refundable' ? 'selected' : '' }}>{{ __('Non-Refundable') }}</option>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <input type="hidden" class="room-status-value" name="hotel_rooms[{{ $index }}][status]" value="{{ $room->status ? 1 : 0 }}">
                                                <div class="form-check form-switch d-flex justify-content-center">
                                                    <input class="form-check-input room-status-toggle" type="checkbox" {{ $room->status ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-room" data-room-id="{{ $room->id }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                    <div id="deleted-rooms-container"></div>
                </div>
            </div>
        </div>

        <div class='col-md-12'>

            <div class="card">
                <h3 class="card-header">{{ __('Location') }}</h3>
                <hr>
                <div class="card-body">
                    <div class="row">
                        {{-- Google Map --}}
                        <div class='col-md-6'>
                            {{-- Map View --}}
                            <div class="card col-md-12" id="map" style="height: 90%">
                                <!-- Google map -->
                            </div>
                        </div>

                        {{-- Details of Map --}}
                        <div class='col-md-6'>
                            <div class="row">

                                {{-- City --}}
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('city', __('City'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::text('city', isset($list->city) ? $list->city : '', ['class' => 'form-control controls', 'placeholder' => __('City'), 'id' => 'searchInput']) }}
                                </div>

                                {{-- Country --}}
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('country', __('Country'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::text('country', isset($list->country) ? $list->country : '', ['class' => 'form-control ', 'placeholder' => trans('Country'), 'id' => 'country']) }}
                                </div>

                                {{-- State --}}
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('state', __('State'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::text('state', isset($list->state) ? $list->state : '', ['class' => 'form-control ', 'placeholder' => trans('State'), 'id' => 'state']) }}
                                </div>


                                {{-- Latitude --}}
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('latitude', __('Latitude'), ['class' => 'form-label col-12 ']) }}
                                    {!! Form::text('latitude', isset($list->latitude) ? $list->latitude : '', ['class' => 'form-control ', 'id' => 'latitude', 'step' => 'any', 'readonly' => true, 'placeholder' => trans('Latitude')]) !!}
                                </div>

                                {{-- Longitude --}}
                                <div class="col-md-6 form-group  mandatory">
                                    {{ Form::label('longitude', __('Longitude'), ['class' => 'form-label col-12 ']) }}
                                    {!! Form::text('longitude', isset($list->longitude) ? $list->longitude : '', ['class' => 'form-control ', 'id' => 'longitude', 'step' => 'any', 'readonly' => true, 'placeholder' => trans('Longitude')]) !!}
                                </div>

                                {{-- Client Address --}}
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('Client Address'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('client_address', isset($list->client_address) ? $list->client_address : (system_setting('company_address') ?? ""), ['class' => 'form-control ', 'placeholder' => trans('Client Address'), 'rows' => '4', 'id' => 'client-address', 'autocomplete' => 'off']) }}
                                </div>

                                {{-- Address --}}
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('Address'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('address', isset($list->address) ? $list->address : '', ['class' => 'form-control ', 'placeholder' => trans('Address'), 'rows' => '4', 'id' => 'address', 'autocomplete' => 'off']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Images --}}
        <div class="col-md-12">
            <div class="card">
                <h3 class="card-header">{{ __('Images') }}</h3>
                <hr>
                <div class="card-body">
                    <div class="row">
                        {{-- Title Image --}}
                        <div class="col-md-3 col-sm-12 form-group mandatory card title_card">
                            {{ Form::label('filepond_title', __('Title Image'), ['class' => 'form-label col-12 ']) }}
                            <input type="file" class="filepond" id="filepond_title" name="title_image" accept="image/png,image/jpg,image/jpeg">
                            @if ($list->title_image)
                                <div class="card1 title_img mt-2">
                                    <img src="{{ $list->title_image }}" alt="Image" class="card1-img">
                                </div>
                            @endif
                        </div>

                        {{-- 3D Image --}}
                        <div class="col-md-3 col-sm-12 card">
                            {{ Form::label('filepond_3d', __('3D Image'), ['class' => 'form-label col-12 ']) }}
                            <input type="file" class="filepond" id="filepond_3d" name="3d_image">
                            @if ($list->three_d_image)
                                <div class="card1 3d_img">
                                    <img src="{{ $list->three_d_image }}" alt="Image" class="card1-img" id="3d_img">
                                    <button data-id="{{ $list->id }}" data-url="{{ route('property.remove-threeD-image',$list->id) }}" class="RemoveBtn1 removeThreeDImage">x</button>
                                </div>
                            @endif
                        </div>

                        {{-- Gallary Images --}}
                        <div class="col-md-3 col-sm-12 card">
                            {{ Form::label('filepond2', __('Gallary Images'), ['class' => 'form-label col-12 ']) }}
                            <input type="file" class="filepond" accept="image/jpg,image/png,image/jpeg" id="filepond2" name="gallery_images[]" multiple>
                            <div class="row mt-0">
                                <?php $i = 0; ?>
                                @if (!empty($list->gallery))
                                    @foreach ($list->gallery as $row)
                                        <div class="col-md-6 col-sm-12" id='{{ $row->id }}'>
                                            <div class="card1" style="height:10vh;">
                                                <img src="{{ url('') . config('global.IMG_PATH') . config('global.PROPERTY_GALLERY_IMG_PATH') . $list->id . '/' . $row->image }}"
                                                    alt="Image" class="card1-img">
                                                <button data-id="{{ $row->id }}"
                                                    class="RemoveBtn1 RemoveBtngallary">x</button>
                                            </div>
                                        </div>

                                        <?php $i++; ?>
                                    @endforeach
                                @endif
                            </div>
                        </div>



                        {{-- Documents Images --}}
                        <div class="col-md-6 form-group">
                            {{ Form::label('edit-documents', __('Documents'), ['class' => 'form-label col-12 ']) }}
                            <input type="file" class="doc-filepond" id="edit-documents" name="documents[]" multiple>
                            <div class="row mt-0 stored-documents-div">
                                @if (!empty($list->documents))
                                    @foreach ($list->documents as $row)
                                        <div class="col-md-2 col-sm-12 text-center mb-3">
                                            @if ($row->type == 'pdf')
                                                <a href="{{ $row->file }}" target="_blank"><img src="{{ url('images/pdf.png') }}" alt="" height="70px" width="70px"></a>
                                            @else
                                                <a href="{{ $row->file }}" target="_blank"><img src="{{ url('images/doc.png') }}" alt="" height="70px" width="70px"></a>
                                            @endif
                                            <button class="btn btn-danger btn-sm removeDocument" data-id={{ $row->id }}>X</button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        {{-- Policy Data / Ownership Contract (hidden for hotel properties) --}}
                        <div class="col-md-6 form-group policy-data-field">
                            {{ Form::label('policy_data', __('Ownership Contract'), ['class' => 'form-label col-12']) }}
                            <input type="file" class="filepond" id="policy_data" name="policy_data" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain">
                            @if (!empty($list->policy_data))
                                <div class="mt-2">
                                    <a href="{{ $list->policy_data }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="bi bi-file-earmark"></i> {{ __('View Ownership Contract') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-3">
                            {{ Form::label('video_link', __('Video Link'), ['class' => 'form-label col-12 ']) }}
                            {{ Form::text('video_link', isset($list->video_link) ? $list->video_link : '', ['class' => 'form-control ', 'placeholder' => trans('Video Link'), 'id' => 'video_link', 'autocomplete' => 'off']) }}

                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Agreement Documents Section --}}
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h3 class="mb-0">{{ __('Agreement Documents') }}</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>{{ __('Information:') }}</strong>
                        {{ __('Upload document files (PDF, Word, images, etc.) for property agreements. All file types are accepted. Maximum file size: 10MB per document.') }}
                    </div>
                    <div class="row g-4">
                        {{-- Identity Proof --}}
                        <div class="col-sm-12 col-md-6 col-lg-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        {{ __('Identity Proof Document') }}
                                    </h6>
                                    @php
                                        $identityProofRaw = $list->getRawOriginal('identity_proof');
                                    @endphp
                                    @if(!empty($identityProofRaw))
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div class="d-flex gap-2 mb-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary flex-fill document-preview-btn" 
                                                        data-document-url="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'identity_proof']) }}"
                                                        data-document-name="{{ __('Identity Proof Document') }}"
                                                        data-document-download="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'identity_proof', 'download' => 1]) }}">
                                                    <i class="bi bi-eye me-1"></i> {{ __('Preview') }}
                                                </button>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'identity_proof']) }}" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View') }}
                                                </a>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'identity_proof', 'download' => 1]) }}" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="bi bi-download me-1"></i> {{ __('Download') }}
                                                </a>
                                            </div>
                                            <small class="text-muted d-block text-truncate" title="{{ $identityProofRaw }}">
                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                <strong>{{ __('Document Uploaded') }}</strong>
                                            </small>
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <input type="file" class="filepond-document" id="identity_proof" name="identity_proof" accept="*/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- National ID/Passport --}}
                        <div class="col-sm-12 col-md-6 col-lg-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        {{ __('National ID/Passport Document') }}
                                    </h6>
                                    @php
                                        $nationalIdRaw = $list->getRawOriginal('national_id_passport');
                                    @endphp
                                    @if(!empty($nationalIdRaw))
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div class="d-flex gap-2 mb-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary flex-fill document-preview-btn" 
                                                        data-document-url="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'national-id']) }}"
                                                        data-document-name="{{ __('National ID/Passport Document') }}"
                                                        data-document-download="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'national-id', 'download' => 1]) }}">
                                                    <i class="bi bi-eye me-1"></i> {{ __('Preview') }}
                                                </button>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'national-id']) }}" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View') }}
                                                </a>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'national-id', 'download' => 1]) }}" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="bi bi-download me-1"></i> {{ __('Download') }}
                                                </a>
                                            </div>
                                            <small class="text-muted d-block text-truncate" title="{{ $nationalIdRaw }}">
                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                <strong>{{ __('Document Uploaded') }}</strong>
                                            </small>
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <input type="file" class="filepond-document" id="national_id_passport" name="national_id_passport" accept="*/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Alternative ID --}}
                        <div class="col-sm-12 col-md-6 col-lg-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        {{ __('Alternative ID Document') }}
                                    </h6>
                                    @php
                                        $alternativeIdRaw = $list->getRawOriginal('alternative_id');
                                    @endphp
                                    @if(!empty($alternativeIdRaw))
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div class="d-flex gap-2 mb-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary flex-fill document-preview-btn" 
                                                        data-document-url="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'alternative-id']) }}"
                                                        data-document-name="{{ __('Alternative ID Document') }}"
                                                        data-document-download="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'alternative-id', 'download' => 1]) }}">
                                                    <i class="bi bi-eye me-1"></i> {{ __('Preview') }}
                                                </button>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'alternative-id']) }}" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View') }}
                                                </a>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'alternative-id', 'download' => 1]) }}" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="bi bi-download me-1"></i> {{ __('Download') }}
                                                </a>
                                            </div>
                                            <small class="text-muted d-block text-truncate" title="{{ $alternativeIdRaw }}">
                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                <strong>{{ __('Document Uploaded') }}</strong>
                                            </small>
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <input type="file" class="filepond-document" id="alternative_id" name="alternative_id" accept="*/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Utilities Bills --}}
                        <div class="col-sm-12 col-md-6 col-lg-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        {{ __('Utilities Bills Document') }}
                                    </h6>
                                    @php
                                        $utilitiesBillsRaw = $list->getRawOriginal('utilities_bills');
                                    @endphp
                                    @if(!empty($utilitiesBillsRaw))
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div class="d-flex gap-2 mb-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary flex-fill document-preview-btn" 
                                                        data-document-url="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'utilities-bills']) }}"
                                                        data-document-name="{{ __('Utilities Bills Document') }}"
                                                        data-document-download="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'utilities-bills', 'download' => 1]) }}">
                                                    <i class="bi bi-eye me-1"></i> {{ __('Preview') }}
                                                </button>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'utilities-bills']) }}" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View') }}
                                                </a>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'utilities-bills', 'download' => 1]) }}" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="bi bi-download me-1"></i> {{ __('Download') }}
                                                </a>
                                            </div>
                                            <small class="text-muted d-block text-truncate" title="{{ $utilitiesBillsRaw }}">
                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                <strong>{{ __('Document Uploaded') }}</strong>
                                            </small>
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <input type="file" class="filepond-document" id="utilities_bills" name="utilities_bills" accept="*/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Power of Attorney --}}
                        <div class="col-sm-12 col-md-6 col-lg-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        {{ __('Power of Attorney Document') }}
                                    </h6>
                                    @php
                                        $poaRaw = $list->getRawOriginal('power_of_attorney');
                                    @endphp
                                    @if(!empty($poaRaw))
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div class="d-flex gap-2 mb-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary flex-fill document-preview-btn" 
                                                        data-document-url="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'power-of-attorney']) }}"
                                                        data-document-name="{{ __('Power of Attorney Document') }}"
                                                        data-document-download="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'power-of-attorney', 'download' => 1]) }}">
                                                    <i class="bi bi-eye me-1"></i> {{ __('Preview') }}
                                                </button>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'power-of-attorney']) }}" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View') }}
                                                </a>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'power-of-attorney', 'download' => 1]) }}" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="bi bi-download me-1"></i> {{ __('Download') }}
                                                </a>
                                            </div>
                                            <small class="text-muted d-block text-truncate" title="{{ $poaRaw }}">
                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                <strong>{{ __('Document Uploaded') }}</strong>
                                            </small>
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <input type="file" class="filepond-document" id="power_of_attorney" name="power_of_attorney" accept="*/*">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Ownership Contract --}}
                        <div class="col-sm-12 col-md-6 col-lg-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="bi bi-file-earmark-text me-2"></i>
                                        {{ __('Ownership Contract Document') }}
                                    </h6>
                                    @php
                                        $ownershipContractRaw = $list->getRawOriginal('ownership_contract');
                                    @endphp
                                    @if(!empty($ownershipContractRaw))
                                        <div class="mb-3 p-3 bg-light rounded">
                                            <div class="d-flex gap-2 mb-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-primary flex-fill document-preview-btn" 
                                                        data-document-url="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'ownership-contract']) }}"
                                                        data-document-name="{{ __('Ownership Contract Document') }}"
                                                        data-document-download="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'ownership-contract', 'download' => 1]) }}">
                                                    <i class="bi bi-eye me-1"></i> {{ __('Preview') }}
                                                </button>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'ownership-contract']) }}" target="_blank" class="btn btn-sm btn-info">
                                                    <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('View') }}
                                                </a>
                                                <a href="{{ route('property.document.view', ['propertyId' => $list->id, 'documentType' => 'ownership-contract', 'download' => 1]) }}" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="bi bi-download me-1"></i> {{ __('Download') }}
                                                </a>
                                            </div>
                                            <small class="text-muted d-block text-truncate" title="{{ $ownershipContractRaw }}">
                                                <i class="bi bi-check-circle-fill text-success me-1"></i>
                                                <strong>{{ __('Document Uploaded') }}</strong>
                                            </small>
                                        </div>
                                    @endif
                                    <div class="mt-2">
                                        <input type="file" class="filepond-document" id="ownership_contract" name="ownership_contract" accept="*/*">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <div class="card">
                <h3 class="card-header">{{ __('Accesibility') }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <h3 class="card-header">{{ __('Accesibility') }}</h3>
                <hr>
                <div class="card-body">
                    <div class="col-sm-12 col-md-12  col-xs-12 d-flex">
                        <label class="col-sm-1 form-check-label mandatory mt-3 ">{{ __('Is Private?') }}</label>
                        <div class="form-check form-switch mt-3">
                            <input type="hidden" name="is_premium" id="is_premium" value=" {{ $list->is_premium ? 1 : 0 }}">
                            <input class="form-check-input" type="checkbox" role="switch" {{ $list->is_premium ? 'checked' : '' }} id="is_premium_switch">
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class='col-md-12 d-flex justify-content-end mb-3'>
            <input type="submit" class="btn btn-primary" value="{{ __('Save') }}">
            &nbsp;
            &nbsp;
            <button class="btn btn-secondary reset-form" type="button">{{ __('Reset') }}</button>
        </div>
        {!! Form::close() !!}

    </div>
@endsection
@section('script')
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?libraries=places&key={{ env('PLACE_API_KEY') }}&callback=initMap" async defer></script>
    <script>
        // Flag to track if map is initialized
        var mapInitialized = false;
        
        function initMap() {
            // Check if Google Maps API is loaded
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                console.warn('Google Maps API not loaded yet, waiting...');
                // Retry after a short delay
                setTimeout(initMap, 100);
                return;
            }
            
            // Prevent multiple initializations
            if (mapInitialized) {
                console.log('Map already initialized, skipping...');
                return;
            }
            
            // Properly parse latitude and longitude as floats, with fallback values
            var latitude = parseFloat($('#latitude').val()) || 20.593684;
            var longitude = parseFloat($('#longitude').val()) || 78.96288;

            console.log("Map initialization with coordinates:", latitude, longitude);
            console.log("Raw latitude value:", $('#latitude').val());
            console.log("Raw longitude value:", $('#longitude').val());

            var map = new google.maps.Map(document.getElementById('map'), {
                center: {
                    lat: latitude,
                    lng: longitude
                },
                zoom: 13
            });
            var marker = new google.maps.Marker({
                position: {
                    lat: latitude,
                    lng: longitude
                },
                map: map,
                draggable: true,
                title: 'Marker Title'
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
            var input = document.getElementById('searchInput');
            if (!input) {
                console.error('searchInput element not found');
                return;
            }
            
            // map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

            var autocomplete = new google.maps.places.Autocomplete(input);
            autocomplete.bindTo('bounds', map);

            var infowindow = new google.maps.InfoWindow();
            var marker = new google.maps.Marker({
                map: map,
                anchorPoint: new google.maps.Point(0, -29)
            });
            autocomplete.addListener('place_changed', function() {
                infowindow.close();
                marker.setVisible(false);
                var place = autocomplete.getPlace();
                if (!place.geometry) {
                    window.alert("Autocomplete's returned place contains no geometry");
                    return;
                }

                // If the place has a geometry, then present it on a map.
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
                    console.log(place);

                    if (place.address_components[i].types[0] == 'locality') {
                        $('#searchInput').val(place.address_components[i].long_name);
                    }
                    if (place.address_components[i].types[0] == 'country') {
                        $('#country').val(place.address_components[i].long_name);
                    }
                    if (place.address_components[i].types[0] == 'administrative_area_level_1') {
                        console.log(place.address_components[i].long_name);
                        $('#state').val(place.address_components[i].long_name);
                    }
                }
                var latitude = place.geometry.location.lat();
                var longitude = place.geometry.location.lng();
                $('#address').val(place.formatted_address);
                $('#latitude').val(place.geometry.location.lat());
                $('#longitude').val(place.geometry.location.lng());
            });
            
            // Mark map as initialized
            mapInitialized = true;
        }

        $(document).ready(function() {
            // Don't call initMap() here - it's called automatically by Google Maps API callback
            // The callback=initMap in the script tag will call it when API is ready
            // Debug form values on page load
            console.log("Form values on page load:");
            console.log("City:", $('#searchInput').val());
            console.log("Country:", $('#country').val());
            console.log("State:", $('#state').val());
            console.log("Latitude:", $('#latitude').val());
            console.log("Longitude:", $('#longitude').val());
            console.log("Address:", $('#address').val());

            $('.reset-form').on('click',function(e){
                e.preventDefault();
                $('#myForm')[0].reset();
            });
            if ($('input[name="property_type"]:checked').val() == 0) {
                $('#duration').hide();
                $('#price_duration').val('');
                $('.price-field').show();
            } else {
                $('#duration').show();
                $('#price_duration').val('');
                $('.price-field').show();
            }
            getWordCount("edit_meta_title", "edit_meta_title_count", "19.9px arial");
            getWordCount("edit_meta_description", "edit_meta_description_count", "12.9px arial");

        });
        $('input[name="property_type"]').change(function() {
            // Get the selected value
            var selectedType = $('input[name="property_type"]:checked').val();

            // Perform actions based on the selected value

            if (selectedType == 1) {
                $('#duration').show();
                $('.price-field').show();
            } else {
                $('#duration').hide();
                $('#price_duration').val('');
                $('.price-field').show();
            }
        });
        $(".RemoveBtngallary").click(function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            Swal.fire({
                title: window.trans['Are you sure you wants to remove this document ?'],
                icon: 'error',
                showDenyButton: true,
                confirmButtonText: window.trans['Yes'],
                denyCanceButtonText: window.trans['No'],
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('property.removeGalleryImage') }}",

                        type: "POST",
                        data: {
                            '_token': "{{ csrf_token() }}",
                            "id": id
                        },
                        success: function(response) {

                            if (response.error == false) {
                                Toastify({
                                    text: 'Image Delete Successful',
                                    duration: 6000,
                                    close: !0,
                                    backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)"
                                }).showToast();
                                $("#" + id).html('');
                            } else if (response.error == true) {
                                Toastify({
                                    text: 'Something Wrong !!!',
                                    duration: 6000,
                                    close: !0,
                                    backgroundColor: '#dc3545' //"linear-gradient(to right, #dc3545, #96c93d)"
                                }).showToast()
                            }
                        },
                        error: function(xhr) {}
                    });
                }
            })

        });
        $(document).on('click', '#filepond_3d', function(e) {

            $('.3d_img').hide();
        });
        $(document).on('click', '#filepond_title', function(e) {

            $('.title_img').hide();
        });
        jQuery(document).ready(function() {
            // Don't call initMap() here - it's called automatically by Google Maps API callback
            // The callback=initMap in the script tag will call it when API is ready
            // Only call it manually if Google Maps API is already loaded (fallback)
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined' && !mapInitialized) {
                // API is already loaded, initialize map
                initMap();
            }

            // Don't add iframe here - it's causing conflicts with the Google Maps API
            $('.parsley-error filled,.parsley-required').attr("aria-hidden", "true");
            $('.parsley-error filled,.parsley-required').hide();
            
            // Ensure Parsley validation errors are visible if validation fails
            $('#myForm').parsley().on('form:error', function() {
                console.error('Parsley validation failed');
                // Show validation errors
                $('.parsley-error').show();
            });
            
            // Log when form is actually submitted (this is just for logging, validation is in the other handler)
            $('#myForm').on('submit', function(e) {
                console.log('Form is being submitted...');
                // Don't prevent default here - let the validation handler do its work
            });

            // Add back the is_premium_switch functionality
            $("#is_premium_switch").on('change', function() {
                $("#is_premium_switch").is(':checked') ? $("#is_premium").val(1) : $("#is_premium").val(0);
            });

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

            $('#filepond2').filepond({
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
                allowMultiple: true,
            });
        });
        $("#title").on('keyup',function(e){
            let title = $(this).val();
            let id = "{{ $id }}";
            let slugElement = $("#slug");
            if(title){
                $.ajax({
                    type: 'POST',
                    url: "{{ route('property.generate-slug') }}",
                    data: {
                        '_token': $('meta[name="csrf-token"]').attr('content'),
                        title: title,
                        id: id
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




        $(".removeDocument").click(function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            Swal.fire({
                title: window.trans['Are you sure you wants to remove this document ?'],
                icon: 'error',
                showDenyButton: true,
                confirmButtonText: window.trans['Yes'],
                denyCanceButtonText: window.trans['No'],
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('property.remove-documents') }}",
                        type: "POST",
                        data: {
                            '_token': "{{ csrf_token() }}",
                            "id": id
                        },
                        success: function(response) {
                            if (response.error == false) {
                                Toastify({
                                    text: window.trans['Document Deleted Successfully'],
                                    duration: 1500,
                                    close: !0,
                                    backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)"
                                }).showToast();

                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);

                                $("#" + id).html('');
                            } else if (response.error == true) {
                                Toastify({
                                    text: window.trans['Something Went Wrong'],
                                    duration: 5000,
                                    close: !0,
                                    backgroundColor: '#dc3545' //"linear-gradient(to right, #dc3545, #96c93d)"
                                }).showToast()
                            }
                        },
                        error: function(xhr) {}
                    });
                }
            })

        });


        $(".removeThreeDImage").on('click',function(e){
            e.preventDefault();
            let url = $(this).data('url');
            showDeletePopupModal(url,{
                successCallBack: function () {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }, errorCallBack: function (response) {
                    showErrorToast(response.message);
                }
            })
        })
    </script>
    <!-- Guest Pricing Modal -->
    <div class="modal fade" id="guestPricingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Configure Guest Pricing') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="current-room-index">
                    <div class="alert alert-info">
                        {{ __('Base Price is set for') }} <span id="modal-base-guests">2</span> {{ __('guests') }}.
                        {{ __('Set percentage for other guest counts (e.g., 90% for 1 guest, 110% for 3 guests).') }}
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Guest Count') }}</th>
                                    <th>{{ __('Percentage of Base Price (%)') }}</th>
                                    <th>{{ __('Estimated Price') }}</th>
                                </tr>
                            </thead>
                            <tbody id="guest-pricing-rows">
                                <!-- Rows generated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="save-guest-pricing">{{ __('Save Changes') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            // Guest Pricing Modal Logic
            $(document).on('click', '.configure-pricing-btn', function() {
                var index = $(this).data('index');
                $('#current-room-index').val(index);
                
                var row = $(this).closest('tr');
                var minGuests = parseInt(row.find('input[name="hotel_rooms[' + index + '][min_guests]"]').val()) || 1;
                var maxGuests = parseInt(row.find('input[name="hotel_rooms[' + index + '][max_guests]"]').val()) || 2;
                var baseGuests = parseInt(row.find('input[name="hotel_rooms[' + index + '][base_guests]"]').val()) || 2;
                var basePrice = parseFloat(row.find('input[name="hotel_rooms[' + index + '][price_per_night]"]').val()) || 0;
                var existingRules = row.find('.guest-pricing-rules-input').val();
                
                try {
                    // Handle JSON encoded string if it comes from PHP value
                    if (typeof existingRules === 'string' && existingRules.startsWith('"')) {
                        existingRules = JSON.parse(existingRules);
                    }
                    if (typeof existingRules === 'string') {
                         existingRules = JSON.parse(existingRules);
                    }
                } catch(e) {
                    existingRules = {};
                }
                
                // Ensure existingRules is an object
                if (typeof existingRules !== 'object' || existingRules === null) {
                    existingRules = {};
                }

                $('#modal-base-guests').text(baseGuests);
                var tbody = $('#guest-pricing-rows');
                tbody.empty();

                for (var i = minGuests; i <= maxGuests; i++) {
                    if (i === baseGuests) continue;

                    var percentage = existingRules[i] || 100;
                    var price = (basePrice * percentage / 100).toFixed(2);

                    var tr = `
                        <tr>
                            <td>${i} {{ __('Guest(s)') }}</td>
                            <td>
                                <input type="number" class="form-control pricing-percentage" data-guests="${i}" value="${percentage}" min="0" step="0.01">
                            </td>
                            <td>
                                <span class="estimated-price">${price}</span>
                            </td>
                        </tr>
                    `;
                    tbody.append(tr);
                }
                
                // Live calculation
                tbody.on('input', '.pricing-percentage', function() {
                    var pct = parseFloat($(this).val()) || 0;
                    var est = (basePrice * pct / 100).toFixed(2);
                    $(this).closest('tr').find('.estimated-price').text(est);
                });

                var modal = new bootstrap.Modal(document.getElementById('guestPricingModal'));
                modal.show();
            });

            $('#save-guest-pricing').click(function() {
                var index = $('#current-room-index').val();
                var rules = {};
                
                $('#guest-pricing-rows .pricing-percentage').each(function() {
                    var guests = $(this).data('guests');
                    var pct = $(this).val();
                    rules[guests] = pct;
                });

                var jsonRules = JSON.stringify(rules);
                $('input[name="hotel_rooms[' + index + '][guest_pricing_rules]"]').val(jsonRules);
                
                // Hide modal
                var modalEl = document.getElementById('guestPricingModal');
                var modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
            });
        });
    </script>
        $(document).ready(function() {
            // Handle property classification change
            function handlePropertyClassification() {
                var propertyClassification = $('#property_classification').val();

                // Hide all specific fields first
                $('.vacation-home-fields').hide();
                $('.hotel-fields').hide();
                $('.hotel-rooms').hide();
                $('.policy-data-field').hide(); // Hide policy data field

                // Show fields based on classification
                if (propertyClassification == 4) { // Vacation Homes
                    $('.vacation-home-fields').show();
                    $('.price-field').show(); // Show price for vacation homes
                } else if (propertyClassification == 5) { // Hotel Booking
                    $('.hotel-fields').show();
                    $('.hotel-rooms').show();
                    $('.policy-data-field').hide(); // Hide policy data for hotels
                    $('.price-field').show(); // Show price for hotels
                } else {
                    $('.policy-data-field').show(); // Show policy data for non-hotels
                    $('.price-field').show(); // Show price for non-hotels
                }
            }

            // Call on page load
            handlePropertyClassification();

            // Initialize FilePond for document fields (accept all file types)
            // Wait a bit to ensure DOM is ready and global FilePond config has been applied
            setTimeout(function() {
                FilePond.registerPlugin(FilePondPluginFileValidateSize, FilePondPluginFileValidateType, FilePondPluginPdfPreview);
                
                // Configure FilePond for agreement documents - accept all file types
                // Use class selector to target all document fields
                $('.filepond-document').each(function() {
                    var fieldElement = this;
                    
                    // Destroy existing FilePond instance if any
                    var pondInstance = FilePond.find(fieldElement);
                    if (pondInstance) {
                        pondInstance.destroy();
                    }
                    
                    // Initialize with all file types accepted - optimized for documents
                    FilePond.create(fieldElement, {
                        credits: null,
                        allowFileSizeValidation: true,
                        maxFileSize: '10MB',
                        labelMaxFileSizeExceeded: '{{ __('File is too large. Maximum size is 10MB.') }}',
                        labelMaxFileSize: '{{ __('Maximum file size is {filesize}') }}',
                        allowFileTypeValidation: false, // Disable file type validation to accept all types
                        storeAsFile: true,
                        allowPdfPreview: true,
                        pdfPreviewHeight: 320,
                        pdfComponentExtraParams: 'toolbar=0&navpanes=0&scrollbar=0&view=fitH',
                        labelIdle: '{!! __('Drag & Drop your document or <span class="filepond--label-action">Browse</span>') !!}',
                        labelFileTypeNotAllowed: '{{ __('File type not allowed') }}',
                        labelFileProcessing: '{{ __('Uploading...') }}',
                        labelFileProcessingComplete: '{{ __('Upload complete') }}',
                        labelFileProcessingError: '{{ __('Error during upload') }}',
                        labelFileRemoveError: '{{ __('Error during remove') }}',
                        labelTapToCancel: '{{ __('Tap to cancel') }}',
                        labelTapToRetry: '{{ __('Tap to retry') }}',
                        labelTapToUndo: '{{ __('Tap to undo') }}',
                        labelButtonRemoveItem: '{{ __('Remove') }}',
                        labelButtonAbortItemLoad: '{{ __('Abort') }}',
                        labelButtonRetryItemLoad: '{{ __('Retry') }}',
                        labelButtonAbortItemProcessing: '{{ __('Cancel') }}',
                        labelButtonUndoItemProcessing: '{{ __('Undo') }}',
                        labelButtonProcessItem: '{{ __('Upload') }}'
                    });
                });
            }, 200);

            // Call when classification changes
            $('#property_classification').on('change', function() {
                handlePropertyClassification();
            });

            // Room management
            var roomIndex = {{ isset($list->hotelRooms) ? count($list->hotelRooms) : 0 }};

            // Handle room type change
            $(document).on('change', '.room-type-select', function() {
                var value = $(this).val();
                var input = $(this).closest('td').find('.custom-room-type-input');
                
                if (value === 'other') {
                    input.show();
                    input.attr('required', 'required');
                } else {
                    input.hide();
                    input.removeAttr('required');
                    input.val('');
                }
            });

            // Handle Refund Policy Type Change
            $('#refund_policy_type').on('change', function() {
                var type = $(this).val();
                var refundPolicyInput = $('#refund_policy_input');
                
                if (type === 'both') {
                    refundPolicyInput.val('flexible'); // Default for backend compatibility
                    $('.non-ref-col').show();
                    $('.ref-policy-col').show();
                    
                    // Show inputs in existing rows
                    $('.non-ref-input').show();
                    $('.room-refund-policy').closest('td').show();
                } else if (type === 'flexible') {
                    refundPolicyInput.val('flexible');
                    $('.non-ref-col').hide();
                    $('.ref-policy-col').hide();
                    
                    // Update existing rows
                    $('.room-refund-policy').val('flexible');
                    $('.non-ref-input').val('').hide();
                    $('.room-refund-policy').closest('td').hide();
                } else if (type === 'non-refundable') {
                    refundPolicyInput.val('non-refundable');
                    $('.non-ref-col').hide();
                    $('.ref-policy-col').hide();
                    
                    // Update existing rows
                    $('.room-refund-policy').val('non-refundable');
                    $('.non-ref-input').val('').hide();
                    $('.room-refund-policy').closest('td').hide();
                }
            });

            // Add new room
            $('#add-room-btn').on('click', function() {
                var refundPolicyType = $('#refund_policy_type').val();
                var displayStyle = refundPolicyType === 'both' ? '' : 'display:none;';
                var defaultRefundPolicy = 'flexible';
                if (refundPolicyType === 'non-refundable') {
                    defaultRefundPolicy = 'non-refundable';
                }

                var newRow = `
                    <tr class="room-row">
                        <td>
                            <input type="text" class="form-control" name="hotel_rooms[${roomIndex}][room_number]">
                        </td>
                        <td>
                            <select class="form-control room-type-select" name="hotel_rooms[${roomIndex}][room_type_id]">
                                @foreach(App\Models\HotelRoomType::where('status', 1)->get() as $roomType)
                                    <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
                                @endforeach
                                <option value="other">{{ __('Other') }}</option>
                            </select>
                            <input type="text" class="form-control mt-2 custom-room-type-input" name="hotel_rooms[${roomIndex}][custom_room_type]" style="display:none;" placeholder="{{ __('Enter Room Type Name') }}">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][min_guests]" value="1" min="1">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][max_guests]" value="2" min="1">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][base_guests]" value="2" min="1">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][price_per_night]" min="0" step="0.01">
                        </td>
                        <td>
                            <button type="button" class="btn btn-info btn-sm configure-pricing-btn" data-index="${roomIndex}">
                                <i class="bi bi-gear"></i> {{ __('Configure') }}
                            </button>
                            <input type="hidden" name="hotel_rooms[${roomIndex}][guest_pricing_rules]" class="guest-pricing-rules-input">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][discount_percentage]" value="0" min="0" max="100" step="0.01">
                        </td>
                        <td class="non-ref-col" style="${displayStyle}">
                            <input type="number" class="form-control non-ref-input" name="hotel_rooms[${roomIndex}][nonrefundable_percentage]" min="0" max="100" step="0.01" style="${displayStyle}">
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
                        <td class="ref-policy-col" style="${displayStyle}">
                            <select class="form-control room-refund-policy" name="hotel_rooms[${roomIndex}][refund_policy]">
                                <option value="flexible" ${defaultRefundPolicy == 'flexible' ? 'selected' : ''}>{{ __('Flexible') }}</option>
                                <option value="non-refundable" ${defaultRefundPolicy == 'non-refundable' ? 'selected' : ''}>{{ __('Non-Refundable') }}</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="hidden" class="room-status-value" name="hotel_rooms[${roomIndex}][status]" value="1">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input room-status-toggle" type="checkbox" checked>
                            </div>
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
                var roomId = $(this).data('room-id');
                if (roomId) {
                    // If existing room, add to deleted list
                    $('#deleted-rooms-container').append(`<input type="hidden" name="deleted_room_ids[]" value="${roomId}">`);
                }
                $(this).closest('tr').remove();
            });

            $(document).on('change', '.room-status-toggle', function() {
                var isActive = $(this).is(':checked');
                $(this).closest('td').find('.room-status-value').val(isActive ? 1 : 0);
            });

            // Corresponding Day Management
            var correspondingDays = [];

            // Initialize with existing data if available
            var existingCorrespondingDay = $('#corresponding_day_json').val();
            if (existingCorrespondingDay) {
                try {
                    correspondingDays = JSON.parse(existingCorrespondingDay);
                    updateCorrespondingDayList();
                } catch (e) {
                    console.error('Error parsing existing corresponding day data:', e);
                }
            }

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

            // Form validation before submit
            $('#myForm').on('submit', function(e) {
                // Debug form values before submission
                console.log("Form values before submission:");
                console.log("City:", $('#searchInput').val());
                console.log("Country:", $('#country').val());
                console.log("State:", $('#state').val());
                console.log("Latitude:", $('#latitude').val());
                console.log("Longitude:", $('#longitude').val());
                console.log("Address:", $('#address').val());

                var agentAddonsValue = $('#agent_addons_field').val().trim();
                if (agentAddonsValue) {
                    try {
                        JSON.parse(agentAddonsValue);
                    } catch (err) {
                        e.preventDefault();
                        $('#agent_addons_field').addClass('is-invalid');
                        $('#agent_addons_error').text('Invalid JSON format. Please fix the syntax before submitting.');
                        $('#agent_addons_field').focus();
                        return false;
                    }
                }
                
                // Show loading indicator
                var submitButton = $(this).find('button[type="submit"]');
                var originalButtonText = submitButton.text();
                if (submitButton.length) {
                    submitButton.prop('disabled', true).text('Saving...');
                }
                
                // Re-enable button if form submission fails (after 10 seconds timeout)
                setTimeout(function() {
                    if (submitButton.length) {
                        submitButton.prop('disabled', false).text(originalButtonText);
                    }
                }, 10000);
                
                // Let the form submit normally if validation passes
                // Don't prevent default unless there's an error
            });
            
            // Handle form submission errors
            $(document).ajaxError(function(event, xhr, settings, thrownError) {
                console.error('AJAX Error:', {
                    url: settings.url,
                    status: xhr.status,
                    error: thrownError,
                    response: xhr.responseText
                });
            });
        });

        // Document Preview Functionality
        $(document).ready(function() {
            // Handle document preview button clicks
            $('.document-preview-btn').on('click', function() {
                const documentUrl = $(this).data('document-url');
                const documentName = $(this).data('document-name');
                const downloadUrl = $(this).data('document-download');
                
                // Set modal title
                $('#documentPreviewTitle').text(documentName);
                
                // Set download and view links
                $('#documentPreviewDownload').attr('href', downloadUrl);
                $('#documentPreviewView').attr('href', documentUrl);
                
                // Get file extension to determine preview method
                // Extract extension from URL (handle query parameters)
                const urlParts = documentUrl.split('?')[0].split('.');
                const fileExtension = urlParts.length > 1 ? urlParts[urlParts.length - 1].toLowerCase() : '';
                const previewBody = $('#documentPreviewBody');
                
                // Clear previous content
                previewBody.html('<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-3">Loading document...</p></div>');
                
                // Show modal
                const previewModal = new bootstrap.Modal(document.getElementById('documentPreviewModal'));
                previewModal.show();
                
                // Determine preview method based on file type
                // Try to detect file type from Content-Type header if extension is not available
                if (fileExtension) {
                    // We have an extension, use it
                    if (['pdf'].includes(fileExtension)) {
                        // PDF Preview
                        previewBody.html('<iframe src="' + documentUrl + '" class="document-preview-iframe"></iframe>');
                    } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(fileExtension)) {
                        // Image Preview
                        previewBody.html('<img src="' + documentUrl + '" class="document-preview-image" alt="' + documentName + '" onerror="this.parentElement.innerHTML=\'<div class=\\\'document-preview-unsupported\\\'><i class=\\\'bi bi-file-earmark\\\'></i><p>Unable to load image. Please download to view.</p></div>\'">');
                    } else if (['txt', 'csv'].includes(fileExtension)) {
                        // Text file - try to load as text
                        fetch(documentUrl)
                            .then(response => response.text())
                            .then(text => {
                                previewBody.html('<pre class="p-3" style="white-space: pre-wrap; word-wrap: break-word;">' + escapeHtml(text) + '</pre>');
                            })
                            .catch(error => {
                                previewBody.html('<div class="document-preview-unsupported"><i class="bi bi-file-earmark"></i><p>Unable to preview this file type. Please download to view.</p></div>');
                            });
                    } else {
                        // Unsupported file type (Word, Excel, etc.)
                        previewBody.html('<div class="document-preview-unsupported"><i class="bi bi-file-earmark"></i><p>Preview not available for this file type.</p><p class="text-muted">Please use "Download" or "Open in New Tab" to view this document.</p></div>');
                    }
                } else {
                    // No extension in URL - try to detect from Content-Type header
                    // Most documents are PDFs, so try PDF first
                    fetch(documentUrl, { method: 'HEAD' })
                        .then(response => {
                            const contentType = response.headers.get('content-type') || '';
                            if (contentType.includes('application/pdf')) {
                                previewBody.html('<iframe src="' + documentUrl + '" class="document-preview-iframe"></iframe>');
                            } else if (contentType.startsWith('image/')) {
                                previewBody.html('<img src="' + documentUrl + '" class="document-preview-image" alt="' + documentName + '" onerror="this.parentElement.innerHTML=\'<div class=\\\'document-preview-unsupported\\\'><i class=\\\'bi bi-file-earmark\\\'></i><p>Unable to load image. Please download to view.</p></div>\'">');
                            } else if (contentType.includes('text/')) {
                                fetch(documentUrl)
                                    .then(response => response.text())
                                    .then(text => {
                                        previewBody.html('<pre class="p-3" style="white-space: pre-wrap; word-wrap: break-word;">' + escapeHtml(text) + '</pre>');
                                    })
                                    .catch(error => {
                                        previewBody.html('<div class="document-preview-unsupported"><i class="bi bi-file-earmark"></i><p>Unable to preview this file type. Please download to view.</p></div>');
                                    });
                            } else {
                                // Default: try PDF (most common document type)
                                previewBody.html('<iframe src="' + documentUrl + '" class="document-preview-iframe" onerror="this.parentElement.innerHTML=\'<div class=\\\'document-preview-unsupported\\\'><i class=\\\'bi bi-file-earmark\\\'></i><p>Preview not available for this file type.</p><p class=\\\'text-muted\\\'>Please use \\\"Download\\\" or \\\"Open in New Tab\\\" to view this document.</p></div>\'"></iframe>');
                            }
                        })
                        .catch(error => {
                            // If HEAD request fails, try PDF as default (most common)
                            previewBody.html('<iframe src="' + documentUrl + '" class="document-preview-iframe" onerror="this.parentElement.innerHTML=\'<div class=\\\'document-preview-unsupported\\\'><i class=\\\'bi bi-file-earmark\\\'></i><p>Preview not available for this file type.</p><p class=\\\'text-muted\\\'>Please use \\\"Download\\\" or \\\"Open in New Tab\\\" to view this document.</p></div>\'"></iframe>');
                        });
                }
            });
            
            // Helper function to escape HTML
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) { return map[m]; });
            }
            
            // Clean up modal when closed
            $('#documentPreviewModal').on('hidden.bs.modal', function () {
                $('#documentPreviewBody').html('');
            });
        });
    </script>

    <!-- Document Preview Modal -->
    <div class="modal fade document-preview-modal" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="documentPreviewModalLabel">
                        <i class="bi bi-file-earmark-text me-2"></i>
                        <span id="documentPreviewTitle">{{ __('Document Preview') }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="documentPreviewBody">
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('Loading...') }}</span>
                        </div>
                        <p class="mt-3">{{ __('Loading document...') }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="documentPreviewDownload" class="btn btn-success" target="_blank">
                        <i class="bi bi-download me-1"></i> {{ __('Download') }}
                    </a>
                    <a href="#" id="documentPreviewView" class="btn btn-info" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i> {{ __('Open in New Tab') }}
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
