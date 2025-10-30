@extends('layouts.app')

@section('title', 'Guaranteed Email System')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">
                        <i class="fas fa-envelope-open-text"></i> Guaranteed Email System
                    </h3>
                    <small>Multiple fallback mechanisms for ALL email types</small>
                </div>
                <div class="card-body">
                    <!-- Email Types Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-comment-dots"></i> Feedback Requests</h5>
                                    <p class="mb-0">Daily at 10:30 AM</p>
                                    <small><strong>Sent ONLY on checkout date</strong></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-door-open"></i> Checkout Reminders</h5>
                                    <p class="mb-0">Daily at 9:30 AM</p>
                                    <small>Checkout day notifications</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-file-invoice-dollar"></i> Tax Invoices</h5>
                                    <p class="mb-0">15th at 9:30 AM</p>
                                    <small>Monthly revenue reports</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h5><i class="fas fa-cogs"></i> All Systems</h5>
                                    <p class="mb-0">Multiple Fallbacks</p>
                                    <small>Guaranteed delivery</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Important Information -->
                    <div class="alert alert-success">
                        <h5><i class="fas fa-info-circle"></i> Feedback Request Email Behavior</h5>
                        <p><strong>Feedback Request Emails are sent ONLY on the checkout date of each reservation.</strong></p>
                        <ul class="mb-0">
                            <li>The system runs <strong>daily</strong> to check for reservations checking out on that specific day</li>
                            <li>Each reservation receives the feedback email <strong>ONCE</strong>, on their checkout date</li>
                            <li>If a reservation checks out on January 15th, the email is sent on January 15th only</li>
                            <li>The system will not send duplicate emails if already sent</li>
                        </ul>
                    </div>

                    <!-- Hostinger Setup -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-server"></i> Hostinger Setup Instructions</h5>
                        <p><strong>Set up this ONE cron job in your Hostinger cPanel:</strong></p>
                        <div class="bg-dark text-light p-3 rounded">
                            <code>
                                # All guaranteed emails - runs every 2 hours<br>
                                0 */2 * * * /usr/bin/php /home/yourusername/public_html/guaranteed-emails-cron.php?key=guaranteed_emails_2025_secure_key_99999
                            </code>
                        </div>
                        <p class="mt-2"><strong>Alternative - Daily cron:</strong></p>
                        <div class="bg-dark text-light p-3 rounded">
                            <code>
                                # Daily at 8 AM<br>
                                0 8 * * * /usr/bin/php /home/yourusername/public_html/guaranteed-emails-cron.php?key=guaranteed_emails_2025_secure_key_99999
                            </code>
                        </div>
                        <p class="mt-2"><strong>IMPORTANT:</strong> Change the secret key in the file for security!</p>
                    </div>

                    <!-- Manual Controls -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Individual Email Controls</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-success" id="feedback-btn">
                                            <i class="fas fa-comment-dots"></i> Send Feedback Requests
                                        </button>
                                        <button class="btn btn-warning" id="checkout-btn">
                                            <i class="fas fa-door-open"></i> Send Checkout Reminders
                                        </button>
                                        <button class="btn btn-info" id="tax-btn">
                                            <i class="fas fa-file-invoice-dollar"></i> Send Tax Invoices
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>System Controls</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary" id="all-emails-btn">
                                            <i class="fas fa-paper-plane"></i> Send All Emails Now
                                        </button>
                                        <button class="btn btn-secondary" id="status-btn">
                                            <i class="fas fa-chart-line"></i> Check System Status
                                        </button>
                                        <button class="btn btn-dark" id="test-btn">
                                            <i class="fas fa-vial"></i> Test All Systems
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Email Form -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Test Email System</h5>
                        </div>
                        <div class="card-body">
                            <form id="test-form">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="test_email">Test Email Address</label>
                                            <input type="email" class="form-control" id="test_email" name="test_email" 
                                                   placeholder="test@example.com" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email_type">Email Type</label>
                                            <select class="form-control" id="email_type" name="email_type">
                                                <option value="all">All Email Types</option>
                                                <option value="feedback">Feedback Requests</option>
                                                <option value="checkout">Checkout Reminders</option>
                                                <option value="tax">Tax Invoices</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" id="test-submit-btn">
                                    <span id="test-btn-text">Send Test Emails</span>
                                    <span class="spinner-border spinner-border-sm d-none" id="test-spinner"></span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Results -->
                    <div id="results" class="mt-4"></div>

                    <!-- System Status -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>System Status & Logs</h5>
                        </div>
                        <div class="card-body">
                            <div id="status-content">
                                <p class="text-muted">Click "Check System Status" to view current status and logs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Individual email controls
    $('#feedback-btn').on('click', function() {
        executeCommand('feedback:guaranteed-send', {}, this);
    });

    $('#checkout-btn').on('click', function() {
        executeCommand('checkout:guaranteed-reminders', {}, this);
    });

    $('#tax-btn').on('click', function() {
        const month = new Date().toISOString().slice(0, 7); // Current month
        executeCommand('tax:guaranteed-invoices', {month: month}, this);
    });

    // System controls
    $('#all-emails-btn').on('click', function() {
        executeCommand('all-emails', {}, this);
    });

    $('#status-btn').on('click', function() {
        checkSystemStatus();
    });

    $('#test-btn').on('click', function() {
        executeCommand('test-all', {}, this);
    });

    // Test form
    $('#test-form').on('submit', function(e) {
        e.preventDefault();
        const email = $('#test_email').val();
        const type = $('#email_type').val();
        
        if (type === 'all') {
            executeCommand('test-all', {email: email}, '#test-submit-btn', '#test-btn-text', '#test-spinner');
        } else if (type === 'feedback') {
            executeCommand('feedback:guaranteed-send', {email: email}, '#test-submit-btn', '#test-btn-text', '#test-spinner');
        } else if (type === 'checkout') {
            executeCommand('checkout:guaranteed-reminders', {email: email}, '#test-submit-btn', '#test-btn-text', '#test-spinner');
        } else if (type === 'tax') {
            const month = new Date().toISOString().slice(0, 7);
            executeCommand('tax:guaranteed-invoices', {month: month, email: email}, '#test-submit-btn', '#test-btn-text', '#test-spinner');
        }
    });

    function executeCommand(command, data, button, buttonText, spinner) {
        const $btn = $(button);
        const $btnText = buttonText ? $(buttonText) : null;
        const $spinner = spinner ? $(spinner) : null;
        const $results = $('#results');

        // Disable button and show spinner
        $btn.prop('disabled', true);
        if ($btnText) $btnText.text('Processing...');
        if ($spinner) $spinner.removeClass('d-none');

        $.ajax({
            url: '{{ route("admin.tax-invoice.generate") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                command: command,
                ...data
            },
            success: function(response) {
                $results.html(`
                    <div class="alert alert-success">
                        <h5>Command Executed Successfully!</h5>
                        <p><strong>Command:</strong> ${command}</p>
                        <p><strong>Result:</strong> ${response.message || 'Completed'}</p>
                        ${response.total_emails_sent ? `<p><strong>Emails Sent:</strong> ${response.total_emails_sent}</p>` : ''}
                        ${response.total_errors ? `<p><strong>Errors:</strong> ${response.total_errors}</p>` : ''}
                    </div>
                `);
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while executing the command.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                $results.html(`
                    <div class="alert alert-danger">
                        <h5>Error</h5>
                        <p>${errorMessage}</p>
                    </div>
                `);
            },
            complete: function() {
                // Re-enable button
                $btn.prop('disabled', false);
                if ($btnText) $btnText.text('Send Test Emails');
                if ($spinner) $spinner.addClass('d-none');
            }
        });
    }

    function checkSystemStatus() {
        $.ajax({
            url: '{{ route("admin.tax-invoice.status") }}',
            type: 'GET',
            success: function(response) {
                $('#status-content').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6>System Status</h6>
                            <p><strong>Last Run:</strong> ${response.last_run || 'Never'}</p>
                            <p><strong>Next Scheduled:</strong> ${response.next_scheduled}</p>
                            <p><strong>Is Today 15th:</strong> ${response.is_today ? 'Yes' : 'No'}</p>
                            <p><strong>Cron Enabled:</strong> ${response.cron_enabled ? 'Yes' : 'No'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Email Types Status</h6>
                            <div class="list-group">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Feedback Requests
                                    <span class="badge bg-success rounded-pill">Active</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Checkout Reminders
                                    <span class="badge bg-success rounded-pill">Active</span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    Tax Invoices
                                    <span class="badge bg-success rounded-pill">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <h6>Recent Logs</h6>
                        <p class="text-muted">Check the log files in storage/logs/ for detailed information:</p>
                        <ul>
                            <li>guaranteed-emails.log</li>
                            <li>guaranteed-feedback.log</li>
                            <li>guaranteed-checkout.log</li>
                            <li>guaranteed-tax.log</li>
                        </ul>
                    </div>
                `);
            },
            error: function() {
                $('#status-content').html('<div class="alert alert-danger">Failed to fetch system status</div>');
            }
        });
    }
});
</script>
@endsection
