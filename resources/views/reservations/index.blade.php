@extends('layouts.main')

@section('title', 'Reservations')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Reservations</h3>
                <p class="text-subtitle text-muted">Manage all reservations for vacation homes and hotels</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reservations</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-primary">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-0" id="total-reservations">0</h4>
                            <p class="mb-0 text-muted">Total Reservations</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-success">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-0" id="total-revenue">0.00 EGP</h4>
                            <p class="mb-0 text-muted">Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-warning">
                            <i class="bi bi-house"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-0" id="vacation-home-reservations">0</h4>
                            <p class="mb-0 text-muted">Vacation Homes</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg bg-info">
                            <i class="bi bi-building"></i>
                        </div>
                        <div class="ms-3">
                            <h4 class="mb-0" id="hotel-reservations">0</h4>
                            <p class="mb-0 text-muted">Hotel Reservations</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-title">Filter Reservations</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date-from">From Date</label>
                        <input type="date" id="date-from" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date-to">To Date</label>
                        <input type="date" id="date-to" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="status-filter">Status</label>
                        <select id="status-filter" class="form-control">
                            <option value="all">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="payment-status-filter">Payment Status</label>
                        <select id="payment-status-filter" class="form-control">
                            <option value="all">All Payments</option>
                            <option value="paid">Paid</option>
                            <option value="unpaid">Unpaid</option>
                            <option value="partial">Partial</option>
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="d-flex">
                            <button type="button" class="btn btn-primary me-1" id="apply-filter">Apply</button>
                            <button type="button" class="btn btn-success" id="export-btn">Export</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button id="reset-filter" class="btn btn-secondary">Reset Filters</button>
        </div>
    </div>

    <!-- Reservations Table -->
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">All Reservations</h4>
        </div>
        <div class="card-body">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs" id="reservationTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                        All Reservations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vacation-homes-tab" data-bs-toggle="tab" data-bs-target="#vacation-homes" type="button" role="tab" aria-controls="vacation-homes" aria-selected="false">
                        Vacation Homes
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="hotels-tab" data-bs-toggle="tab" data-bs-target="#hotels" type="button" role="tab" aria-controls="hotels" aria-selected="false">
                        Hotels
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="reservationTabsContent">
                <!-- All Reservations Tab -->
                <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                    <div class="table-responsive">
                        <table class="table table-striped" id="all-reservations-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Property</th>
                                    <th>Type</th>
                                    <th>Room ID</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Guests</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Vacation Homes Tab -->
                <div class="tab-pane fade" id="vacation-homes" role="tabpanel" aria-labelledby="vacation-homes-tab">
                    <div class="table-responsive">
                        <table class="table table-striped" id="vacation-homes-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Property</th>
                                    <th>Room ID</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Guests</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Hotels Tab -->
                <div class="tab-pane fade" id="hotels" role="tabpanel" aria-labelledby="hotels-tab">
                    <div class="table-responsive">
                        <table class="table table-striped" id="hotels-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Hotel</th>
                                    <th>Room</th>
                                    <th>Room ID</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Guests</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reservation Details Modal -->
<div class="modal fade" id="reservationDetailsModal" tabindex="-1" aria-labelledby="reservationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reservationDetailsModalLabel">Reservation Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Customer Information</h6>
                        <p><strong>Name:</strong> <span id="modal-customer-name"></span></p>
                        <p><strong>Email:</strong> <span id="modal-customer-email"></span></p>
                        <p><strong>Phone:</strong> <span id="modal-customer-phone"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Property Information</h6>
                        <p><strong>Property:</strong> <span id="modal-property-name"></span></p>
                        <p><strong>Type:</strong> <span id="modal-property-type"></span></p>
                        <p><strong>Room ID:</strong> <span id="modal-room-id"></span></p>
                        <p><strong>Transaction ID:</strong> <span id="modal-transaction-id"></span></p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>Reservation Details</h6>
                        <p><strong>Check In:</strong> <span id="modal-check-in"></span></p>
                        <p><strong>Check Out:</strong> <span id="modal-check-out"></span></p>
                        <p><strong>Guests:</strong> <span id="modal-guests"></span></p>
                        <p><strong>Total Price:</strong> <span id="modal-price"></span></p>
                    </div>
                    <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>Status Information</h6>
                        <p><strong>Status:</strong> <span id="modal-status"></span></p>
                        <p><strong>Payment Status:</strong> <span id="modal-payment-status"></span></p>
                        <p><strong>Created:</strong> <span id="modal-created"></span></p>
                        <p><strong>Updated:</strong> <span id="modal-updated"></span></p>
                    </div>
                </div>
                <div class="row mt-3" id="modal-rooms-section" style="display: none;">
                    <div class="col-12">
                        <h6>Booked Rooms</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Room Type</th>
                                        <th>Price</th>
                                        <th>Guests</th>
                                        <th>Nights</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-rooms-list">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="row mt-3" id="special-requests-section" style="display: none;">
                    <div class="col-12">
                        <h6>Special Requests</h6>
                        <p id="modal-special-requests"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
