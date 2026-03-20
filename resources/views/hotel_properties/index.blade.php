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
                                    <th scope="col" data-field="cancellation_period" data-sortable="true" data-formatter="cancellationPeriodFormatter">{{ __('Cancellation Period') }}</th>
                                    <th scope="col" data-field="instant_booking" data-sortable="true" data-formatter="instantBookingFormatter">{{ __('Instant Booking') }}</th>
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

    <!-- Update Cancellation Period Modal -->
    <div class="modal fade" id="updateCancellationPeriodModal" tabindex="-1" role="dialog" aria-labelledby="updateCancellationPeriodModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="updateCancellationPeriodForm" action="{{ route('hotel_properties.update_cancellation_period') }}" method="POST">
                    @csrf
                    <input type="hidden" name="property_id" id="property_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateCancellationPeriodModalLabel">{{ __('Update Cancellation Period') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="cancellation_period">{{ __('Cancellation Period') }}</label>
                            <select name="cancellation_period" id="cancellation_period" class="form-select">
                                <option value="">{{ __('No Cancellation Policy') }}</option>
                                <option value="3">{{ __('3 Days') }}</option>
                                <option value="5">{{ __('5 Days') }}</option>
                                <option value="7">{{ __('7 Days') }}</option>
                                <option value="14">{{ __('14 Days') }}</option>
                                <option value="7_days">{{ __('7 Days (Legacy)') }}</option>
                                <option value="same_day_6pm">{{ __('Same Day at 06:00 PM') }}</option>
                            </select>
                            <small class="text-muted">
                                <ul>
                                    <li><strong>N Days:</strong> No flexible bookings if check-in is within N days.</li>
                                    <li><strong>Same Day 6 PM:</strong> No flexible bookings on the same day after 06:00 PM.</li>
                                </ul>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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

        function instantBookingFormatter(value, row) {
            if (value == 1) {
                return '<span class="badge bg-success">{{ __('Yes') }}</span>';
            } else {
                return '<span class="badge bg-danger">{{ __('No') }}</span>';
            }
        }

        function cancellationPeriodFormatter(value, row) {
            if (!value || value === 'N/A') {
                return '<span class="badge bg-secondary">{{ __('No Policy') }}</span>';
            }

            if (value === 'same_day_6pm') {
                return '<span class="badge bg-warning">Same Day 6:00 PM</span>';
            }

            var numeric = null;
            if (/^\d+$/.test(value)) {
                numeric = parseInt(value, 10);
            } else if (/^\d+_days$/.test(value)) {
                numeric = parseInt(value.split('_')[0], 10);
            } else if (value === '7_days') {
                numeric = 7;
            }

            if (numeric !== null && !isNaN(numeric)) {
                return '<span class="badge bg-info">' + numeric + ' Days</span>';
            }

            return '<span class="badge bg-secondary">{{ __('No Policy') }}</span>';
        }

        window.actionEvents = {
            'click .update-cancellation-period': function(e, value, row, index) {
                $('#property_id').val(row.id);
                $('#cancellation_period').val(row.cancellation_period == 'N/A' ? '' : row.cancellation_period);
                $('#updateCancellationPeriodModal').modal('show');
            }
        };

        $(document).on('change', '.update-instant-booking', function() {
            var id = $(this).data('id');
            var is_checked = $(this).is(':checked') ? 1 : 0;
            var url = "{{ route('hotel_properties.update_instant_booking') }}";

            $.ajax({
                type: "POST",
                url: url,
                data: {
                    _token: "{{ csrf_token() }}",
                    property_id: id,
                    instant_booking: is_checked
                },
                success: function(response) {
                    if (response.error == false) {
                        $('#hotel_properties_list').bootstrapTable('refresh');
                        Toastify({
                            text: response.message,
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                        }).showToast();
                    } else {
                        Toastify({
                            text: response.message,
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
                        }).showToast();
                    }
                }
            });
        });

        $('#updateCancellationPeriodForm').submit(function(e) {
            e.preventDefault();
            var form = $(this);
            var url = form.attr('action');
            var formData = form.serialize();

            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                success: function(response) {
                    if (response.error == false) {
                        $('#updateCancellationPeriodModal').modal('hide');
                        $('#hotel_properties_list').bootstrapTable('refresh');
                        Toastify({
                            text: response.message,
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                        }).showToast();
                    } else {
                        Toastify({
                            text: response.message,
                            duration: 3000,
                            close: true,
                            gravity: "top",
                            position: "right",
                            backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
                        }).showToast();
                    }
                }
            });
        });
    </script>
@endsection
