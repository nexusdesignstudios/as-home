@extends('layouts.main')

@section('title', 'Statement of Account')

@section('content')
<div class="page-heading" style="background-color: #fff; padding: 20px;">
    <!-- Header Section -->
    <div class="text-center mb-4">
        <h1 style="font-size: 24px; font-weight: bold; margin-bottom: 10px; text-align: left;">Standard Operating Process - Owner Statement of Account</h1>
        <div style="text-align: center;">
            <h2 style="font-size: 20px; font-weight: bold; margin: 5px 0;">As-home for Asset Management</h2>
            <h2 style="font-size: 22px; font-weight: bold; margin: 5px 0;">Statement of Account</h2>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-3">
        <div class="card-header">
            <h4 class="card-title">Select Property or Owner</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="property-select">Select Hotel Property</label>
                        <select id="property-select" class="form-control select2" style="width: 100%;">
                            <option value="">-- Select Property --</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date-from-filter">From Date</label>
                        <input type="date" id="date-from-filter" class="form-control">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date-to-filter">To Date</label>
                        <input type="date" id="date-to-filter" class="form-control">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="load-statement-btn" class="btn btn-primary w-100 me-2">
                        <i class="bi bi-search"></i> Load Statement
                    </button>
                    <button id="export-statement-btn" class="btn btn-success w-100" style="display: none;">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Owner Information Section -->
    <div class="card mb-3" id="owner-info-card" style="display: none;">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 style="font-weight: bold; margin-bottom: 15px;">Owner Details</h5>
                    <div class="mb-2">
                        <strong>Owner Name :</strong> <span id="owner-name">-</span>
                    </div>
                    <div class="mb-2">
                        <strong>Owner Address :</strong> <a href="#" id="owner-address-link" target="_blank" style="color: #007bff; text-decoration: underline;"><span id="owner-address">-</span></a>
                    </div>
                    <div class="mb-2">
                        <strong>Owner Contact Person Telephone :</strong> <span id="owner-phone">-</span>
                    </div>
                    <div class="mb-2">
                        <strong>Owner Contact Person email :</strong> <span id="owner-email">-</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 style="font-weight: bold; margin-bottom: 15px;">Customer/Unit Details</h5>
                    <div class="mb-2">
                        <strong>Customer Number :</strong> <span id="customer-number">-</span>
                    </div>
                    <div class="mb-2">
                        <strong>Unit Number :</strong> <span id="unit-number">-</span>
                    </div>
                    <div class="mb-2">
                        <strong>Date :</strong> <span id="statement-date">-</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statement of Account Table -->
    <div class="card" id="statement-card" style="display: none;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="statement-table" style="border-collapse: collapse;">
                    <thead style="background-color: #1E3A8A; color: white;">
                        <tr>
                            <th style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Date</th>
                            <th style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Reference #</th>
                            <th style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Description</th>
                            <th style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Payment</th>
                            <th style="padding: 10px; border: 1px solid #ddd; font-weight: bold; text-align: right;">Debit</th>
                            <th style="padding: 10px; border: 1px solid #ddd; font-weight: bold; text-align: right;">Credit</th>
                            <th style="padding: 10px; border: 1px solid #ddd; font-weight: bold; text-align: right;">Balance</th>
                            <th style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Comments</th>
                            <th style="padding: 10px; border: 1px solid #ddd; font-weight: bold; width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="statement-tbody" style="background-color: #fff;">
                        <!-- Transactions will be loaded here -->
                    </tbody>
                    <tfoot style="background-color: #FFEB3B;">
                        <tr id="total-row" style="display: none;">
                            <td colspan="3" style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">Total</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"></td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right; font-weight: bold;" id="total-debit">0.00</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right; font-weight: bold;" id="total-credit">0.00</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right; font-weight: bold;" id="total-balance">0.00</td>
                            <td style="padding: 10px; border: 1px solid #ddd;"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Initialize Select2 for property dropdown
    $('#property-select').select2({
        placeholder: 'Search for a property...',
        allowClear: true,
        ajax: {
            url: '{{ route("statement-of-account.hotel-properties") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    search: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.title + ' - ' + item.owner_name,
                            owner_id: item.owner_id,
                            owner_name: item.owner_name
                        };
                    })
                };
            },
            cache: true
        }
    });

    // Load statement button
    $('#load-statement-btn').on('click', function() {
        loadOwnerStatement();
    });

    // Export statement button
    $('#export-statement-btn').on('click', function() {
        exportStatement();
    });

    // Load properties on page load
    loadHotelProperties();

    // Setup credit editing for statement
    setupStatementCreditEditing();
});

let currentStatementData = null;

