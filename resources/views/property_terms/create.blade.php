@extends('layouts.main')

@section('title')
    {{ __('Add Property Terms & Conditions') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
                <p class="text-subtitle text-muted">{{ __('Create terms and conditions for a property classification') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('property-terms.index') }}">{{ __('Property Terms') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('Add') }}</li>
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
                <h4 class="card-title">{{ __('Add New Terms & Conditions') }}</h4>
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

                @if(count($availableClassifications) === 0)
                    <div class="alert alert-info">
                        {{ __('All property classifications already have terms and conditions defined. You can edit existing ones from the list.') }}
                        <a href="{{ route('property-terms.index') }}" class="btn btn-primary mt-3">{{ __('Back to List') }}</a>
                    </div>
                @else
                    <form action="{{ route('property-terms.store') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="classification_id" class="form-label">{{ __('Property Classification') }}</label>
                            <select class="form-select" id="classification_id" name="classification_id" required>
                                <option value="" selected disabled>{{ __('Select a classification') }}</option>
                                @foreach($availableClassifications as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="terms_conditions" class="form-label">{{ __('Terms & Conditions') }}</label>
                            <div class="alert alert-info mb-2">
                                <i class="bi bi-info-circle"></i> 
                                <strong>{{ __('Unlimited Text Length') }}</strong> - {{ __('You can add large contracts with unlimited text length. Maximum supported: Up to 4GB of text content.') }}
                            </div>
                            <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="15" required style="min-height: 500px;" data-maxlength="0">{{ old('terms_conditions') }}</textarea>
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
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                @endif
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
