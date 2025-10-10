@extends('layouts.main')

@section('title')
    {{ __('Property Question Answers') }}
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
                        <li class="breadcrumb-item">
                            <a href="{{ route('property-question-form.answers') }}">{{ __('Select Property') }}</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ __('Answers') }}
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">{{ $property->title }}</h4>
                    <a href="{{ route('property-question-form.answers') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> {{ __('Back to Property Selection') }}
                    </a>
                </div>
                <div class="card-body">
                    <div class="property-info mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>{{ __('Property Type') }}:</strong>
                                    @if ($property->getRawOriginal('property_classification') == 4)
                                        {{ __('Vacation Home') }}
                                    @elseif ($property->getRawOriginal('property_classification') == 5)
                                        {{ __('Hotel') }}
                                    @elseif ($property->getRawOriginal('property_classification') == 1)
                                        {{ __('Sell/Rent') }}
                                    @elseif ($property->getRawOriginal('property_classification') == 2)
                                        {{ __('Commercial') }}
                                    @endif
                                </p>
                                <p><strong>{{ __('Address') }}:</strong> {{ $property->address }}</p>
                            </div>
                            <div class="col-md-6">
                                @if($property->title_image)
                                    <img src="{{ $property->title_image }}" alt="{{ $property->title }}" class="img-fluid rounded" style="max-height: 150px;">
                                @endif
                            </div>
                        </div>
                    </div>

                    <h5 class="mt-4 mb-3">{{ __('Question Answers') }}</h5>

                    @if($property->propertyQuestionAnswers->isEmpty())
                        <div class="alert alert-info">
                            {{ __('No answers found for this property.') }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>{{ __('Question') }}</th>
                                        <th>{{ __('Answer') }}</th>
                                        <th>{{ __('Field Type') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($property->propertyQuestionAnswers as $answer)
                                        <tr>
                                            <td>{{ $answer->property_question_field->name }}</td>
                                            <td>
                                                @switch($answer->property_question_field->field_type)
                                                    @case('file')
                                                        @if($answer->value)
                                                            <a href="{{ url('') . config('global.IMG_PATH') . config('global.PROPERTY_QUESTION_PATH') . '/' . $answer->value }}"
                                                               target="_blank" class="btn btn-sm btn-info">
                                                                <i class="bi bi-file-earmark"></i> {{ __('View File') }}
                                                            </a>
                                                        @else
                                                            {{ __('No file uploaded') }}
                                                        @endif
                                                        @break

                                                    @case('checkbox')
                                                        @php
                                                            $values = json_decode($answer->value, true);
                                                            if(is_array($values)) {
                                                                echo implode(', ', $values);
                                                            } else {
                                                                echo $answer->value;
                                                            }
                                                        @endphp
                                                        @break

                                                    @default
                                                        {{ $answer->value }}
                                                @endswitch
                                            </td>
                                            <td>
                                                <span class="badge bg-light-primary">
                                                    {{ ucfirst($answer->property_question_field->field_type) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if($allFields->count() > $property->propertyQuestionAnswers->count())
                        <div class="mt-4">
                            <h5 class="mb-3">{{ __('Unanswered Questions') }}</h5>
                            <div class="alert alert-warning">
                                <ul class="mb-0">
                                    @foreach($allFields as $field)
                                        @if(!$property->propertyQuestionAnswers->contains('property_question_field_id', $field->id))
                                            <li>{{ $field->name }} ({{ ucfirst($field->field_type) }})</li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
