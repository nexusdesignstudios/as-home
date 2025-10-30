@extends('layouts.app')

@section('title', 'Manual Tax Invoice Generation')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Manual Tax Invoice Generation</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Note:</strong> This will generate and send tax invoices for the selected month. 
                        Make sure to test with a small group first.
                    </div>

                    <form id="tax-invoice-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="month">Select Month</label>
                                    <input type="month" class="form-control" id="month" name="month" 
                                           value="{{ date('Y-m', strtotime('-1 month')) }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="test_email">Test Email (Optional)</label>
                                    <input type="email" class="form-control" id="test_email" name="test_email" 
                                           placeholder="Leave empty to send to all owners">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="dry_run" name="dry_run">
                                <label class="form-check-label" for="dry_run">
                                    Dry Run (Test mode - no emails will be sent)
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="generate-btn">
                            <span id="btn-text">Generate Tax Invoices</span>
                            <span class="spinner-border spinner-border-sm d-none" id="btn-spinner"></span>
                        </button>
                    </form>

                    <div id="result" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tax-invoice-form').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#generate-btn');
        const $btnText = $('#btn-text');
        const $btnSpinner = $('#btn-spinner');
        const $result = $('#result');
        
        // Disable button and show spinner
        $btn.prop('disabled', true);
        $btnText.text('Generating...');
        $btnSpinner.removeClass('d-none');
        
        // Prepare data
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("admin.tax-invoice.generate") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $result.html(`
                    <div class="alert alert-success">
                        <h5>Tax Invoice Generation Completed!</h5>
                        <p><strong>Total Owners Processed:</strong> ${response.total_owners}</p>
                        <p><strong>Total Emails Sent:</strong> ${response.total_emails_sent}</p>
                        <p><strong>Total Errors:</strong> ${response.total_errors}</p>
                        ${response.errors && response.errors.length > 0 ? 
                            '<p><strong>Errors:</strong><br>' + response.errors.join('<br>') + '</p>' : ''}
                    </div>
                `);
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while generating tax invoices.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $result.html(`
                    <div class="alert alert-danger">
                        <h5>Error</h5>
                        <p>${errorMessage}</p>
                    </div>
                `);
            },
            complete: function() {
                // Re-enable button
                $btn.prop('disabled', false);
                $btnText.text('Generate Tax Invoices');
                $btnSpinner.addClass('d-none');
            }
        });
    });
});
</script>
@endsection
