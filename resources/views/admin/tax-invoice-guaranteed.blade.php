@extends('layouts.app')

@section('title', 'Guaranteed Tax Invoice System')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="card-title">
                        <i class="fas fa-shield-alt"></i> Guaranteed Tax Invoice System
                    </h3>
                    <small>Multiple fallback mechanisms ensure invoices are always sent</small>
                </div>
                <div class="card-body">
                    <!-- Status Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5>Primary Method</h5>
                                    <p class="mb-0">Laravel Scheduler</p>
                                    <small>15th at 9:00 AM</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5>Backup Method</h5>
                                    <p class="mb-0">Backup Sender</p>
                                    <small>15th at 11:00 AM</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h5>Queue Processor</h5>
                                    <p class="mb-0">Daily at 12:00 PM</p>
                                    <small>Processes pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h5>Manual Trigger</h5>
                                    <p class="mb-0">On Demand</p>
                                    <small>Always available</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hostinger Setup Instructions -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Hostinger Setup Instructions</h5>
                        <p><strong>Step 1:</strong> Set up these cron jobs in your Hostinger cPanel:</p>
                        <div class="bg-dark text-light p-3 rounded">
                            <code>
                                # Monthly cron (15th of each month)<br>
                                0 9 15 * * /usr/bin/php /home/yourusername/public_html/cron-hostinger.php?key=hostinger_tax_invoice_2025_secure_key_12345<br><br>
                                # Daily cron (every day at 10 AM)<br>
                                0 10 * * * /usr/bin/php /home/yourusername/public_html/hostinger-cron-daily.php?key=hostinger_daily_cron_2025_secure_key_67890
                            </code>
                        </div>
                        <p class="mt-2"><strong>Step 2:</strong> Change the secret keys in the cron files for security!</p>
                    </div>

                    <!-- Manual Controls -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Manual Tax Invoice Generation</h5>
                                </div>
                                <div class="card-body">
                                    <form id="manual-form">
                                        @csrf
                                        <div class="form-group">
                                            <label for="month">Select Month</label>
                                            <input type="month" class="form-control" id="month" name="month" 
                                                   value="{{ date('Y-m', strtotime('-1 month')) }}" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="test_email">Test Email (Optional)</label>
                                            <input type="email" class="form-control" id="test_email" name="test_email" 
                                                   placeholder="Leave empty to send to all owners">
                                        </div>
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="dry_run" name="dry_run">
                                                <label class="form-check-label" for="dry_run">
                                                    Dry Run (Test mode - no emails sent)
                                                </label>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary" id="manual-btn">
                                            <span id="manual-btn-text">Generate Now</span>
                                            <span class="spinner-border spinner-border-sm d-none" id="manual-spinner"></span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Backup Methods</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-warning" id="backup-btn">
                                            <i class="fas fa-shield-alt"></i> Run Backup Sender
                                        </button>
                                        <button class="btn btn-info" id="queue-btn">
                                            <i class="fas fa-tasks"></i> Process Queue
                                        </button>
                                        <button class="btn btn-secondary" id="status-btn">
                                            <i class="fas fa-chart-line"></i> Check Status
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results -->
                    <div id="results" class="mt-4"></div>

                    <!-- Logs -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Recent Logs</h5>
                        </div>
                        <div class="card-body">
                            <div id="logs-content">
                                <p class="text-muted">Click "Check Status" to view recent logs</p>
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
    // Manual form submission
    $('#manual-form').on('submit', function(e) {
        e.preventDefault();
        executeCommand('tax:generate-monthly-invoices', $(this).serialize(), '#manual-btn', '#manual-btn-text', '#manual-spinner');
    });

    // Backup sender
    $('#backup-btn').on('click', function() {
        const month = $('#month').val();
        executeCommand('tax:backup-send', {month: month}, this, null, null);
    });

    // Process queue
    $('#queue-btn').on('click', function() {
        executeCommand('tax:process-queue', {}, this, null, null);
    });

    // Check status
    $('#status-btn').on('click', function() {
        checkStatus();
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
                        ${response.total_owners ? `<p><strong>Owners Processed:</strong> ${response.total_owners}</p>` : ''}
                        ${response.total_emails_sent ? `<p><strong>Emails Sent:</strong> ${response.total_emails_sent}</p>` : ''}
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
                if ($btnText) $btnText.text('Generate Now');
                if ($spinner) $spinner.addClass('d-none');
            }
        });
    }

    function checkStatus() {
        $.ajax({
            url: '{{ route("admin.tax-invoice.status") }}',
            type: 'GET',
            success: function(response) {
                $('#logs-content').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <h6>System Status</h6>
                            <p><strong>Last Run:</strong> ${response.last_run || 'Never'}</p>
                            <p><strong>Next Scheduled:</strong> ${response.next_scheduled}</p>
                            <p><strong>Is Today 15th:</strong> ${response.is_today ? 'Yes' : 'No'}</p>
                            <p><strong>Cron Enabled:</strong> ${response.cron_enabled ? 'Yes' : 'No'}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Recent Activity</h6>
                            <p class="text-muted">Check the log files in storage/logs/ for detailed information.</p>
                        </div>
                    </div>
                `);
            },
            error: function() {
                $('#logs-content').html('<div class="alert alert-danger">Failed to fetch status</div>');
            }
        });
    }
});
</script>
@endsection
