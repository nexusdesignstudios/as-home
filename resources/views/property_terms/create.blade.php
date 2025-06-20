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
                            <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="10" required>{{ old('terms_conditions') }}</textarea>
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
        // Initialize rich text editor if available
        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#terms_conditions',
                height: 400,
                plugins: [
                    'advlist autolink lists link image charmap print preview anchor',
                    'searchreplace visualblocks code fullscreen',
                    'insertdatetime media table paste code help wordcount'
                ],
                toolbar: 'undo redo | formatselect | bold italic backcolor | \
                    alignleft aligncenter alignright alignjustify | \
                    bullist numlist outdent indent | removeformat | help'
            });
        }
    });
</script>
@endsection
