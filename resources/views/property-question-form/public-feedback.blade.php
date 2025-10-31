<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ url('assets/images/logo/' . (system_setting('favicon_icon') ?? null)) }}" type="image/x-icon">
    <title>{{ __('Feedback Form') }} - {{ config('app.name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    @include('layouts.include')
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 col-lg-10" style="margin-top:50px; margin-bottom:50px">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">{{ __('Share Your Feedback') }}</h4>
                        <p class="mb-0 small">Property: {{ $property->title }}</p>
                    </div>
                    <div class="card-body">
                        @if(session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if($hasExistingFeedback)
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> {{ __('You have already submitted feedback for this reservation. Thank you!') }}
                            </div>
                        @else
                            {{-- Debug Info (remove in production) --}}
                            @if(config('app.debug'))
                            <div class="alert alert-secondary small">
                                <strong>Debug Info:</strong><br>
                                Form Type: {{ $formType ?? 'N/A' }}<br>
                                Property Classification: {{ $property->getRawOriginal('property_classification') ?? 'N/A' }}<br>
                                Questions Count: {{ $allFields->count() }}
                            </div>
                            @endif
                            
                            <p class="mb-4">{{ __('Thank you for choosing') }} {{ env("APP_NAME") ?? "As-home" }}! {{ __('We hope you had a wonderful stay. Your feedback helps us improve our services.') }}</p>

                            <form id="feedback-form" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">
                                <input type="hidden" name="property_id" value="{{ $property->id }}">
                                <input type="hidden" name="reservation_id" value="{{ $reservation->id }}">

                                @if($allFields->isEmpty())
                                    <div class="alert alert-warning">
                                        {{ __('No feedback questions are available at this time.') }}
                                    </div>
                                @else
                                    @foreach($allFields as $field)
                                        <div class="form-group mb-4">
                                            <label class="form-label">
                                                {{ $field->name }}
                                                @if(in_array($field->field_type, ['text', 'number', 'textarea', 'file', 'dropdown']))
                                                    <span class="text-danger">*</span>
                                                @endif
                                            </label>

                                            @if($field->field_type === 'text')
                                                <input type="text" name="answers[{{ $field->id }}][field_id]" value="{{ $field->id }}" hidden>
                                                <input type="text" class="form-control" name="answers[{{ $field->id }}][value]" required>

                                            @elseif($field->field_type === 'number')
                                                <input type="hidden" name="answers[{{ $field->id }}][field_id]" value="{{ $field->id }}">
                                                <input type="number" class="form-control" name="answers[{{ $field->id }}][value]" required>

                                            @elseif($field->field_type === 'textarea')
                                                <input type="hidden" name="answers[{{ $field->id }}][field_id]" value="{{ $field->id }}">
                                                <textarea class="form-control" name="answers[{{ $field->id }}][value]" rows="4" required></textarea>

                                            @elseif($field->field_type === 'radio')
                                                <input type="hidden" name="answers[{{ $field->id }}][field_id]" value="{{ $field->id }}">
                                                <div>
                                                    @foreach($field->field_values as $value)
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="radio" name="answers[{{ $field->id }}][value]" id="radio_{{ $field->id }}_{{ $value->id }}" value="{{ $value->value }}" required>
                                                            <label class="form-check-label" for="radio_{{ $field->id }}_{{ $value->id }}">
                                                                {{ $value->value }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>

                                            @elseif($field->field_type === 'checkbox')
                                                <input type="hidden" name="answers[{{ $field->id }}][field_id]" value="{{ $field->id }}">
                                                <div>
                                                    @foreach($field->field_values as $value)
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="answers[{{ $field->id }}][value][]" id="checkbox_{{ $field->id }}_{{ $value->id }}" value="{{ $value->value }}">
                                                            <label class="form-check-label" for="checkbox_{{ $field->id }}_{{ $value->id }}">
                                                                {{ $value->value }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>

                                            @elseif($field->field_type === 'dropdown')
                                                <input type="hidden" name="answers[{{ $field->id }}][field_id]" value="{{ $field->id }}">
                                                <select class="form-select" name="answers[{{ $field->id }}][value]" required>
                                                    <option value="">{{ __('Select') }}</option>
                                                    @foreach($field->field_values as $value)
                                                        <option value="{{ $value->value }}">{{ $value->value }}</option>
                                                    @endforeach
                                                </select>

                                            @elseif($field->field_type === 'file')
                                                <input type="hidden" name="answers[{{ $field->id }}][field_id]" value="{{ $field->id }}">
                                                <input type="file" class="form-control" name="{{ $field->id }}" accept="image/*,application/pdf" required>
                                                <small class="form-text text-muted">{{ __('Accepted formats: Images, PDF') }}</small>

                                            @endif
                                        </div>
                                    @endforeach

                                    <div class="form-group mt-4">
                                        <button type="submit" class="btn btn-primary btn-lg" id="submit-btn">
                                            <span id="submit-text">{{ __('Submit Feedback') }}</span>
                                            <span id="submit-loading" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                @endif
                            </form>

                            <div id="success-message" class="alert alert-success d-none mt-3">
                                <i class="bi bi-check-circle"></i> <span id="success-text"></span>
                            </div>

                            <div id="error-message" class="alert alert-danger d-none mt-3">
                                <i class="bi bi-exclamation-circle"></i> <span id="error-text"></span>
                            </div>
                        @endif

                        <div class="mt-4 text-muted small">
                            <p><strong>{{ __('Reservation Details') }}:</strong></p>
                            <ul class="list-unstyled">
                                <li><strong>{{ __('Check-in Date') }}:</strong> {{ $reservation->check_in_date ? $reservation->check_in_date->format('d M Y') : 'N/A' }}</li>
                                <li><strong>{{ __('Check-out Date') }}:</strong> {{ $reservation->check_out_date ? $reservation->check_out_date->format('d M Y') : 'N/A' }}</li>
                                <li><strong>{{ __('Guests') }}:</strong> {{ $reservation->number_of_guests ?? 'N/A' }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
@include('layouts.footer_script')
<script>
$(document).ready(function() {
    $('#feedback-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $('#submit-btn');
        const $submitText = $('#submit-text');
        const $submitLoading = $('#submit-loading');
        const $successMsg = $('#success-message');
        const $errorMsg = $('#error-message');
        
        // Hide previous messages
        $successMsg.addClass('d-none');
        $errorMsg.addClass('d-none');
        
        // Disable submit button
        $submitBtn.prop('disabled', true);
        $submitText.text('{{ __('Submitting...') }}');
        $submitLoading.removeClass('d-none');
        
        // Prepare form data
        const answers = [];
        const finalFormData = new FormData();
        
        // Process each form group to collect field_id and values
        $form.find('.form-group').each(function() {
            const $formGroup = $(this);
            const $hiddenField = $formGroup.find('input[type="hidden"][name*="[field_id]"]');
            
            if ($hiddenField.length) {
                const fieldId = $hiddenField.val();
                const nameAttr = $hiddenField.attr('name');
                const match = nameAttr.match(/answers\[(\d+)\]\[field_id\]/);
                
                if (match) {
                    const fieldIndex = match[1];
                    let value = null;
                    
                    // Get value based on field type
                    const $valueField = $formGroup.find('input[type="text"], input[type="number"], textarea, select, input[type="radio"]:checked, input[type="checkbox"]');
                    
                    if ($valueField.is('input[type="checkbox"]')) {
                        // For checkboxes, collect all checked values
                        value = $formGroup.find('input[type="checkbox"]:checked').map(function() {
                            return $(this).val();
                        }).get();
                    } else if ($valueField.is('input[type="radio"]')) {
                        value = $formGroup.find('input[type="radio"]:checked').val();
                    } else if ($valueField.is('input[type="file"]')) {
                        // File will be handled separately
                        value = null;
                    } else if ($valueField.length) {
                        value = $valueField.val();
                    }
                    
                    // Only add if value is not empty
                    if (value !== null && value !== '' && (Array.isArray(value) ? value.length > 0 : true)) {
                        answers.push({
                            field_id: fieldId,
                            value: value
                        });
                    }
                }
            }
        });
        
        // Build FormData
        finalFormData.append('token', $form.find('[name="token"]').val());
        finalFormData.append('property_id', $form.find('[name="property_id"]').val());
        finalFormData.append('answers', JSON.stringify(answers));
        
        // Add file uploads with field_id as the key
        $form.find('input[type="file"]').each(function() {
            if (this.files.length > 0) {
                const $fileInput = $(this);
                const $formGroup = $fileInput.closest('.form-group');
                const $hiddenField = $formGroup.find('input[type="hidden"][name*="[field_id]"]');
                
                if ($hiddenField.length) {
                    const fieldId = $hiddenField.val();
                    finalFormData.append(fieldId, this.files[0]);
                }
            }
        });
        
        // Send AJAX request
        $.ajax({
            url: '{{ url("/api/save-feedback-answers") }}',
            type: 'POST',
            data: finalFormData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (!response.error) {
                    $successMsg.removeClass('d-none');
                    $('#success-text').text(response.message || '{{ __('Thank you for your feedback!') }}');
                    $form[0].reset();
                    
                    // Redirect after 3 seconds
                    setTimeout(function() {
                        window.location.href = '{{ route("home") }}';
                    }, 3000);
                } else {
                    $errorMsg.removeClass('d-none');
                    $('#error-text').text(response.message || '{{ __('An error occurred. Please try again.') }}');
                }
            },
            error: function(xhr) {
                let errorMessage = '{{ __('An error occurred. Please try again.') }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $errorMsg.removeClass('d-none');
                $('#error-text').text(errorMessage);
            },
            complete: function() {
                $submitBtn.prop('disabled', false);
                $submitText.text('{{ __('Submit Feedback') }}');
                $submitLoading.addClass('d-none');
            }
        });
    });
});
</script>
</html>

