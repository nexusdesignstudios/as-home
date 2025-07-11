@extends('layouts.main')

@section('title')
    {{ __('Hotel Properties') }}
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
                            <a href="{{ url('home') }}">{{ __('Dashboard') }}</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            @yield('title')
                        </li>
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
                <div class="divider">
                    <div class="divider-text">
                        <h4>{{ __('Hotel Properties List') }}</h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        @if (has_permissions('create', 'property'))
                            <a href="{{ route('property.create') }}" class="btn btn-primary mb-3 float-end">
                                <i class="bi bi-plus"></i> {{ __('Create Hotel Property') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <table class="table-light" aria-describedby="mydesc" class='table-striped' id="hotel_properties_list"
                            data-toggle="table" data-url="{{ url('hotel_properties_list') }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200,All]" data-search="true" data-toolbar="#toolbar"
                            data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                            data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                            data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                            data-pagination-successively-size="3" data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true">{{ __('ID') }}</th>
                                    <th scope="col" data-field="title" data-sortable="true">{{ __('Title') }}</th>
                                    <th scope="col" data-field="address" data-sortable="true">{{ __('Address') }}</th>
                                    <th scope="col" data-field="refund_policy" data-sortable="true">{{ __('Refund Policy') }}</th>
                                    <th scope="col" data-field="room_count" data-sortable="true">{{ __('Room Count') }}</th>
                                    <th scope="col" data-field="status" data-sortable="true" data-formatter="statusFormatter">{{ __('Status') }}</th>
                                    <th scope="col" data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                                    <th scope="col" data-field="operate" data-sortable="false" data-events="actionEvents">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
    <script>
        function queryParams(p) {
            return {
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                limit: p.limit,
                search: p.search
            };
        }

        function statusFormatter(value, row) {
            if (value == 1) {
                return '<span class="badge bg-success">{{ __('Active') }}</span>';
            } else {
                return '<span class="badge bg-danger">{{ __('Inactive') }}</span>';
            }
        }
    </script>
@endsection