function setupStatementCreditEditing() {
    // Track changes instead of auto-saving on blur
    $(document).off('input', '.editable-credit-statement').on('input', '.editable-credit-statement', function() {
        let $input = $(this);
        let $row = $input.closest('tr');
        let originalCredit = parseFloat($input.data('original-credit') || 0);
        let newValue = parseFloat($input.val() || 0);
        
        // Mark row as modified if value changed
        if (Math.abs(newValue - originalCredit) > 0.01) {
            $row.addClass('row-modified');
            $row.find('.save-row-btn').prop('disabled', false).show();
            
            // Recalculate balance locally (without saving)
            let transactionIndex = parseInt($input.data('transaction-index'));
            let balanceBefore = parseFloat($input.data('balance-before') || 0);
            recalculateBalances(transactionIndex, balanceBefore, newValue, false); // false = don't save
            
            // Update totals in preview mode
            updateAllTotals();
        } else {
            $row.removeClass('row-modified');
            $row.find('.save-row-btn').hide();
            
            // Restore original balance calculation
            let transactionIndex = parseInt($input.data('transaction-index'));
            let balanceBefore = parseFloat($input.data('balance-before') || 0);
            recalculateBalances(transactionIndex, balanceBefore, originalCredit, false);
            
            // Update totals after restoring
            updateAllTotals();
        }
    });
    
    // Handle save button click for credit transactions only
    // Manual entries have their own handler below
    $(document).off('click', '.save-row-btn').on('click', '.save-row-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        let $btn = $(this);
        let $row = $btn.closest('tr');
        
        // Check if this is a manual entry row - if so, let the manual entry handler deal with it
        if ($row.hasClass('manual-entry-row')) {
            return; // Let the manual entry handler below process it
        }
        
        let $creditInput = $row.find('.editable-credit-statement');
        
        if ($creditInput.length) {
            let reservationId = $creditInput.data('reservation-id');
            let propertyId = $creditInput.data('property-id');
            let transactionIndex = parseInt($creditInput.data('transaction-index'));
            let balanceBefore = parseFloat($creditInput.data('balance-before') || 0);
            let creditAmount = parseFloat($creditInput.val() || 0);
            
            // If propertyId is not set, try to get it from currentStatementData or property select
            if (!propertyId) {
                propertyId = currentStatementData ? (currentStatementData.property_id || $('#property-select').val()) : $('#property-select').val();
            }
            
            if (!propertyId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Property ID is missing. Please reload the statement.'
                    });
                }
                return;
            }
            
            updateStatementCredit(reservationId, propertyId, creditAmount, $creditInput, transactionIndex, balanceBefore);
        }
    });
}

function updateStatementCredit(reservationId, propertyId, creditAmount, $input, transactionIndex, balanceBefore) {
    $input.prop('disabled', true);
    
    // Get transaction type and manual entry ID from data attributes
    let transactionType = $input.data('type');
    let manualEntryId = $input.data('manual-entry-id');
    
    // Determine which endpoint to use based on transaction type
    let url, data;
    
    if (transactionType === 'manual' || manualEntryId) {
        // Update manual entry - use saveManualEntry endpoint
        let $row = $input.closest('tr');
        let date = $row.find('.manual-date').val() || $row.find('td').eq(0).text().trim();
        let reference = $row.find('.manual-reference').val() || $row.find('td').eq(1).text().trim();
        let description = $row.find('.manual-description').val() || $row.find('td').eq(2).text().trim();
        let debit = parseFloat($row.find('.manual-debit').val() || $row.find('td').eq(3).text().replace(/,/g, '') || 0);
        let comments = $row.find('.manual-comments').val() || $row.find('td').eq(6).text().trim();
        
        // Convert date format if needed (from 'd-M-y' to 'Y-m-d')
        if (date && date.includes('-')) {
            let parts = date.split('-');
            if (parts.length === 3 && parts[0].length <= 2) {
                // Convert from 'd-M-y' format
                let day = parts[0], month = parts[1], year = parts[2];
                let monthMap = {'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04', 'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08', 'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'};
                if (monthMap[month]) {
                    date = `20${year}-${monthMap[month]}-${day.padStart(2, '0')}`;
                }
            }
        }
        
        url = '{{ url("statement-of-account/property") }}/' + propertyId + '/manual-entry';
        data = {
            id: manualEntryId || null, // Include ID if updating existing entry
            date: date,
            reference: reference,
            description: description,
            debit_amount: debit,
            credit_amount: creditAmount,
            comments: comments,
            _token: '{{ csrf_token() }}'
        };
    } else {
        // Update reservation credit
        url = '{{ url("statement-of-account/property") }}/' + propertyId + '/update-credit';
        data = {
            credit_amount: creditAmount,
            reservation_id: reservationId, // Include reservation ID to check flexible rate
            _token: '{{ csrf_token() }}'
        };
    }
    
    $.ajax({
        url: url,
        method: 'POST',
        data: data,
        success: function(response) {
            if (response && response.success) {
                // Update the input value
                $input.val(creditAmount);
                $input.data('original-credit', creditAmount);
                
                // Update manual_entry_id if returned
                if (response.entry && response.entry.id) {
                    $input.data('manual-entry-id', response.entry.id);
                }
                
                // Recalculate balance for this transaction and all subsequent ones
                recalculateBalances(transactionIndex, balanceBefore, creditAmount, true);
                
                // Update all totals (debit, credit, balance) after save
                updateAllTotals();
                
                // Mark row as saved
                let $row = $input.closest('tr');
                $row.removeClass('row-modified');
                $row.find('.save-row-btn').prop('disabled', true).hide();
                
                // Show success message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Credit amount updated successfully',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
                
                // NO AUTO-REFRESH - Keep the current view
            } else {
                // Restore original value on error
                $input.val($input.data('original-credit'));
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update credit amount'
                    });
                }
            }
        },
        error: function(xhr) {
            // Restore original value on error
            $input.val($input.data('original-credit'));
            
            let errorMessage = 'Failed to update credit amount';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.status === 400) {
                errorMessage = transactionType === 'manual' 
                    ? 'Failed to update manual entry.' 
                    : 'Credit can only be edited for flexible rate reservations.';
            }
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        },
        complete: function() {
            $input.prop('disabled', false);
        }
    });
}

