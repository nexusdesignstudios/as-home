@extends('layouts.payment')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Payment Successful</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fa fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h5 class="card-title">Thank You for Your Payment</h5>
                    <p class="card-text">Your transaction has been completed successfully.</p>
                    <p class="card-text">Transaction ID: {{ request('transaction_id') ?? 'N/A' }}</p>

                    <div class="mt-4">
                        <a href="{{ url('/') }}" class="btn btn-primary">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