let currentTab = 'all';
let tables = {};
let dateFrom = '';
let dateTo = '';
let status = 'all';
let paymentStatus = 'all';

$(document).ready(function() {
    // Load statistics
    loadStatistics();

    // Initialize tables
    initializeTables();
    
    // Force refresh all tables on page load
    setTimeout(function() {
        refreshAllTables();
    }, 1000);

    // Handle tab changes
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('data-bs-target');
        if (target === '#all') {
            currentTab = 'all';
        } else if (target === '#vacation-homes') {
            currentTab = 'vacation_homes';
        } else if (target === '#hotels') {
            currentTab = 'hotels';
        }

        // Refresh the current table
        if (tables[currentTab]) {
            tables[currentTab].bootstrapTable('refresh');
        }
    });

    // Handle apply filter button
    $('#apply-filter').on('click', function() {
        dateFrom = $('#date-from').val();
        dateTo = $('#date-to').val();
        status = $('#status-filter').val() || 'all';
        paymentStatus = $('#payment-status-filter').val() || 'all';

        // Refresh all tables with the new filters
        refreshAllTables();
    });

    // Handle export button
    $('#export-btn').on('click', function() {
        const dFrom = $('#date-from').val();
        const dTo = $('#date-to').val();
        const stat = $('#status-filter').val() || 'all';
        const pStat = $('#payment-status-filter').val() || 'all';
        
        let url = "{{ route('reservations.export') }}?type=" + currentTab;
        if(dFrom) url += "&date_from=" + dFrom;
        if(dTo) url += "&date_to=" + dTo;
        if(stat !== 'all') url += "&status=" + stat;
        if(pStat !== 'all') url += "&payment_status=" + pStat;
        
        window.location.href = url;
    });

    // Handle reset filter
    $('#reset-filter').on('click', function() {
        $('#date-from').val('');
        $('#date-to').val('');
        $('#status-filter').val('all');
        $('#payment-status-filter').val('all');
        dateFrom = '';
        dateTo = '';
        status = 'all';
        paymentStatus = 'all';

        // Refresh all tables
        refreshAllTables();
    });
});