// Calculate balance for a manual entry row based on previous row's balance
function calculateManualEntryBalance($row, updateSubsequent) {
    // Default to true if not specified (update all subsequent rows)
    if (typeof updateSubsequent === 'undefined') {
        updateSubsequent = true;
    }
    
    let $balanceCell = $row.find('.manual-balance');
    if (!$balanceCell.length) {
        // If no manual-balance cell, look for balance-cell
        $balanceCell = $row.find('.balance-cell');
    }
    
    if ($balanceCell.length) {
        // Get the previous row's balance
        let $prevRow = $row.prev('tr');
        let previousBalance = 0;
        
        if ($prevRow.length) {
            let $prevBalanceCell = $prevRow.find('.balance-cell, .manual-balance');
            if ($prevBalanceCell.length && $prevBalanceCell.text().trim() !== '') {
                previousBalance = parseFloat($prevBalanceCell.text().replace(/,/g, '') || 0);
            } else {
                // If previous row doesn't have balance, find the last row with a balance
                let rows = $('#statement-tbody tr').not('#total-row');
                let rowIndex = rows.index($row);
                
                // Work backwards to find the last valid balance
                for (let i = rowIndex - 1; i >= 0; i--) {
                    let $checkRow = $(rows[i]);
                    let $checkBalance = $checkRow.find('.balance-cell, .manual-balance');
                    if ($checkBalance.length && $checkBalance.text().trim() !== '') {
                        previousBalance = parseFloat($checkBalance.text().replace(/,/g, '') || 0);
                        break;
                    }
                }
            }
        } else {
            // This is the first row, check if there's a total balance to start from
            let totalBalanceText = $('#total-balance').text().trim();
            if (totalBalanceText) {
                previousBalance = parseFloat(totalBalanceText.replace(/,/g, '') || 0);
            }
        }
        
        // Get debit and credit values
        let debit = parseFloat($row.find('.manual-debit').val() || $row.find('td').eq(3).text().replace(/,/g, '') || 0);
        let credit = parseFloat($row.find('.manual-credit, .editable-credit-statement').val() || $row.find('td').eq(4).text().replace(/,/g, '') || 0);
        
        // Calculate new balance: previous balance + debit - credit
        let newBalance = previousBalance + debit - credit;
        $balanceCell.text(formatNumber(newBalance));
        $balanceCell.attr('data-balance-before', previousBalance + debit);
        
        // Only update subsequent rows and totals if updateSubsequent is true
        if (updateSubsequent) {
            // Recalculate all subsequent rows
            let rows = $('#statement-tbody tr').not('#total-row');
            let currentIndex = rows.index($row);
            let runningBalance = newBalance;
            
            rows.slice(currentIndex + 1).each(function() {
                let $nextRow = $(this);
                let $nextBalanceCell = $nextRow.find('.balance-cell, .manual-balance');
                
                if ($nextBalanceCell.length) {
                    let $debitCell = $nextRow.find('td').eq(4); // Debit column (index changed)
                    let $creditCell = $nextRow.find('td').eq(5); // Credit column (index changed)
                    let $creditInput = $nextRow.find('.editable-credit-statement');
                    
                    let nextDebit = parseFloat($debitCell.text().replace(/,/g, '') || $debitCell.find('input').val() || 0);
                    let nextCredit = 0;
                    if ($creditInput.length) {
                        nextCredit = parseFloat($creditInput.val().replace(/,/g, '') || 0);
                    } else {
                        nextCredit = parseFloat($creditCell.text().replace(/,/g, '') || 0);
                    }
                    
                    runningBalance = runningBalance + nextDebit - nextCredit;
                    $nextBalanceCell.text(formatNumber(runningBalance));
                    $nextBalanceCell.attr('data-balance-before', runningBalance + nextCredit);
                }
            });
            
            // Update all totals (debit, credit, balance) after recalculation
            updateAllTotals();
        } else {
            // Even if not updating subsequent rows, update total balance
            updateTotalBalance();
        }
    }
}

