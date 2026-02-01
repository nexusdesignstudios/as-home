@extends('layouts.main')

@section('title')
    {{ __('Customer') }}
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
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div id="toolbar">
                            <a href="{{ route('export-all-customers') }}" class="btn btn-primary btn-sm">{{ __('Export All Customers') }}</a>
                        </div>
                        <table class="table table-striped" id="table_list"
                            data-toggle="table" data-url="{{ url('customerList') }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true"
                            data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true" data-toolbar="#toolbar"
                            data-show-columns="true" data-show-refresh="true" data-trim-on-search="false"
                            data-responsive="true" data-sort-name="id" data-sort-order="desc"
                            data-pagination-successively-size="3" data-query-params="queryParams" data-show-export="true"
                            data-export-options='{ "fileName": "data-list-<?= date(' d-m-y') ?>" }'>
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true">
                                        {{ __('ID') }}</th>
                                    <th scope="col" data-field="profile" data-sortable="false" data-align="center"
                                        data-formatter="imageFormatter">
                                        {{ __('Profile') }}</th>
                                    <th scope="col" data-field="name" data-sortable="true" data-align="center">
                                        {{ __('Name') }}</th>
                                    <th scope="col" data-field="mobile" data-sortable="true" data-align="center">
                                        {{ __('Number') }}</th>
                                    <th scope="col" data-field="email" data-sortable="true" data-align="center">
                                        {{ __('Email') }}</th>
                                    <th scope="col" data-field="logintype" data-sortable="true" data-formatter="customerLoginTypeFormatter" data-align="center">
                                        {{ __('Login Methods') }}</th>
                                    <th scope="col" data-field="address" data-sortable="false" data-align="center">
                                        {{ __('Address') }}</th>
                                    <th scope="col" data-field="total_properties" data-sortable="false"
                                        data-align="center">
                                        {{ __('Total Property Post') }}</th>
                                    <th scope="col" data-field="total_projects" data-sortable="false"
                                        data-align="center">
                                        {{ __('Total Project Post') }}</th>
                                    @if (has_permissions('update', 'customer'))
                                        <th scope="col" data-field="isActive" data-formatter="enableDisableSwitchFormatter"
                                            data-sortable="false" data-align="center">
                                            {{ __('Enable/Disable') }}
                                        </th>
                                    @endif
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

        $(document).ready(function() {
            $(document).on('click', '.verify-email-btn', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                var status = $(this).data('status');
                var action = status == 1 ? 'Approve' : 'Reject';
                
                if(confirm('Are you sure you want to ' + action + ' email verification?')) {
                    $.ajax({
                        url: "{{ route('customer.verify-email') }}",
                        type: "POST",
                        data: {
                            id: id,
                            status: status,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(result) {
                            if (!result.error) {
                                Toastify({
                                    text: result.message,
                                    duration: 3000,
                                    close: true,
                                    backgroundColor: "#4fbe87",
                                }).showToast();
                                $('#table_list').bootstrapTable('refresh');
                            } else {
                                Toastify({
                                    text: result.message,
                                    duration: 3000,
                                    close: true,
                                    backgroundColor: "#dc3545",
                                }).showToast();
                            }
                        },
                        error: function(xhr) {
                            Toastify({
                                text: "An error occurred",
                                duration: 3000,
                                close: true,
                                backgroundColor: "#dc3545",
                            }).showToast();
                        }
                    });
                }
            });

            // Delete Customer
            $(document).on('click', '.delete-customer-btn', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                
                if(confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
                    $.ajax({
                        url: "{{ url('customer') }}/" + id,
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(result) {
                            if (!result.error) {
                                Toastify({
                                    text: result.message,
                                    duration: 3000,
                                    close: true,
                                    backgroundColor: "#4fbe87",
                                }).showToast();
                                $('#table_list').bootstrapTable('refresh');
                            } else {
                                Toastify({
                                    text: result.message,
                                    duration: 3000,
                                    close: true,
                                    backgroundColor: "#dc3545",
                                }).showToast();
                            }
                        },
                        error: function(xhr) {
                            Toastify({
                                text: "An error occurred",
                                duration: 3000,
                                close: true,
                                backgroundColor: "#dc3545",
                            }).showToast();
                        }
                    });
                }
            });
        });
    </script>
@endsection
