@extends('layouts.main')

@section('title')
    {{ __('Google Analytics') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="divider">
                            <div class="divider-text">
                                <h4>{{ __('Google Analytics Configuration') }}</h4>
                            </div>
                        </div>
                    </div>

                    <div class="card-content">
                        <div class="card-body">
                            {!! Form::open(['url' => route('google-analytics.store'), 'data-parsley-validate']) !!}
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i> {{ __('Enter your Google Analytics Measurement ID (e.g., G-XXXXXXXXXX) to enable tracking on your website.') }}
                                    </div>
                                </div>
                                
                                <div class="col-md-12 form-group mandatory">
                                    {{ Form::label('google_analytics_id', __('Measurement ID'), ['class' => 'form-label']) }}
                                    {{ Form::text('google_analytics_id', $google_analytics_id ?? '', ['class' => 'form-control', 'placeholder' => 'G-XXXXXXXXXX', 'required' => 'true']) }}
                                    <small class="text-muted">{{ __('You can find this in your Google Analytics account under Admin > Data Streams.') }}</small>
                                </div>

                                <div class="col-12 d-flex justify-content-end mt-3">
                                    {{ Form::submit(__('Save Settings'), ['class' => 'btn btn-primary me-1 mb-1']) }}
                                </div>
                            </div>

                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
