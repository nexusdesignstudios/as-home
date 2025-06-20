@extends('layouts.main')

@section('title')
    {{ __('System Settings') }}
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
        <form class="form" id="myForm" action="{{ url('set_settings') }}" data-parsley-validate method="POST" id="setting_form" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="form-group row">
                <div class="col-12">
                    <div class="card" style="height: 95%">

                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('Company Details') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">

                                    {{-- Company Name --}}
                                    <div class="col-sm-12 col-md-6 mt-2">
                                        <label class="form-label center" for="company_name">{{ __('Company Name') }}</label>
                                        <input name="company_name" type="text" class="form-control" id="company_name" placeholder="{{ __('Company Name') }}" value="{{ system_setting('company_name') != '' ? system_setting('company_name') : 'eBroker' }}">
                                    </div>

                                    {{-- Email --}}
                                    <div class="col-sm-12 col-md-6 mt-2">
                                        <label class="form-label" for="email">{{ __('Email') }}</label>
                                        <input name="company_email" type="email" id="email" class="form-control" placeholder="{{ __('Email') }}" value="{{ system_setting('company_email') != '' ? system_setting('company_email') : '' }}">
                                    </div>

                                    {{-- Contact Number 1 --}}
                                    <div class="col-sm-12 col-md-6 mt-2">
                                        <label class="form-label" for="company-tel1">{{ __('Contact Number 1') }}</label>
                                        <input name="company_tel1" type="text" id="company-tel1" class="form-control" placeholder="{{ __('Contact Number 1') }}" value="{{ system_setting('company_tel1') != '' ? system_setting('company_tel1') : '' }}">
                                    </div>

                                    {{-- Contact Number 2 --}}
                                    <div class="col-sm-12 col-md-6 mt-2">
                                        <label class="form-label mt-1" for="company-tel2">{{ __('Contact Number 2') }}</label>
                                        <input name="company_tel2" type="text" id="company-tel2" class="form-control" placeholder="{{ __('Contact Number 2') }}" value="{{ system_setting('company_tel2') != '' ? system_setting('company_tel2') : '' }}">
                                    </div>

                                    {{-- Latitude --}}
                                    <div class="col-sm-12 col-md-6 mt-2">
                                        <label class="form-label" for="latitude">{{ __('Latitude') }}</label>
                                        <input name="latitude" type="text" id="latitude" class="form-control" placeholder="{{ __('Latitude') }}" value="{{ system_setting('latitude') != '' ? system_setting('latitude') : '' }}">
                                    </div>

                                    {{-- Longitude --}}
                                    <div class="col-sm-12 col-md-6 mt-2">
                                        <label class="form-label mt-1" for="longitude">{{ __('Longitude') }}</label>
                                        <input name="longitude" type="text" id="longitude" class="form-control" placeholder="{{ __('Longitude') }}" value="{{ system_setting('longitude') != '' ? system_setting('longitude') : '' }}">
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <label class="form-label-mandatory" for="company-address">{{ __('Company Address') }}</label>
                                    <div class="col-sm-12">
                                        <textarea name="company_address" class="form-control" id="company_address" rows="3" placeholder="{{ __('Company Address') }}">{{ system_setting('company_address') != '' ? system_setting('company_address') : '' }}</textarea>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="divider pt-3">
                                <h6 class="divider-text">{{ __('More Settings') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">

                                    {{-- Countries --}}
                                    <div class="col-sm-12 col-md-6 col-lg-4 mt-2 form-group mandatory">
                                        <label class="col-sm-12 form-label" for="currency-code">{{ __('Currency Name') }}</label>
                                        <select id="currency-code" class="form-select form-control-sm select2" name="currency_code" required>
                                            <option value="">{{ __('Select Currency') }}</option>
                                            @if(!empty($listOfCurrencies))
                                                @foreach ($listOfCurrencies as $data)
                                                    <option value="{{ $data['currency_code'] }}">{{ $data['currency_name'] }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        <input type="hidden" id="url-for-currency-symbol" value="{{ route('get-currency-symbol') }}">
                                    </div>

                                    {{-- Currency Symbol --}}
                                    <div class="col-sm-12 col-md-6 col-lg-4 mt-2 form-group mandatory">
                                        <label class="col-sm-12 form-label " for="curreny-symbol">{{ __('Currency Symbol') }}</label>
                                        <input name="currency_symbol" type="text" id="currency-symbol" class="form-control" placeholder="{{ __('Currency Symbol') }}" required maxlength="5">
                                    </div>

                                    {{-- Default Language --}}
                                    <div class="col-sm-12 col-md-6 col-lg-4 mt-2 form-group mandatory">
                                        <label class="col-sm-12 form-label mt-1" for="default_language">{{ __('Default Language') }}</label>
                                        <select name="default_language" id="default_language" class="choosen-select form-select form-control-sm" required>
                                            @foreach ($languages as $row)
                                                {{ $row }}
                                                <option value="{{ $row->code }}"
                                                    {{ system_setting('default_language') == $row->code ? 'selected' : '' }}>
                                                    {{ $row->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <hr class="mt-4" style="">

                                    {{-- Unsplash API Key --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group">
                                        <label class="col-sm-12 form-label" for="unsplash-api-key">{{ __('Unsplash API Key') }}</label>
                                        <input name="unsplash_api_key" type="text" id="unsplash-api-key" class="form-control" placeholder="{{ __('Unsplash API Key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('unsplash_api_key') != '' ? system_setting('unsplash_api_key') : '' ) : '****************************' ) : ( system_setting('unsplash_api_key') != '' ? system_setting('unsplash_api_key') : '' ))}}">
                                    </div>

                                    {{-- Place API Key --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group">
                                        <label class="col-sm-12 form-label" for="place-api-key">{{ __('Place API Key') }}</label>
                                        <input name="place_api_key" type="text" id="place-api-key" class="form-control" placeholder="{{ __('Place API Key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('place_api_key') != '' ? system_setting('place_api_key') : '' ) : '****************************' ) : ( system_setting('place_api_key') != '' ? system_setting('place_api_key') : '' ))}}">
                                    </div>

                                    {{-- Playstore App link --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group">
                                        <label class="col-sm-12 form-label">{{ __('Playstore Id') }}</label>
                                        <input name="playstore_id" type="text" class="form-control" placeholder="{{ __('Playstore Id') }}" value="{{ system_setting('playstore_id') != '' ? system_setting('playstore_id') : '' }}">
                                    </div>

                                    {{-- Appstore App link --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group">
                                        <label class="col-sm-12 form-label">{{ __('Appstore Id') }}</label>
                                        <input name="appstore_id" type="text" class="form-control" placeholder="{{ __('Appstore Id') }}" value="{{ system_setting('appstore_id') != '' ? system_setting('appstore_id') : '' }}">
                                    </div>

                                    {{-- Number With Suffix --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group">
                                        <label class="col-sm-4 form-check-label" for="switch_number_with_suffix">{{ __('Number With Suffix') }}</label>
                                        <div class="col-sm-1 col-md-1 col-xs-12 ">
                                            <div class="form-check form-switch">
                                                <input type="hidden" name="number_with_suffix" id="number_with_suffix" value="{{ system_setting('number_with_suffix') != '' ? system_setting('number_with_suffix') : 0 }}">
                                                <input class="form-check-input" type="checkbox" role="switch" {{ system_setting('number_with_suffix') == '1' ? 'checked' : '' }} id="switch_number_with_suffix">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Change Icon Colors to theme Color --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group">
                                        <label class="col-sm-5 form-check-label" for="switch_svg_clr">{{ __('Change Icon Colors to theme Color ?') }}</label>
                                        <div class="col-sm-1">
                                            <div class="form-check form-switch ">
                                                <input type="hidden" name="svg_clr" id="svg_clr" value="{{ system_setting('svg_clr') != '' ? system_setting('svg_clr') : 0 }}">
                                                <input class="form-check-input" type="checkbox" role="switch" {{ system_setting('svg_clr') == '1' ? 'checked' : '' }} id="switch_svg_clr">
                                                <label class="form-check-label" for="switch_svg_clr"></label>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- Distance Options --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group mandatory">
                                        <label class="col-sm-12 form-label mt-3" for="distance-option">{{ __('Distance Options') }}</label>
                                        <select name="distance_option" id="distance-option" class="choosen-select form-select form-control-sm" required>
                                            <option  {{ system_setting('distance_option') == 'km' ? 'selected' : '' }} value="km">{{__('Kilometers')}}</option>
                                            <option  {{ system_setting('distance_option') == 'm' ? 'selected' : '' }} value="m">{{__('Meters')}}</option>
                                            <option  {{ system_setting('distance_option') == 'mi' ? 'selected' : '' }} value="mi">{{__('Miles')}}</option>
                                            <option  {{ system_setting('distance_option') == 'yd' ? 'selected' : '' }} value="yd">{{__('Yards')}}</option>
                                        </select>
                                    </div>

                                    {{-- System Color --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group">
                                        <label class="col-sm-12 form-label mt-3">{{ __('System Color') }}</label>
                                        <input name="system_color" type="color" class="form-control" placeholder="{{ __('System Color') }}" value="{{ system_setting('system_color') != '' ? system_setting('system_color') : '#087C7C' }}" id="systemColor">
                                        <input type="hidden" id="hiddenRGBA" name="rgb_color">
                                    </div>

                                    {{-- Web URL --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group">
                                        <label class="form-label mt-3">{{ __('Web URL') }}</label>
                                        <input name="web_url" id="web-url" type="text" class="form-control" placeholder="{{ __('Web URL') }}" value="{{ ( system_setting('web_url') != '' ? system_setting('web_url') : '' )}}">
                                    </div>

                                    {{-- Text after property submission --}}
                                    <div class="col-sm-12 col-md-6 mt-2 form-group mandatory">
                                        <label class="col-sm-12 form-label mt-3">{{ __('Text after property submission') }}</label>
                                        <textarea name="text_property_submission" class="form-control" rows="2" placeholder="{{ __("Text after property submission") }}" required>{{ ( system_setting('text_property_submission') != '' ? system_setting('text_property_submission') : '' )}}</textarea>
                                    </div>


                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Login Methods --}}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="divider pt-3">
                            <h6 class="divider-text">{{ __('Login Methods') }}</h6>
                        </div>
                        <div class="card-body row">

                            {{-- Number with OTP Login Toggle --}}
                            <div class="col-sm-12 col-md-6 mt-2 form-group mandatory">
                                <label class="form-check-label" for="number-with-otp-login-toggle">{{ __('Number with OTP Login') }}</label>
                                <div class="col-sm-1">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="number_with_otp_login" id="number-with-otp-login" value="{{ system_setting('number_with_otp_login') == 1 ? 1 : 0 }}">
                                        <input class="form-check-input" type="checkbox" role="switch" {{ system_setting('number_with_otp_login') == '1' ? 'checked' : '' }} id="number-with-otp-login-toggle">
                                        <label class="form-check-label" for="number-with-otp-login-toggle"></label>
                                    </div>
                                </div>
                            </div>

                            {{-- OTP Services Provider --}}
                            <div class="col-sm-12 col-md-6 mt-2 form-group mandatory" id="otp-services-provider-div" style="display: none">
                                <label class="col-sm-12 form-label-mandatory" for="otp-services-provider">{{ __('OTP Services Provider') }}</label>
                                <select name="otp_service_provider" id="otp-services-provider" class="choosen-select form-select form-control-sm">
                                    <option  {{ system_setting('otp_service_provider') == 'firebase' ? 'selected' : '' }} value="firebase">{{__('Firebase')}}</option>
                                    <option  {{ system_setting('otp_service_provider') == 'twilio' ? 'selected' : '' }} value="twilio">{{__('Twilio')}}</option>
                                </select>
                            </div>

                            <div class="col-12 mt-2 p-4 row bg-light rounded" id="twilio-sms-settings-div" style="display: none">
                                {{-- TWILIO --}}
                                <h5>{{ __('Twilio SMS Settings') }}</h5>

                                {{-- Account SID --}}
                                <div class="col-sm-12 col-md-6 col-lg-4 mt-2 form-group mandatory">
                                    <label class="col-sm-12 form-label" for="twilio-account-sid">{{ __('Account SID') }}</label>
                                    <input name="twilio_account_sid" type="text" class="form-control twilio-account-settings" id="twilio-account-sid" placeholder="{{ __('Account SID') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('twilio_account_sid') != '' ? system_setting('twilio_account_sid') : '' ) : '****************************' ) : ( system_setting('twilio_account_sid') != '' ? system_setting('twilio_account_sid') : '' ))}}">
                                </div>

                                {{-- Auth Token --}}
                                <div class="col-sm-12 col-md-6 col-lg-4 mt-2 form-group mandatory">
                                    <label class="col-sm-12 form-label" for="twilio-auth-token">{{ __('Auth Token') }}</label>
                                    <input name="twilio_auth_token" type="text" class="form-control twilio-account-settings" id="twilio-auth-token" placeholder="{{ __('Auth Token') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('twilio_auth_token') != '' ? system_setting('twilio_auth_token') : '' ) : '****************************' ) : ( system_setting('twilio_auth_token') != '' ? system_setting('twilio_auth_token') : '' ))}}">
                                </div>

                                {{-- My Twilio Phone Number --}}
                                <div class="col-sm-12 col-md-6 col-lg-4 mt-2 form-group mandatory">
                                    <label class="col-sm-12 form-label" for="twilio-my-phone-number">{{ __('My Twilio Phone Number') }}</label>
                                    <input name="twilio_my_phone_number" type="text" class="form-control twilio-account-settings" id="twilio-my-phone-number" placeholder="{{ __('My Twilio Phone Number') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('twilio_my_phone_number') != '' ? system_setting('twilio_my_phone_number') : '' ) : '****************************' ) : ( system_setting('twilio_my_phone_number') != '' ? system_setting('twilio_my_phone_number') : '' ))}}">
                                </div>
                            </div>

                            {{-- Social Login Toggle --}}
                            <div class="col-sm-12 col-md-6 mt-2 form-group mandatory">
                                <label class="form-check-label" for="social-login-toggle">{{ __('Social Login') }}</label>
                                <div class="col-sm-1">
                                    <div class="form-check form-switch ">
                                        <input type="hidden" name="social_login" id="social-login" value="{{ system_setting('social_login') == 1 ? 1 : 0 }}">
                                        <input class="form-check-input" type="checkbox" role="switch" {{ system_setting('social_login') == '1' ? 'checked' : '' }} id="social-login-toggle">
                                        <label class="form-check-label mandatory" for="social-login-toggle"></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">

                    {{-- Paypal Settings --}}
                    <div class="divider pt-3 mt-3">
                        <h6 class="divider-text">{{ __('Paypal Setting') }}</h6>
                    </div>
                    <div class="form-group row">

                        {{-- Paypal Business ID --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Paypal Business ID') }}</label>
                            <input name="paypal_business_id" type="text" class="form-control" placeholder="{{ __('Paypal Business ID') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('paypal_business_id') != '' ? system_setting('paypal_business_id') : '' ) : '****************************' ) : ( system_setting('paypal_business_id') != '' ? system_setting('paypal_business_id') : '' ))}}">
                        </div>

                        {{-- Paypal Webhook URL --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Paypal Webhook URL') }}</label>
                            <input name="paypal_webhook_url" type="text" class="form-control" placeholder="{{ __('Paypal Webhook URL') }}" value="{{ system_setting('paypal_webhook_url') != '' ? system_setting('paypal_webhook_url') : url('/webhook/paypal') }}" readonly>
                        </div>

                        {{-- Paypal Currency Symbol --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label">{{ __('Paypal Currency Symbol') }}</label>
                            <select name="paypal_currency" id="paypal_currency" class="choosen-select form-select form-control-sm">
                                @foreach ($paypalCurrencies as $key => $value)
                                    <option value={{ $key }} {{ system_setting('paypal_currency') == $key ? 'selected' : '' }}> {{ $key }} - {{ $value }}</option>
                                @endforeach
                            </select>
                        </div>


                        {{-- Status --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label" id='lbl_paypal'>{{ __('Enable') }}</label>
                            <div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="paypal_gateway" id="paypal_gateway" value="{{ system_setting('paypal_gateway') != '' ? system_setting('paypal_gateway') : 0 }}">
                                    <input class="form-check-input" type="checkbox" role="switch" class="switch-input" name='op' {{ system_setting('paypal_gateway') == '1' ? 'checked' : '' }} id="switch_paypal_gateway">
                                    <label class="form-check-label" for="switch_paypal_gateway"></label>
                                </div>
                            </div>
                        </div>

                        {{-- Sandbox Mode --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label">{{ __('Sandbox Mode') }}</label>
                            <div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="sandbox_mode" id="sandbox_mode" value="{{ system_setting('sandbox_mode') != '' ? system_setting('sandbox_mode') : 0 }}">
                                    <input class="form-check-input" type="checkbox" role="switch" {{ system_setting('sandbox_mode') == '1' ? 'checked' : '' }} id="switch_sandbox_mode">
                                    <label class="form-check-label" for="switch_sandbox_mode"></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Razorpay Setting --}}
                    <div class="divider pt-3 mt-3">
                        <h6 class="divider-text">{{ __('Razorpay Setting') }}</h6>
                    </div>

                    <div class="form-group row">

                        {{-- Razorpay key --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Razorpay key') }}</label>
                            <input name="razor_key" type="text" class="form-control" placeholder="{{ __('Razorpay Key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('razor_key') != '' ? system_setting('razor_key') : '' ) : '****************************' ) : ( system_setting('razor_key') != '' ? system_setting('razor_key') : '' ))}}">
                        </div>

                        {{-- Razorpay Webhook URL --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Razorpay Webhook URL') }}</label>
                            <input name="razorpay_webhook_url" type="text" class="form-control" placeholder="{{ __('Razorpay Webhook URL') }}" value="{{ system_setting('razorpay_webhook_url') != '' ? system_setting('razorpay_webhook_url') : url('/webhook/razorpay') }}" readonly>
                        </div>

                        {{-- Razorpay Secret --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Razorpay Secret') }}</label>
                            <input name="razor_secret" type="text" class="form-control" placeholder="{{ __('Razorpay Secret') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('razor_secret') != '' ? system_setting('razor_secret') : '' ) : '****************************' ) : ( system_setting('razor_secret') != '' ? system_setting('razor_secret') : '' ))}}">
                        </div>

                        {{-- Status --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label" id='lbl_razorpay'>{{ __('Enable') }}</label>
                            <div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="razorpay_gateway" id="razorpay_gateway" value="{{ system_setting('razorpay_gateway') != '' ? system_setting('razorpay_gateway') : 0 }}">
                                    <input class="form-check-input" type="checkbox" role="switch" class="switch-input" name='op' {{ system_setting('razorpay_gateway') == '1' ? 'checked' : '' }} id="switch_razorpay_gateway">
                                    <label class="form-check-label" for="switch_razorpay_gateway"></label>
                                </div>
                            </div>
                        </div>

                        {{-- Razorpay Webhook Secret --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Razorpay Webhook Secret') }}</label>
                            <input name="razor_webhook_secret" type="text" class="form-control" placeholder="{{ __('Razorpay Webhook Secret') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('razor_webhook_secret') != '' ? system_setting('razor_webhook_secret') : '' ) : '****************************' ) : ( system_setting('razor_webhook_secret') != '' ? system_setting('razor_webhook_secret') : '' ))}}">
                        </div>

                    </div>

                    {{-- Paystack Setting --}}
                    <div class="divider pt-3 mt-3">
                        <h6 class="divider-text">{{ __('Paystack Setting') }}</h6>
                    </div>

                    <div class="form-group row">

                        {{-- Paystack Secret key --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Paystack Secret key') }}</label>
                            <input name="paystack_secret_key" type="text" class="form-control" placeholder="{{ __('Paystack Secret Key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('paystack_secret_key') != '' ? system_setting('paystack_secret_key') : '' ) : '****************************' ) : ( system_setting('paystack_secret_key') != '' ? system_setting('paystack_secret_key') : '' ))}}">
                        </div>

                        {{-- Paystack Webhook URL --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Paystack Webhook URL') }}</label>
                            <input name="paystack_webhook_url" type="text" class="form-control" placeholder="{{ __('Paystack Webhook URL') }}" value="{{ system_setting('paystack_webhook_url') != '' ? system_setting('paystack_webhook_url') : url('/webhook/paystack') }}" readonly>
                        </div>

                        {{-- Paystack Currency Symbol --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Paystack Currency Symbol') }}</label>
                            <select name="paystack_currency" id="paystack_currency" class="choosen-select form-select form-control-sm">
                                <option value="GHS" {{ system_setting('paystack_currency') == 'GHS' ? 'selected' : '' }}> GHS</option>
                                <option value="NGN" {{ system_setting('paystack_currency') == 'NGN' ? 'selected' : '' }}> NGN</option>
                                <option value="USD" {{ system_setting('paystack_currency') == 'USD' ? 'selected' : '' }}> USD</option>
                                <option value="ZAR" {{ system_setting('paystack_currency') == 'ZAR' ? 'selected' : '' }}> ZAR</option>
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label" id='lbl_paystack'>{{ __('Enable') }}</label>
                            <div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="paystack_gateway" id="paystack_gateway" value="{{ system_setting('paystack_gateway') != '' ? system_setting('paystack_gateway') : 0 }}">
                                    <input class="form-check-input" type="checkbox" role="switch" class="switch-input" name='op' {{ system_setting('paystack_gateway') == '1' ? 'checked' : '' }} id="switch_paystack_gateway">
                                </div>
                            </div>
                        </div>

                        {{-- Paystack Public key --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Paystack Public key') }}</label>
                            <input name="paystack_public_key" type="text" class="form-control" placeholder="{{ __('Paystack Public Key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('paystack_public_key') != '' ? system_setting('paystack_public_key') : '' ) : '****************************' ) : ( system_setting('paystack_public_key') != '' ? system_setting('paystack_public_key') : '' ))}}">
                        </div>

                    </div>

                    {{-- Stripe Setting --}}
                    <div class="divider pt-3 mt-3">
                        <h6 class="divider-text">{{ __('Stripe Setting') }}</h6>
                    </div>

                    <div class="form-group row">
                        {{-- Stripe publishable key --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Stripe publishable key') }}</label>
                            <input name="stripe_publishable_key" type="text" class="form-control" placeholder="{{ __('Stripe publishable key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('stripe_publishable_key') != '' ? system_setting('stripe_publishable_key') : '' ) : '****************************' ) : ( system_setting('stripe_publishable_key') != '' ? system_setting('stripe_publishable_key') : '' ))}}">
                        </div>

                        {{-- Stripe Webhook URL --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Stripe Webhook URL') }}</label>
                            <input name="stripe_webhook_url" type="text" class="form-control" placeholder="{{ __('Stripe Webhook URL') }}" value="{{ system_setting('stripe_webhook_url') != '' ? system_setting('stripe_webhook_url') : url('/webhook/stripe') }}" readonly>
                        </div>

                        {{-- Stripe Currency Symbol --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label">{{ __('Stripe Currency Symbol') }}</label>
                            <select name="stripe_currency" id="stripe_currency" class="choosen-select form-select form-control-sm">
                                @foreach ($stripe_currencies as $value)
                                <option value={{ $value }}
                                {{ system_setting('stripe_currency') == $value ? 'selected' : '' }}>
                                {{ $value }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label" id='lbl_stripe'>{{ __('Enable') }}</label>
                            <div>
                                <div class="form-check form-switch ">
                                    <input type="hidden" name="stripe_gateway" id="stripe_gateway" value="{{ system_setting('stripe_gateway') != '' ? system_setting('stripe_gateway') : 0 }}">
                                    <input class="form-check-input" type="checkbox" role="switch" class="switch-input" name='op' {{ system_setting('stripe_gateway') == '1' ? 'checked' : '' }} id="switch_stripe_gateway">
                                </div>
                            </div>
                        </div>

                        {{-- Stripe Secret key --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label-mandatory">{{ __('Stripe Secret key') }}</label>
                            <input name="stripe_secret_key" type="text" class="form-control" placeholder="{{ __('Stripe Secret key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('stripe_secret_key') != '' ? system_setting('stripe_secret_key') : '' ) : '****************************' ) : ( system_setting('stripe_secret_key') != '' ? system_setting('stripe_secret_key') : '' ))}}">
                        </div>

                        {{-- Stripe Secret key --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label-mandatory">{{ __('Stripe Webhook Secret key') }}</label>
                            <input name="stripe_webhook_secret_key" type="text" class="form-control" placeholder="{{ __('Stripe Webhook Secret key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('stripe_webhook_secret_key') != '' ? system_setting('stripe_webhook_secret_key') : '' ) : '****************************' ) : ( system_setting('stripe_webhook_secret_key') != '' ? system_setting('stripe_webhook_secret_key') : '' ))}}">
                        </div>

                    </div>


                    {{-- Flutterwave Setting --}}
                    <div class="divider pt-3 mt-3">
                        <h6 class="divider-text">{{ __('Flutterwave Setting') }}</h6>
                    </div>
                    <div class="form-group row">

                        {{-- Public Key --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Public Key') }}</label>
                            <input name="flutterwave_public_key" type="text" class="form-control" placeholder="{{ __('Public Key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('flutterwave_public_key') != '' ? system_setting('flutterwave_public_key') : '' ) : '****************************' ) : ( system_setting('flutterwave_public_key') != '' ? system_setting('flutterwave_public_key') : '' ))}}">
                        </div>

                        {{-- Secret Key --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Secret Key') }}</label>
                            <input name="flutterwave_secret_key" type="text" class="form-control" placeholder="{{ __('Secret Key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('flutterwave_secret_key') != '' ? system_setting('flutterwave_secret_key') : '' ) : '****************************' ) : ( system_setting('flutterwave_secret_key') != '' ? system_setting('flutterwave_secret_key') : '' ))}}">
                        </div>

                        {{-- Encryption key --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Encryption Key') }}</label>
                            <input name="flutterwave_encryption_key" type="text" class="form-control" placeholder="{{ __('Encryption Key') }}" value="{{ (env('DEMO_MODE') ? ( env('DEMO_MODE') == true && Auth::user()->email == 'superadmin@gmail.com' ? ( system_setting('flutterwave_encryption_key') != '' ? system_setting('flutterwave_encryption_key') : '' ) : '****************************' ) : ( system_setting('flutterwave_encryption_key') != '' ? system_setting('flutterwave_encryption_key') : '' ))}}">
                        </div>

                        {{-- Flutterwave Webhook URL --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Flutterwave Webhook URL') }}</label>
                            <input name="flutterwave_webhook_url" type="text" class="form-control" placeholder="{{ __('Flutterwave Webhook URL') }}" value="{{ system_setting('flutterwave_webhook_url') != '' ? system_setting('flutterwave_webhook_url') : url('/webhook/flutterwave') }}" readonly>
                        </div>

                        {{-- Flutterwave Currency Symbol --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-label">{{ __('Flutterwave Currency Symbol') }}</label>
                            <select name="flutterwave_currency" id="flutterwave_currency" class="choosen-select form-select form-control-sm">
                                <option value="GBP" {{ system_setting('flutterwave_currency') == 'GBP' ? 'selected' : '' }}> British Pound Sterling (GBP)</option>
                                <option value="CAD" {{ system_setting('flutterwave_currency') == 'CAD' ? 'selected' : '' }}> Canadian Dollar (CAD)</option>
                                <option value="XAF" {{ system_setting('flutterwave_currency') == 'XAF' ? 'selected' : '' }}> Central African CFA Franc (XAF)</option>
                                <option value="CLP" {{ system_setting('flutterwave_currency') == 'CLP' ? 'selected' : '' }}> Chilean Peso (CLP)</option>
                                <option value="COP" {{ system_setting('flutterwave_currency') == 'COP' ? 'selected' : '' }}> Colombian Peso (COP)</option>
                                <option value="EGP" {{ system_setting('flutterwave_currency') == 'EGP' ? 'selected' : '' }}> Egyptian Pound (EGP)</option>
                                <option value="EUR" {{ system_setting('flutterwave_currency') == 'EUR' ? 'selected' : '' }}> SEPA (EUR)</option>
                                <option value="GHS" {{ system_setting('flutterwave_currency') == 'GHS' ? 'selected' : '' }}> Ghanaian Cedi (GHS)</option>
                                <option value="GNF" {{ system_setting('flutterwave_currency') == 'GNF' ? 'selected' : '' }}> Guinean Franc (GNF)</option>
                                <option value="KES" {{ system_setting('flutterwave_currency') == 'KES' ? 'selected' : '' }}> Kenyan Shilling (KES)</option>
                                <option value="MWK" {{ system_setting('flutterwave_currency') == 'MWK' ? 'selected' : '' }}> Malawian Kwacha (MWK)</option>
                                <option value="MAD" {{ system_setting('flutterwave_currency') == 'MAD' ? 'selected' : '' }}> Moroccan Dirham (MAD)</option>
                                <option value="NGN" {{ system_setting('flutterwave_currency') == 'NGN' ? 'selected' : '' }}> Nigerian Naira (NGN)</option>
                                <option value="RWF" {{ system_setting('flutterwave_currency') == 'RWF' ? 'selected' : '' }}> Rwandan Franc (RWF)</option>
                                <option value="SLL" {{ system_setting('flutterwave_currency') == 'SLL' ? 'selected' : '' }}> Sierra Leonean Leone (SLL)</option>
                                <option value="STD" {{ system_setting('flutterwave_currency') == 'STD' ? 'selected' : '' }}> São Tomé and Príncipe dobra (STD)</option>
                                <option value="ZAR" {{ system_setting('flutterwave_currency') == 'ZAR' ? 'selected' : '' }}> South African Rand (ZAR)</option>
                                <option value="TZS" {{ system_setting('flutterwave_currency') == 'TZS' ? 'selected' : '' }}> Tanzanian Shilling (TZS)</option>
                                <option value="UGX" {{ system_setting('flutterwave_currency') == 'UGX' ? 'selected' : '' }}> Ugandan Shilling (UGX)</option>
                                <option value="USD" {{ system_setting('flutterwave_currency') == 'USD' ? 'selected' : '' }}> United States Dollar (USD)</option>
                                <option value="XOF" {{ system_setting('flutterwave_currency') == 'XOF' ? 'selected' : '' }}> West African CFA Franc BCEAO (XOF)</option>
                                <option value="ZMW" {{ system_setting('flutterwave_currency') == 'ZMW' ? 'selected' : '' }}> Zambian Kwacha (ZMW)</option>
                            </select>
                        </div>


                        {{-- Status --}}
                        <div class="col-sm-12 col-md-6 mt-2">
                            <label class="form-check-label" id='lbl_flutterwave'>{{ __('Enable') }}</label>
                            <div>
                                <div class="form-check form-switch">
                                    <input type="hidden" name="flutterwave_status" id="flutterwave_status" value="{{ system_setting('flutterwave_status') != '' ? system_setting('flutterwave_status') : 0 }}">
                                    <input class="form-check-input" type="checkbox" role="switch" class="switch-input" name='op' {{ system_setting('flutterwave_status') == '1' ? 'checked' : '' }} id="switch_flutterwave_status">
                                    <label class="form-check-label" for="switch_flutterwave_status"></label>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="divider pt-3">
                            <h6 class="divider-text">{{ __('Images') }}</h6>
                        </div>

                        <div class="row">
                            {{-- Favicon --}}
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Favicon Icon') }}</label>
                                <button class="bottomleft btn btn-primary fav_icon_btn" type="button">+</button>
                                <input accept="image/*" name='favicon_icon' type='file' id="fav_image" style="display: none" />
                                <img id="blah_fav" height="100" width="110" style="margin-left: 5%;background: #f7f7f7" src="{{ url('assets/images/logo/' . system_setting('favicon_icon')) }}" />
                            </div>

                            {{-- Company Logo --}}
                            <div class="col-md-4">
                                <label class="form-label">{{ __('Comapany Logo') }}</label>
                                <button class="bottomleft btn btn-primary btn_comapany_logo" type="button">+</button>
                                <input accept="image/*" name='company_logo' type='file' id="company_logo" style="display: none" />
                                <img id="blah_comapany_logo" style="margin: 5%;background: #f7f7f7;height:70px;width:200px;" src="{{ url('assets/images/logo/' . (system_setting('company_logo') ? system_setting('company_logo') : 'logo.png')) }}" />
                            </div>

                            {{-- Login Page Image --}}
                            <div class="col-md-4">
                                <label class="form-label ">{{ __('Login Page Image') }}</label>
                                <button class="bottomleft btn btn-primary btn_login_image" type="button">+</button>
                                <input accept="image/*" name='login_image' type='file' id="login_image" style="display: none" />
                                <img id="blah_login_image" height="100" width="110" style="margin-left: 5%;background: #f7f7f7" src="{{ url('assets/images/bg/' . (system_setting('login_image') ? system_setting('login_image') : 'Login_BG.jpg')) }}" />
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" name="btnAdd" value="btnAdd" class="btn btn-primary me-1 mb-1">{{ __('Save') }}</button>
            </div>
        </form>

    </section>
@endsection

@section('script')
<script type="text/javascript">
        $(document).ready(function () {
            let countryValue = "{{ system_setting('currency_code') != '' ? system_setting('currency_code') : '' }}";
            $("#currency-code").val(countryValue).trigger("change").promise().done(function () {
                let currencySymbol = "{{ system_setting('currency_symbol') != '' ? system_setting('currency_symbol') : '' }}";
                setTimeout(() => {
                    $("#currency-symbol").val(currencySymbol);
                }, 100);
            });
        });


        $(document).on('click', '#favicon_icon', function(e) {

            $('.favicon_icon').hide();

        });
        $(document).on('click', '#company_logo', function(e) {

            $('.company_logo').hide();

        });

        $(document).on('click', '#login_image', function(e) {
            $('.login_image').hide();
        });


        const checkboxes = document.querySelectorAll('input[type=checkbox][role=switch][name=op]', );
        checkboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', (event) => {
                if (event.target.checked) {
                    checkboxes.forEach((checkbox) => {
                        if (checkbox !== event.target) {
                            checkbox.checked = false;
                            $("#switch_paypal_gateway").is(':checked') ? $("#paypal_gateway").val(1) : $("#paypal_gateway") .val(0);
                            $("#switch_razorpay_gateway").is(':checked') ? $("#razorpay_gateway").val(1) : $("#razorpay_gateway") .val(0);
                            $("#switch_paystack_gateway").is(':checked') ? $("#paystack_gateway").val(1) : $("#paystack_gateway") .val(0);
                            $("#switch_stripe_gateway").is(':checked') ? $("#stripe_gateway").val(1) : $("#stripe_gateway") .val(0);
                            $("#switch_flutterwave_status").is(':checked') ? $("#flutterwave_status").val(1) : $("#flutterwave_status") .val(0);
                        }
                    });
                }
            });
        });


        $("#switch_svg_clr").on('change', function() {
            $("#switch_svg_clr").is(':checked') ? $("#svg_clr").val(1) : $("#svg_clr") .val(0);
        });


        $("#switch_force_update").on('change', function() {
            $("#switch_force_update").is(':checked') ? $("#force_update").val(1) : $("#force_update") .val(0);
        });

        $("#switch_number_with_suffix").on('change', function() {
            $("#switch_number_with_suffix").is(':checked') ? $("#number_with_suffix").val(1) : $( "#number_with_suffix") .val(0);
        });

        // Change Event on OTP login Toggle
        $("#number-with-otp-login-toggle").on('change', function() {
            if ($("#number-with-otp-login-toggle").is(':checked')) {
                // If number with otp login is checked then make database value 1, show the otp services provider div, and trigger select option
                $("#number-with-otp-login").val(1);
                $("#otp-services-provider-div").show(100);
                $("#otp-services-provider").trigger('change');
            }else{
                // make database value 0, hide services provider div hide
                $("#number-with-otp-login") .val(0);
                $("#otp-services-provider-div").hide();
                $("#twilio-sms-settings-div").hide()
                $(".twilio-account-settings").removeAttr('required');
            }

        });

        // Change event on OTP services provider selection
        $("#otp-services-provider").on('change',function(){
            // Get the value of selection
            let otpServicesProviderValue = $(this).val();
            if(otpServicesProviderValue == 'twilio'){
                // IF Twilio then show the div of twilio sms settings with all the details required attribute
                $("#twilio-sms-settings-div").show();
                $(".twilio-account-settings").attr('required',true);
            }else{
                // IF other then hide the div of twilio sms settings and remove required attribute in all twilio settings
                $(".twilio-account-settings").removeAttr('required');
                $("#twilio-sms-settings-div").hide();
            }
        })

        $("#social-login-toggle").on('change', function() {
            $("#social-login-toggle").is(':checked') ? $("#social-login").val(1) : $( "#social-login") .val(0);
        });

        $("#switch_sandbox_mode").on('change', function() {
            $("#switch_sandbox_mode").is(':checked') ? $("#sandbox_mode").val(1) : $("#sandbox_mode") .val(0);
        });

        $("#switch_paypal_gateway").on('change', function() {
            $("#switch_paypal_gateway").is(':checked') ? $("#paypal_gateway").val(1) : $("#paypal_gateway") .val(0);
        });

        $("#switch_razorpay_gateway").on('change', function() {
            $("#switch_razorpay_gateway").is(':checked') ? $("#razorpay_gateway").val(1) : $("#razorpay_gateway") .val(0);
        });

        $("#switch_stripe_gateway").on('change', function() {
            $("#switch_stripe_gateway").is(':checked') ? $("#stripe_gateway").val(1) : $("#stripe_gateway") .val(0);
        });

        $("#switch_paystack_gateway").on('change', function() {
            $("#switch_paystack_gateway").is(':checked') ? $("#paystack_gateway").val(1) : $("#paystack_gateway") .val(0);
        });

        $("#switch_flutterwave_status").on('change', function() {
            $("#switch_flutterwave_status").is(':checked') ? $("#flutterwave_status").val(1) : $("#flutterwave_status") .val(0);
        });

        function hexToRgb(hex) {
            const bigint = parseInt(hex.slice(1), 16);
            const r = (bigint >> 16) & 255;
            const g = (bigint >> 8) & 255;
            const b = bigint & 255;
            return `rgb(${r}, ${g}, ${b},0.15)`;
        }


        const colorForm = document.getElementById("setting_form");
        const systemColorInput = document.getElementById("systemColor");

        const hiddenRGBAInput = document.getElementById("hiddenRGBA");


        systemColorInput.addEventListener("change", function() {
            const selectedColor = systemColorInput.value;
            const alpha = 0.15; // You can adjust the alpha value as needed (1 for fully opaque)
            const rgba = hexToRgb(selectedColor);
            hiddenRGBAInput.value = rgba; // Update the hidden input with the new RGBA value
        });



        $(document).ready(function() {
            var companyname = $('#company_name').val();
            sessionStorage.setItem('comapanyname', $('#company_name').val());
            const newValue = `"${companyname}"`;
            const rgba = hexToRgb(systemColorInput.value);
            hiddenRGBAInput.value = rgba;

            // Bind change to login methods
            $("#number-with-otp-login-toggle, #social-login-toggle").change(function() {
                var loginCheckBoxToggle = $(this).attr("id") === "number-with-otp-login-toggle" ? "social-login-toggle" : "number-with-otp-login-toggle";
                var loginCheckbox = $("#" + loginCheckBoxToggle);

                // Check if both toggles are currently unchecked
                if (!$(this).is(":checked") && !loginCheckbox.is(":checked")) {
                    // Check the other toggle if both are unchecked
                    loginCheckbox.prop("checked", true).trigger('change');
                }
            });
            $("#number-with-otp-login-toggle").trigger('change')
        });

        $('.fav_icon_btn').click(function() {
            $('#fav_image').click();


        });
        fav_image.onchange = evt => {
            const [file] = fav_image.files
            if (file) {
                blah_fav.src = URL.createObjectURL(file)

            }
        }
        $('.btn_comapany_logo').click(function() {
            $('#company_logo').click();


        });
        company_logo.onchange = evt => {
            const [file] = company_logo.files
            if (file) {
                blah_comapany_logo.src = URL.createObjectURL(file)

            }
        }



        $('.btn_login_image').click(function() {
            $('#login_image').click();


        });
        login_image.onchange = evt => {
            const [file] = login_image.files
            if (file) {
                blah_login_image.src = URL.createObjectURL(file)

            }
        }
    </script>
@endsection