function initializeTables() {
    // Initialize All Reservations table
    tables['all'] = $('#all-reservations-table').bootstrapTable({
        url: '{{ route("reservations.list") }}',
        method: 'get',
        queryParams: function(params) {
            params.type = 'all';
            if (dateFrom) params.date_from = dateFrom;
            if (dateTo) params.date_to = dateTo;
            if (status && status !== 'all') params.status = status;
            if (paymentStatus && paymentStatus !== 'all') params.payment_status = paymentStatus;
            params._token = '{{ csrf_token() }}';
            return params;
        },
        responseHandler: function(res) {
            // Ensure the response format matches Bootstrap Table expectations
            if (res && res.rows && Array.isArray(res.rows)) {
                return {
                    total: res.total || res.rows.length,
                    rows: res.rows
                };
            }
            return res;
        },
        columns: [
            { field: 'id', title: 'ID', sortable: true },
            { field: 'customer_name', title: 'Customer', sortable: true },
            { field: 'customer_email', title: 'Email', sortable: true },
            { field: 'property_name', title: 'Property', sortable: true },
            { field: 'property_type', title: 'Type', sortable: true },
            { field: 'reservable_id', title: 'Room ID', sortable: true, formatter: function(value) {
                return value || '-';
            }},
            { field: 'check_in_date', title: 'Check In', sortable: true },
            { field: 'check_out_date', title: 'Check Out', sortable: true },
            { field: 'number_of_guests', title: 'Guests', sortable: true },
            { field: 'total_price', title: 'Price', sortable: true },
            { field: 'status', title: 'Status', sortable: true, formatter: function(value) {
                return value;
            }},
            { field: 'payment_status', title: 'Payment', sortable: true, formatter: function(value) {
                return value;
            }},
            { field: 'payment_method', title: 'Method', sortable: true, formatter: function(value) {
                return value || '-';
            }},
            { field: 'created_at', title: 'Created', sortable: true },
            { field: 'actions', title: 'Actions', formatter: function(value) {
                return value;
            }}
        ],
        pagination: true,
        search: true,
        showRefresh: true,
        showToggle: true,
        showColumns: true,
        pageSize: 25,
        pageList: [10, 25, 50, 100, 200],
        sidePagination: 'server',
        onLoadError: function(status, jqXHR) {
            console.error('Vacation Homes Table Load Error:', status, jqXHR);
        },
        onLoadSuccess: function(data) {
            console.log('Vacation Homes Table Load Success:', data);
        }
    });

    // Initialize Vacation Homes table
    tables['vacation_homes'] = $('#vacation-homes-table').bootstrapTable({
        url: '{{ route("reservations.list") }}',
        method: 'get',
        queryParams: function(params) {
            params.type = 'vacation_homes';
            if (dateFrom) params.date_from = dateFrom;
            if (dateTo) params.date_to = dateTo;
            if (status && status !== 'all') params.status = status;
            if (paymentStatus && paymentStatus !== 'all') params.payment_status = paymentStatus;
            params._token = '{{ csrf_token() }}';
            return params;
        },
        responseHandler: function(res) {
            // Ensure the response format matches Bootstrap Table expectations
            if (res && res.rows && Array.isArray(res.rows)) {
                return {
                    total: res.total || res.rows.length,
                    rows: res.rows
                };
            }
            return res;
        },
        columns: [
            { field: 'id', title: 'ID', sortable: true },
            { field: 'customer_name', title: 'Customer', sortable: true },
            { field: 'customer_email', title: 'Email', sortable: true },
            { field: 'property_name', title: 'Property', sortable: true },
            { field: 'reservable_id', title: 'Room ID', sortable: true, formatter: function(value) {
                return value || '-';
            }},
            { field: 'check_in_date', title: 'Check In', sortable: true },
            { field: 'check_out_date', title: 'Check Out', sortable: true },
            { field: 'number_of_guests', title: 'Guests', sortable: true },
            { field: 'total_price', title: 'Price', sortable: true },
            { field: 'status', title: 'Status', sortable: true, formatter: function(value) {
                return value;
            }},
            { field: 'payment_status', title: 'Payment', sortable: true, formatter: function(value) {
                return value;
            }},
            { field: 'payment_method', title: 'Method', sortable: true, formatter: function(value) {
                return value || '-';
            }},
            { field: 'created_at', title: 'Created', sortable: true },
            { field: 'actions', title: 'Actions', formatter: function(value) {
                return value;
            }}
        ],
        pagination: true,
        search: true,
        showRefresh: true,
        showToggle: true,
        showColumns: true,
        pageSize: 25,
        pageList: [10, 25, 50, 100, 200],
        sidePagination: 'server',
        onLoadError: function(status, jqXHR) {
            console.error('Vacation Homes Table Load Error:', status, jqXHR);
        },
        onLoadSuccess: function(data) {
            console.log('Vacation Homes Table Load Success:', data);
        }
    });

    // Initialize Hotels table
    tables['hotels'] = $('#hotels-table').bootstrapTable({
        url: '{{ route("reservations.list") }}',
        method: 'get',
        queryParams: function(params) {
            params.type = 'hotels';
            if (dateFrom) params.date_from = dateFrom;
            if (dateTo) params.date_to = dateTo;
            if (status && status !== 'all') params.status = status;
            if (paymentStatus && paymentStatus !== 'all') params.payment_status = paymentStatus;
            params._token = '{{ csrf_token() }}';
            return params;
        },
        responseHandler: function(res) {
            // Ensure the response format matches Bootstrap Table expectations
            if (res && res.rows && Array.isArray(res.rows)) {
                return {
                    total: res.total || res.rows.length,
                    rows: res.rows
                };
            }
            return res;
        },
        columns: [
            { field: 'id', title: 'ID', sortable: true },
            { field: 'customer_name', title: 'Customer', sortable: true },
            { field: 'customer_email', title: 'Email', sortable: true },
            { field: 'property_name', title: 'Hotel', sortable: true },
            { field: 'property_type', title: 'Room', sortable: true },
            { field: 'reservable_id', title: 'Room ID', sortable: true, formatter: function(value) {
                return value || '-';
            }},
            { field: 'check_in_date', title: 'Check In', sortable: true },
            { field: 'check_out_date', title: 'Check Out', sortable: true },
            { field: 'number_of_guests', title: 'Guests', sortable: true },
            { field: 'total_price', title: 'Price', sortable: true },
            { field: 'status', title: 'Status', sortable: true, formatter: function(value) {
                return value;
            }},
            { field: 'payment_status', title: 'Payment', sortable: true, formatter: function(value) {
                return value;
            }},
            { field: 'payment_method', title: 'Method', sortable: true, formatter: function(value) {
                return value || '-';
            }},
            { field: 'created_at', title: 'Created', sortable: true },
            { field: 'actions', title: 'Actions', formatter: function(value) {
                return value;
            }}
        ],
        pagination: true,
        search: true,
        showRefresh: true,
        showToggle: true,
        showColumns: true,
        pageSize: 25,
        pageList: [10, 25, 50, 100, 200],
        sidePagination: 'server',
        onLoadError: function(status, jqXHR) {
            console.error('All Reservations Table Load Error:', status, jqXHR);
        },
        onLoadSuccess: function(data) {
            console.log('All Reservations Table Load Success:', data);
        }
    });
}

