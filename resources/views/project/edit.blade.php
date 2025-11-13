@extends('layouts.main')

@section('title')
    {{ __('Update Project') }}
@endsection

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
                            <a href="{{ route('project.index') }}" id="subURL">{{ __('View Project') }}</a>
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
    {!! Form::open(['route' => ['project.update',$project->id], 'data-parsley-validate', 'id' => 'edit-form', 'files' => true,'data-success-function'=> "formSuccessFunction"]) !!}
    <div class='row'>
        <div class='col-md-6'>
            <div class="card">
                <h3 class="card-header"> {{ __('Details') }}</h3>
                <hr>
                <input type="hidden" id="default-latitude" value="{{ system_setting('latitude') }}">
                <input type="hidden" id="default-longitude" value="{{ system_setting('longitude') }}">

                {{-- Category --}}
                <div class="card-body">
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('category', __('Category'), ['class' => 'form-label col-12 ']) }}
                        <select name="category_id" class="form-select form-control-sm" data-parsley-minSelect='1' id="project-category" required value="{{ $project->category_id }}">
                            <option value="" selected>{{ __('Choose Category') }}</option>
                            @foreach ($category as $row)
                                <option value="{{ $row->id }}" {{ $project->category_id == $row->id ? ' selected=selected' : '' }}>
                                    {{ $row->category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Title --}}
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('title', __('Title'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('title', $project->title, [ 'class' => 'form-control ', 'placeholder' =>  __('Title'), 'required' => 'true', 'id' => 'title', ]) }}
                    </div>

                    {{-- Slug --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('slug', __('Slug'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('slug', $project->slug_id, [ 'class' => 'form-control ', 'placeholder' =>  __('Slug'), 'id' => 'slug', ]) }}
                        <small class="text-danger text-sm">{{ __("Only Small English Characters, Numbers And Hypens Allowed") }}</small>
                    </div>

                    {{-- Description --}}
                    <div class="col-md-12 col-12 form-group mandatory">
                        {{ Form::label('description', __('Description'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('description', $project->description, [ 'class' => 'form-control mb-3', 'rows' => '5', 'id' => '', 'required' => 'true', 'placeholder' => __('Description') ]) }}
                    </div>

                    {{-- Arabic Title --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('title_ar', __('Arabic Title'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::text('title_ar', $project->title_ar ?? '', [ 'class' => 'form-control ', 'placeholder' =>  __('Arabic Title'), 'id' => 'title_ar', 'dir' => 'rtl' ]) }}
                    </div>

                    {{-- Arabic Description --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('description_ar', __('Arabic Description'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('description_ar', $project->description_ar ?? '', [ 'class' => 'form-control mb-3', 'rows' => '5', 'id' => '', 'placeholder' => __('Arabic Description'), 'dir' => 'rtl' ]) }}
                    </div>

                    {{-- Area Description --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('area_description', __('Area Description'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('area_description', $project->area_description ?? '', [ 'class' => 'form-control mb-3', 'rows' => '5', 'id' => '', 'placeholder' => __('Area Description') ]) }}
                    </div>

                    {{-- Arabic Area Description --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('area_description_ar', __('Arabic Area Description'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::textarea('area_description_ar', $project->area_description_ar ?? '', [ 'class' => 'form-control mb-3', 'rows' => '5', 'id' => '', 'placeholder' => __('Arabic Area Description'), 'dir' => 'rtl' ]) }}
                    </div>

                    {{-- Project Type --}}
                    <div class="col-md-12 col-12  form-group  mandatory">
                        <div class="row">
                            {{ Form::label('', __('Project Type'), ['class' => 'form-label col-12 ']) }}

                            {{-- Upcoming --}}
                            <div class="col-md-6">
                                {{ Form::radio('project_type', 'upcoming', null, [ 'class' => 'form-check-input edit-project-type', 'id' => 'upcoming', 'required' => true, 'checked' => true ]) }}
                                {{ Form::label('project_type', __('Upcoming'), ['class' => 'form-check-label','for' => 'upcoming']) }}
                            </div>

                            {{-- Under Construction --}}
                            <div class="col-md-6">
                                {{ Form::radio('project_type', 'under_construction', null, [ 'class' => 'form-check-input edit-project-type', 'id' => 'under_construction', 'required' => true, ]) }}
                                {{ Form::label('project_type', __('Under Construction'), ['class' => 'form-check-label','for' => 'under_construction']) }}
                            </div>
                        </div>
                    </div>

                    {{-- Release Date --}}
                    <div class="col-md-12 col-12 form-group">
                        {{ Form::label('release_date', __('Release Date'), ['class' => 'form-label col-12 ']) }}
                        {{ Form::date('release_date', $project->release_date, [ 'class' => 'form-control', 'placeholder' => __('Release Date'), 'id' => 'release_date' ]) }}
                    </div>

                    {{-- Property Details --}}
                    <div class="row">
                        <div class="col-md-6 form-group">
                            {{ Form::label('bedroom', __('Bedroom'), ['class' => 'form-label col-12 ']) }}
                            {{ Form::text('bedroom', $project->bedroom, [ 'class' => 'form-control', 'placeholder' => __('Bedroom'), 'id' => 'bedroom' ]) }}
                        </div>
                        <div class="col-md-6 form-group">
                            {{ Form::label('bathroom', __('Bathroom'), ['class' => 'form-label col-12 ']) }}
                            {{ Form::text('bathroom', $project->bathroom, [ 'class' => 'form-control', 'placeholder' => __('Bathroom'), 'id' => 'bathroom' ]) }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            {{ Form::label('garage', __('Garage'), ['class' => 'form-label col-12 ']) }}
                            {{ Form::text('garage', $project->garage, [ 'class' => 'form-control', 'placeholder' => __('Garage'), 'id' => 'garage' ]) }}
                        </div>
                        <div class="col-md-6 form-group">
                            {{ Form::label('year_built', __('Year Built'), ['class' => 'form-label col-12 ']) }}
                            {{ Form::text('year_built', $project->year_built, [ 'class' => 'form-control', 'placeholder' => __('Year Built'), 'id' => 'year_built' ]) }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 form-group">
                            {{ Form::label('lot_size', __('Lot Size'), ['class' => 'form-label col-12 ']) }}
                            {{ Form::text('lot_size', $project->lot_size, [ 'class' => 'form-control', 'placeholder' => __('Lot Size'), 'id' => 'lot_size' ]) }}
                        </div>
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
                        <textarea id="meta_title" name="meta_title" class="form-control" oninput="getWordCount('meta_title','meta_title_count','12.9px arial')" rows="2" style="height: 75px" placeholder="{{ __('Title') }}">{{ $project->meta_title }}</textarea>
                        <br>
                        <h6 id="meta_title_count">0</h6>
                    </div>

                    {{-- SEO Image --}}
                    <div class="col-md-6 col-sm-12 form-group card">
                        {{ Form::label('image', __('Image'), ['class' => 'form-label']) }}
                        <input type="file" name="meta_image" id="meta_image" class="filepond from-control" placeholder="{{ __('Image') }}">
                        <div class="img_error"></div>
                        @if(!empty($project->getRawOriginal('meta_image')))
                            <div class="card1 title_img mt-2">
                                <img src="{{ $project->meta_image }}" alt="Image" class="card1-img">
                            </div>
                        @endif
                    </div>

                    {{-- SEO Description --}}
                    <div class="col-md-12 col-sm-12 form-group">
                        {{ Form::label('description', __('Description'), ['class' => 'form-label text-center']) }}
                        <textarea id="meta_description" name="meta_description" class="form-control" oninput="getWordCount('meta_description','meta_description_count','12.9px arial')" rows="3" placeholder="{{ __('Description') }}">{{ $project->meta_description }}</textarea>
                        <br>
                        <h6 id="meta_description_count">0</h6>
                    </div>

                    {{-- SEO Keywords --}}
                    <div class="col-md-12 col-sm-12 form-group">
                        {{ Form::label('keywords', __('Keywords'), ['class' => 'form-label']) }}
                        <textarea name="keywords" id="" class="form-control" rows="3" placeholder="{{ __('Keywords') }}">{{ $project->meta_keywords }}</textarea>
                        (add comma separated keywords)
                    </div>

                </div>
            </div>
        </div>

        {{-- Location --}}
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
                                    {!! Form::hidden('city', $project->city, ['class' => 'form-control ', 'id' => 'city']) !!}
                                    <input id="searchInput" value="{{ $project->city }}" class="controls form-control" type="text" placeholder="{{ __('City') }}" required>
                                </div>
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('country', __('Country'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::text('country', !empty($project->country) ? $project->country : "", ['class' => 'form-control ', 'placeholder' => 'Country', 'id' => 'country', 'required' => true]) }}
                                </div>
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('state', __('State'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::text('state', !empty($project->state) ? $project->state : "", ['class' => 'form-control ', 'placeholder' => 'State', 'id' => 'state', 'required' => true]) }}
                                </div>
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('latitude', __('Latitude'), ['class' => 'form-label col-12 ']) }}
                                    {!! Form::text('latitude', !empty($project->latitude) ? $project->latitude : "", ['class' => 'form-control', 'id' => 'latitude', 'step' => 'any', 'readonly' => true, 'required' => true, 'placeholder' => trans('Latitude')]) !!}
                                </div>
                                <div class="col-md-6 form-group mandatory">
                                    {{ Form::label('longitude', __('Longitude'), ['class' => 'form-label col-12 ']) }}
                                    {!! Form::text('longitude', !empty($project->longitude) ? $project->longitude : "", ['class' => 'form-control', 'id' => 'longitude', 'step' => 'any', 'readonly' => true, 'required' => true, 'placeholder' => trans('Longitude')]) !!}
                                </div>
                                <div class="col-md-12 col-12 form-group mandatory">
                                    {{ Form::label('address', __('Address'), ['class' => 'form-label col-12 ']) }}
                                    {{ Form::textarea('address', $project->location, [
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

        {{-- Floor Plans --}}
        <div class="col-md-12">
            <div class="card">
                <h3 class="card-header">{{ __('Floor Plans') }}</h3>
                <hr>
                <div class="card-body projects-floor-plans">
                    {{-- Floor Section --}}
                    <div class="mt-4" data-repeater-list="floor_data">
                        <div class="row floor-section" data-repeater-item>
                            {!! Form::hidden('id', "",['class' => "floor-id"]) !!}
                            {{-- Floor Title --}}
                            <div class="form-group col-md-5">
                                <label class="form-label">{{ __('Floor') }} - <span class="floor-number">1</span> <span class="text-danger">*</span></label>
                                <input type="text" name="title" placeholder="{{__('Enter Floor Title')}}" class="form-control" required>
                            </div>

                            {{-- Floor Image --}}
                            <div class="form-group col-md-6">
                                <label class="form-label">{{ __('Image') }} <span class="text-danger floor-image-required">*</span></label>
                                <input type="file" class="form-control floor-image" name="floor_image" accept="image/jpg,image/png,image/jpeg" required>
                                <div style="width: 70px;">
                                    <a data-toggle='lightbox' href=><img class="img-fluid w-70 floor-image-preview mt-1" alt="" src=""/></a>
                                </div>
                            </div>
                            <div class="form-group col-md-1 pl-0 mt-4">
                                <button data-repeater-delete type="button" class="btn btn-icon btn-danger remove-floor" title="{{__('Remove Floor')}}">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- Add New Floor Button --}}
                    <div class="col-md-5 pl-0 mb-4">
                        <button type="button" class="btn btn-success add-new-floor" data-repeater-create title="{{__('Add New Floor')}}">
                            <span><i class="fa fa-plus"></i> {{__('Add New Floor')}}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>


        {{-- Images, Videos and Documents --}}
        <div class="col-md-12">
            <div class="card">
                <h3 class="card-header">{{ __('Images, Videos and Documents') }}</h3>
                <hr>
                <div class="card-body">
                    <div class="row">
                        {{-- Title Image --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3  form-group mandatory">
                            {{ Form::label('title-image', __('Title Image'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="title-image" name="image" accept="image/jpg,image/png,image/jpeg">
                            @if(!empty($project->getRawOriginal('image')))
                                <div class="card1 title_img mt-2">
                                    <img src="{{ $project->image }}" alt="Image" class="card1-img">
                                </div>
                            @endif
                        </div>

                        {{-- Gallery Images --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('gallary-images', __('Gallery Images'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="gallary-images" name="gallery_images[]" multiple accept="image/jpg,image/png,image/jpeg">
                            @if (!empty($project->gallary_images))
                                @foreach ($project->gallary_images as $row)
                                    <div class="col-12" id='{{ $row->id }}'>
                                        <div class="card1" style="height:10vh;">
                                            <img src="{{ url('') . config('global.IMG_PATH') . config('global.PROJECT_DOCUMENT_PATH'). '/' . $row->getRawOriginal('name') }}" alt="Image" class="card1-img">
                                            <button type="button" data-id="{{ $row->id }}" class="RemoveBtn1 RemoveBtngallary">x</button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        {{-- Documents --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('documents', __('Documents'), ['class' => 'form-label ']) }}
                            <input type="file" class="filepond doc-filepond" id="documents" name="documents[]" multiple accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            <div class="row mt-3" id="existing-documents-container">
                                @if (!empty($project->documents))
                                    @foreach ($project->documents as $row)
                                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3 col-xxl-3 mt-2" id="document-{{ $row->id }}">
                                            <div class="docs_main_div" style="position: relative;">
                                                <div class="doc_icon">
                                                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512" height="30" width="30" xmlns="http://www.w3.org/2000/svg"><path fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M208 64h66.75a32 32 0 0122.62 9.37l141.26 141.26a32 32 0 019.37 22.62V432a48 48 0 01-48 48H192a48 48 0 01-48-48V304"></path><path fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="32" d="M288 72v120a32 32 0 0032 32h120"></path><path fill="none" stroke-linecap="round" stroke-miterlimit="10" stroke-width="32" d="M160 80v152a23.69 23.69 0 01-24 24c-12 0-24-9.1-24-24V88c0-30.59 16.57-56 48-56s48 24.8 48 55.38v138.75c0 43-27.82 77.87-72 77.87s-72-34.86-72-77.87V144"></path></svg>
                                                </div>
                                                <div class="doc_title">
                                                    @php
                                                        $filename = basename($row->getRawOriginal('name'));
                                                    @endphp
                                                    <span title="{{ $filename }}">{{ $filename }}</span>
                                                </div>
                                                <div class="doc_download_button">
                                                    <a href="{{ $row->name }}" target="_blank">
                                                        <span>
                                                            <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="20" width="20" xmlns="http://www.w3.org/2000/svg"><path d="m12 16 4-5h-3V4h-2v7H8z"></path><path d="M20 18H4v-7H2v7c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2v-7h-2v7z"></path></svg>
                                                        </span>
                                                        <span>{{ __('Download') }}</span>
                                                    </a>
                                                </div>
                                                <div class="doc_remove_button" style="position: absolute; top: 5px; right: 5px;">
                                                    <button type="button" class="btn btn-danger btn-sm removeDocument" data-id="{{ $row->id }}" title="{{ __('Remove Document') }}" style="padding: 2px 6px; font-size: 12px;">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                        {{-- Video Link --}}
                        <div class="col-md-3">
                            {{ Form::label('video_link', __('Video Link'), ['class' => 'form-label']) }}
                            {{ Form::text('video_link', isset($project->video_link) ? $project->video_link : '', [ 'class' => 'form-control ', 'placeholder' => 'Video Link', 'id' => 'address', 'autocomplete' => 'off', ]) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Agreements --}}
        <div class="col-md-12">
            <div class="card">
                <h3 class="card-header">{{ __('Agreements') }}</h3>
                <hr>
                <div class="card-body">
                    <div class="row">
                        {{-- Ownership Contract --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('ownership_contract', __('Ownership Contract'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="ownership_contract" name="ownership_contract" accept="image/jpg,image/png,image/jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            @if(!empty($project->ownership_contract))
                                <div class="mt-2">
                                    <a href="{{ url('') . config('global.IMG_PATH') . config('global.PROJECT_DOCUMENT_PATH') . '/' . $project->ownership_contract }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="bi bi-file-earmark"></i> {{ __('View Document') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- National ID/Passport --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('national_id_passport', __('National ID/Passport'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="national_id_passport" name="national_id_passport" accept="image/jpg,image/png,image/jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            @if(!empty($project->national_id_passport))
                                <div class="mt-2">
                                    <a href="{{ url('') . config('global.IMG_PATH') . config('global.PROJECT_DOCUMENT_PATH') . '/' . $project->national_id_passport }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="bi bi-file-earmark"></i> {{ __('View Document') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Alternative ID --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('alternative_id', __('Alternative ID'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="alternative_id" name="alternative_id" accept="image/jpg,image/png,image/jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            @if(!empty($project->alternative_id))
                                <div class="mt-2">
                                    <a href="{{ url('') . config('global.IMG_PATH') . config('global.PROJECT_DOCUMENT_PATH') . '/' . $project->alternative_id }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="bi bi-file-earmark"></i> {{ __('View Document') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Utilities Bills --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('utilities_bills', __('Utilities Bills'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="utilities_bills" name="utilities_bills" accept="image/jpg,image/png,image/jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            @if(!empty($project->utilities_bills))
                                <div class="mt-2">
                                    <a href="{{ url('') . config('global.IMG_PATH') . config('global.PROJECT_DOCUMENT_PATH') . '/' . $project->utilities_bills }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="bi bi-file-earmark"></i> {{ __('View Document') }}
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Power of Attorney --}}
                        <div class="col-sm-12 col-md-6 col-lg-4 col-xl-3">
                            {{ Form::label('power_of_attorney', __('Power of Attorney'), ['class' => 'form-label']) }}
                            <input type="file" class="filepond" id="power_of_attorney" name="power_of_attorney" accept="image/jpg,image/png,image/jpeg,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            @if(!empty($project->power_of_attorney))
                                <div class="mt-2">
                                    <a href="{{ url('') . config('global.IMG_PATH') . config('global.PROJECT_DOCUMENT_PATH') . '/' . $project->power_of_attorney }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="bi bi-file-earmark"></i> {{ __('View Document') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Contact Details --}}
        <div class="col-md-12">
            <div class="card">
                <h3 class="card-header">{{ __('Contact Details') }}</h3>
                <hr>
                <div class="card-body">
                    {{-- Ownership Type --}}
                    <div class="form-group mb-4">
                        {{ Form::label('ownership_type', __('Ownership Type'), ['class' => 'form-label col-12']) }}
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                {{ Form::radio('ownership_type', 'individual', ($project->ownership_type ?? 'individual') === 'individual', ['class' => 'form-check-input', 'id' => 'ownership_individual']) }}
                                {{ Form::label('ownership_individual', __('Admin Info'), ['class' => 'form-check-label']) }}
                            </div>
                            <div class="form-check">
                                {{ Form::radio('ownership_type', 'employee', ($project->ownership_type ?? '') === 'employee', ['class' => 'form-check-input', 'id' => 'ownership_employee']) }}
                                {{ Form::label('ownership_employee', __('Employee Details'), ['class' => 'form-check-label']) }}
                            </div>
                            <div class="form-check">
                                {{ Form::radio('ownership_type', 'company', ($project->ownership_type ?? '') === 'company', ['class' => 'form-check-input', 'id' => 'ownership_company']) }}
                                {{ Form::label('ownership_company', __('Company'), ['class' => 'form-check-label']) }}
                            </div>
                        </div>
                    </div>

                    {{-- Admin Info (Individual) --}}
                    <div id="admin-info-section" class="contact-section" style="display: {{ ($project->ownership_type ?? 'individual') === 'individual' ? 'block' : 'none' }};">
                        <h5 class="mb-3">{{ __('Admin Info') }}</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                {{ Form::label('admin_full_name', __('Full Name'), ['class' => 'form-label']) }}
                                {{ Form::text('admin_full_name', $project->admin_full_name ?? '', ['class' => 'form-control', 'placeholder' => __('Full Name')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('admin_email', __('Email'), ['class' => 'form-label']) }}
                                {{ Form::email('admin_email', $project->admin_email ?? '', ['class' => 'form-control', 'placeholder' => __('Email')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('admin_phone_number', __('Phone Number'), ['class' => 'form-label']) }}
                                {{ Form::text('admin_phone_number', $project->admin_phone_number ?? '', ['class' => 'form-control', 'placeholder' => __('Phone Number')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('admin_whatsapp_number', __('WhatsApp Number'), ['class' => 'form-label']) }}
                                {{ Form::text('admin_whatsapp_number', $project->admin_whatsapp_number ?? '', ['class' => 'form-control', 'placeholder' => __('WhatsApp Number')]) }}
                            </div>
                            <div class="col-md-12 form-group">
                                {{ Form::label('admin_address', __('Address'), ['class' => 'form-label']) }}
                                {{ Form::textarea('admin_address', $project->admin_address ?? '', ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('Address')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('admin_profile_image', __('Profile Image'), ['class' => 'form-label']) }}
                                <input type="file" class="filepond" id="admin_profile_image" name="admin_profile_image" accept="image/jpg,image/png,image/jpeg">
                                @if(!empty($project->admin_profile_image))
                                    <div class="mt-2">
                                        <img src="{{ url('') . config('global.IMG_PATH') . config('global.PROJECT_DOCUMENT_PATH') . '/' . $project->admin_profile_image }}" alt="Profile Image" style="max-width: 100px; max-height: 100px;">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Employee Details --}}
                    <div id="employee-section" class="contact-section" style="display: {{ ($project->ownership_type ?? '') === 'employee' ? 'block' : 'none' }};">
                        <h5 class="mb-3">{{ __('Employee Details') }}</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                {{ Form::label('company_employee_username', __('Username'), ['class' => 'form-label']) }}
                                {{ Form::text('company_employee_username', $project->company_employee_username ?? '', ['class' => 'form-control', 'placeholder' => __('Username')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('company_employee_email', __('Email'), ['class' => 'form-label']) }}
                                {{ Form::email('company_employee_email', $project->company_employee_email ?? '', ['class' => 'form-control', 'placeholder' => __('Email')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('company_employee_phone_number', __('Phone Number'), ['class' => 'form-label']) }}
                                {{ Form::text('company_employee_phone_number', $project->company_employee_phone_number ?? '', ['class' => 'form-control', 'placeholder' => __('Phone Number')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('company_employee_whatsappnumber', __('WhatsApp Number'), ['class' => 'form-label']) }}
                                {{ Form::text('company_employee_whatsappnumber', $project->company_employee_whatsappnumber ?? '', ['class' => 'form-control', 'placeholder' => __('WhatsApp Number')]) }}
                            </div>
                        </div>
                    </div>

                    {{-- Company Details --}}
                    <div id="company-section" class="contact-section" style="display: {{ ($project->ownership_type ?? '') === 'company' ? 'block' : 'none' }};">
                        <h5 class="mb-3">{{ __('Company Details') }}</h5>
                        <div class="row">
                            <div class="col-md-6 form-group">
                                {{ Form::label('company_legal_name', __('Company Legal Name'), ['class' => 'form-label']) }}
                                {{ Form::text('company_legal_name', $project->company_legal_name ?? '', ['class' => 'form-control', 'placeholder' => __('Company Legal Name')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('manager_name', __('Manager Name'), ['class' => 'form-label']) }}
                                {{ Form::text('manager_name', $project->manager_name ?? '', ['class' => 'form-control', 'placeholder' => __('Manager Name')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('type_of_company', __('Type of Company'), ['class' => 'form-label']) }}
                                {{ Form::text('type_of_company', $project->type_of_company ?? '', ['class' => 'form-control', 'placeholder' => __('Type of Company')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('company_email_address', __('Email Address'), ['class' => 'form-label']) }}
                                {{ Form::email('company_email_address', $project->company_email_address ?? '', ['class' => 'form-control', 'placeholder' => __('Email Address')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('bank_branch', __('Bank Branch'), ['class' => 'form-label']) }}
                                {{ Form::text('bank_branch', $project->bank_branch ?? '', ['class' => 'form-control', 'placeholder' => __('Bank Branch')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('company_country', __('Country'), ['class' => 'form-label']) }}
                                {{ Form::text('company_country', $project->company_country ?? '', ['class' => 'form-control', 'placeholder' => __('Country')]) }}
                            </div>
                            <div class="col-md-12 form-group">
                                {{ Form::label('bank_address', __('Bank Address'), ['class' => 'form-label']) }}
                                {{ Form::textarea('bank_address', $project->bank_address ?? '', ['class' => 'form-control', 'rows' => '3', 'placeholder' => __('Bank Address')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('bank_account_number', __('Bank Account Number'), ['class' => 'form-label']) }}
                                {{ Form::text('bank_account_number', $project->bank_account_number ?? '', ['class' => 'form-control', 'placeholder' => __('Bank Account Number')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('iban', __('IBAN'), ['class' => 'form-label']) }}
                                {{ Form::text('iban', $project->iban ?? '', ['class' => 'form-control', 'placeholder' => __('IBAN')]) }}
                            </div>
                            <div class="col-md-6 form-group">
                                {{ Form::label('swift_code', __('Swift Code'), ['class' => 'form-label']) }}
                                {{ Form::text('swift_code', $project->swift_code ?? '', ['class' => 'form-control', 'placeholder' => __('Swift Code')]) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save --}}
        <div class='col-md-12 d-flex justify-content-end mb-3'>
            <input type="submit" class="btn btn-primary" value="{{ __('Save') }}"> &nbsp;&nbsp;
            <button class="btn btn-secondary" type="button" onclick="myForm.reset();">{{ __('Reset') }}</button>
        </div>
    </div>

    {!! Form::close() !!}
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
            // Initialize map
            initMap();

            // Don't add iframe here - it's causing conflicts with the Google Maps API
            $('.parsley-error filled,.parsley-required').attr("aria-hidden", "true");
            $('.parsley-error filled,.parsley-required').hide();
            FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateSize,
                FilePondPluginFileValidateType);
            
            // Configure FilePond for documents input
            if ($('#documents').length) {
                $('#documents').filepond({
                    credits: null,
                    allowFileSizeValidation: true,
                    maxFileSize: '25MB',
                    labelMaxFileSizeExceeded: 'File is too large',
                    labelMaxFileSize: 'Maximum file size is {filesize}',
                    allowFileTypeValidation: true,
                    acceptedFileTypes: ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    labelFileTypeNotAllowed: 'File of invalid type',
                    fileValidateTypeLabelExpectedTypes: 'Expects PDF or Word documents',
                    storeAsFile: true,
                    allowPdfPreview: true,
                    pdfPreviewHeight: 320,
                    pdfComponentExtraParams: 'toolbar=0&navpanes=0&scrollbar=0&view=fitH',
                    allowMultiple: true
                });
            }
        });

        $(document).ready(function() {
            $('.reset-form').on('click',function(e){
                e.preventDefault();
                $('#myForm')[0].reset();
            });
            if ($('input[name="property_type"]:checked').val() == 0) {
                $('#duration').hide();
                $('#price_duration').removeAttr('required');
            } else {
                $('#duration').show();
            }
            getWordCount("meta_title", "meta_title_count", "19.9px arial");
            getWordCount("meta_description", "meta_description_count", "12.9px arial");

            projectFloorPlanRepeater.setList([
                @foreach($project->plans as $key => $floorPlan)
                    {
                        id: "{{$floorPlan->id}}",
                        title: "{{$floorPlan->title}}",
                    },
                @endforeach
            ]);

            @foreach($project->plans as $key => $floorPlan)
                // if floor plan image Exists
                @if($floorPlan->getOriginal('document'))
                    $('#floor-image-required-{{ $key }}').html("") // remove * from label
                    $('#floor-image-required-{{ $key }}').parent().siblings().removeAttr('required') // Remove Required from file input
                    $('#floor-image-preview-{{ $key }}').attr('src', "{{ $floorPlan->document }}") // Add floor plan image in Image Tag
                    $('#floor-image-preview-{{ $key }}').parent().attr('href', "{{ $floorPlan->document }}") // Add floor plan image in image Tag

                    $('#remove-floor-{{ $key }}').attr('data-id', "{{ $floorPlan->id }}") // Add floor plan image in image Tag
                    $('#remove-floor-{{ $key }}').attr('data-url', "{{ route('project.remove-floor-plan',$floorPlan->id) }}") // Add floor plan image in image Tag
                @else
                    $('#floor-image-required-{{ $key }}').parent().siblings().attr('required', true) // Add * in label
                    $('#floor-image-required-{{ $key }}').html("*") // Add Required attribute in file input
                @endif
            @endforeach
        });

        $('input[name="property_type"]').change(function() {
            // Get the selected value
            var selectedType = $('input[name="property_type"]:checked').val();

            // Perform actions based on the selected value
            if (selectedType == 1) {
                $('#duration').show();
                $('#price_duration').attr('required', 'true');
            } else {
                $('#duration').hide();
                $('#price_duration').removeAttr('required');
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
                        url: "{{ route('project.remove-gallary-images') }}",

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
        $("#title").on('keyup',function(e){
            let title = $(this).val();
            let id = "{{ $project->id }}";
            let slugElement = $("#slug");
            if(title){
                $.ajax({
                    type: 'POST',
                    url: "{{ route('project.generate-slug') }}",
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

        // Handle ownership type change
        $(document).ready(function() {
            $('input[name="ownership_type"]').on('change', function() {
                var selectedType = $(this).val();
                $('.contact-section').hide();
                if (selectedType === 'individual') {
                    $('#admin-info-section').show();
                } else if (selectedType === 'employee') {
                    $('#employee-section').show();
                } else if (selectedType === 'company') {
                    $('#company-section').show();
                }
            });
        });




        $(document).on('click', '.removeDocument', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var $documentElement = $('#document-' + id);
            
            Swal.fire({
                title: window.trans['Are you sure you wants to remove this document ?'] || 'Are you sure you want to remove this document?',
                icon: 'warning',
                showDenyButton: true,
                confirmButtonText: window.trans['Yes'] || 'Yes',
                denyButtonText: window.trans['No'] || 'No',
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    // Remove from DOM immediately for better UX
                    $documentElement.fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    $.ajax({
                        url: "{{ route('project.remove-document') }}",
                        type: "POST",
                        data: {
                            '_token': "{{ csrf_token() }}",
                            "id": id
                        },
                        success: function(response) {
                            if (response.error == false) {
                                Toastify({
                                    text: window.trans['Document Deleted Successfully'] || 'Document Deleted Successfully',
                                    duration: 2000,
                                    close: !0,
                                    backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)"
                                }).showToast();
                            } else if (response.error == true) {
                                // If deletion failed, restore the element
                                $documentElement.show();
                                Toastify({
                                    text: window.trans['Something Went Wrong'] || 'Something Went Wrong',
                                    duration: 5000,
                                    close: !0,
                                    backgroundColor: '#dc3545'
                                }).showToast();
                            }
                        },
                        error: function(xhr) {
                            // If deletion failed, restore the element
                            $documentElement.show();
                            Toastify({
                                text: window.trans['Something Went Wrong'] || 'Something Went Wrong',
                                duration: 5000,
                                close: !0,
                                backgroundColor: '#dc3545'
                            }).showToast();
                        }
                    });
                }
            });
        });

        function formSuccessFunction(response) {
            if(!response.error){
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
        }
    </script>
@endsection