// Function to update all totals (debit, credit, balance) from current row values
function updateAllTotals() {
    let rows = $('#statement-tbody tr').not('#total-row');
    let totalDebit = 0;
    let totalCredit = 0;
    
    // Calculate totals from all rows
    rows.each(function() {
        let $row = $(this);
        
        // Get debit value (from input or text)
        let $debitCell = $row.find('td').eq(4); // Debit column (index changed due to payment method column)
        let debit = 0;
        if ($debitCell.find('input').length) {
            debit = parseFloat($debitCell.find('input').val() || 0);
        } else {
            debit = parseFloat($debitCell.text().replace(/,/g, '') || 0);
        }
        
        // Get credit value (from input or text)
        let $creditCell = $row.find('td').eq(5); // Credit column (index changed due to payment method column)
        let credit = 0;
        if ($creditCell.find('input').length) {
            credit = parseFloat($creditCell.find('input').val() || 0);
        } else {
            credit = parseFloat($creditCell.text().replace(/,/g, '') || 0);
        }
        
        totalDebit += debit;
        totalCredit += credit;
    });
    
    // Calculate total balance as difference: Total Debit - Total Credit
    let totalBalance = totalDebit - totalCredit;
    
    // Update all totals
    $('#total-debit').text(formatNumber(totalDebit));
    $('#total-credit').text(formatNumber(totalCredit));
    $('#total-balance').text(formatNumber(totalBalance));
}

// Function to update total balance based on the last row's balance (legacy, kept for compatibility)
function updateTotalBalance() {
    updateAllTotals(); // Use the comprehensive function
}

function recalculateBalances(changedIndex, balanceBefore, newCredit, isSaved) {
    // Recalculate balance starting from the changed transaction
    let rows = $('#statement-tbody tr').not('#total-row');
    let previousBalance = null;
    
    // Get the original credit to calculate the change
    let $changedRow = rows.eq(changedIndex);
    let originalCredit = parseFloat($changedRow.find('.editable-credit-statement').data('original-credit') || 0);
    
    // If saved, update the original credit value
    if (isSaved) {
        $changedRow.find('.editable-credit-statement').data('original-credit', newCredit);
    }
    
    rows.each(function(index) {
        let $row = $(this);
        let $balanceCell = $row.find('.balance-cell');
        
        if ($balanceCell.length) {
            if (index === changedIndex) {
                // For the changed row, recalculate balance
                let newBalance = balanceBefore - newCredit;
                $balanceCell.text(formatNumber(newBalance));
                $balanceCell.attr('data-balance-before', balanceBefore);
                previousBalance = newBalance;
            } else if (index > changedIndex && previousBalance !== null) {
                // For subsequent rows, recalculate based on the new previous balance
                let $creditInput = $row.find('.editable-credit-statement');
                let $debitCell = $row.find('td').eq(3); // Debit is 4th column (0-indexed)
                
                let debit = parseFloat($debitCell.text().replace(/,/g, '') || 0);
                // Use input value if editable, otherwise use text
                let credit = 0;
                if ($creditInput.length) {
                    credit = parseFloat($creditInput.val().replace(/,/g, '') || 0);
                } else {
                    credit = parseFloat($row.find('td').eq(4).text().replace(/,/g, '') || 0);
                }
                
                // Calculate new balance: previous balance + debit - credit
                let newBalance = previousBalance + debit - credit;
                $balanceCell.text(formatNumber(newBalance));
                $balanceCell.attr('data-balance-before', previousBalance + debit);
                previousBalance = newBalance;
            } else if (index < changedIndex) {
                // For previous rows, keep their balance but update the data attribute
                let currentBalance = parseFloat($balanceCell.text().replace(/,/g, '') || 0);
                let $debitCell = $row.find('td').eq(3);
                let $creditCell = $row.find('td').eq(4);
                let debit = parseFloat($debitCell.text().replace(/,/g, '') || 0);
                let credit = parseFloat($creditCell.text().replace(/,/g, '') || 0);
                $balanceCell.attr('data-balance-before', currentBalance + credit);
            }
        }
    });
    
    // Update all totals (debit, credit, balance) after recalculation
    updateAllTotals();
}

