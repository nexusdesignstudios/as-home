@extends('layouts.main')
@section('title')
    {{ __('Update Property') }}
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

                    {{-- Area Description --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('area_description', __('Area Description'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('area_description', isset($list->area_description) ? $list->area_description : '', ['class' => 'form-control mb-3', 'rows' => '3', 'id' => 'area_description', 'placeholder' => __('Area Description')]) }}
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
                            {{ Form::label('refund_policy', __('Refund Policy'), ['class' => 'form-label col-12']) }}
                            <select name="refund_policy" class="form-select form-control-sm">
                                <option value="flexible" {{ isset($list->refund_policy) && $list->refund_policy == 'flexible' ? 'selected' : '' }}>{{ __('Flexible Booking') }}</option>
                                <option value="non-refundable" {{ isset($list->refund_policy) && $list->refund_policy == 'non-refundable' ? 'selected' : '' }}>{{ __('Non-Refundable') }}</option>
                            </select>
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
                                    <th>{{ __('Refund Policy') }}</th>
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
                                                <select class="form-control" name="hotel_rooms[{{ $index }}][room_type_id]">
                                                    @foreach(App\Models\HotelRoomType::where('status', 1)->get() as $roomType)
                                                        <option value="{{ $roomType->id }}" {{ $room->room_type_id == $roomType->id ? 'selected' : '' }}>{{ $roomType->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="hotel_rooms[{{ $index }}][price_per_night]" value="{{ $room->price_per_night }}" min="0" step="0.01">
                                            </td>
                                            <td>
                                                <input type="number" class="form-control" name="hotel_rooms[{{ $index }}][discount_percentage]" value="{{ $room->discount_percentage }}" min="0" max="100" step="0.01">
                                            </td>
                                            <td>
                                                <select class="form-control" name="hotel_rooms[{{ $index }}][refund_policy]">
                                                    <option value="flexible" {{ $room->refund_policy == 'flexible' ? 'selected' : '' }}>{{ __('Flexible') }}</option>
                                                    <option value="non-refundable" {{ $room->refund_policy == 'non-refundable' ? 'selected' : '' }}>{{ __('Non-Refundable') }}</option>
                                                </select>
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
                                    {!! Form::hidden('city', isset($list->city) ? $list->city : '', ['class' => 'form-control ', 'id' => 'city']) !!}
                                    <input id="searchInput" value="{{ isset($list->city) ? $list->city : '' }}"  class="controls form-control" type="text" placeholder="{{ __('City') }}">
                                    {{-- {{ Form::text('city', isset($list->city) ? $list->city : '', ['class' => 'form-control ', 'placeholder' => 'City', 'id' => 'city']) }} --}}
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

                        {{-- Policy Data (hidden for hotel properties) --}}
                        <div class="col-md-6 form-group policy-data-field">
                            {{ Form::label('policy_data', __('Policy Data'), ['class' => 'form-label col-12']) }}
                            <input type="file" class="filepond" id="policy_data" name="policy_data" accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain">
                            @if (!empty($list->policy_data))
                                <div class="mt-2">
                                    <a href="{{ $list->policy_data }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="bi bi-file-earmark"></i> {{ __('View Policy Document') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-3">
                            {{ Form::label('video_link', __('Video Link'), ['class' => 'form-label col-12 ']) }}
                            {{ Form::text('video_link', isset($list->video_link) ? $list->video_link : '', ['class' => 'form-control ', 'placeholder' => trans('Video Link'), 'id' => 'address', 'autocomplete' => 'off']) }}

                        </div>
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
        function initMap() {
            // Properly parse latitude and longitude as floats, with fallback values
            var latitude = parseFloat($('#latitude').val()) || 20.593684;
            var longitude = parseFloat($('#longitude').val()) || 78.96288;

            console.log("Map initialization with coordinates:", latitude, longitude);

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
            var input = document.getElementById('searchInput');
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
                        $('#city').val(place.address_components[i].long_name);


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
        }

        $(document).ready(function() {
            $('.reset-form').on('click',function(e){
                e.preventDefault();
                $('#myForm')[0].reset();
            });
            if ($('input[name="property_type"]:checked').val() == 0) {
                $('#duration').hide();
                $('#price_duration').val('');
                $('.price-field').hide();
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
                $('.price-field').hide();
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
            initMap();

            // Don't add iframe here - it's causing conflicts with the Google Maps API
            $('.parsley-error filled,.parsley-required').attr("aria-hidden", "true");
            $('.parsley-error filled,.parsley-required').hide();

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
@endsection
@section('js')
    <script>
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
                    $('.price-field').hide(); // Hide price for hotels
                } else {
                    $('.policy-data-field').show(); // Show policy data for non-hotels
                    $('.price-field').show(); // Show price for non-hotels
                }
            }

            // Call on page load
            handlePropertyClassification();

            // Call when classification changes
            $('#property_classification').on('change', function() {
                handlePropertyClassification();
            });

            // Room management
            var roomIndex = {{ isset($list->hotelRooms) ? count($list->hotelRooms) : 0 }};

            // Add new room
            $('#add-room-btn').on('click', function() {
                var newRow = `
                    <tr class="room-row">
                        <td>
                            <input type="text" class="form-control" name="hotel_rooms[${roomIndex}][room_number]">
                        </td>
                        <td>
                            <select class="form-control" name="hotel_rooms[${roomIndex}][room_type_id]">
                                @foreach(App\Models\HotelRoomType::where('status', 1)->get() as $roomType)
                                    <option value="{{ $roomType->id }}">{{ $roomType->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][price_per_night]" min="0" step="0.01">
                        </td>
                        <td>
                            <input type="number" class="form-control" name="hotel_rooms[${roomIndex}][discount_percentage]" value="0" min="0" max="100" step="0.01">
                        </td>
                        <td>
                            <select class="form-control" name="hotel_rooms[${roomIndex}][refund_policy]">
                                <option value="flexible">{{ __('Flexible') }}</option>
                                <option value="non-refundable">{{ __('Non-Refundable') }}</option>
                            </select>
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
        });
    </script>
@endsection
