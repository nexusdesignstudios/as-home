@extends('layouts.main')

@section('title')
    {{ __('Select Property for Question Answers') }}
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
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ __('Select Property') }}
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">{{ __('Select a Property to View Question Answers') }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('property-question-form.answers') }}" method="get">
                        <div class="form-group">
                            <label for="property_id">{{ __('Property') }}</label>
                            <select class="form-select" id="property_id" name="property_id" required>
                                <option value="">{{ __('Select Property') }}</option>
                                @foreach ($properties as $property)
                                    <option value="{{ $property->id }}">
                                        {{ $property->title }}
                                        @if ($property->property_classification == 4)
                                            ({{ __('Vacation Home') }})
                                        @elseif ($property->property_classification == 5)
                                            ({{ __('Hotel') }})
                                        @elseif ($property->property_classification == 1)
                                            ({{ __('Sell/Rent') }})
                                        @elseif ($property->property_classification == 2)
                                            ({{ __('Commercial') }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($classification)
                            <input type="hidden" name="classification" value="{{ $classification }}">
                        @endif

                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary">{{ __('View Answers') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
