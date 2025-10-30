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

                    <!-- Reviews Analytics Section -->
                    <div class="reviews-analytics mb-4">
                        <h5 class="mb-3" style="color: #D4AF37; font-weight: bold;">{{ __('Reviews Analytics') }}</h5>
                        <div class="row">
                            <!-- Total Reviews -->
                            <div class="col-md-3">
                                <div class="card" style="background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%); border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                    <div class="card-body text-white">
                                        <h6 class="card-title text-white" style="color: white !important;">{{ __('Total Reviews') }}</h6>
                                        <h2 class="mb-0 text-white" style="color: white !important; font-weight: bold;">{{ $reviewsAnalytics['total_reviews'] }}</h2>
                                    </div>
                                </div>
                            </div>
                            <!-- Average Rating -->
                            <div class="col-md-3">
                                <div class="card" style="background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%); border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                    <div class="card-body text-white">
                                        <h6 class="card-title text-white" style="color: white !important;">{{ __('Average Rating') }}</h6>
                                        <h2 class="mb-0 text-white" style="color: white !important; font-weight: bold;">
                                            @if($reviewsAnalytics['average_rating'] > 0)
                                                {{ $reviewsAnalytics['average_rating'] }}/5
                                            @else
                                                0
                                            @endif
                                        </h2>
                                    </div>
                                </div>
                            </div>
                            <!-- Rating by Questions -->
                            @if(!empty($reviewsAnalytics['by_question']))
                                @foreach($reviewsAnalytics['by_question'] as $questionId => $questionData)
                                    <div class="col-md-3">
                                        <div class="card" style="background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%); border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                                            <div class="card-body text-white">
                                                <h6 class="card-title text-white" style="color: white !important;">{{ $questionData['question_name'] }}</h6>
                                                <p class="mb-1 text-white" style="color: white !important;"><strong>Average:</strong> {{ $questionData['average_rating'] }}/5</p>
                                                <p class="mb-0 text-white" style="color: white !important;"><small>{{ $questionData['total_ratings'] }} {{ __('ratings') }}</small></p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <!-- Rating Distribution -->
                        @if(!empty($reviewsAnalytics['rating_distribution']))
                            <div class="mt-4">
                                <h6 style="color: #D4AF37; font-weight: bold;">{{ __('Rating Distribution') }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>5 {{ __('Stars') }}</th>
                                                <th>4 {{ __('Stars') }}</th>
                                                <th>3 {{ __('Stars') }}</th>
                                                <th>2 {{ __('Stars') }}</th>
                                                <th>1 {{ __('Star') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%); width: {{ $reviewsAnalytics['rating_distribution'][5]['percentage'] }}%;">
                                                            </div>
                                                        </div>
                                                        <span style="color: #D4AF37; font-weight: bold;">{{ $reviewsAnalytics['rating_distribution'][5]['count'] }} ({{ $reviewsAnalytics['rating_distribution'][5]['percentage'] }}%)</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%); width: {{ $reviewsAnalytics['rating_distribution'][4]['percentage'] }}%;">
                                                            </div>
                                                        </div>
                                                        <span style="color: #D4AF37; font-weight: bold;">{{ $reviewsAnalytics['rating_distribution'][4]['count'] }} ({{ $reviewsAnalytics['rating_distribution'][4]['percentage'] }}%)</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%); width: {{ $reviewsAnalytics['rating_distribution'][3]['percentage'] }}%;">
                                                            </div>
                                                        </div>
                                                        <span style="color: #D4AF37; font-weight: bold;">{{ $reviewsAnalytics['rating_distribution'][3]['count'] }} ({{ $reviewsAnalytics['rating_distribution'][3]['percentage'] }}%)</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%); width: {{ $reviewsAnalytics['rating_distribution'][2]['percentage'] }}%;">
                                                            </div>
                                                        </div>
                                                        <span style="color: #D4AF37; font-weight: bold;">{{ $reviewsAnalytics['rating_distribution'][2]['count'] }} ({{ $reviewsAnalytics['rating_distribution'][2]['percentage'] }}%)</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%); width: {{ $reviewsAnalytics['rating_distribution'][1]['percentage'] }}%;">
                                                            </div>
                                                        </div>
                                                        <span style="color: #D4AF37; font-weight: bold;">{{ $reviewsAnalytics['rating_distribution'][1]['count'] }} ({{ $reviewsAnalytics['rating_distribution'][1]['percentage'] }}%)</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <!-- Recent Reviews -->
                        @if(!empty($reviewsAnalytics['recent_reviews']))
                            <div class="mt-4">
                                <h6 style="color: #D4AF37; font-weight: bold;">{{ __('Recent Reviews') }}</h6>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Customer') }}</th>
                                                <th>{{ __('Date') }}</th>
                                                <th>{{ __('Answers') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($reviewsAnalytics['recent_reviews'] as $review)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $review['customer_name'] }}</strong><br>
                                                        <small class="text-muted">{{ $review['customer_email'] }}</small>
                                                    </td>
                                                    <td>{{ $review['review_date'] }}</td>
                                                    <td>
                                                        @foreach($review['answers'] as $answer)
                                                            <div>
                                                                <strong>{{ $answer['question'] }}:</strong> 
                                                                @if($answer['field_type'] == 'dropdown' && is_numeric($answer['value']) && (int)$answer['value'] >= 1 && (int)$answer['value'] <= 5)
                                                                    <span class="badge" style="background: linear-gradient(135deg, #D4AF37 0%, #F4D03F 100%); color: white; border: none;">{{ $answer['value'] }}/5 ⭐</span>
                                                                @else
                                                                    {{ \Illuminate\Support\Str::limit($answer['value'], 50) }}
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
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
                                        <th>{{ __('Customer') }}</th>
                                        <th>{{ __('Reservation ID') }}</th>
                                        <th>{{ __('Question') }}</th>
                                        <th>{{ __('Answer') }}</th>
                                        <th>{{ __('Field Type') }}</th>
                                        <th>{{ __('Submitted At') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($property->propertyQuestionAnswers as $answer)
                                        <tr>
                                            <td>
                                                @if($answer->customer)
                                                    <strong>{{ $answer->customer->name }}</strong><br>
                                                    <small class="text-muted">{{ $answer->customer->email }}</small>
                                                @else
                                                    <span class="text-muted">{{ __('N/A') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($answer->reservation_id)
                                                    <span class="badge bg-info">#{{ $answer->reservation_id }}</span>
                                                @else
                                                    <span class="text-muted">{{ __('N/A') }}</span>
                                                @endif
                                            </td>
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
                                            <td>
                                                {{ $answer->created_at ? $answer->created_at->format('d M Y, h:i A') : __('N/A') }}
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
