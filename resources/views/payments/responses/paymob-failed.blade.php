@extends('layouts.payment')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Payment Failed</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fa fa-times-circle text-danger" style="font-size: 5rem;"></i>
                    </div>
                    <h5 class="card-title">Payment Unsuccessful</h5>
                    <p class="card-text">We're sorry, but your payment with Paymob could not be processed.</p>

                    @if(request('transaction_id'))
                        <div class="alert alert-warning">
                            <p class="mb-0"><strong>Transaction ID:</strong> {{ request('transaction_id') }}</p>
                        </div>
                    @endif

                    <div class="mt-4">
                        <p>If you believe this is an error, please contact our support team.</p>
                    </div>

                    <div class="mt-4">
                        <a href="{{ url('/') }}" class="btn btn-primary">Back to Home</a>
                        <a href="{{ url('/payments') }}" class="btn btn-outline-primary ml-2">Try Again</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
