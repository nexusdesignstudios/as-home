@extends('layouts.main')

@section('title')
    {{ __('Edit Property Terms & Conditions') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
                <p class="text-subtitle text-muted">{{ __('Update terms and conditions for a property classification') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('property-terms.index') }}">{{ __('Property Terms') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('Edit') }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">{{ __('Edit Terms & Conditions') }}</h4>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('property-terms.update', $propertyTerm->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group mb-3">
                        <label for="classification_id" class="form-label">{{ __('Property Classification') }}</label>
                        <select class="form-select" id="classification_id" name="classification_id" disabled>
                            @foreach($classifications as $id => $name)
                                <option value="{{ $id }}" {{ $propertyTerm->classification_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="classification_id" value="{{ $propertyTerm->classification_id }}">
                        <small class="text-muted">{{ __('Classification cannot be changed once created') }}</small>
                    </div>

                    <div class="form-group mb-3">
                        <label for="terms_conditions" class="form-label">{{ __('Terms & Conditions') }}</label>
                        <div class="alert alert-info mb-2">
                            <i class="bi bi-info-circle"></i> 
                            <strong>{{ __('Unlimited Text Length') }}</strong> - {{ __('You can add large contracts with unlimited text length. Maximum supported: Up to 4GB of text content.') }}
                        </div>
                        <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="15" required style="min-height: 500px;" data-maxlength="0">{{ old('terms_conditions', $propertyTerm->terms_conditions) }}</textarea>
                        <div class="mt-2">
                            <small class="text-muted">
                                <span id="character-count-display">
                                    <strong>{{ __('Current Length:') }}</strong> <span id="character-count">0</span> {{ __('characters') }} 
                                    <span class="badge bg-success ms-2">{{ __('Unlimited') }}</span>
                                </span>
                            </small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('property-terms.index') }}" class="btn btn-secondary me-2">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

@section('page-script')
<script>
    $(document).ready(function() {
        // Function to update character count
        function updateCharacterCount() {
            var editor = tinymce.get('terms_conditions');
            if (editor) {
                var content = editor.getContent({format: 'text'});
                var charCount = content.length;
                $('#character-count').text(charCount.toLocaleString());
            } else {
                // Fallback for plain textarea
                var content = $('#terms_conditions').val();
                var charCount = content.length;
                $('#character-count').text(charCount.toLocaleString());
            }
        }

        // Initialize rich text editor if available
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#terms_conditions',
                height: 600,
                max_chars: 0, // No character limit (0 = unlimited)
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | \
                    alignleft aligncenter alignright alignjustify | \
                    bullist numlist outdent indent | removeformat | help',
                // Remove any text length restrictions
                setup: function(editor) {
                    // Ensure no character limits
                    editor.on('init', function() {
                        editor.getBody().setAttribute('data-maxlength', '0');
                        updateCharacterCount(); // Initial count
                    });
                    
                    // Update character count on content change
                    editor.on('keyup', function() {
                        updateCharacterCount();
                    });
                    
                    editor.on('change', function() {
                        updateCharacterCount();
                    });
                }
            });
        } else {
            // Fallback: Update count for plain textarea
            $('#terms_conditions').on('input keyup', function() {
                updateCharacterCount();
            });
            updateCharacterCount(); // Initial count
        }
    });
</script>
@endsection
