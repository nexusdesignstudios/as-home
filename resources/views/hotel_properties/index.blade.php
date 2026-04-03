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

    <!-- Update Cancellation Policy Modal -->
    <div class="modal fade" id="updateCancellationPeriodModal" tabindex="-1" role="dialog" aria-labelledby="updateCancellationPeriodModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="updateCancellationPeriodForm" action="{{ route('hotel_properties.update_cancellation_period') }}" method="POST">
                    @csrf
                    <input type="hidden" name="property_id" id="property_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateCancellationPeriodModalLabel">{{ __('Edit Cancellation Policy') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('Cancellation Policy') }}</label>
                            
                            <!-- No Cancellation Policy -->
                            <div class="form-check mb-2 p-2 rounded" style="background: #f8f9fa; border-left: 4px solid #6c757d;">
                                <input class="form-check-input" type="radio" name="cancellation_policy" id="policy_none" value="none" checked>
                                <label class="form-check-label d-flex align-items-center" for="policy_none">
                                    <span class="me-2">🚫</span>
                                    <span>{{ __('No Cancellation Policy') }}</span>
                                </label>
                            </div>

                            <!-- 3 Days -->
                            <div class="form-check mb-2 p-2 rounded" style="background: #e3f2fd; border-left: 4px solid #2196F3;">
                                <input class="form-check-input" type="radio" name="cancellation_policy" id="policy_3_days" value="3_days">
                                <label class="form-check-label d-flex align-items-center" for="policy_3_days">
                                    <span class="me-2">📅</span>
                                    <span>{{ __('3 Days Cancellation Period') }}</span>
                                </label>
                            </div>

                            <!-- 5 Days -->
                            <div class="form-check mb-2 p-2 rounded" style="background: #e3f2fd; border-left: 4px solid #2196F3;">
                                <input class="form-check-input" type="radio" name="cancellation_policy" id="policy_5_days" value="5_days">
                                <label class="form-check-label d-flex align-items-center" for="policy_5_days">
                                    <span class="me-2">📅</span>
                                    <span>{{ __('5 Days Cancellation Period') }}</span>
                                </label>
                            </div>

                            <!-- 7 Days -->
                            <div class="form-check mb-2 p-2 rounded" style="background: #e3f2fd; border-left: 4px solid #2196F3;">
                                <input class="form-check-input" type="radio" name="cancellation_policy" id="policy_7_days" value="7_days">
                                <label class="form-check-label d-flex align-items-center" for="policy_7_days">
                                    <span class="me-2">📅</span>
                                    <span>{{ __('7 Days Cancellation Period') }}</span>
                                </label>
                            </div>

                            <!-- Same Day 6 PM -->
                            <div class="form-check mb-2 p-2 rounded" style="background: #fff3e0; border-left: 4px solid #FF9800;">
                                <input class="form-check-input" type="radio" name="cancellation_policy" id="policy_same_day_6pm" value="same_day_6pm">
                                <label class="form-check-label d-flex align-items-center" for="policy_same_day_6pm">
                                    <span class="me-2">⏰</span>
                                    <span>{{ __('Same Day at 06:00 PM') }}</span>
                                </label>
                            </div>

                            <!-- Custom Days -->
                            <div class="form-check mb-2 p-2 rounded" style="background: #f3e5f5; border-left: 4px solid #9C27B0;">
                                <input class="form-check-input" type="radio" name="cancellation_policy" id="policy_custom" value="custom">
                                <label class="form-check-label d-flex align-items-center" for="policy_custom">
                                    <span class="me-2">⚙️</span>
                                    <span>{{ __('Custom Days') }}</span>
                                </label>
                                <div class="mt-2 ms-4" id="custom_days_container" style="display: none;">
                                    <input type="number" name="cancellation_custom_days" id="cancellation_custom_days" class="form-control form-control-sm" placeholder="{{ __('Enter days') }}" min="1" max="365">
                                </div>
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <span class="me-2">ℹ️</span>
                            <small>{{ __('Allows flexible bookings at any time.') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" style="background-color: #D4AF37; border-color: #D4AF37;">{{ __('Save Changes') }}</button>
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
                
                // Map cancellation_period to cancellation_policy
                var policy = 'none';
                if (row.cancellation_period && row.cancellation_period !== 'N/A') {
                    if (row.cancellation_period === 'same_day_6pm') {
                        policy = 'same_day_6pm';
                    } else if (row.cancellation_period === '3_days' || row.cancellation_period === '3') {
                        policy = '3_days';
                    } else if (row.cancellation_period === '5_days' || row.cancellation_period === '5') {
                        policy = '5_days';
                    } else if (row.cancellation_period === '7_days' || row.cancellation_period === '7') {
                        policy = '7_days';
                    } else if (/^\d+$/.test(row.cancellation_period) || /^\d+_days$/.test(row.cancellation_period)) {
                        // Custom days
                        policy = 'custom';
                        var days = row.cancellation_period.replace('_days', '').replace('_day', '');
                        $('#cancellation_custom_days').val(days);
                    }
                }
                
                // Set radio button
                $('input[name="cancellation_policy"][value="' + policy + '"]').prop('checked', true);
                
                // Show/hide custom days input
                if (policy === 'custom') {
                    $('#custom_days_container').show();
                } else {
                    $('#custom_days_container').hide();
                    $('#cancellation_custom_days').val('');
                }
                
                $('#updateCancellationPeriodModal').modal('show');
            }
        };

        // Show/hide custom days input based on radio selection
        $('input[name="cancellation_policy"]').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#custom_days_container').show();
                $('#cancellation_custom_days').focus();
            } else {
                $('#custom_days_container').hide();
                $('#cancellation_custom_days').val('');
            }
        });

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

            // Debug: Log form data
            console.log('Submitting cancellation policy:', formData);

            $.ajax({
                type: "POST",
                url: url,
                data: formData,
                success: function(response) {
                    console.log('Success response:', response);
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
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    var errorMessage = 'An error occurred while saving';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        errorMessage = xhr.responseText.substring(0, 100);
                    }
                    Toastify({
                        text: errorMessage,
                        duration: 5000,
                        close: true,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "linear-gradient(to right, #ff5f6d, #ffc371)",
                    }).showToast();
                }
            });
        });
    </script>
@endsection
