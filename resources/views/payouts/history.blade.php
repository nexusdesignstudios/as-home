@extends('layouts.main')

@section('title', 'Payout History')

@section('content')
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Payout History</h3>
                <p class="text-subtitle text-muted">View processed property payouts</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('payouts.index') }}">Payouts</a></li>
                        <li class="breadcrumb-item active" aria-current="page">History</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h4 class="card-title">Payout History</h4>
                <a href="{{ route('payouts.index') }}" class="btn btn-primary">View Pending Payouts</a>
            </div>
            <div class="card-body">
                <div class="filters mb-4">
                    <form action="{{ route('payouts.history') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="month" class="form-label">Month</label>
                            <select name="month" id="month" class="form-select">
                                <option value="">All Months</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}" {{ request('month') == sprintf('%02d', $i) ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 1)) }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label">Year</label>
                            <select name="year" id="year" class="form-select">
                                <option value="">All Years</option>
                                @for($i = date('Y'); $i >= date('Y') - 5; $i--)
                                    <option value="{{ $i }}" {{ request('year') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Successful</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="{{ route('payouts.history') }}" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped" id="history-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Property</th>
                                <th>Customer</th>
                                <th>Original Amount</th>
                                <th>Commission %</th>
                                <th>Payout Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Period</th>
                                <th>Date</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($processedPayouts as $payout)
                                <tr>
                                    <td>{{ $payout->id }}</td>
                                    <td>{{ $payout->property->title ?? 'N/A' }}</td>
                                    <td>{{ $payout->customer->name ?? 'N/A' }}</td>
                                    <td>{{ number_format($payout->original_amount, 2) }} EGP</td>
                                    <td>{{ $payout->commission_percentage }}%</td>
                                    <td>{{ number_format($payout->amount, 2) }} EGP</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $payout->issuer)) }}</td>
                                    <td>
                                        <span class="badge {{ $payout->status_badge_class }}">
                                            {{ ucfirst($payout->disbursement_status) }}
                                        </span>
                                    </td>
                                    <td>{{ $payout->payout_month }}/{{ $payout->payout_year }}</td>
                                    <td>{{ $payout->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal{{ $payout->id }}">
                                            Details
                                        </button>
                                    </td>
                                </tr>

                                <!-- Details Modal -->
                                <div class="modal fade" id="detailsModal{{ $payout->id }}" tabindex="-1" aria-labelledby="detailsModalLabel{{ $payout->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="detailsModalLabel{{ $payout->id }}">Payout Details #{{ $payout->id }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <h6>Transaction Information</h6>
                                                        <table class="table table-bordered">
                                                            <tr>
                                                                <th>Transaction ID</th>
                                                                <td>{{ $payout->transaction_id }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Status</th>
                                                                <td>{{ ucfirst($payout->disbursement_status) }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Status Code</th>
                                                                <td>{{ $payout->status_code }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Status Description</th>
                                                                <td>{{ $payout->status_description }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Reference Number</th>
                                                                <td>{{ $payout->reference_number }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Date</th>
                                                                <td>{{ $payout->created_at->format('Y-m-d H:i:s') }}</td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h6>Payment Details</h6>
                                                        <table class="table table-bordered">
                                                            <tr>
                                                                <th>Original Amount</th>
                                                                <td>{{ number_format($payout->original_amount, 2) }} EGP</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Commission</th>
                                                                <td>{{ $payout->commission_percentage }}%</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Payout Amount</th>
                                                                <td>{{ number_format($payout->amount, 2) }} EGP</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Payment Method</th>
                                                                <td>{{ ucfirst(str_replace('_', ' ', $payout->issuer)) }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Period</th>
                                                                <td>{{ $payout->payout_month }}/{{ $payout->payout_year }}</td>
                                                            </tr>
                                                            <tr>
                                                                <th>Notes</th>
                                                                <td>{{ $payout->notes }}</td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>

                                                @if($payout->issuer == 'aman' && !empty($payout->aman_cashing_details))
                                                <div class="row mt-4">
                                                    <div class="col-12">
                                                        <h6>Aman Cashing Details</h6>
                                                        <table class="table table-bordered">
                                                            @foreach($payout->aman_cashing_details as $key => $value)
                                                            <tr>
                                                                <th>{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                                                <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </table>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">No payout history found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $processedPayouts->links() }}
                </div>
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#history-table').DataTable({
            "order": [[9, "desc"]],
            "pageLength": 25,
            "dom": 'Bfrtip',
            "buttons": [
                'copy', 'csv', 'excel', 'pdf', 'print'
            ]
        });
    });
</script>
@endsection