function refreshAllTables() {
    // Refresh all tables with the current filter
    Object.keys(tables).forEach(function(tableKey) {
        if (tables[tableKey]) {
            tables[tableKey].bootstrapTable('refresh');
        }
    });

    // Also refresh statistics as they might be affected by date filters
    loadStatistics();
}

function loadStatistics() {
    // Prepare data with date filters if available
    let data = {};
    if (dateFrom) data.date_from = dateFrom;
    if (dateTo) data.date_to = dateTo;
    if (status && status !== 'all') data.status = status;
    if (paymentStatus && paymentStatus !== 'all') data.payment_status = paymentStatus;

    $.ajax({
        url: '{{ route("reservations.statistics") }}',
        method: 'GET',
        data: data,
        success: function(response) {
            $('#total-reservations').text(response.total_reservations);
            $('#total-revenue').text(response.total_revenue);
            $('#vacation-home-reservations').text(response.vacation_home_reservations);
            $('#hotel-reservations').text(response.hotel_reservations);
        },
        error: function(xhr) {
            console.error('Error loading statistics:', xhr);
        }
    });
}

function viewReservation(id) {
    $.ajax({
        url: '{{ route("reservations.details", ":id") }}'.replace(':id', id),
        method: 'GET',
        success: function(response) {
            const reservation = response.reservation;

            // Populate modal fields
            $('#modal-customer-name').text(reservation.customer_name);
            $('#modal-customer-email').text(reservation.customer_email);
            $('#modal-customer-phone').text(reservation.customer_phone);
            $('#modal-property-name').text(reservation.property_name);
            $('#modal-property-type').text(reservation.property_type);
            $('#modal-room-id').text(reservation.reservable_id || 'N/A');
            $('#modal-transaction-id').text(reservation.transaction_id || 'N/A');
            $('#modal-check-in').text(reservation.check_in_date);
            $('#modal-check-out').text(reservation.check_out_date);
            $('#modal-guests').text(reservation.number_of_guests);
            $('#modal-price').text(reservation.total_price);
            $('#modal-status').text(reservation.status);
            $('#modal-payment-status').text(reservation.payment_status);
            $('#modal-created').text(reservation.created_at);
            $('#modal-updated').text(reservation.updated_at);

            // Handle multi-room display
            if (reservation.rooms && reservation.rooms.length > 0) {
                let roomsHtml = '';
                reservation.rooms.forEach(function(room) {
                    roomsHtml += `
                        <tr>
                            <td>${room.room_type_name}</td>
                            <td>${room.amount}</td>
                            <td>${room.guest_count}</td>
                            <td>${room.nights}</td>
                        </tr>
                    `;
                });
                $('#modal-rooms-list').html(roomsHtml);
                $('#modal-rooms-section').show();
            } else {
                $('#modal-rooms-section').hide();
            }

            // Handle special requests
            if (reservation.special_requests) {
                $('#modal-special-requests').text(reservation.special_requests);
                $('#special-requests-section').show();
            } else {
                $('#special-requests-section').hide();
            }

            // Show modal
            $('#reservationDetailsModal').modal('show');
        },
        error: function(xhr) {
            console.error('Error loading reservation details:', xhr);
            alert('Error loading reservation details');
        }
    });
}

function updateStatus(id, status) {
    if (!confirm('Are you sure you want to update this reservation status?')) {
        return;
    }

    $.ajax({
        url: '{{ route("reservations.update-status", ":id") }}'.replace(':id', id),
        method: 'POST',
        data: {
            status: status,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // Refresh the current table
                if (tables[currentTab]) {
                    tables[currentTab].bootstrapTable('refresh');
                }

                // Refresh statistics to reflect changes
                loadStatistics();

                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message
                });
            }
        },
        error: function(xhr) {
            console.error('Error updating status:', xhr);
            let errorMessage = 'Error updating reservation status';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorMessage
            });
        }
    });
}

function updatePaymentStatus(id, paymentStatus) {
    const actionText = paymentStatus === 'paid' ? 'mark as paid' : 'mark as unpaid';
    if (!confirm(`Are you sure you want to ${actionText} this reservation?`)) {
        return;
    }

    $.ajax({
        url: '{{ route("reservations.update-payment-status", ":id") }}'.replace(':id', id),
        method: 'POST',
        data: {
            payment_status: paymentStatus,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                // Refresh the current table
                if (tables[currentTab]) {
                    tables[currentTab].bootstrapTable('refresh');
                }

                // Refresh statistics to reflect changes
                loadStatistics();

                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Payment status updated successfully'
                });
            }
        },
        error: function(xhr) {
            console.error('Error updating payment status:', xhr);
            let errorMessage = 'Error updating payment status';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorMessage
            });
        }
    });
}
</script>
@endsection