// Track changes in manual entry rows (no auto-save on blur)
// Save buttons are already visible for empty rows, so we just need to keep them enabled
$(document).on('input', '.manual-entry-row input', function() {
    let $row = $(this).closest('tr');
    let date = $row.find('.manual-date').val();
    let reference = $row.find('.manual-reference').val();
    let description = $row.find('.manual-description').val();
    let debit = parseFloat($row.find('.manual-debit').val() || 0);
    let credit = parseFloat($row.find('.manual-credit, .editable-credit-statement').val() || 0);
    let comments = $row.find('.manual-comments').val();
    
    // Calculate balance in real-time as user types
    if (debit || credit) {
        calculateManualEntryBalance($row, false); // false = preview only, don't update subsequent rows yet
    } else {
        // Clear balance if both debit and credit are 0
        $row.find('.manual-balance, .balance-cell').text('');
    }
    
    // Update totals in preview mode (show changes as user types)
    updateAllTotals();
    
    // Check if row has any data
    if (date || reference || description || debit || credit || comments) {
        $row.addClass('row-modified');
        $row.find('.save-row-btn').prop('disabled', false); // Keep enabled, already visible
    } else {
        // If all fields are empty, still allow save (for clearing/deleting entries)
        $row.find('.save-row-btn').prop('disabled', false);
    }
});

// Save manual entry on save button click - Use higher priority with stopPropagation first
$(document).on('click', '.manual-entry-row .save-row-btn', function(e) {
    e.preventDefault();
    e.stopImmediatePropagation(); // Stop other handlers
    
    let $btn = $(this);
    let $row = $btn.closest('tr');
    
    // Double check it's a manual entry row
    if (!$row.hasClass('manual-entry-row')) {
        return false;
    }
    
    let propertyId = currentStatementData ? (currentStatementData.property_id || $('#property-select').val()) : $('#property-select').val();
    
    if (!propertyId) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ icon: 'warning', title: 'Warning', text: 'Please select a property first' });
        }
        return;
    }

    let date = $row.find('.manual-date').val();
    let reference = $row.find('.manual-reference').val();
    let description = $row.find('.manual-description').val();
    let debit = parseFloat($row.find('.manual-debit').val() || 0);
    let credit = parseFloat($row.find('.manual-credit, .editable-credit-statement').val() || 0);
    let comments = $row.find('.manual-comments').val();
    let manualEntryId = $row.data('manual-entry-id');

    // Disable inputs during save
    $row.find('input').prop('disabled', true);
    $btn.prop('disabled', true);
    
    console.log('Saving manual entry:', { propertyId, date, debit, credit, manualEntryId }); // Debug

    $.ajax({
        url: '{{ url("statement-of-account/property") }}/' + propertyId + '/manual-entry',
        method: 'POST',
        data: {
            id: manualEntryId || null,
            date: date,
            reference: reference,
            description: description,
            debit_amount: debit,
            credit_amount: credit,
            comments: comments,
            _token: '{{ csrf_token() }}'
        },
        success: function(resp) {
            if (resp && resp.success) {
                // Store the entry ID if it's a new entry
                if (resp.entry && resp.entry.id) {
                    $row.data('manual-entry-id', resp.entry.id);
                    $row.find('.editable-credit-statement').data('manual-entry-id', resp.entry.id);
                    $row.find('.editable-credit-statement').data('type', 'manual');
                }
                
                // Calculate and display balance for this row (this will also update subsequent rows)
                calculateManualEntryBalance($row, true); // true = update subsequent rows
                
                // Update all totals (debit, credit, balance) after save
                updateAllTotals();
                
                $row.removeClass('row-modified');
                $btn.prop('disabled', false); // Keep enabled for future edits
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: 'Entry saved successfully',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
                
                // NO AUTO-REFRESH - Keep the current view
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'Error', text: (resp && resp.message) || 'Failed to save entry' });
            }
        },
        error: function(xhr){
            let msg = 'Failed to save entry';
            if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: msg });
        },
        complete: function(){
            $row.find('input').prop('disabled', false);
            $btn.prop('disabled', false);
        }
    });
});

function loadHotelProperties() {
    $.ajax({
        url: '{{ route("statement-of-account.hotel-properties") }}',
        method: 'GET',
        success: function(data) {
            // Properties are loaded via Select2 AJAX
        }
    });
}

function loadOwnerStatement() {
    const propertyId = $('#property-select').val();
    const dateFrom = $('#date-from-filter').val();
    const dateTo = $('#date-to-filter').val();

    if (!propertyId) {
        Swal.fire({
            icon: 'warning',
            title: 'Select Property',
            text: 'Please select a property first'
        });
        return;
    }

    // Show loading
    $('#statement-tbody').html('<tr><td colspan="7" class="text-center"><i class="spinner-border spinner-border-sm"></i> Loading statement...</td></tr>');
    $('#statement-card').show();
    $('#owner-info-card').show();

    $.ajax({
        url: '{{ route("statement-of-account.owner-statement") }}',
        method: 'GET',
        data: {
            property_id: propertyId,
            date_from: dateFrom,
            date_to: dateTo
        },
        success: function(response) {
            if (response.error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to load statement'
                });
                $('#statement-tbody').html('<tr><td colspan="7" class="text-center text-danger">' + (response.message || 'Error loading statement') + '</td></tr>');
                return;
            }

            currentStatementData = response;
            renderStatement(response);
            $('#export-statement-btn').show();
        },
        error: function(xhr) {
            console.error('Error loading statement:', xhr);
            let errorMessage = 'Failed to load statement';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage
            });
            $('#statement-tbody').html('<tr><td colspan="7" class="text-center text-danger">' + errorMessage + '</td></tr>');
        }
    });
}

