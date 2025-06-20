@extends('layouts.main')

@section('title')
    {{ __('Property Terms & Conditions') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
                <p class="text-subtitle text-muted">{{ __('Manage terms and conditions for different property classifications') }}</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('Property Terms & Conditions') }}</li>
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
                <div class="d-flex justify-content-between">
                    <h4 class="card-title">{{ __('Property Terms & Conditions') }}</h4>
                    <a href="{{ route('property-terms.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus"></i> {{ __('Add New Terms') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (isset($tableNotExists) && $tableNotExists)
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <h4 class="alert-heading">{{ __('Database Table Not Found') }}</h4>
                        <p>{{ __('The property_terms table doesn\'t exist in the database. Please run the migration to create it:') }}</p>
                        <code>php artisan migrate --path=database/migrations/2025_05_24_000000_create_property_terms_table.php</code>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @elseif (isset($error))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ $error }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped" id="property-terms-table">
                            <thead>
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Classification') }}</th>
                                    <th>{{ __('Terms & Conditions') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($propertyTerms as $term)
                                    <tr>
                                        <td>{{ $term->id }}</td>
                                        <td>
                                            @switch($term->classification_id)
                                                @case(1)
                                                    {{ __('Sell/Rent') }}
                                                    @break
                                                @case(2)
                                                    {{ __('Commercial') }}
                                                    @break
                                                @case(3)
                                                    {{ __('New Project') }}
                                                    @break
                                                @case(4)
                                                    {{ __('Vacation Homes') }}
                                                    @break
                                                @case(5)
                                                    {{ __('Hotel Booking') }}
                                                    @break
                                                @default
                                                    {{ __('Unknown') }}
                                            @endswitch
                                        </td>
                                        <td>{{ \Illuminate\Support\Str::limit($term->terms_conditions, 100) }}</td>
                                        <td>{{ $term->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('property-terms.edit', $term->id) }}" class="btn btn-sm btn-primary me-1">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </a>
                                                <form action="{{ route('property-terms.destroy', $term->id) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete these terms?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash-fill"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">{{ __('No terms and conditions found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection

@section('page-script')
<script>
    $(document).ready(function() {
        $('#property-terms-table').DataTable();
    });
</script>
@endsection
