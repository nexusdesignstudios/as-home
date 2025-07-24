@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Payment Failed</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fa fa-times-circle text-danger" style="font-size: 5rem;"></i>
                    </div>
                    <h5 class="card-title">Payment Unsuccessful</h5>
                    <p class="card-text">We're sorry, but your payment could not be processed.</p>
                    <p class="card-text">Transaction ID: {{ request('transaction_id') ?? 'N/A' }}</p>

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
