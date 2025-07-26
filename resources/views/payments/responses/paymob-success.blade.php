@extends('layouts.payment')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Payment Successful</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fa fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h5 class="card-title">Thank You for Your Payment</h5>
                    <p class="card-text">Your payment with Paymob has been processed successfully.</p>

                    @if(request('transaction_id'))
                        <div class="alert alert-info">
                            <p class="mb-0"><strong>Transaction ID:</strong> {{ request('transaction_id') }}</p>
                        </div>
                    @endif
            </div>
        </div>
    </div>
</div>
@endsection
