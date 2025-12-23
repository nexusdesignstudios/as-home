@extends('layouts.main')

@section('title')
    {{ __('Terms & Conditions') }}
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
        <div class="card">

            <form action="{{ url('settings') }}" method="post">
                @csrf
                <div class="card-body">
                    <input name="type" value="terms_conditions" type="hidden">
                    <div class="row form-group">
                        <div class="col-12 d-flex justify-content-end">
                            <a href="{{ route('customer-terms-conditions') }}"col-sm-12 col-md-12 d-fluid
                                class="btn icon btn-primary btn-sm rounded-pill" onclick="" title="Enable"><i
                                    class="bi bi-eye-fill"></i></a>
                        </div>
                        <div class="col-md-12 mt-3">
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle"></i> 
                                <strong>{{ __('Unlimited Text Length') }}</strong> - {{ __('You can add large contracts with unlimited text length. Maximum supported: Up to 4GB of text content.') }}
                            </div>
                            <textarea id="tinymce_editor" name="data" class="form-control col-md-7 col-xs-12" style="min-height: 500px;">{{ $data }}</textarea>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <span id="character-count-display">
                                        <strong>{{ __('Current Length:') }}</strong> <span id="character-count">0</span> {{ __('characters') }} 
                                        <span class="badge bg-success ms-2">{{ __('Unlimited') }}</span>
                                    </span>
                                </small>
                            </div>
                        </div>

                    </div>
                    <div class="col-12 d-flex justify-content-end">

                        <button class="btn btn-primary me-1 mb-1" type="submit" name="submit">{{ __('Save') }}</button>

                    </div>
                </div>
                {{-- <div class="card-footer"> --}}


                {{-- </div> --}}
            </form>
        </div>
    </section>
@endsection

@section('page-script')
<script>
    $(document).ready(function() {
        // Function to update character count
        function updateCharacterCount() {
            var editor = tinymce.get('tinymce_editor');
            if (editor) {
                var content = editor.getContent({format: 'text'});
                var charCount = content.length;
                $('#character-count').text(charCount.toLocaleString());
            } else {
                // Fallback for plain textarea
                var content = $('#tinymce_editor').val();
                var charCount = content.length;
                $('#character-count').text(charCount.toLocaleString());
            }
        }

        // Wait for TinyMCE to initialize, then set up character count
        setTimeout(function() {
            var editor = tinymce.get('tinymce_editor');
            if (editor) {
                // Update count on content change
                editor.on('keyup', function() {
                    updateCharacterCount();
                });
                
                editor.on('change', function() {
                    updateCharacterCount();
                });
                
                // Initial count
                updateCharacterCount();
            } else {
                // Fallback: Update count for plain textarea
                $('#tinymce_editor').on('input keyup', function() {
                    updateCharacterCount();
                });
                updateCharacterCount(); // Initial count
            }
        }, 1000);
    });
</script>
@endsection