function renderStatement(data) {
    // Update owner information
    if (data.owner) {
        $('#owner-name').text(data.owner.name || '-');
        
        // Set owner address as clickable link
        const address = data.owner.address || '-';
        $('#owner-address').text(address);
        if (address && address !== '-') {
            // Create Google Maps link
            const mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(address);
            $('#owner-address-link').attr('href', mapsUrl);
        } else {
            $('#owner-address-link').attr('href', '#').removeAttr('target');
        }
        
        $('#owner-phone').text(data.owner.mobile || '-');
        $('#owner-email').text(data.owner.email || '-');
        $('#customer-number').text(data.owner.id || '-');
        $('#unit-number').text(data.property_id || '-');
        $('#statement-date').text(data.statement_date || '-');
    }

    // Clear and render transactions
    let tbody = $('#statement-tbody');
    tbody.empty();

    if (!data.transactions || data.transactions.length === 0) {
        tbody.html('<tr><td colspan="7" class="text-center">No transactions found</td></tr>');
        $('#total-row').hide();
        return;
    }

    let totalDebit = 0;
    let totalCredit = 0;

    data.transactions.forEach(function(transaction, index) {
        totalDebit += parseFloat(transaction.debit || 0);
        totalCredit += parseFloat(transaction.credit || 0);

        let tr = $('<tr></tr>');
        
        // Date - Editable for manual entries
        let dateCell;
        if (transaction.type === 'manual') {
            dateCell = $('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>');
            let dateValue = transaction.date || '';
            // Convert from 'd-M-y' to 'Y-m-d' for date input if needed
            if (dateValue && dateValue.includes('-') && !dateValue.match(/^\d{4}-\d{2}-\d{2}$/)) {
                let parts = dateValue.split('-');
                if (parts.length === 3 && parts[0].length <= 2) {
                    let day = parts[0], month = parts[1], year = parts[2];
                    let monthMap = {'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04', 'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08', 'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'};
                    if (monthMap[month]) {
                        dateValue = `20${year}-${monthMap[month]}-${day.padStart(2, '0')}`;
                    }
                }
            }
            let dateInput = $('<input>', {
                type: 'date',
                class: 'form-control form-control-sm manual-date',
                value: dateValue
            });
            dateCell.append(dateInput);
        } else {
            dateCell = $('<td style="padding: 8px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>').text(transaction.date || '');
        }
        tr.append(dateCell);
        
        // Reference - Editable for manual entries
        let referenceCell;
        if (transaction.type === 'manual') {
            referenceCell = $('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>');
            let referenceInput = $('<input>', {
                type: 'text',
                class: 'form-control form-control-sm manual-reference',
                value: transaction.reference || ''
            });
            referenceCell.append(referenceInput);
        } else {
            referenceCell = $('<td style="padding: 8px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>').text(transaction.reference || '');
        }
        tr.append(referenceCell);
        
        // Description with refund policy indicator (only for credit transactions, not for "As-home Commission")
        let descriptionCell;
        if (transaction.type === 'manual') {
            descriptionCell = $('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>');
            let descriptionInput = $('<input>', {
                type: 'text',
                class: 'form-control form-control-sm manual-description',
                value: transaction.description || ''
            });
            descriptionCell.append(descriptionInput);
        } else {
            let descriptionText = transaction.description || '';
            // Only add refund policy if it's a credit transaction (not for debit/As-home Commission)
            if (transaction.type === 'credit' && transaction.refund_policy) {
                descriptionText += ` (${transaction.refund_policy})`;
            }
            // Only add room number if it exists and is not empty/null/0
            if (transaction.room_number && transaction.room_number !== 'N/A' && transaction.room_number !== '0' && transaction.room_number !== 0) {
                descriptionText += ` - Room ${transaction.room_number}`;
            }
            descriptionCell = $('<td style="padding: 8px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>').text(descriptionText);
        }
        tr.append(descriptionCell);
        
        // Payment Method Badge (new column)
        let paymentMethodCell;
        if (transaction.type === 'manual') {
            paymentMethodCell = $('<td style="padding: 8px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: center;"></td>').html('<span class="badge bg-secondary">-</span>');
        } else {
            let paymentMethodBadge = '';
            if (transaction.payment_method === 'online') {
                paymentMethodBadge = '<span class="badge bg-primary" title="Online Payment (Paymob/Gateway)"><i class="bi bi-credit-card"></i> Online</span>';
            } else {
                paymentMethodBadge = '<span class="badge bg-secondary" title="Manual/Cash Payment"><i class="bi bi-cash"></i> Manual</span>';
            }
            paymentMethodCell = $('<td style="padding: 8px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: center;"></td>').html(paymentMethodBadge);
        }
        tr.append(paymentMethodCell);
        
        // Debit (right-aligned) - Editable for manual entries
        let debitCell;
        if (transaction.type === 'manual') {
            debitCell = $('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: right;"></td>');
            let debitInput = $('<input>', {
                type: 'number',
                step: '0.01',
                class: 'form-control form-control-sm text-end manual-debit',
                value: transaction.debit || 0,
                style: 'width: 100%; text-align: right;'
            });
            debitCell.append(debitInput);
        } else {
            debitCell = $('<td style="padding: 8px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: right;"></td>').text(formatNumber(transaction.debit || 0));
        }
        tr.append(debitCell);
        
        // Credit (right-aligned) - Make ALL credit columns editable (validation happens on backend)
        let creditCell = $('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: right;"></td>');
        if (transaction.type === 'credit' || transaction.credit > 0) {
            // Make credit editable for all credit transactions
            let creditInput = $('<input>', {
                type: 'number',
                step: '0.01',
                class: 'form-control form-control-sm text-end editable-credit-statement',
                value: transaction.credit || 0,
                style: 'width: 100%; border: 1px solid #ddd; background: #fff; text-align: right;',
                'data-transaction-index': index,
                'data-reservation-id': transaction.reservation_id || null,
                'data-property-id': transaction.property_id,
                'data-type': transaction.type || 'credit',
                'data-manual-entry-id': transaction.manual_entry_id || null,
                'data-is-flexible-rate': transaction.is_flexible_rate === true,
                'data-original-credit': transaction.credit || 0,
                'data-balance-before': transaction.balance + (transaction.credit || 0) // Balance before this transaction
            });
            creditCell.append(creditInput);
        } else if (transaction.type === 'manual') {
            // Manual entries are always editable
            let creditInput = $('<input>', {
                type: 'number',
                step: '0.01',
                class: 'form-control form-control-sm text-end editable-credit-statement manual-credit',
                value: transaction.credit || 0,
                style: 'width: 100%; border: 1px solid #ddd; background: #fff; text-align: right;',
                'data-transaction-index': index,
                'data-reservation-id': null,
                'data-property-id': transaction.property_id,
                'data-type': 'manual',
                'data-manual-entry-id': transaction.manual_entry_id || null,
                'data-original-credit': transaction.credit || 0,
                'data-balance-before': transaction.balance + (transaction.credit || 0)
            });
            creditCell.append(creditInput);
        } else {
            creditCell.text(formatNumber(transaction.credit || 0));
        }
        tr.append(creditCell);
        
        // Balance (right-aligned) - Auto-calculated
        let balanceCell = $('<td style="padding: 8px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: right; balance-cell"></td>');
        balanceCell.text(formatNumber(transaction.balance || 0));
        balanceCell.attr('data-transaction-index', index);
        balanceCell.attr('data-balance-before', transaction.balance + (transaction.credit || 0));
        tr.append(balanceCell);
        
        // Comments - Editable for manual entries
        let commentsCell;
        if (transaction.type === 'manual') {
            commentsCell = $('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>');
            let commentsInput = $('<input>', {
                type: 'text',
                class: 'form-control form-control-sm manual-comments',
                value: transaction.comments || '',
                style: 'width: 100%;'
            });
            commentsCell.append(commentsInput);
        } else {
            commentsCell = $('<td style="padding: 8px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>').text(transaction.comments || '');
        }
        tr.append(commentsCell);
        
        // Save button column - Only show for editable rows (credit or manual entries)
        let saveCell = $('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: center;"></td>');
        if (transaction.type === 'credit' || transaction.type === 'manual' || transaction.credit > 0) {
            // For existing transactions, hide by default (show on change)
            // For manual entries (type === 'manual'), always show the button
            let shouldShow = (transaction.type === 'manual');
            let saveBtn = $('<button>', {
                type: 'button',
                class: 'btn btn-sm btn-success save-row-btn',
                style: shouldShow ? 'padding: 2px 8px; font-size: 11px;' : 'display: none; padding: 2px 8px; font-size: 11px;',
                text: 'Save',
                disabled: !shouldShow // Disable if not a manual entry (will enable on change)
            });
            saveCell.append(saveBtn);
        } else {
            saveCell.text(''); // Empty cell for non-editable rows
        }
        tr.append(saveCell);
        
        // Mark manual entry rows and store the entry ID
        if (transaction.type === 'manual' && transaction.manual_entry_id) {
            tr.addClass('manual-entry-row');
            tr.data('manual-entry-id', transaction.manual_entry_id);
        }

        tbody.append(tr);
    });

    // Add empty editable rows for manual entries
    for (let i = 0; i < 3; i++) {
        let tr = $('<tr class="manual-entry-row"></tr>');
        // Date
        tr.append($('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>')
            .append('<input type="date" class="form-control form-control-sm manual-date" />'));
        // Reference
        tr.append($('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>')
            .append('<input type="text" class="form-control form-control-sm manual-reference" placeholder="Ref" />'));
        // Description
        tr.append($('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>')
            .append('<input type="text" class="form-control form-control-sm manual-description" placeholder="Description" />'));
        // Debit
        tr.append($('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: right;"></td>')
            .append('<input type="number" step="0.01" class="form-control form-control-sm text-end manual-debit" placeholder="0" />'));
        // Credit
        tr.append($('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: right;"></td>')
            .append('<input type="number" step="0.01" class="form-control form-control-sm text-end manual-credit" placeholder="0" />'));
        // Balance (auto after save)
        tr.append($('<td style="padding: 8px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: right;" class="manual-balance"></td>').text(''));
        // Comments
        tr.append($('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd;"></td>')
            .append('<input type="text" class="form-control form-control-sm manual-comments" placeholder="Comments" />'));
        // Save button - Always visible for empty rows to make it easier to add and save entries
        let saveBtn = $('<button>', {
            type: 'button',
            class: 'btn btn-sm btn-success save-row-btn',
            style: 'padding: 2px 8px; font-size: 11px;',
            text: 'Save',
            disabled: false // Enable by default for empty rows
        });
        tr.append($('<td style="padding: 4px; border: 1px solid #ddd; border-style: dotted; border-bottom: 1px solid #ddd; text-align: center;"></td>').append(saveBtn));

        tbody.append(tr);
    }

    // Update totals - but recalculate from actual row values after rendering
    // Use setTimeout to ensure all rows are rendered first
    setTimeout(function() {
        updateAllTotals(); // Recalculate all totals from actual row values
    }, 100);
    
    $('#total-row').show();
}

