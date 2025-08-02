@extends('layouts.main')

@section('title', 'Pending Payouts')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Pending Payouts</h3>
                <p class="text-subtitle text-muted">Manage property payouts</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Pending Payouts</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h4 class="card-title">Pending Payouts</h4>
                <a href="{{ route('payouts.history') }}" class="btn btn-primary">View Payout History</a>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="filters mb-4">
                    <form action="{{ route('payouts.index') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="month" class="form-label">Month</label>
                            <select name="month" id="month" class="form-select">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}" {{ request('month', date('m')) == sprintf('%02d', $i) ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label">Year</label>
                            <select name="year" id="year" class="form-select">
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ request('year', date('Y')) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="{{ route('payouts.index') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped" id="payouts-table">
                        <thead>
                            <tr>
                                <th>Property</th>
                                <th>Owner</th>
                                <th>Classification</th>
                                <th>Package</th>
                                <th>Original Amount</th>
                                <th>Commission %</th>
                                <th>Amount After Commission</th>
                                <th>Month/Year</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingPayouts as $payout)
                                <tr>
                                    <td>{{ $payout->property_title }}</td>
                                    <td>{{ $payout->customer_name }}</td>
                                    <td>{{ $payout->property_classification }}</td>
                                    <td>{{ ucfirst($payout->rent_package) }}</td>
                                    <td>{{ number_format($payout->original_amount, 2) }} EGP</td>
                                    <td>{{ $payout->commission_percentage }}%</td>
                                    <td>{{ number_format($payout->amount_after_commission, 2) }} EGP</td>
                                    <td>{{ $payout->payout_month }}/{{ $payout->payout_year }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payoutModal{{ $payout->property_id }}">
                                            Process Payout
                                        </button>
                                    </td>
                                </tr>

                                <!-- Payout Modal -->
                                <div class="modal fade" id="payoutModal{{ $payout->property_id }}" tabindex="-1" aria-labelledby="payoutModalLabel{{ $payout->property_id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="payoutModalLabel{{ $payout->property_id }}">Process Payout for {{ $payout->property_title }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('payouts.process', $payout->property_id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="issuer" class="form-label">Payment Method</label>
                                                                <select class="form-select" id="issuer" name="issuer" required>
                                                                    <option value="">Select Payment Method</option>
                                                                    <option value="vodafone">Vodafone Cash</option>
                                                                    <option value="etisalat">Etisalat Cash</option>
                                                                    <option value="orange">Orange Cash</option>
                                                                    <option value="aman">Aman</option>
                                                                    <option value="bank_wallet">Bank Wallet</option>
                                                                    <option value="bank_card">Bank Card</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="msisdn" class="form-label">Phone Number</label>
                                                                <input type="text" class="form-control" id="msisdn" name="msisdn" placeholder="11 digits phone number">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="bank-fields d-none">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="bank_card_number" class="form-label">Bank Card Number</label>
                                                                    <input type="text" class="form-control" id="bank_card_number" name="bank_card_number">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="bank_code" class="form-label">Bank Code</label>
                                                                    <input type="text" class="form-control" id="bank_code" name="bank_code">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="mb-3">
                                                                    <label for="bank_transaction_type" class="form-label">Transaction Type</label>
                                                                    <select class="form-select" id="bank_transaction_type" name="bank_transaction_type">
                                                                        <option value="salary">Salary</option>
                                                                        <option value="credit_card">Credit Card</option>
                                                                        <option value="prepaid_card">Prepaid Card</option>
                                                                        <option value="cash_transfer">Cash Transfer</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="aman-fields d-none">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="first_name" class="form-label">First Name</label>
                                                                    <input type="text" class="form-control" id="first_name" name="first_name">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="mb-3">
                                                                    <label for="last_name" class="form-label">Last Name</label>
                                                                    <input type="text" class="form-control" id="last_name" name="last_name">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="mb-3">
                                                                <label for="client_reference_id" class="form-label">Reference ID (Optional)</label>
                                                                <input type="text" class="form-control" id="client_reference_id" name="client_reference_id">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="alert alert-info">
                                                        <h5>Payout Summary</h5>
                                                        <p><strong>Original Amount:</strong> {{ number_format($payout->original_amount, 2) }} EGP</p>
                                                        <p><strong>Commission Rate:</strong> {{ $payout->commission_percentage }}%</p>
                                                        <p><strong>Amount After Commission:</strong> {{ number_format($payout->amount_after_commission, 2) }} EGP</p>
                                                        <p><strong>Period:</strong> {{ $payout->payout_month }}/{{ $payout->payout_year }}</p>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Process Payout</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">No pending payouts found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#payouts-table').DataTable({
            "order": [[7, "desc"]],
            "pageLength": 25
        });

        // Show/hide fields based on issuer selection
        $('select[name="issuer"]').on('change', function() {
            const issuer = $(this).val();

            // Hide all conditional fields first
            $('.bank-fields, .aman-fields').addClass('d-none');

            if (issuer === 'bank_card') {
                $('.bank-fields').removeClass('d-none');
            } else if (issuer === 'aman') {
                $('.aman-fields').removeClass('d-none');
            }
        });
    });
</script>
@endsection
