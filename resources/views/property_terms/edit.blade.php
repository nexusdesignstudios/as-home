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
                        <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="10" required>{{ old('terms_conditions', $propertyTerm->terms_conditions) }}</textarea>
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