function formatNumber(value) {
    if (!value && value !== 0) return '';
    let num = parseFloat(value);
    if (isNaN(num)) return '';
    // Format with commas and 2 decimal places
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });
}

function exportStatement() {
    if (!currentStatementData || !currentStatementData.transactions || currentStatementData.transactions.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Data',
            text: 'Please load statement data first'
        });
        return;
    }

    let csv = [];
    
    // Header row 1
    csv.push(['Standard Operating Process - Owner Statement of Account'].join(','));
    csv.push(['As-home for Asset Management'].join(','));
    csv.push(['Statement of Account'].join(','));
    csv.push(['']); // Empty row
    
    // Owner Information
    if (currentStatementData.owner) {
        csv.push(['Owner Name:', currentStatementData.owner.name || '']);
        csv.push(['Owner Address:', currentStatementData.owner.address || '']);
        csv.push(['Owner Contact Person Telephone:', currentStatementData.owner.mobile || '']);
        csv.push(['Owner Contact Person email:', currentStatementData.owner.email || '']);
        csv.push(['Customer Number:', currentStatementData.owner.id || '']);
        csv.push(['Unit Number:', currentStatementData.property_id || '']);
        csv.push(['Date:', currentStatementData.statement_date || '']);
        csv.push(['']); // Empty row
    }
    
    // Table headers
    csv.push(['Date', 'Reference #', 'Description', 'Payment', 'Debit', 'Credit', 'Balance', 'Comments'].join(','));
    
    // Transaction rows
    currentStatementData.transactions.forEach(function(transaction) {
        let paymentMethod = transaction.payment_method === 'online' ? 'Online' : (transaction.payment_method === 'cash' ? 'Manual' : '-');
        csv.push([
            transaction.date || '',
            transaction.reference || '',
            transaction.description || '',
            paymentMethod,
            transaction.debit || '0',
            transaction.credit || '0',
            transaction.balance || '0',
            '"' + (transaction.comments || '').replace(/"/g, '""') + '"'
        ].join(','));
    });
    
    // Total row
    csv.push([
        '',
        '',
        'Total',
        '',
        currentStatementData.transactions.reduce((sum, t) => sum + parseFloat(t.debit || 0), 0),
        currentStatementData.transactions.reduce((sum, t) => sum + parseFloat(t.credit || 0), 0),
        currentStatementData.total_balance || 0,
        ''
    ].join(','));
    
    // Download CSV
    let csvContent = '\uFEFF' + csv.join('\n'); // Add BOM for Excel UTF-8 support
    let blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    let link = document.createElement('a');
    let url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    
    let filename = 'statement_of_account';
    if (currentStatementData.owner && currentStatementData.owner.name) {
        filename += '_' + currentStatementData.owner.name.replace(/[^a-z0-9]/gi, '_');
    }
    filename += '_' + new Date().toISOString().split('T')[0] + '.csv';
    
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
@endsection
